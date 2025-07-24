<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociatedWeb extends Model
{
    protected $fillable = [
        'link',
        'get_domain',
        'description',
        'screenshots',
        'badge_color',
        'server_movie_id',
    ];

    protected $casts = [
        'screenshots' => 'array',
    ];

    public function serverMovie()
    {
        return $this->belongsTo(ServerMovie::class);
    }

    public function movieLinks()
    {
        return $this->belongsToMany(MovieLink::class)
                    ->withPivot('was_updated')
                    ->withTimestamps();
    }

    public function movieLinkDetails()
{
    return $this->hasMany(AssociatedWebMovieLink::class);
}

}
