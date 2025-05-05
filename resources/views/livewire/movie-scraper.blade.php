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

    <x-filament::section>
        <x-slot name="heading">YouTube</x-slot>
        <p onclick="copy(this)">🔴 {{ $xtitulo }} 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
        <p onclick="copy(this)">✅ {{ $xtitulo }} ESTRENO 2024 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
        <p onclick="copy(this)">⏩ {{ $xtitulo }} ✅ PELICULA ESPAÑOL LATINO 🍿 ESTRENO 2024</p>
        <p onclick="copy(this)">▷ {{ $xtitulo }} 🎬 PELICULA COMPLETA ✅ ESPAÑOL LATINO</p>
        <p onclick="copy(this)">🔴 {{ $noPuntos }} 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
        <p onclick="copy(this)">✅ {{ $xtitulo }} [2024], Película Completa 🔴 Español Latino</p>
        <p onclick="copy(this)">▷ {{ $noPuntos }} ESTRENO 2024 🎬 PELICULA 🍿 ESPAÑOL LATINO</p>
        <p onclick="copy(this)">⏩ {{ $noPuntos }} ✅ PELICULA ESPAÑOL LATINO 🍿 ESTRENO 2024</p>
        <p onclick="copy(this)">▷ {{ $noPuntos }} 🎬 PELICULA COMPLETA ✅ ESPAÑOL LATINO</p>
        <p onclick="copy(this)">{{ $noPuntos }} [2024] 👉 Película, Completa en Español Latino</p>
        <p onclick="copy(this)">{{ $noPuntos }} Pelicula, Completa en Espanol Latino</p>
        <p onclick="copy(this)">✔ {{ $noPuntos }} 2024 PELICULA, Completas en Español latino</p>
        <p onclick="copy(this)">{{ $noPuntos }}</p>
        <p onclick="copy(this)">{{ $noPuntos }} 2024</p>
        <p onclick="copy(this)">{{ $noPuntos }} Trailer</p>
        <p onclick="copy(this)">{{ $noPuntos }} portada</p>

        <p onclick="copy(this)">Ver {{ $xtitulo }} Pelicula de Estreno 2024 Completa en Español Latino HD</p>
        <p onclick="copy(this)">ESTRENO {{ $xtitulo }} Pelicula [2024] Completa de {{ $generos }} | {{ $xtitulo }} en Español Latino</p>
        <p onclick="copy(this)">{{ $xtitulo }} Estreno 2024 PELICULA de {{ $generos }} COMPLETA en ESPAÑOL LATINO</p>
        <p onclick="copy(this)">{{ $xtitulo }} PELICULAS COMPLETAS en ESPAÑOL'LATINO Pelicula de {{ $generos }} 2024</p>
        <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA de ESTRENO {{ $generos }} 2024 en ESPAÑOL LATINO</p>
        <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA de {{ $generos }} 2024 en ESPAÑOL LATINO HD</p>
        <p onclick="copy(this)">Estreno 2024 {{ $xtitulo }} PELICULA de {{ $generos }} Completa Español'Latino</p>
        <p onclick="copy(this)">Ver y Descargar {{ $xtitulo }} PELICULA de {{ $generos }} COMPLETA ESPAÑOL LATINO 2024 HD</p>
        <p onclick="copy(this)">{{ $xtitulo }} | PELICULAS COMPLETAS en ESPAÑOL'LATINO | Pelicula de {{ $generos }} 2024</p>
        <p onclick="copy(this)">{{ $xtitulo }} PELICULA de {{ $generos }} COMPLETA ESPAÑOL LATINO 2024 HD</p>
        <p onclick="copy(this)">{{ $xtitulo }} PELICULA'COMPLETA'en'ESPAÑOL'LATINO'HD</p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sinopsis</x-slot>
        <p onclick="copy(this)">{{ $sinopsis }}</p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Descripcion</x-slot>
        <p onclick="copy(this)"><?php echo $xtitulo;?> pelicula (2024) esta disponible, como siempre en nuestra pagina web de peliculas. Nuestro contenido está adaptado al Español, Latino y Subtitulado. El genero de <?php echo $xtitulo;?> es <?php echo $generos;?>. <?php echo $xtitulo;?> la Pelicula tiene una duración de <?php echo $time;?> min. Nuestro contenido es para ver online y tenemos siempre la mejor calidad centrandonos en contenido HD, 1080, o 720, sin registrarse.</p>
        <p onclick="copy(this)"><?php echo $xtitulo;?> película de <?php echo $generos;?> (2024) esta disponible, como siempre en nuestra pagina web de películas. Esta Película los mantendrá muy atentos a la pantalla, ya que sin duda sera una de las mejores películas que veras este año. Espero disfruten de la película como yo, suerte.</p>
        <p onclick="copy(this)"><?php echo $xtitulo;?> película de <?php echo $generos;?> Estreno 2024, esta es una de las peliculas mas esperadas de este año. La película <?php echo $xtitulo;?> es de <?php echo $generos;?> y en los primeros minutos de la película ya te tiene enganchado a la pantalla, sin duda no se lo pueden perder, espero disfruten de la película, cuidense.</p>
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
        <p onclick="copy(this)">❎ Aqui pueden ver "<?php echo $xtitulo;?>": <?php echo $formUrl;?></p>
    </x-filament::section>
</div>

    <style>
        p{margin:8px 0;box-shadow:0 1px 4px 0 #000;border-left:2px dashed #f50400;padding:10px;cursor:pointer}p:hover{background:#341750}
        #emoji{display:inline-block}
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
