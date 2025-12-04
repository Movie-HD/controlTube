@props(['backdrops', 'currentBackdrop' => null, 'statePath'])

<div x-data="{ 
        selected: @entangle($statePath),
        backdrops: @js($backdrops)
    }" class="space-y-4">
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Selecciona un Backdrop de TMDB
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto p-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
        style="display:grid;grid-template-columns: repeat(auto-fit, minmax(min(calc(50% - 3px), calc(120px + 10vw)), 1fr)); gap: 6px;">
        <template x-for="backdrop in backdrops" :key="backdrop">
            <div @click="selected = backdrop"
                :class="selected === backdrop ? 'ring-4 ring-primary-500 scale-105' : 'ring-1 ring-gray-300 dark:ring-gray-600'"
                class="cursor-pointer rounded-lg overflow-hidden transition-all duration-200 hover:scale-105 hover:shadow-lg">
                <img :src="backdrop" alt="Backdrop" class="w-full h-32 object-cover" loading="lazy">
            </div>
        </template>
    </div>

    <div x-show="selected" class="text-xs text-gray-500 dark:text-gray-400 truncate">
        Seleccionado: <span x-text="selected"></span>
    </div>
</div>