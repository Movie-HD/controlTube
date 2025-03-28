<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function youtubeAccounts()
    {
        return $this->hasMany(YoutubeAccount::class, 'status_id');
    }
}
