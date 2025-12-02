<?php

namespace App\Services;

use App\Models\WordPress\WordPressPost;
use App\Models\WordPress\WordPressPostMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WordPressImdbService
{
    /**
     * Detecta qué conexión usar basándose en el dominio
     * 
     * @param string $domain
     * @return string Nombre de la conexión ('wordpress' o 'onlipeli')
     */
    protected function getConnectionByDomain(string $domain): string
    {
        if (str_contains($domain, 'onlipeli.net')) {
            return 'onlipeli';
        }

        // Por defecto clubpeli.com
        return 'wordpress';
    }

    /**
     * Obtiene el nombre del campo TMDB según el sitio
     * 
     * @param string $connection
     * @return string
     */
    protected function getTmdbFieldName(string $connection): string
    {
        return match ($connection) {
            'onlipeli' => 'Checkbx2',
            default => 'imdbLink',
        };
    }

    /**
     * Obtiene el nombre del campo URL según el sitio
     * 
     * @param string $connection
     * @return string
     */
    protected function getUrlFieldName(string $connection): string
    {
        return match ($connection) {
            'onlipeli' => 'video_mp4',
            default => 'url_film',
        };
    }

    /**
     * Encuentra todos los posts de WordPress que tienen el mismo tmdb_id
     * 
     * @param string $tmdbId El TMDB ID del ServerMovie
     * @param string $domain El dominio para determinar qué base de datos usar
     * @return Collection Colección de WordPressPost
     */
    public function findPostsByTmdbId(string $tmdbId, string $domain): Collection
    {
        $connection = $this->getConnectionByDomain($domain);
        $tmdbField = $this->getTmdbFieldName($connection);

        // Buscar todos los post_id que tienen este tmdb_id
        $postIds = DB::connection($connection)
            ->table('postmeta')
            ->where('meta_key', $tmdbField)
            ->where('meta_value', $tmdbId)
            ->pluck('post_id');

        if ($postIds->isEmpty()) {
            return collect();
        }

        // Obtener los posts completos que están publicados
        return WordPressPost::on($connection)
            ->whereIn('ID', $postIds)
            ->where('post_status', 'publish')
            ->with('meta')
            ->get();
    }

    /**
     * Obtiene el url_film/video_mp4 de un post de WordPress
     * 
     * @param WordPressPost $post
     * @param string $domain
     * @return string|null
     */
    public function getUrlFilmFromPost(WordPressPost $post, string $domain): ?string
    {
        $connection = $this->getConnectionByDomain($domain);
        $urlField = $this->getUrlFieldName($connection);

        return $post->meta()
            ->where('meta_key', $urlField)
            ->value('meta_value');
    }

    /**
     * Busca un post de WordPress por su post_name
     * 
     * @param string $postName
     * @param string $domain
     * @return WordPressPost|null
     */
    public function findPostByPostName(string $postName, string $domain): ?WordPressPost
    {
        $connection = $this->getConnectionByDomain($domain);

        return WordPressPost::on($connection)
            ->where('post_name', $postName)
            ->whereIn('post_status', ['publish', 'draft'])
            ->with('meta')
            ->first();
    }

    /**
     * Actualiza el url_film/video_mp4 de un post en WordPress
     * 
     * @param WordPressPost $post
     * @param string $urlFilm
     * @param string $domain
     * @return bool
     */
    public function updateUrlFilm(WordPressPost $post, string $urlFilm, string $domain): bool
    {
        $connection = $this->getConnectionByDomain($domain);
        $urlField = $this->getUrlFieldName($connection);

        \Log::info("Attempting to update WordPress", [
            'connection' => $connection,
            'post_id' => $post->ID,
            'field' => $urlField,
            'url' => $urlFilm,
        ]);

        try {
            // Buscar si ya existe el meta_key usando DB directamente
            $meta = DB::connection($connection)
                ->table('postmeta')
                ->where('post_id', $post->ID)
                ->where('meta_key', $urlField)
                ->first();

            if ($meta) {
                // Verificar si el valor ya es el correcto
                if ($meta->meta_value === $urlFilm) {
                    \Log::info("Meta value already correct", ['meta_id' => $meta->meta_id]);
                    return true; // Ya está correcto, no necesita actualización
                }

                // Actualizar existente
                \Log::info("Updating existing meta", ['meta_id' => $meta->meta_id]);
                $result = DB::connection($connection)
                    ->table('postmeta')
                    ->where('meta_id', $meta->meta_id)
                    ->update(['meta_value' => $urlFilm]);

                \Log::info("Update result", ['rows_affected' => $result]);
                return $result > 0;
            } else {
                // Crear nuevo
                \Log::info("Creating new meta");
                $result = DB::connection($connection)
                    ->table('postmeta')
                    ->insert([
                        'post_id' => $post->ID,
                        'meta_key' => $urlField,
                        'meta_value' => $urlFilm,
                    ]);

                \Log::info("Insert result", ['success' => $result]);
                return $result;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update WordPress", [
                'error' => $e->getMessage(),
                'post_id' => $post->ID,
            ]);
            return false;
        }
    }
}
