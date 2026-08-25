<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Pengirim Gift
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filter: project (opsional) + tipe periode + nilai periode. Input nilai
                 periode gantian bentuknya (date/week/month/number) ngikutin periodType,
                 pakai <input> bawaan browser (bukan library date-picker) - formatnya
                 di-parse App\Livewire\GiftSenders\Index::dateRange(). -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label value="Project" />
                        <select wire:model.live="projectId"
                            class="mt-1 block w-48 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua Project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Tipe Periode" />
                        <select wire:model.live="periodType"
                            class="mt-1 block w-36 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="day">Harian</option>
                            <option value="week">Mingguan</option>
                            <option value="month">Bulanan</option>
                            <option value="year">Tahunan</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Periode" />
                        @if ($periodType === 'day')
                            <x-text-input type="date" wire:model.live="periodValue" class="mt-1 block text-sm" />
                        @elseif ($periodType === 'week')
                            <x-text-input type="week" wire:model.live="periodValue" class="mt-1 block text-sm" />
                        @elseif ($periodType === 'year')
                            <x-text-input type="number" wire:model.live="periodValue" min="2000" max="2100" class="mt-1 block w-28 text-sm" />
                        @else
                            <x-text-input type="month" wire:model.live="periodValue" class="mt-1 block text-sm" />
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pengirim</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gift</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</th>
                                @if (! $projectId)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Terakhir Kirim</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($entries as $entry)
                                <tr wire:key="entry-{{ $loop->index }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            @if ($entry->avatarUrl)
                                                <img src="{{ $entry->avatarUrl }}" class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 flex-shrink-0"></div>
                                            @endif
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $entry->nickname }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            @if ($entry->giftIconUrl)
                                                <img src="{{ $entry->giftIconUrl }}" class="w-6 h-6 rounded flex-shrink-0" alt="">
                                            @endif
                                            <div>
                                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $entry->giftName }}</div>
                                                <div class="text-xs text-gray-400">{{ number_format($entry->giftDiamondCount) }} poin</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ number_format($entry->qty) }}x
                                    </td>
                                    @if (! $projectId)
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $entry->projectName }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $entry->lastSentAt ? \Illuminate\Support\Carbon::parse($entry->lastSentAt)->diffForHumans() : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $projectId ? 4 : 5 }}" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada gift yang dikirim di periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- "Lazy load": bukan pagination bernomor, tapi tombol nambah LIMIT query
                     (lihat App\Livewire\GiftSenders\Index::loadMore()) - dicek via "intip 1
                     baris ekstra" ($hasMore), bukan hitung total (lebih murah). -->
                @if ($hasMore)
                    <div class="px-4 py-4 flex justify-center border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore" type="button"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50">
                            <span wire:loading.remove wire:target="loadMore">Muat Lebih Banyak</span>
                            <span wire:loading wire:target="loadMore">Memuat...</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
