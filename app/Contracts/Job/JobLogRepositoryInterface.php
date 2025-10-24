<?php

namespace App\Contracts\Job;

use App\Models\Pim\Job\PimJob;
use App\Models\Pim\Job\PimJobLog;
use Illuminate\Database\Eloquent\Collection;

interface JobLogRepositoryInterface
{
    public function writeJobLog(PimJob $job, string $status, string $message, string $log): PimJobLog;

    public function getJobLogs(string $jobId): Collection;

    public function getJobLogsByDate(string $jobId, string $date);
}
