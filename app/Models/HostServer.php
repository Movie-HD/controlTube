<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostServer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'screenshots',
        'email',
        'password',
        'badge_color',
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    public function movieLinks()
    {
        return $this->hasMany(MovieLink::class);
    }
}
