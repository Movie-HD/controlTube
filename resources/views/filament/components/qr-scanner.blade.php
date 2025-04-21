<x-filament::card>
    <div
        x-data="{
            scanner: null,
            cameras: [],
            selectedCamera: null,
            isScanning: false,

            init() {
                this.loadLibrary().then(() => {
                    this.loadCameras();
                });
            },

            loadLibrary() {
                return new Promise((resolve) => {
                    if (window.Html5Qrcode) {
                        return resolve();
                    }

                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                    script.onload = () => resolve();
                    document.head.appendChild(script);
                });
            },

            loadCameras() {
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        this.cameras = devices;
                        this.selectedCamera = devices[0].id;

                        // Evitar doble inicio
                        if (!this.isScanning && !this.scanner) {
                            this.initScanner();
                        }
                    }
                }).catch(err => {
                    console.error('Error al obtener cámaras:', err);
                });
            },


            initScanner() {
                this.scanner = new Html5Qrcode('reader');

                if (this.selectedCamera) {
                    this.startScanner();
                }
            },

            startScanner() {
                if (!this.scanner || this.isScanning) return;

                this.isScanning = true;
                this.scanner.start(
                    this.selectedCamera,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText) => {
                        this.stopScanner();

                        @this.set('data.iccid_code', decodedText);

                        Livewire.dispatch('close-modal', { id: 'scan_qr' });
                    },
                    (errorMessage) => {
                        // Ignorar errores normales de escaneo
                    }
                ).catch((err) => {
                    this.isScanning = false;
                    console.error('Error al iniciar el escáner:', err);
                });
            },

            stopScanner() {
                if (this.scanner && this.isScanning) {
                    this.isScanning = false;
                    return this.scanner.stop().catch(err => {
                        console.error('Error al detener el escáner:', err);
                    });
                }
                return Promise.resolve();
            },

            async changeCamera() {
                try {
                    await this.stopScanner();
                    setTimeout(() => {
                        this.startScanner();
                    }, 300);
                } catch (error) {
                    console.error('Error al cambiar de cámara:', error);
                }
            }
        }"
        x-init="init"
        x-on:hidden.window="stopScanner"
        class="space-y-6"
    >
        {{-- Lector QR --}}
        <div id="reader" class="w-full h-64 border rounded-lg overflow-hidden"></div>

        {{-- Selector de cámaras (si hay más de una) --}}
        <div x-show="cameras.length > 1" class="space-y-2">
            <label for="camera-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">Seleccionar cámara</label>
            <select
                id="camera-select"
                x-model="selectedCamera"
                @change="changeCamera()"
                class="filament-forms-select-component w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:bg-gray-700 dark:text-white dark:border-gray-600"
            >
                <template x-for="camera in cameras" :key="camera.id">
                    <option :value="camera.id" x-text="camera.label"></option>
                </template>
            </select>
        </div>

        {{-- Indicaciones --}}
        <p class="text-sm text-gray-500">
            Posiciona el código QR frente a la cámara para escanearlo automáticamente.
        </p>
    </div>
</x-filament::card>
