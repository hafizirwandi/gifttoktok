<?php

namespace App\Observers;

use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;

class ProjectLiveObserver
{
    public const SEAT_COUNT = 8;

    public function created(ProjectLive $projectLive): void
    {
        for ($position = 1; $position <= self::SEAT_COUNT; $position++) {
            ProjectLiveDetail::firstOrCreate(
                [
                    'project_live_id' => $projectLive->id,
                    'position' => $position,
                ],
                [
                    'status' => DetailStatus::Hide,
                ]
            );
        }
    }
}
