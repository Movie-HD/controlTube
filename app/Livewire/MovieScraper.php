<?php

namespace App\Livewire;

use DOMDocument;
use DOMXPath;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

    public function render(): View
    {
        return view('livewire.movie-scraper');
    }

    public function copy($text): void
    {
        $this->dispatch('copy-to-clipboard', text: $text);
    }
}
