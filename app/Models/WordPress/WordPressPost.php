<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;

class WordPressPost extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'post_title',
        'post_name',
        'post_status',
        'post_type',
    ];

    /**
     * Relación con los metadatos del post
     */
    public function meta()
    {
        return $this->hasMany(WordPressPostMeta::class, 'post_id', 'ID');
    }

    /**
     * Obtiene el imdbLink (que en realidad es el TMDB ID)
     */
    public function getImdbLink(): ?string
    {
        return $this->meta()
            ->where('meta_key', 'imdbLink')
            ->value('meta_value');
    }

    /**
     * Obtiene el url_film del post
     */
    public function getUrlFilm(): ?string
    {
        return $this->meta()
            ->where('meta_key', 'url_film')
            ->value('meta_value');
    }

    /**
     * Construye la URL completa del post
     * 
     * @param string $domain El dominio a usar (clubpeli.com o onlipeli.net)
     */
    public function getFullUrl(string $domain = 'clubpeli.com'): string
    {
        return "https://{$domain}/{$this->post_name}";
    }
}
