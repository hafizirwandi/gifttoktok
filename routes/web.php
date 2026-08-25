<?php

use App\Livewire\GiftSenders\Index as GiftSendersIndex;
use App\Livewire\ProjectLive\DetailAdmin;
use App\Livewire\ProjectLive\EventTrigger;
use App\Livewire\ProjectLive\FrameHost;
use App\Livewire\ProjectLive\FrameHostLive;
use App\Livewire\ProjectLive\GiftMapping;
use App\Livewire\ProjectLive\HotkeyColor;
use App\Livewire\ProjectLive\Index as ProjectLiveIndex;
use App\Livewire\ProjectLive\LiveShow;
use App\Livewire\ProjectLive\PreviewLive;
use App\Livewire\User\Index as UserIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            return redirect()->route('project-live.index');
        }

        if ($user->liveProject) {
            return redirect()->route('project-live.admin', $user->liveProject);
        }

        return view('no-project-assigned');
    })->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('role:superadmin')->group(function () {
        Route::get('/project-live', ProjectLiveIndex::class)->name('project-live.index');
        Route::get('/users', UserIndex::class)->name('users.index');
        Route::get('/gift-senders', GiftSendersIndex::class)->name('gift-senders.index');
    });

    // Superadmin (project apa pun) atau akun live (project miliknya sendiri) — dicek via Policy di mount().
    Route::get('/project-live/{projectLive}/admin', DetailAdmin::class)->name('project-live.admin');
    Route::get('/project-live/{projectLive}/live', LiveShow::class)->name('project-live.live');
    Route::get('/project-live/{projectLive}/gift-mapping', GiftMapping::class)->name('project-live.gift-mapping');
    Route::get('/project-live/{projectLive}/frame-host', FrameHost::class)->name('project-live.frame-host');
    Route::get('/project-live/{projectLive}/frame-host-live', FrameHostLive::class)->name('project-live.frame-host-live');
    Route::get('/project-live/{projectLive}/hotkey-color', HotkeyColor::class)->name('project-live.hotkey-color');
    Route::get('/project-live/{projectLive}/event-trigger', EventTrigger::class)->name('project-live.event-trigger');
    Route::get('/project-live/{projectLive}/preview-live', PreviewLive::class)->name('project-live.preview-live');
});

require __DIR__.'/auth.php';
