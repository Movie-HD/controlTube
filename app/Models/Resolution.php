<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{

    protected $fillable = ['name'];

    public function youtubeAccounts()
    {
        return $this->hasMany(YoutubeAccount::class, 'resolution_id');
    }
}
