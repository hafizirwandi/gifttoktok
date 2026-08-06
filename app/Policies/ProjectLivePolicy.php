<?php

namespace App\Policies;

use App\Models\ProjectLive;
use App\Models\User;

class ProjectLivePolicy
{
    /**
     * Superadmin only — dipakai untuk index CRUD & halaman admin detail (edit per kotak).
     */
    public function manage(User $user, ?ProjectLive $projectLive = null): bool
    {
        return $user->hasRole('superadmin');
    }

    /**
     * Superadmin (project apa pun) atau akun live yang di-assign ke project ini.
     */
    public function viewLive(User $user, ProjectLive $projectLive): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        return $user->hasRole('live') && $projectLive->user_id === $user->id;
    }
}
