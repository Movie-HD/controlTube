<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieLinkHistory extends Model
{
    protected $fillable = [
        'server_movie_id',
        'old_link',
    ];

    public function serverMovie()
    {
        return $this->belongsTo(ServerMovie::class);
    }
}
