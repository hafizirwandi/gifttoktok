<?php

use App\Livewire\ProjectLive\DetailAdmin;
use App\Livewire\ProjectLive\Index as ProjectLiveIndex;
use App\Livewire\ProjectLive\LiveShow;
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
    });

    // Superadmin (project apa pun) atau akun live (project miliknya sendiri) — dicek via Policy di mount().
    Route::get('/project-live/{projectLive}/admin', DetailAdmin::class)->name('project-live.admin');
    Route::get('/project-live/{projectLive}/live', LiveShow::class)->name('project-live.live');
});

require __DIR__.'/auth.php';
