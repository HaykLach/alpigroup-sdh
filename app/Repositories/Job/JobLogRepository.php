<?php

namespace App\Repositories\Job;

use App\Contracts\Job\JobLogRepositoryInterface;
use App\Models\Pim\Job\PimJob;
use App\Models\Pim\Job\PimJobLog;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class JobLogRepository extends BaseRepository implements JobLogRepositoryInterface
{
    public function writeJobLog(PimJob $job, string $status, string $message, string $log): PimJobLog
    {
        return PimJobLog::create([
            'pim_job_id' => $job->id,
            'status' => $status,
            'message' => $message,
            'log' => $log,
        ]);
    }

    public function getJobLogs(string $jobId): Collection
    {
        return PimJobLog::where('pim_job_id', $jobId)->get();
    }

    public function getJobLogsByDate(string $jobId, string $date): Collection
    {
        return PimJobLog::where('pim_job_id', $jobId)->where('created_at', '>=', $date)->get();
    }
}
