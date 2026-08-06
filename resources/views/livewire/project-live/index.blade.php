<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Project Live
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <button wire:click="openCreate" type="button"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    + Project Baru
                </button>
            </div>

            @if ($projects->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada project live. Klik "+ Project Baru" untuk membuat.
                </div>
            @endif

            <!-- Mobile: card list -->
            <div class="sm:hidden space-y-3">
                @foreach ($projects as $project)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->nama_akun ?: '-' }}</p>
                            </div>
                            @if ($project->status->value === 'live')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 flex-shrink-0">Live</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 flex-shrink-0">Off</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Akun live: {{ $project->user?->name ?? '-' }}</p>
                        @if ($project->desc)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $project->desc }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2 pt-1">
                            <a href="{{ route('project-live.admin', $project) }}" wire:navigate
                                class="inline-flex items-center px-3 py-1.5 bg-gray-800 dark:bg-gray-700 text-white text-xs font-semibold rounded-md">
                                Admin
                            </a>
                            <a href="{{ route('project-live.live', $project) }}" wire:navigate
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md">
                                Live
                            </a>
                            <button wire:click="openEdit({{ $project->id }})" type="button"
                                class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-md">
                                Edit
                            </button>
                            <button wire:click="delete({{ $project->id }})" wire:confirm="Hapus project ini beserta seluruh kursinya?" type="button"
                                class="inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-md">
                                Hapus
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop: table -->
            <div class="hidden sm:block bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama Akun</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Akun Live</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($projects as $project)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $project->name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($project->status->value === 'live')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Live</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Off</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $project->nama_akun ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $project->user?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">{{ $project->desc ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                        <a href="{{ route('project-live.admin', $project) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-800 dark:bg-gray-700 text-white text-xs font-semibold rounded-md hover:bg-gray-700 dark:hover:bg-gray-600">
                                            Admin
                                        </a>
                                        <a href="{{ route('project-live.live', $project) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-500">
                                            Live
                                        </a>
                                        <button wire:click="openEdit({{ $project->id }})" type="button"
                                            class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $project->id }})" wire:confirm="Hapus project ini beserta seluruh kursinya?" type="button"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-md hover:bg-red-100 dark:hover:bg-red-900/50">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada project live. Klik "+ Project Baru" untuk membuat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-1">
                {{ $projects->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit -->
    <div x-show="$wire.showModal" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false"
                class="fixed inset-0 bg-black/60"></div>

            <div x-show="$wire.showModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $editing ? 'Edit Project Live' : 'Project Live Baru' }}
                </h3>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama Project" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select wire:model="status" id="status"
                            class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="off">Off</option>
                            <option value="live">Live</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nama_akun" value="Nama Akun TikTok" />
                        <x-text-input wire:model="nama_akun" id="nama_akun" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_akun')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="desc" value="Deskripsi" />
                        <textarea wire:model="desc" id="desc" rows="3"
                            class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('desc')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user_id" value="Akun Live (Operator)" />
                        <select wire:model="user_id" id="user_id"
                            class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">- Belum di-assign -</option>
                            @foreach ($liveUsers as $liveUser)
                                <option value="{{ $liveUser->id }}">{{ $liveUser->name }} ({{ $liveUser->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-800">
                            Batal
                        </button>
                        <x-primary-button>
                            Simpan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
