<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;

class WordPressPostMeta extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'postmeta';
    protected $primaryKey = 'meta_id';
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'meta_key',
        'meta_value',
    ];

    /**
     * Relación con el post
     */
    public function post()
    {
        return $this->belongsTo(WordPressPost::class, 'post_id', 'ID');
    }
}
