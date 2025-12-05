@props(['backdrops', 'targetField'])

@php
    // El targetField viene como "data.clubpeli_backdrops", necesitamos solo "clubpeli_backdrops"
    $fieldName = str_replace('data.', '', $targetField);
@endphp

<div x-data="{ 
        backdrops: @js($backdrops),
        fieldName: @js($fieldName),
        selectBackdrop(url) {
            // In Filament action modals, the input uses mountedActionsData format
            // Try multiple selector strategies to find the input
            const selectors = [
                `input[id$='${this.fieldName}']`,
                `input[name$='${this.fieldName}']`,
                `[x-model*='${this.fieldName}']`,
                `textarea[id$='${this.fieldName}']`,
            ];
            
            let input = null;
            for (const selector of selectors) {
                input = document.querySelector(selector);
                if (input) break;
            }
            
            if (input) {
                // Set value and dispatch events
                input.value = url;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                
                // For Alpine/Livewire binding
                if (input._x_model) {
                    input._x_model.set(url);
                }
                
                // Flash effect to show the field was updated
                input.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    input.classList.remove('ring-2', 'ring-green-500');
                }, 1000);
            } else {
                console.warn('Could not find input field for:', this.fieldName);
            }
        }
    }" class="space-y-4">
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Haz clic en una imagen para seleccionarla
    </div>

    <div class="grid gap-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
        style="display:grid; grid-template-columns: repeat(auto-fit, minmax(min(calc(33% - 3px), calc(120px + 10vw)), 1fr)); gap: 2px; max-height: 400px; overflow-y: auto;">
        <template x-for="backdrop in backdrops" :key="backdrop">
            <div @click="selectBackdrop(backdrop)"
                class="cris cursor-pointer rounded-lg overflow-hidden transition-all duration-200 hover:scale-105 hover:shadow-lg hover:ring-2 hover:ring-primary-500 ring-1 ring-gray-300 dark:ring-gray-600">
                <img :src="backdrop" alt="Backdrop" class="w-full h-24 object-cover" loading="lazy">
            </div>
        </template>
    </div>

    <div class="text-xs text-gray-500 dark:text-gray-400">
        <span x-text="backdrops.length"></span> imágenes disponibles de TMDB
    </div>
    <style>
        .cris {
            transition: all .3s ease-in-out;

            &:hover {
                transform: scale(.95);
                box-shadow: 0 0 2px 1px #fff;
                cursor: pointer;
                transition: all .3s ease-in-out;
            }
        }
    </style>
</div>