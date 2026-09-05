{{-- Satu baris background di list App\Livewire\ProjectLive\Background — butuh $bg
     (App\Models\ProjectLiveBackground). --}}
<div wire:key="bg-{{ $bg->id }}"
    class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-3 flex items-center gap-3">
    <div class="w-16 h-16 flex-shrink-0 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-900">
        @if ($bg->type->value === 'video')
            <video src="{{ $bg->fileUrl() }}" class="w-full h-full object-cover" muted></video>
        @else
            <img src="{{ $bg->fileUrl() }}" alt="{{ $bg->name }}" class="w-full h-full object-cover">
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
            {{ $bg->name ?: ($bg->placement->value === 'seat' ? 'Kotak #'.$bg->seat_position : 'Tanpa nama') }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $bg->type->label() }}
            @if ($bg->placement->value === 'seat')
                &middot; Kotak #{{ $bg->seat_position }}
            @endif
            &middot; {{ $bg->fit_mode->label() }}
        </p>
    </div>

    <button type="button" wire:click="{{ $bg->is_active ? 'deactivate' : 'activate' }}({{ $bg->id }})"
        title="{{ $bg->is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}"
        class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full pl-1 pr-3 py-1 transition {{ $bg->is_active ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
        <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-black/20">
            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition {{ $bg->is_active ? 'translate-x-[18px]' : 'translate-x-1' }}"></span>
        </span>
        <span class="text-xs font-semibold text-white">{{ $bg->is_active ? 'Aktif' : 'Nonaktif' }}</span>
    </button>

    <button type="button" wire:click="openEdit({{ $bg->id }})"
        class="flex-shrink-0 px-2 py-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
        Edit
    </button>

    <button type="button" wire:click="delete({{ $bg->id }})" wire:confirm="Hapus background ini?"
        class="flex-shrink-0 px-2 py-1 text-xs text-red-600 dark:text-red-400 hover:underline">
        Hapus
    </button>
</div>
