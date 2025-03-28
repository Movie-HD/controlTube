<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YoutubePage extends Model {

    protected $fillable = ['name', 'url'];

    public function accounts() {
        return $this->hasMany(YoutubeAccountPage::class, 'youtube_page_id');
    }
}
