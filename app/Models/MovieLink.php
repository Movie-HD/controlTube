<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieLink extends Model
{
    protected $fillable = [
        'server_movie_id',
        'host_server_id',
        'movie_link',
        'sort',
    ];

    public function serverMovie()
    {
        return $this->belongsTo(ServerMovie::class);
    }

    public function hostServer()
    {
        return $this->belongsTo(HostServer::class);
    }

    public function histories()
    {
        return $this->hasMany(MovieLinkHistory::class);
    }

    public function associatedWebs()
    {
        return $this->belongsToMany(AssociatedWeb::class)
            ->withPivot('was_updated')
            ->withTimestamps();
    }

}
