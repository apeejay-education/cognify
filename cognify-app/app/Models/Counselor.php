<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counselor extends Model
{
    protected $fillable = ['name','email','phone','meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function activities()
    {
        return $this->hasMany(LeadActivity::class, 'counselor_id');
    }
}
