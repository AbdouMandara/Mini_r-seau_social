<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportSubmitted
{
    use Dispatchable, SerializesModels;

    public $report;

    /**
     * Create a new event instance.
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
    }
}
