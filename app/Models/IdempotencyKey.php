<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = ['key', 'response', 'expires_at'];

    protected $casts = [
        'response' => 'array',  // Convertit automatiquement JSON en array
        'expires_at' => 'datetime',
    ];
}
