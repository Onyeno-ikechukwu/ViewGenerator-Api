<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Posters;

class IncrementPosterViews implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $posterId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Posters::where('id', $this->posterId)->increment('views');
    }
}
