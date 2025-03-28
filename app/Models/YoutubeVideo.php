<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeVideo extends Model
{
    use HasFactory;

    protected $fillable = ['account_id', 'video_url', 'status'];

    public function account()
    {
        return $this->belongsTo(YoutubeAccount::class, 'account_id');
    }
}