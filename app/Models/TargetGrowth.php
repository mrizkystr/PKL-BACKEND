<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetGrowth extends Model
{
    use HasFactory;

    protected $table = 'targets';

    protected $fillable = [
        'month',
        'year',
        'target_growth',
        'target_rkap',
        // If you have more fields like target_rkap, add them here
    ];
}
