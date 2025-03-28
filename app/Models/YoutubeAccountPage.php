<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeAccountPage extends Model {
    use HasFactory;

    protected $fillable = [
        'youtube_account_id',
        'youtube_page_id',
        'email',
        'password'
    ];

    public function account() {
        return $this->belongsTo(YoutubeAccount::class, 'youtube_account_id');
    }

    public function page() {
        return $this->belongsTo(YoutubePage::class, 'youtube_page_id');
    }
}
