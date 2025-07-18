<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMovie extends Model
{
    protected $fillable = [
        'movie_name',
        'tmdb_id',
        'movie_link',
        'description',
        'screenshots',
        'host_server_id',
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    public function hostServer()
    {
        return $this->belongsTo(HostServer::class);
    }

    public function movieLinkHistories()
    {
        return $this->hasMany(MovieLinkHistory::class);
    }
}
