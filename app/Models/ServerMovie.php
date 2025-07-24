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

}
