<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'tag',
    ];

    protected function casts(): array
    { 
        return [
            'tag' => 'array',
        ];
    }

    public function noteDetails()
    {
        return $this->hasMany(NoteDetail::class);
    }
}
