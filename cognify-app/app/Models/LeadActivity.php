<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    protected $fillable = ['lead_id','counselor_id','type','notes','meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function counselor()
    {
        return $this->belongsTo(Counselor::class);
    }
}
