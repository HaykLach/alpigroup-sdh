<?php

namespace App\Services\Export;

use App\Models\Pim\Job\PimJob;
use Carbon\Carbon;

class PimJobExportService
{
    public function registerPimJob(Carbon $timestamp, string $provider): void
    {
        $pimJobName = $this->getPimJobName($provider);
        PimJob::query()->create([
            'job_name' => $pimJobName,
            'last_execution_date' => $timestamp,
            'last_execution_duration' => 0,
            'last_execution_result' => json_encode(['status' => 'dispatched']),
        ]);
    }

    protected function getPimJobName(string $provider): string
    {
        return 'product_export_'.$provider;
    }
}
