<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociatedWebMovieLink extends Model
{
    protected $fillable = [
        'associated_web_id',
        'movie_link_id',
        'was_updated',
        'sort',
    ];

    protected $table = 'associated_web_movie_link';

    public function movieLink()
    {
        return $this->belongsTo(MovieLink::class);
    }

    public function associatedWeb()
    {
        return $this->belongsTo(AssociatedWeb::class);
    }
}
