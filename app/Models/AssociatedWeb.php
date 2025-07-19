<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociatedWeb extends Model
{
    protected $fillable = [
        'server_movie_id',
        'link',
        'was_updated',
        'description',
        'screenshots',
    ];

    protected $casts = [
        'was_updated' => 'boolean',
        'screenshots' => 'array',
    ];
}
