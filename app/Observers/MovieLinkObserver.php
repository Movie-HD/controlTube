<?php

namespace App\Observers;

use App\Models\MovieLink;
use App\Models\MovieLinkHistory;

class MovieLinkObserver
{
    /**
     * Handle the MovieLink "updating" event.
     *
     * Se ejecuta ANTES de que el link se actualice en la base de datos.
     */

    public function updating(MovieLink $movieLink): void
    {
        if ($movieLink->isDirty('movie_link') && $movieLink->getOriginal('movie_link')) {
            // Guardar historial
            MovieLinkHistory::create([
                'movie_link_id' => $movieLink->id,
                'old_link' => $movieLink->getOriginal('movie_link'),
            ]);

            // Actualizar el campo was_updated en la tabla pivote
            foreach ($movieLink->associatedWebs as $web) {
                $movieLink->associatedWebs()->updateExistingPivot($web->id, ['was_updated' => false]);
            }
        }
    }

    /**
     * Handle the MovieLink "created" event.
     */
    public function created(MovieLink $movieLink): void
    {
        //
    }

    /**
     * Handle the MovieLink "updated" event.
     */
    public function updated(MovieLink $movieLink): void
    {
        //
    }

    /**
     * Handle the MovieLink "deleted" event.
     */
    public function deleted(MovieLink $movieLink): void
    {
        //
    }

    /**
     * Handle the MovieLink "restored" event.
     */
    public function restored(MovieLink $movieLink): void
    {
        //
    }

    /**
     * Handle the MovieLink "force deleted" event.
     */
    public function forceDeleted(MovieLink $movieLink): void
    {
        //
    }
}
