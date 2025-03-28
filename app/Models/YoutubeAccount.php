<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'phone_number', 'gender',
        'birth_date', 'proxy_id', 'keyword', 'channel_url',
        'status_id', 'captcha_required', 'verification_pending'
    ];

    public function status()
    {
        return $this->belongsTo(AccountStatus::class, 'status_id');
    }

    public function keywords()
    {
        return $this->hasMany(YoutubeKeyword::class);
    }

    public function proxy()
    {
        return $this->belongsTo(YoutubeProxy::class, 'proxy_id');
    }

    public function videos()
    {
        return $this->hasMany(YoutubeVideo::class, 'account_id');
    }

    public function pages()
    {
        return $this->hasMany(YoutubeAccountPage::class, 'youtube_account_id');
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }
}