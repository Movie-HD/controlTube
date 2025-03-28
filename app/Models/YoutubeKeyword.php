<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeKeyword extends Model
{
    use HasFactory;

    protected $fillable = ['youtube_account_id', 'keyword', 'used'];

    public function account()
    {
        return $this->belongsTo(YoutubeAccount::class, 'youtube_account_id');
    }
}