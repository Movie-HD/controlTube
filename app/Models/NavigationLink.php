<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationLink extends Model
{
    protected $fillable = [
        'name',
        'url',
        'icon',
        'group_id',
        'sort_order',
        'open_in_new_tab',
        'is_active',
        'email',
        'password',
        'descripcion',
        'screenshots'
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'screenshots' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(NavigationGroup::class);
    }
}
