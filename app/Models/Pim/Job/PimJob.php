<?php

namespace App\Models\Pim\Job;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PimJob extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_jobs';

    protected $fillable = [
        'job_name',
        'last_execution_date',
        'last_execution_duration',
        'last_execution_result',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(PimJobLog::class, 'pim_job_id', 'id');
    }
}
