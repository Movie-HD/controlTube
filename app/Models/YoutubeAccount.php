<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'phone_number_id', 'gender',
        'birth_date', 'proxy_id', 'channel_url',
        'status_id', 'resolution_id', 'captcha_required', 'verification_pending', 'descripcion', 'screenshots', 'start_time', 'end_time'
    ];

    protected $casts = [
        'screenshots' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
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

    public function phoneNumber()
    {
        return $this->belongsTo(PhoneNumber::class);
    }
}
