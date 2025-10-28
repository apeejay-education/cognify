<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends TenantAwareModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'source', 'name', 'email', 'phone', 'course_interest', 'meta', 'score', 'status',
    ];

    protected $casts = [
        'meta' => 'array',
        'score' => 'float',
    ];
}
