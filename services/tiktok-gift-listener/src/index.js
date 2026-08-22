require('dotenv').config();
const axios = require('axios');
const { TikTokLiveConnection, WebcastEvent } = require('tiktok-live-connector');

const TIKTOK_USERNAME = process.env.TIKTOK_USERNAME;
const PROJECT_LIVE_ID = Number(process.env.PROJECT_LIVE_ID);
const WEBHOOK_URL = process.env.WEBHOOK_URL;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
// Opsional - tanpa ini, semua pengguna tiktok-live-connector berbagi SATU jatah
// rate-limit anonim yang sama di signing server TikTok (lihat isSignatureRateLimitBug()
// di bawah). Daftar gratis di eulerstream.com kalau sering kena rate-limit.
const SIGN_API_KEY = process.env.TIKTOK_SIGN_API_KEY || undefined;
const HEARTBEAT_URL = WEBHOOK_URL ? WEBHOOK_URL.replace(/\/?$/, '') + '/heartbeat' : null;
// Event non-gift (join/share/follow/subscribe/like/chat) — lihat App\Enums\EventTriggerType
// & App\Http\Controllers\Webhooks\TikTokEventWebhookController di sisi Laravel.
const EVENT_URL = WEBHOOK_URL ? WEBHOOK_URL.replace(/\/?$/, '') + '/event' : null;
const HEARTBEAT_INTERVAL_MS = 20000;

if (!TIKTOK_USERNAME || !PROJECT_LIVE_ID || !WEBHOOK_URL || !WEBHOOK_SECRET) {
    console.error('Isi dulu file .env (contoh ada di .env.example) sebelum menjalankan service ini.');
    process.exit(1);
}

let heartbeatTimer = null;

function sendHeartbeat() {
    axios.post(HEARTBEAT_URL, {
        project_live_id: PROJECT_LIVE_ID,
        tiktok_username: TIKTOK_USERNAME,
    }, {
        headers: { Authorization: `Bearer ${WEBHOOK_SECRET}` },
        timeout: 5000,
    }).catch(() => {}); // heartbeat gagal sesekali tidak masalah, dicoba lagi siklus berikutnya
}

function startHeartbeat() {
    stopHeartbeat();
    sendHeartbeat(); // langsung kirim 1x supaya status "Terhubung" muncul instan, tidak nunggu 20 detik
    heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL_MS);
}

function stopHeartbeat() {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
}

// Catatan: connection.fetchAvailableGifts() (fetch daftar LENGKAP gift TikTok) butuh
// paket berbayar Euler Stream Business plan (lihat eulerstream.com/pricing) — TIDAK
// dipakai. Katalog gift di Laravel diisi lewat seeder statis (bukan dari fetch API
// maupun dari event gift secara live), jadi payload di bawah cuma kirim ID gift
// (untuk cek opt-in) + total value (untuk leaderboard), tanpa nama/icon/diamond.

// Field ini sudah dicocokkan dengan node_modules/tiktok-live-proto/dist/node/v3.d.ts
// (WebcastGiftMessage, User, Gift, ImageModel) untuk tiktok-live-connector v2.4.3.
function extractGiftPayload(data) {
    const userId = data.user?.id;
    const nickname = data.user?.nickname;
    const avatarUrl =
        data.user?.avatarLarge?.urlList?.[0] ??
        data.user?.avatarMedium?.urlList?.[0] ??
        data.user?.avatarThumb?.urlList?.[0] ??
        null;

    const giftId = data.gift?.id;
    const giftType = data.gift?.type;
    const diamondCount = data.gift?.diamondCount ?? 0;
    const repeatCount = data.repeatCount ?? 1;
    const repeatEnd = Boolean(data.repeatEnd); // proto: number (0/1), bukan boolean
    const groupId = data.groupId;

    return { userId, nickname, avatarUrl, giftId, giftType, diamondCount, repeatCount, repeatEnd, groupId };
}

// Dipakai semua event NON-gift (join/share/follow/subscribe/like/chat) — field `user`
// bentuknya sama di semua WebcastXxxMessage terkait (lihat tiktok-live-proto/dist/node/v3.d.ts).
function extractUserPayload(data) {
    const userId = data.user?.id;
    const nickname = data.user?.nickname;
    const avatarUrl =
        data.user?.avatarLarge?.urlList?.[0] ??
        data.user?.avatarMedium?.urlList?.[0] ??
        data.user?.avatarThumb?.urlList?.[0] ??
        null;

    return { userId, nickname, avatarUrl };
}

async function sendWebhook(url, payload, attempt = 1) {
    try {
        await axios.post(url, payload, {
            headers: { Authorization: `Bearer ${WEBHOOK_SECRET}` },
            timeout: 5000,
        });
    } catch (err) {
        if (attempt < 3) {
            console.warn(`Webhook gagal (percobaan ${attempt}), coba lagi...`, err.message);
            setTimeout(() => sendWebhook(url, payload, attempt + 1), 1000 * attempt);
        } else {
            console.error('Webhook gagal terkirim setelah 3 percobaan, event ini dilewati:', err.message);
        }
    }
}

// event_type: 'join'|'share'|'follow'|'subscribe'|'like'|'chat' — lihat
// App\Http\Controllers\Webhooks\TikTokEventWebhookController.
function sendEventWebhook(eventType, user, extra = {}) {
    if (!user.userId) {
        return;
    }

    sendWebhook(EVENT_URL, {
        project_live_id: PROJECT_LIVE_ID,
        tiktok_username: TIKTOK_USERNAME,
        event_type: eventType,
        user: {
            tiktok_user_id: String(user.userId),
            unique_id: null,
            nickname: user.nickname ?? null,
            avatar_url: user.avatarUrl,
        },
        ...extra,
    });
}

// Backoff koneksi (lihat penjelasan di catch() connect() di bawah).
const BASE_RETRY_MS = 10000;
const MAX_RETRY_MS = 300000; // 5 menit
let consecutiveConnectFailures = 0;

/**
 * tiktok-live-connector 2.4.x (masih ada juga di 2.4.4) punya bug: kalau signing
 * server TikTok membalas rate-limit, konstruktor SignatureRateLimitError-nya sendiri
 * salah kirim argumen (`response.data` alih-alih `response`) sehingga error asli
 * ketiban TypeError "Cannot read properties of undefined (reading 'retry-after')"
 * sebelum sempat dibentuk. Ini bukan masalah kredensial/koneksi kita — deteksi lewat
 * pesan errornya supaya log-nya jelas, bukan cuma stack trace membingungkan.
 */
function isSignatureRateLimitBug(err) {
    return typeof err?.message === 'string' && err.message.includes("reading 'retry-after'");
}

function start() {
    // Argumen kedua (options) WAJIB ada walau kosong — versi 2.4.3 membaca
    // options.processInitialData dkk langsung tanpa fallback kalau options undefined.
    // signApiKey: undefined kalau TIKTOK_SIGN_API_KEY tidak diisi - tetap jalan spt
    // biasa (jatah rate-limit anonim bersama), cuma lebih rawan kena limit.
    const connection = new TikTokLiveConnection(TIKTOK_USERNAME, { signApiKey: SIGN_API_KEY });

    connection.on(WebcastEvent.GIFT, (data) => {
        try {
            const gift = extractGiftPayload(data);

            // Gift streak-able (giftType 1): TikTok kirim event berkali-kali selama user
            // masih nge-gift beruntun, repeatCount naik tiap kali. Baru final saat
            // repeatEnd:true — jangan proses sebelum itu, supaya tidak double-count.
            if (gift.giftType === 1 && !gift.repeatEnd) {
                return;
            }

            if (!gift.userId || !gift.giftId) {
                console.warn('Event gift tanpa userId/giftId, dilewati:', data);
                return;
            }

            // Nilai poin/coin dihitung di sisi Laravel dari katalog gift kita sendiri
            // (bisa diedit admin), BUKAN dari diamondCount asli TikTok — di sini cuma
            // kirim berapa kali gift ini di-kirim (repeat_count), bukan nilainya.
            sendWebhook(WEBHOOK_URL, {
                project_live_id: PROJECT_LIVE_ID,
                tiktok_username: TIKTOK_USERNAME,
                gifter: {
                    tiktok_user_id: String(gift.userId),
                    unique_id: null,
                    nickname: gift.nickname ?? null,
                    avatar_url: gift.avatarUrl,
                },
                gift: {
                    tiktok_gift_id: String(gift.giftId),
                    group_id: String(gift.groupId || `${gift.userId}-${Date.now()}`),
                    repeat_count: gift.repeatCount,
                },
            });

            console.log(`Gift: ${gift.nickname ?? gift.userId} -> giftId ${gift.giftId} x${gift.repeatCount}`);
        } catch (err) {
            console.error('Gagal memproses event gift:', err.message);
        }
    });

    // Event non-gift — dipetakan ke Event Trigger di admin (lihat
    // App\Livewire\ProjectLive\EventTrigger). MEMBER = orang masuk room ("join"),
    // FOLLOW/SHARE/SUB_NOTIFY user-nya sama persis bentuknya spt gift.
    connection.on(WebcastEvent.MEMBER, (data) => {
        try {
            const user = extractUserPayload(data);
            sendEventWebhook('join', user);
            console.log(`Join: ${user.nickname ?? user.userId} masuk room`);
        } catch (err) {
            console.error('Gagal memproses event join:', err.message);
        }
    });

    connection.on(WebcastEvent.FOLLOW, (data) => {
        try {
            const user = extractUserPayload(data);
            sendEventWebhook('follow', user);
            console.log(`Follow: ${user.nickname ?? user.userId}`);
        } catch (err) {
            console.error('Gagal memproses event follow:', err.message);
        }
    });

    connection.on(WebcastEvent.SHARE, (data) => {
        try {
            const user = extractUserPayload(data);
            sendEventWebhook('share', user);
            console.log(`Share: ${user.nickname ?? user.userId}`);
        } catch (err) {
            console.error('Gagal memproses event share:', err.message);
        }
    });

    connection.on(WebcastEvent.SUB_NOTIFY, (data) => {
        try {
            const user = extractUserPayload(data);
            sendEventWebhook('subscribe', user);
            console.log(`Subscribe: ${user.nickname ?? user.userId}`);
        } catch (err) {
            console.error('Gagal memproses event subscribe:', err.message);
        }
    });

    connection.on(WebcastEvent.LIKE, (data) => {
        try {
            const user = extractUserPayload(data);
            // data.count = jumlah tap like DALAM 1 event ini (bukan total kumulatif room),
            // dicocokkan ke ambang minimal trigger like di Laravel. data.total = akumulasi
            // like di seluruh room sejak live mulai — dua-duanya sengaja di-log biar
            // kelihatan langsung apakah combo tap datang sbg banyak event count=1 atau
            // 1 event count=N (tergantung seberapa cepat orangnya tap & batching TikTok).
            const count = data.count ?? 1;
            sendEventWebhook('like', user, { like_count: count });
            console.log(`Like: ${user.nickname ?? user.userId} tap x${count} (total room: ${data.total ?? '?'})`);
        } catch (err) {
            console.error('Gagal memproses event like:', err.message);
        }
    });

    connection.on(WebcastEvent.CHAT, (data) => {
        try {
            const user = extractUserPayload(data);
            const content = data.content ?? '';
            sendEventWebhook('chat', user, { chat_content: content });
            console.log(`Chat: ${user.nickname ?? user.userId}: "${content}"`);
        } catch (err) {
            console.error('Gagal memproses event chat:', err.message);
        }
    });

    connection.connect()
        .then((state) => {
            console.log(`Terhubung ke room TikTok LIVE @${TIKTOK_USERNAME} (roomId: ${state.roomId})`);
            consecutiveConnectFailures = 0;
            startHeartbeat();
        })
        .catch((err) => {
            consecutiveConnectFailures += 1;
            const retryMs = Math.min(BASE_RETRY_MS * 2 ** (consecutiveConnectFailures - 1), MAX_RETRY_MS);

            if (isSignatureRateLimitBug(err)) {
                console.error('Gagal connect: signing server TikTok sedang rate-limit percobaan connect kita (bukan masalah username/kredensial). Backoff otomatis diperpanjang supaya tidak makin kena limit.');
            } else {
                console.error('Gagal connect ke room TikTok LIVE:', err.message);
            }

            console.log(`Coba lagi dalam ${Math.round(retryMs / 1000)} detik...`);
            setTimeout(start, retryMs);
        });

    connection.on('disconnected', () => {
        console.warn('Terputus dari TikTok LIVE, coba reconnect dalam 5 detik...');
        stopHeartbeat();
        setTimeout(start, 5000);
    });

    connection.on('error', (err) => {
        console.error('Connector error:', err?.message ?? err);
    });
}

process.on('uncaughtException', (err) => console.error('Uncaught exception:', err));
process.on('unhandledRejection', (err) => console.error('Unhandled rejection:', err));
process.on('SIGINT', () => { console.log('Dihentikan.'); process.exit(0); });
process.on('SIGTERM', () => { console.log('Dihentikan.'); process.exit(0); });

console.log(`Menghubungkan ke TikTok LIVE @${TIKTOK_USERNAME}...`);
start();
