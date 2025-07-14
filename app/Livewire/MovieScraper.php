<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use GuzzleHttp\Client;
use Exception;
use DOMDocument;
use DOMXPath;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\Models\ScrapedUrl;

class MovieScraper extends Component implements HasForms
{
    use InteractsWithForms;

    // Reemplazar el array con propiedades individuales
    public $formTitle = '';
    public $formUrl = '';

    // Variables para almacenar los datos extraídos
    public $titulo = '';
    public $noPuntos = '';
    public $xtitulo = '';
    public $sinopsis = null;
    public $generos = null;
    public $time = null;
    public $keywords = null;
    public $imagen = null;

    public $urlHistory = [];

    // TMDB
    public $tmdbResults = [];
    public $selectedTmdbMovie = null;
    public $tmdbBackdrops = [];
    private $tmdbApiKey = '46a5b7d36b5a603dfab0482e7844c00a';

    // Definir reglas de validación
    protected function rules()
    {
        return [
            'formTitle' => 'nullable|string',
            'formUrl' => 'required|url',
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'formTitle' => $this->formTitle,
            'formUrl' => $this->formUrl,
        ]);

        $this->loadUrlHistory();
    }

    public function loadUrlHistory()
{
    $this->urlHistory = ScrapedUrl::orderByDesc('created_at')->limit(10)->pluck('url')->toArray();
}

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('formTitle')
                    ->label('Título')
                    ->placeholder('Título...'),
                TextInput::make('formUrl')
                    ->label('URL')
                    ->placeholder('URL...')
                    ->required()
                    ->url(),
            ]);
    }

    public function scrape(): void
    {
        // Limpiar selección previa de TMDB #NUEVO
        $this->selectedTmdbMovie = null;
        $this->tmdbBackdrops = [];

        // Obtener datos del formulario y actualizar propiedades
        $data = $this->form->getState();
        $this->formTitle = $data['formTitle'];
        $this->formUrl = $data['formUrl'];

        // Validar los datos
        $this->validate();

        // Guardar la URL en el historial
        ScrapedUrl::create(['url' => $this->formUrl]);
        $this->loadUrlHistory();

        $urlx = $this->formUrl;

        if (!empty($urlx)) {
            if (strpos($urlx, 'onlipeli') !== false) {
                $this->scrapeOnlipeli($urlx);
            } else {
                $this->scrapeOtherSite($urlx);
            }
        }

        // Si el usuario ingresó un título, úsalo; si no, usa el extraído
        $this->xtitulo = $this->formTitle ?: $this->titulo;
        $this->noPuntos = str_replace('.', '', $this->xtitulo);

        // Buscar automáticamente en TMDB
        $this->searchTmdbMovie();
    }

    private function scrapeOnlipeli($url): void
    {
        $html = $this->fileGetContentsCurl($url);

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $sinopsisNode = $xpath->query('/html/body/div[4]/div/div[3]/div[13]/div[3]/div/div[1]/p/text()')->item(0);
        $generosNode = $xpath->query('/html/body/div[4]/div/div[3]/div[13]/div[1]/div[2]/p[4]/i/text()')->item(0);

        $this->sinopsis = $sinopsisNode ? $sinopsisNode->nodeValue : null;
        $this->generos = $generosNode ? $generosNode->nodeValue : null;
        $this->time = ($node = $xpath->query('/html/body/div[4]/div/div[3]/div[13]/div[1]/div[2]/div[1]/div[2]/b/span/text()')->item(0)) ? $node->nodeValue : null;
        $this->keywords = ($node = $xpath->query('/html/body/div[4]/div/div[3]/p/text()')->item(0)) ? $node->nodeValue : null;
        $eltitulo = $xpath->query('/html/body/div[4]/div/div[2]/div/div[4]/span[3]/text()');

        if ($eltitulo->length > 0) {
            $this->titulo = $eltitulo->item(0)->nodeValue;
        }
    }

    private function scrapeOtherSite($url): void
    {
        $html = $this->fileGetContentsCurl($url);

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $sinopsisNode = $xpath->query('/html/body/div[2]/div/div/p[2]/text()')->item(0);
        $generosNode = $xpath->query('/html/body/div[2]/div/div/p[1]/span[1]/a[1]/text()')->item(0);

        $this->sinopsis = $sinopsisNode ? $sinopsisNode->nodeValue : null;
        $this->generos = $generosNode ? $generosNode->nodeValue : null;
        $this->time = ($node = $xpath->query('/html/body/div[2]/div/div/p[1]/span[2]/text()')->item(0)) ? $node->nodeValue : null;
        $this->keywords = ($node = $xpath->query('/html/body/div[2]/div/div/p[1]/span[1]/text()')->item(0)) ? $node->nodeValue : null;
        $eltitulo = $xpath->query('/html/body/div[2]/div/div/h2/text()');
        $laimagen = $xpath->query('/html/body/div[2]/div/img/@src');

        if ($eltitulo->length > 0) {
            $this->titulo = $eltitulo->item(0)->nodeValue;
        }

        if ($laimagen->length > 0) {
            $this->imagen = $laimagen->item(0)->nodeValue;
        }
    }

    private function fileGetContentsCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    // Buscar películas en TMDB por título
    public function searchTmdbMovie()
    {
        $query = $this->xtitulo;
        if (empty($query)) {
            $this->tmdbResults = [];
            return;
        }
        $client = new Client();
        $url = 'https://api.themoviedb.org/3/search/movie?api_key=' . $this->tmdbApiKey . '&query=' . urlencode($query) . '&language=es';
        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            $this->tmdbResults = $data['results'] ?? [];
        } catch (Exception $e) {
            $this->tmdbResults = [];
        }
    }

    // Seleccionar película de TMDB y cargar imágenes
    public function selectTmdbMovie($movieId)
    {
        $this->selectedTmdbMovie = null;
        $this->tmdbBackdrops = [];
        $client = new Client();
        $url = 'https://api.themoviedb.org/3/movie/' . $movieId . '?api_key=' . $this->tmdbApiKey . '&language=es';
        $imagesUrl = 'https://api.themoviedb.org/3/movie/' . $movieId . '/images?api_key=' . $this->tmdbApiKey;
        try {
            $response = $client->get($url);
            $this->selectedTmdbMovie = json_decode($response->getBody(), true);
            $imagesResponse = $client->get($imagesUrl);
            $imagesData = json_decode($imagesResponse->getBody(), true);
            $this->tmdbBackdrops = $imagesData['backdrops'] ?? [];
        } catch (Exception $e) {
            $this->selectedTmdbMovie = null;
            $this->tmdbBackdrops = [];
        }
    }

    public function render(): View
    {
        return view('livewire.movie-scraper');
    }

    public function copy($text): void
    {
        $this->dispatch('copy-to-clipboard', text: $text);
    }
}
