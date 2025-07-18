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
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    public function serverMovies()
    {
        return $this->hasMany(ServerMovie::class);
    }
}
