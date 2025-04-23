<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    protected $fillable = [
        'phone_number',
        'is_physical_chip',
        'name',
        'dni',
        'iccid_code',
        'registered_at',
        'in_use',
        'used_by_account_id',
        'phone_country',
        'descripcion',
        'screenshots',
    ];

    protected $casts = [
        'is_physical_chip' => 'boolean',
        'in_use' => 'boolean',
        'registered_at' => 'date',
        'screenshots' => 'array',
    ];

    // Relación con cuentas de YouTube
    public function youtubeAccounts()
    {
        return $this->hasMany(YoutubeAccount::class);
    }

    public function usedByAccount()
    {
        return $this->belongsTo(YoutubeAccount::class, 'used_by_account_id');
    }

}
