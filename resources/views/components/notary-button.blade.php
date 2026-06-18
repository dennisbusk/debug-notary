<!-- Script dependencies -->
<script src="https://unpkg.com/markerjs2"></script>
<script>
    function notaryCollector() {
        return {
            isOpen: false,
            isSubmitting: false,
            screenshotUrl: null,
            screenshotFile: null,
            annotatedImage: null,
            attachmentFile: null,
            attachmentName: '',
            note: '',
            tags: '',
            metadata: {
                url: '',
                userAgent: '',
                screen: '',
            },

            init() {
                document.addEventListener('paste', (event) => {
                    if (!this.isOpen) return;

                    const items = event.clipboardData?.items;
                    if (!items) return;

                    for (const item of items) {
                        if (item.type.startsWith('image/')) {
                            const file = item.getAsFile();
                            if (this.screenshotUrl) {
                                URL.revokeObjectURL(this.screenshotUrl);
                            }
                            this.screenshotFile = file;
                            this.screenshotUrl = URL.createObjectURL(file);
                            this.annotatedImage = null;
                        }
                    }
                });

                @if(config('debug-notary.console_log', true))
                // JS Error tracking
                window.addEventListener('error', (event) => {
                    this.logJsError(event.message, event.filename, event.lineno, event.colno, event.error);
                });

                window.addEventListener('unhandledrejection', (event) => {
                    let message = 'Unhandled Rejection';
                    if (event.reason) {
                        message += ': ' + (event.reason.message || event.reason);
                    }
                    this.logJsError(message, window.location.href, 0, 0, event.reason);
                });
                @endif
            },

            async logJsError(message, file, line, col, error) {
                // Undgå at logge fejl fra selve Notary (hvis muligt)
                if (file && (file.includes('markerjs2') || file.includes('alpinejs'))) return;
                if (message && message.includes('markerjs2')) return;

                const data = {
                    message: message.startsWith('Unhandled Rejection')
                        ? message.replace('Unhandled Rejection', '{{ __('debug-notary::messages.unhandled_rejection') }}')
                        : message,
                    file: file,
                    line: line,
                    log_type: 'javascript',
                    browser_data: {
                        url: window.location.href,
                        userAgent: navigator.userAgent,
                        column: col,
                        stack: error && error.stack ? error.stack : null
                    }
                };

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    fetch('{{ route('debug-notary.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                } catch (e) {
                    // Silent fail
                }
            },

            openModal() {
                this.isOpen = true;
                this.note = '';
                this.tags = '';
                this.screenshotUrl = null;
                this.screenshotFile = null;
                this.annotatedImage = null;
                this.markerArea = null;

                this.metadata.url = window.location.href;
                this.metadata.userAgent = navigator.userAgent;
                this.metadata.screen = window.innerWidth + 'x' + window.innerHeight;
            },

            closeModal() {
                this.isOpen = false;
                if (this.markerArea) {
                    this.markerArea.close();
                    this.markerArea = null;
                }
            },

            annotate() {
                if (!this.screenshotUrl) return;

                if (typeof markerjs2 === 'undefined') {
                    alert('{{ __('debug-notary::messages.alert_markerjs_not_loaded') }}');
                    return;
                }

                const imageElement = this.$refs.screenshotPreview;
                this.markerArea = new markerjs2.MarkerArea(imageElement);

                // Ensure the markerjs UI is on top of the modal (modal is z-10000)
                this.markerArea.uiStyleSettings.zIndex = 20000;

                // Set targetRoot to the modal container to fix positioning in centered modals
                this.markerArea.targetRoot = imageElement.parentElement;

                this.markerArea.availableMarkerTypes = [
                    'FreehandMarker',
                    'ArrowMarker',
                    'RectMarker',
                    'EllipseMarker',
                    'TextMarker',
                    'HighlightMarker',
                    'PixelateMarker'
                ];

                this.markerArea.addEventListener('render', (event) => {
                    this.annotatedImage = event.dataUrl;
                    this.markerArea = null;
                });

                this.markerArea.addEventListener('close', () => {
                    this.markerArea = null;
                });

                this.markerArea.show();
            },

            async submitReport() {
                if (this.markerArea) {
                    try {
                        this.annotatedImage = await this.markerArea.render();
                    } catch (e) {
                        console.error('Marker rendering failed', e);
                    }
                    this.markerArea = null;
                }

                if (!this.screenshotFile && !this.annotatedImage) {
                    alert('{{ __('debug-notary::messages.alert_screenshot_needed') }}');
                    return;
                }

                this.isSubmitting = true;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        ? document.querySelector('meta[name="csrf-token"]').content
                        : '';

                    const formData = new FormData();
                    formData.append('note', this.note);
                    formData.append('tags', this.tags);
                    formData.append('url', this.metadata.url);
                    formData.append('browser_data', JSON.stringify(this.metadata));

                    if (this.annotatedImage) {
                        formData.append('screenshot', this.annotatedImage);
                    } else if (this.screenshotFile) {
                        formData.append('screenshot', this.screenshotFile);
                    }

                    if (this.attachmentFile) {
                        formData.append('attachment', this.attachmentFile);
                    }

                    const response = await fetch('{{ route('debug-notary.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });

                    if (response.ok) {
                        this.isOpen = false;
                        alert('{{ __('debug-notary::messages.alert_submit_success') }}');
                        if (this.screenshotUrl) {
                            URL.revokeObjectURL(this.screenshotUrl);
                        }
                    } else {
                        alert('{{ __('debug-notary::messages.alert_submit_error') }}');
                    }
                } catch (e) {
                    console.error('Submission failed', e);
                    alert('{{ __('debug-notary::messages.alert_error_occurred') }}');
                } finally {
                    this.isSubmitting = false;
                }
            }
        };
    }
</script>
<div x-data="notaryCollector()" class="fixed bottom-6 right-6 z-[9999]" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999;">
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Ensure marker.js UI is always on top of the modal */
        div[id^="mjs2-"], .mjs2-ui-container {
            z-index: 20000 !important;
        }
    </style>
    <!-- Floating Button -->
    <button @click="openModal"
            class="w-14 h-14 bg-yellow-400 hover:bg-yellow-500 text-white rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110 focus:outline-none debug-notary-ignore"
            style="width: 56px; height: 56px; background-color: rgb(250, 204, 21); color: rgb(255, 0, 0); border-radius: 9999px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"
            title="{{ __('debug-notary::messages.report_bug') }}">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 32px; height: 32px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
    </button>

    <!-- Modal -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/50 debug-notary-ignore" x-cloak>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col" @click.away="closeModal()">
            <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-yellow-400">
                <h3 class="text-lg font-bold text-gray-900">{{ __('debug-notary::messages.debug_notary_report') }}</h3>
                <button @click="closeModal()" class="text-gray-700 dark:text-gray-900 hover:text-black text-2xl">&times;</button>
            </div>

            <div class="p-6 overflow-y-auto flex-1">
                <div x-show="!screenshotUrl" class="mb-6 p-10 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center bg-gray-50 dark:bg-gray-900/50">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('debug-notary::messages.insert_screenshot') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{!! __('debug-notary::messages.shortcut_instruction') !!}</p>
                </div>

                <div x-show="screenshotUrl">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('debug-notary::messages.your_note') }}</label>
                        <textarea x-model="note" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500" rows="3"
                                  placeholder="{{ __('debug-notary::messages.note_placeholder') }}"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('debug-notary::messages.tags_label') }}</label>
                        <input type="text" x-model="tags" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                               placeholder="{{ __('debug-notary::messages.tags_placeholder') }}">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('debug-notary::messages.attachment') }} (JSON/LOG/TXT)</label>
                        <input type="file" @change="attachmentFile = $event.target.files[0]; attachmentName = $event.target.files[0].name"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100">
                        <template x-if="attachmentFile">
                            <p class="mt-1 text-xs text-gray-500">Valgt: <span x-text="attachmentName"></span></p>
                        </template>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('debug-notary::messages.screenshot_preview') }}</p>
                        <div class="relative border rounded shadow-sm bg-gray-100 dark:bg-gray-900 mb-2">
                            <img x-ref="screenshotPreview" :src="annotatedImage || screenshotUrl" class="max-w-full h-auto block mx-auto">
                        </div>
                        <button type="button" @click="annotate" class="w-full py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            {{ __('debug-notary::messages.annotate_screenshot') }}
                        </button>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-2 rounded">
                        <p><strong>{{ __('debug-notary::messages.url') }}:</strong> <span x-text="metadata.url"></span></p>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t dark:border-gray-700 flex justify-end space-x-3 bg-gray-50 dark:bg-gray-900">
                <button @click="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">{{ __('debug-notary::messages.cancel') }}</button>
                <button @click="submitReport" :disabled="isSubmitting" class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white bg-yellow-500 rounded-md hover:bg-yellow-600 disabled:opacity-50 flex items-center">
                    <span x-show="isSubmitting" class="mr-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75"
                                                                                                                                                                                                             fill="currentColor"
                                                                                                                                                                                                             d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </span>
                    {{ __('debug-notary::messages.submit_report') }}
                </button>
            </div>
        </div>
    </div>

</div>
