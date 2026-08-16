require('dotenv').config();
const axios = require('axios');
const { TikTokLiveConnection, WebcastEvent } = require('tiktok-live-connector');

const TIKTOK_USERNAME = process.env.TIKTOK_USERNAME;
const PROJECT_LIVE_ID = Number(process.env.PROJECT_LIVE_ID);
const WEBHOOK_URL = process.env.WEBHOOK_URL;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;
const HEARTBEAT_URL = WEBHOOK_URL ? WEBHOOK_URL.replace(/\/?$/, '') + '/heartbeat' : null;
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

async function sendWebhook(payload, attempt = 1) {
    try {
        await axios.post(WEBHOOK_URL, payload, {
            headers: { Authorization: `Bearer ${WEBHOOK_SECRET}` },
            timeout: 5000,
        });
    } catch (err) {
        if (attempt < 3) {
            console.warn(`Webhook gagal (percobaan ${attempt}), coba lagi...`, err.message);
            setTimeout(() => sendWebhook(payload, attempt + 1), 1000 * attempt);
        } else {
            console.error('Webhook gagal terkirim setelah 3 percobaan, event ini dilewati:', err.message);
        }
    }
}

function start() {
    // Argumen kedua (options) WAJIB ada walau kosong — versi 2.4.3 membaca
    // options.processInitialData dkk langsung tanpa fallback kalau options undefined.
    const connection = new TikTokLiveConnection(TIKTOK_USERNAME, {});

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

            const totalValue = gift.diamondCount * gift.repeatCount;

            sendWebhook({
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
                    total_value: totalValue,
                },
            });

            console.log(`Gift: ${gift.nickname ?? gift.userId} -> ${totalValue}`);
        } catch (err) {
            console.error('Gagal memproses event gift:', err.message);
        }
    });

    connection.connect()
        .then((state) => {
            console.log(`Terhubung ke room TikTok LIVE @${TIKTOK_USERNAME} (roomId: ${state.roomId})`);
            startHeartbeat();
        })
        .catch((err) => {
            console.error('Gagal connect ke room TikTok LIVE:', err.message);
            console.log('Coba lagi dalam 10 detik...');
            setTimeout(start, 10000);
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
