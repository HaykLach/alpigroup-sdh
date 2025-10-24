<?php

namespace App\Models\Pim;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimMedia extends Model
{
    use HasUuids, SoftDeletes;
}
