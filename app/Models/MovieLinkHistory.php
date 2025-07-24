<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieLinkHistory extends Model
{
    protected $fillable = [
        'movie_link_id',
        'old_link',
    ];

    public function serverMovie()
    {
        return $this->belongsTo(ServerMovie::class);
    }
    public function movieLink()
    {
        return $this->belongsTo(MovieLink::class);
    }
}
