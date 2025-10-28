<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class TenantAwareModel extends Model
{
    // Do not force a 'tenant' connection at class-level. We'll pick it at runtime
    // so local/dev environments without a 'tenant' connection still work.
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // If a 'tenant' connection is configured, use it. Otherwise fallback
        // to the application's default connection.
        if (config('database.connections.tenant')) {
            $this->setConnection('tenant');
        } else {
            $this->setConnection(config('database.default'));
        }
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (app()->bound('tenant') && empty($model->tenant_id)) {
                $model->tenant_id = app('tenant')->id ?? null;
            }
        });
    }

    public function scopeForCurrentTenant($query)
    {
        if (app()->bound('tenant')) {
            return $query->where('tenant_id', app('tenant')->id);
        }

        return $query;
    }
}
