<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeProxy extends Model
{
    use HasFactory;
    protected $fillable = ['proxy', 'in_use', 'used_by_account_id'];

    public function usedByAccount()
    {
        return $this->belongsTo(YoutubeAccount::class, 'used_by_account_id');
    }
}