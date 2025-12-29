<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TmdbService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.api_key');
    }

    /**
     * Obtiene información de una película por TMDB ID
     */
    public function getMovie(string $tmdbId): ?array
    {
        $cacheKey = "tmdb_movie_{$tmdbId}";

        return Cache::remember($cacheKey, 3600, function () use ($tmdbId) {
            try {
                $response = Http::get("{$this->baseUrl}/movie/{$tmdbId}", [
                    'api_key' => $this->apiKey,
                    'language' => 'es-ES',
                    'append_to_response' => 'credits,images',
                    'include_image_language' => 'en,null', // Obtener imágenes en inglés y sin idioma
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                \Log::warning("TMDB API failed for movie {$tmdbId}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                \Log::error("TMDB API error: " . $e->getMessage());
                return null;
            }
        });
    }

    public function formatForWordPress(array $movieData): array
    {
        $title = $movieData['title'] ?? 'Sin título';
        $overview = $movieData['overview'] ?? '';
        $releaseYear = isset($movieData['release_date'])
            ? date('Y', strtotime($movieData['release_date']))
            : '';

        // Géneros
        $genres = collect($movieData['genres'] ?? [])
            ->pluck('name')
            ->implode(', ');

        // Slug
        $slug = \Str::slug($title);

        // Runtime en formato HH:MM:SS
        $runtime = '';
        if (isset($movieData['runtime']) && $movieData['runtime'] > 0) {
            $hours = floor($movieData['runtime'] / 60);
            $minutes = $movieData['runtime'] % 60;
            $runtime = sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Países
        $countries = collect($movieData['production_countries'] ?? [])
            ->pluck('name')
            ->implode(', ');

        return [
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $overview,
            'post_excerpt' => \Str::limit($overview, 150),
            'post_status' => 'draft',
            'post_type' => 'post',
            'meta' => [
                'release_date' => $releaseYear,
                'genres' => $genres,
                'original_title' => $movieData['original_title'] ?? $title,
                // Campos IMDB para OnliPeli
                'imdbRating' => $movieData['vote_average'] ?? '',
                'imdbVotes' => $movieData['vote_count'] ?? '',
                'Title' => $movieData['original_title'] ?? $title,
                'Rated' => '', // TMDB no tiene clasificación exacta
                'Released' => $movieData['release_date'] ?? '',
                'Runtime' => $runtime,
                'Awards' => '', // TMDB no tiene premios
                'Country' => $countries,
            ],
        ];
    }

    /**
     * Busca películas por nombre
     */
    public function searchMovie(string $query): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/search/movie", [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => 'es-ES',
            ]);

            if ($response->successful()) {
                return $response->json()['results'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            \Log::error("TMDB Search error: " . $e->getMessage());
            return [];
        }
    }
}
