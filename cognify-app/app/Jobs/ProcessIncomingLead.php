<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Lead $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function handle(): void
    {
        // Duplicate detection (very simple example)
        $duplicate = Lead::where('email', $this->lead->email)
            ->where('id', '<>', $this->lead->id)
            ->first();

        if ($duplicate) {
            $this->lead->status = 'duplicate';
            $this->lead->save();
            return;
        }

        // Placeholder for scoring algorithm
        $score = $this->calculateScore();
        $this->lead->score = $score;
        $this->lead->save();
    }

    private function calculateScore(): float
    {
        $score = 10.0;
        if ($this->lead->email) $score += 20;
        if ($this->lead->phone) $score += 20;
        if ($this->lead->course_interest) $score += 10;
        return min(100, $score);
    }
}
