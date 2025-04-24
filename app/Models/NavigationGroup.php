<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationGroup extends Model
{
    protected $fillable = [
        'name',
        'color',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function links()
    {
        return $this->hasMany(NavigationLink::class, 'group_id');
    }
}
