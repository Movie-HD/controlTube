<div class="space-y-6">
<x-filament::section>
    <x-filament-panels::form wire:submit="scrape">
        {{ $this->form }}

        <x-filament::button type="submit">
            Extraer Datos
        </x-filament::button>
    </x-filament-panels::form>
</x-filament::section>

<div class="mt-8 space-y-6">
    <x-filament::section>
        <x-slot name="heading">Título</x-slot>
        <p onclick="copy(this)">{{ $xtitulo }}</p>
    </x-filament::section>

    {{-- Contenedor Tabs --}}
    <x-filament::section>
        <div x-data="{ activeTab: 'titulo' }">
            {{-- Tabs --}}
            <x-filament::tabs class="justify-center grande">
                <x-filament::tabs.item
                    alpine-active="activeTab === 'titulo'"
                    x-on:click="activeTab = 'titulo'"
                    icon="heroicon-m-bell"
                >
                    Titulo
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'tituloblackhat'"
                    x-on:click="activeTab = 'tituloblackhat'"
                    icon="heroicon-o-film"
                >
                    Titulo Blackhat
                </x-filament::tabs.item>
            </x-filament::tabs>

            {{-- Contenido dinámico según tab activo --}}
            <div class="mt-4">
                <div x-show="activeTab === 'titulo'" x-cloak>
                    <p onclick="copy(this)">🔴 {{ $xtitulo }} 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">✅ {{ $xtitulo }} ESTRENO 2025 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">⏩ {{ $xtitulo }} ✅ PELICULA ESPAÑOL LATINO 🍿 ESTRENO 2025</p>
                    <p onclick="copy(this)">▷ {{ $xtitulo }} 🎬 PELICULA COMPLETA ✅ ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">🔴 {{ $noPuntos }} 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">✅ {{ $xtitulo }} [2025], Película Completa 🔴 Español Latino</p>
                    <p onclick="copy(this)">▷ {{ $noPuntos }} ESTRENO 2025 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">⏩ {{ $noPuntos }} ✅ PELICULA ESPAÑOL LATINO 🍿 ESTRENO 2025</p>
                    <p onclick="copy(this)">▷ {{ $noPuntos }} 🎬 PELICULA COMPLETA ✅ ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">{{ $noPuntos }} [2025] 👉 Película, Completa en Español Latino</p>
                    <p onclick="copy(this)">{{ $noPuntos }} Pelicula, Completa en Espanol Latino</p>
                    <p onclick="copy(this)">✔ {{ $noPuntos }} 2025 PELICULA, Completas en Español latino</p>
                    <p onclick="copy(this)">{{ $noPuntos }}</p>
                    <p onclick="copy(this)">{{ $noPuntos }} 2025</p>
                    <p onclick="copy(this)">{{ $noPuntos }} Trailer</p>
                    <p onclick="copy(this)">{{ $noPuntos }} portada</p>

                    <p onclick="copy(this)">Ver {{ $xtitulo }} Pelicula de Estreno 2025 Completa en Español Latino HD</p>
                    <p onclick="copy(this)">ESTRENO {{ $xtitulo }} Pelicula [2025] Completa de {{ $generos }} | {{ $xtitulo }} en Español Latino</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Estreno 2025 PELICULA de {{ $generos }} COMPLETA en ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">{{ $xtitulo }} PELICULAS COMPLETAS en ESPAÑOL'LATINO Pelicula de {{ $generos }} 2025</p>
                    <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA de ESTRENO {{ $generos }} 2025 en ESPAÑOL LATINO</p>
                    <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA de {{ $generos }} 2025 en ESPAÑOL LATINO HD</p>
                    <p onclick="copy(this)">Estreno 2025 {{ $xtitulo }} PELICULA de {{ $generos }} Completa Español'Latino</p>
                    <p onclick="copy(this)">Ver y Descargar {{ $xtitulo }} PELICULA de {{ $generos }} COMPLETA ESPAÑOL LATINO 2025 HD</p>
                    <p onclick="copy(this)">{{ $xtitulo }} | PELICULAS COMPLETAS en ESPAÑOL'LATINO | Pelicula de {{ $generos }} 2025</p>
                    <p onclick="copy(this)">{{ $xtitulo }} PELICULA de {{ $generos }} COMPLETA ESPAÑOL LATINO 2025 HD</p>
                    <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA'en'ESPAÑOL'LATINO'HD</p>
                </div>

                <div x-show="activeTab === 'tituloblackhat'" x-cloak>
                    <p onclick="copy(this)">{{ $xtitulo }}</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa en Español Latino YouTube</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa en Español Latino Facebook</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa en Español Latino 1080p</p>
                    <p onclick="copy(this)">Ver {{ $xtitulo }} Pelicula Completa en Español Gratis 2025</p>
                    <p onclick="copy(this)">Ver {{ $xtitulo }} la Pelicula Completa Online en Español Latino Gratis sin registrarse</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Indu Completa en Español latino HD sin registrarse</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Indu Completa en Español Online</p>
                    <p onclick="copy(this)">Ver {{ $xtitulo }} Pelicula Indu Completa en Español Latino Gratis</p>
                    <p onclick="copy(this)">{{ $xtitulo }} [2025] Pelicula Completa Online en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} Pelicula Completa en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} [2025] Español Latino MEGA HD Gratis sin registrarse</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} [2025] Español Latino TORRENT HD Gratis</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} [2025] Español Latino MEDIAFIRE HD Gratis</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} la Pelicula por MEGA 2025</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} la Pelicula por MEDIAFIRE HD Gratis</p>
                    <p onclick="copy(this)">Descargar {{ $xtitulo }} la Pelicula por TORRENT Gratis</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Descargar Pelicula Completa en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">Ver y Descargar {{ $xtitulo }} Pelicula Completa en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">Como Ver y Descargar {{ $xtitulo }} Pelicula Completa en Español Latino Gratis HD</p>
                    <p onclick="copy(this)">Donde Ver y Descargar {{ $xtitulo }} Pelicula Completa en Español Latino Gratis HD sin registrarse</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa Subtitulada</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa Sub Español</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Completa Filtrada</p>
                    <p onclick="copy(this)">{{ $xtitulo }} Pelicula Online Castellano</p>
                </div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sinopsis</x-slot>
        <p onclick="copy(this)">{{ $sinopsis }}</p>
    </x-filament::section>

    {{-- Contenedor Tabs --}}
    <x-filament::section>
        <div x-data="{ activeTab: 'descripcion' }">
            {{-- Tabs --}}
            <x-filament::tabs class="justify-center grande">
                <x-filament::tabs.item
                    alpine-active="activeTab === 'descripcion'"
                    x-on:click="activeTab = 'descripcion'"
                    icon="heroicon-m-bell"
                >
                    Descripción
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'blackhat'"
                    x-on:click="activeTab = 'blackhat'"
                    icon="heroicon-o-film"
                >
                    Descripción Blackhat
                </x-filament::tabs.item>
            </x-filament::tabs>

            {{-- Contenido dinámico según tab activo --}}
            <div class="mt-4">
                <div x-show="activeTab === 'descripcion'" x-cloak>
                    <p onclick="copy(this)">{{ $xtitulo }} pelicula (2025) esta disponible, como siempre en nuestra pagina web de peliculas. Nuestro contenido está adaptado al Español, Latino y Subtitulado. El genero de {{ $xtitulo }} es <?php echo $generos;?>. {{ $xtitulo }} la Pelicula tiene una duración de <?php echo $time;?> min. Nuestro contenido es para ver online y tenemos siempre la mejor calidad centrandonos en contenido HD, 1080, o 720, sin registrarse.</p>
                    <p onclick="copy(this)">{{ $xtitulo }} película de <?php echo $generos;?> (2025) esta disponible, como siempre en nuestra pagina web de películas. Esta Película los mantendrá muy atentos a la pantalla, ya que sin duda sera una de las mejores películas que veras este año. Espero disfruten de la película como yo, suerte.</p>
                    <p onclick="copy(this)">{{ $xtitulo }} película de <?php echo $generos;?> Estreno 2025, esta es una de las peliculas mas esperadas de este año. La película {{ $xtitulo }} es de <?php echo $generos;?> y en los primeros minutos de la película ya te tiene enganchado a la pantalla, sin duda no se lo pueden perder, espero disfruten de la película, cuidense.</p>
                </div>

                <div x-show="activeTab === 'blackhat'" x-cloak>
                    <p onclick="copy(this)">⏩ Ver {{ $xtitulo }} PELICULA COMPLETA Online en Español, Latino, Subtitulado en Full HD 2025 Gratis. Descargar {{ $xtitulo }} Completa Online Mega, Torrent. Trailer Youtube, Facebook.</p>
                    <p onclick="copy(this)">✅ Ver {{ $xtitulo }} PELICULA COMPLETA en Español Latino Online Gratis ✚ Descargar {{ $xtitulo }} Completa en Mega Torrent Gratis Full HD. Trailer Youtube, Facebook en español Latino.</p>
                    <p onclick="copy(this)">📌 Ver {{ $xtitulo }} Pelicula Completa en Español Latino [2025] Gratis Full HD sin cortes y sin publicidad. Descargar {{ $xtitulo }} Completa Online Mega, Mediafire, Torrent. Última actualización: HOY.</p>
                </div>
            </div>
        </div>
    </x-filament::section>


    <x-filament::section>
        <x-slot name="heading">Emojis</x-slot>
        <p id="emoji" onclick="copy(this)">⏩</p>
        <p id="emoji" onclick="copy(this)">✅</p>
        <p id="emoji" onclick="copy(this)">▶️</p>
        <p id="emoji" onclick="copy(this)">❎</p>
        <p id="emoji" onclick="copy(this)">❗</p>
        <p id="emoji" onclick="copy(this)">📣</p>
        <p id="emoji" onclick="copy(this)">💻</p>
        <p id="emoji" onclick="copy(this)">📲</p>
        <p id="emoji" onclick="copy(this)">🖥</p>
        <p id="emoji" onclick="copy(this)">📸</p>
        <p id="emoji" onclick="copy(this)">🎥</p>
        <p id="emoji" onclick="copy(this)">🎞</p>
        <p id="emoji" onclick="copy(this)">👉</p>
        <p id="emoji" onclick="copy(this)">🚩</p>
        <p id="emoji" onclick="copy(this)">🎬</p>
        <p id="emoji" onclick="copy(this)">⭐</p>
        <p id="emoji" onclick="copy(this)">🔴</p>
        <p id="emoji" onclick="copy(this)">🥇</p>
        <p id="emoji" onclick="copy(this)">⚡</p>
        <p id="emoji" onclick="copy(this)">📍</p>
        <p id="emoji" onclick="copy(this)">⬇</p>
        <p id="emoji" onclick="copy(this)">🎯</p>
        <p id="emoji" onclick="copy(this)">▷</p>
        <p id="emoji" onclick="copy(this)">➤</p>
        <p id="emoji" onclick="copy(this)">❤️</p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Comentarios YouTube</x-slot>
        <p onclick="copy(this)">🔴 Hola aquí puedes ver la película:<br>🎬👉👉 <?php echo $formUrl;?></p>
        <p onclick="copy(this)">👉 VER AQUI: <?php echo $formUrl;?></p>
        <p onclick="copy(this)">⏩ MIRALA AQUI 👉 <?php echo $formUrl;?></p>
        <p onclick="copy(this)">❎ Aqui pueden ver "{{ $xtitulo }}": <?php echo $formUrl;?></p>
    </x-filament::section>
</div>

    <style>
        p{margin:8px 0;box-shadow:0 1px 4px 0 #000;border-left:2px dashed #f50400;padding:10px;cursor:pointer}p:hover{background:#341750}
        #emoji{display:inline-block}
        .grande button{font-size:16px}
    </style>
    <script>
        function copy(that){
        var inp =document.createElement('input');
        document.body.appendChild(inp)
        inp.value =that.textContent
        inp.select();
        document.execCommand('copy',false);
        inp.remove();
        that.style.cssText="filter:brightness(0.5);background:#341750";
        }
    </script>
</div>
