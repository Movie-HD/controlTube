<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMovie extends Model
{
    protected $fillable = [
        'movie_name',
        'tmdb_id',
        'description',
        'screenshots',
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    public function movieLinks()
    {
        return $this->hasMany(MovieLink::class);
    }

    public function associatedWebs()
    {
        return $this->hasMany(AssociatedWeb::class);
    }

    public function movieLinkHistories()
    {
        return $this->hasManyThrough(
            MovieLinkHistory::class,
            MovieLink::class,
            'server_movie_id', // Foreign key en MovieLink que apunta a ServerMovie
            'movie_link_id',   // Foreign key en MovieLinkHistory que apunta a MovieLink
            'id',              // Local key en ServerMovie
            'id'               // Local key en MovieLink
        );
    }

}
