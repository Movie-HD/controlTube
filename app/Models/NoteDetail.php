<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteDetail extends Model
{
    protected $fillable = [
        'note_id',
        'name',
        'description',
        'content',
        'color',
        'screenshot',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'screenshot' => 'array',
        ];
    }

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
