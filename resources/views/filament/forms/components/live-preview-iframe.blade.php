@props([
    'domain',
    'baseUrl',
    'poster',
    'title',
    'year',
    'trailer',
    'synopsis',
    'backdrop',
    'font',
    'additionalImage'
])

<div 
    x-data="{
        poster: @js($poster),
        title: @js($title),
        year: @js($year),
        trailer: @js($trailer),
        synopsis: @js($synopsis),
        backdrop: @js($backdrop),
        font: @js($font),
        additionalImage: @js($additionalImage),
        
        get previewUrl() {
            const params = new URLSearchParams({
                var1: this.poster || '',
                var2: this.backdrop || '',
                var3: this.title || '',
                var4: this.year || '',
                var5: this.trailer || '',
                var6: this.synopsis || '',
                var7: this.additionalImage || '',
                var8: this.font || 'inherit'
            });
            
            return '{{ $baseUrl }}?' + params.toString();
        }
    }"
    class="space-y-3"
>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Preview en Vivo - {{ $domain }}
    </div>
    
    <div class="relative w-full bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden" style="position: relative;padding-bottom: 56.25%;">
        <iframe 
            :src="previewUrl" 
            class="absolute top-0 left-0 w-full h-full border-0"
            frameborder="0"
            loading="lazy"
            style="position: absolute;width: 100%; height: 100%;"
        ></iframe>
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400">
        El preview se actualiza automáticamente al editar los campos
    </div>
</div>
