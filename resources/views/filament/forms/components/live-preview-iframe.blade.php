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
    'additionalImage',
    'previewId' => 'preview'
])

@php
    $params = http_build_query([
        'var1' => $poster ?? '',
        'var2' => $backdrop ?? '',
        'var3' => $title ?? '',
        'var4' => '1.2',
        'var5' => $trailer ?? '',
        'var6' => $synopsis ?? '',
        'var7' => $additionalImage ?? '',
        'var8' => $font ?? 'inherit'
    ]);
    $previewUrl = $baseUrl . '?' . $params;
@endphp

<div 
    wire:key="{{ $previewId }}-{{ md5($backdrop . $poster . $title . $synopsis . $font . $trailer) }}"
    class="space-y-3"
>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Preview en Vivo - {{ $domain }}
    </div>
    
    <div class="relative w-full bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden" style="position: relative;padding-bottom: 56.25%;">
        <iframe 
            src="{{ $previewUrl }}" 
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
