<?php

namespace App\Observers;

use App\Models\ProjectLive;

class ProjectLiveObserver
{
    /**
     * Jumlah kursi awal ikut default display_mode project (lihat
     * App\Models\ProjectLive::syncDetailsToDisplayMode() — dipakai juga saat
     * admin ganti tata letak belakangan, bukan cuma sekali di sini).
     */
    public function created(ProjectLive $projectLive): void
    {
        $projectLive->syncDetailsToDisplayMode();
    }
}
