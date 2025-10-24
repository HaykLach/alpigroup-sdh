<?php

namespace App\Models\Pim\Job;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PimJobLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pim_job_logs';

    protected $fillable = [
        'pim_job_id',
        'status',
        'message',
        'log',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PimJob::class, 'pim_job_id', 'id');
    }
}
