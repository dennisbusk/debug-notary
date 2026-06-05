<div wire:key="bug-modal-container"
     x-data="{
        isOpen: @entangle('isOpen'),
        copied: false,
        showFullscreen: false,
        fullscreenImageUrl: '',
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        openFullscreen(url) {
            this.fullscreenImageUrl = url;
            this.showFullscreen = true;
        }
     }"
     x-show="isOpen"
     x-on:keydown.escape.window="isOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div wire:key="bug-modal-panel-{{ $bug->id ?? 'none' }}"
             x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full z-10"
             x-on:click.away="isOpen = false">

            @if($bug)
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">
                            {{ __('debug-notary::messages.bug_details') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">ID: #{{ $bug->id }} • {{ $bug->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button x-on:click="copyToClipboard(@js($bug->message))"
                                class="inline-flex items-center text-xs bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm font-medium">
                            <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            {{ __('debug-notary::messages.copy_message') }}
                        </button>

                        @php
                            $markdown = implode("\n", [
                                "**Error:**",
                                "> " . $bug->message,
                                '',
                                "**File:** `{$bug->file}`",
                                "**Line:** {$bug->line}",
                                "**" . __('debug-notary::messages.severity') . ":** " . __('debug-notary::messages.severity_' . $bug->severity),
                                "**" . __('debug-notary::messages.count') . ":** {$bug->count}",
                                "**" . __('debug-notary::messages.last_seen') . ":** " . ($bug->last_seen_at ? $bug->last_seen_at->format('Y-m-d H:i') : 'N/A'),
                            ]);
                        @endphp
                        <button x-on:click="copyToClipboard(@js($markdown))"
                                class="inline-flex items-center text-xs bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm font-medium">
                            <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('debug-notary::messages.copy_markdown') }}
                        </button>

                        <button x-on:click="isOpen = false" class="ml-2 text-gray-400 hover:text-gray-500 transition-colors">
                            <span class="sr-only">{{ __('debug-notary::messages.close') }}</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div x-show="copied" x-transition class="bg-indigo-600 text-white text-xs px-3 py-1 rounded-full absolute top-2 right-2">
                        {{ __('debug-notary::messages.copied') }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.severity') }}</span>
                            <div class="text-sm">{{ __('debug-notary::messages.severity_' . $bug->severity) }}</div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.status') }}</span>
                            <div class="text-sm">{{ __('debug-notary::messages.status_' . $bug->status) }}</div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.count') }}</span>
                            <div class="text-sm">{{ number_format($bug->count) }}</div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.message') }}</span>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 font-mono text-sm break-words whitespace-pre-wrap dark:text-gray-300">
                            {{ $bug->message }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.location') }}</span>
                        <div class="text-sm dark:text-gray-300">
                            <code>{{ $bug->file }}</code> : <code>{{ $bug->line }}</code>
                        </div>
                    </div>

                    @if($bug->stack_trace)
                        <div class="space-y-1" x-data="{ showTrace: false }">
                            <button x-on:click="showTrace = !showTrace" class="text-xs text-indigo-600 hover:underline font-semibold uppercase">
                                <span x-show="!showTrace">{{ __('debug-notary::messages.show_stack_trace') }}</span>
                                <span x-show="showTrace">{{ __('debug-notary::messages.hide_stack_trace') }}</span>
                            </button>
                            <div x-show="showTrace" class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 font-mono text-[10px] overflow-x-auto dark:text-gray-400">
                                <pre>{{ $bug->stack_trace }}</pre>
                            </div>
                        </div>
                    @endif

                    @if($bug->screenshot || $bug->screenshot_path)
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-semibold">{{ __('debug-notary::messages.screenshot') }}</span>
                            <div class="mt-2">
                                @if($bug->screenshot)
                                    <img src="{{ $bug->screenshot }}"
                                         x-on:click="openFullscreen('{{ $bug->screenshot }}')"
                                         class="rounded shadow-lg max-w-full cursor-pointer hover:opacity-90 transition-opacity">
                                @elseif($bug->screenshot_path)
                                    @php $url = Storage::disk('public')->url($bug->screenshot_path); @endphp
                                    <img src="{{ $url }}"
                                         x-on:click="openFullscreen('{{ $url }}')"
                                         class="rounded shadow-lg max-w-full cursor-pointer hover:opacity-90 transition-opacity">
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($bug->browser_data)
                        <div class="space-y-1" x-data="{ showData: false }">
                            <button x-on:click="showData = !showData" class="text-xs text-indigo-600 hover:underline font-semibold uppercase">
                                <span x-show="!showData">{{ __('debug-notary::messages.show_browser_data') }}</span>
                                <span x-show="showData">{{ __('debug-notary::messages.hide_browser_data') }}</span>
                            </button>
                            <div x-show="showData" class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 font-mono text-[10px] dark:text-gray-400">
                                <pre>{{ json_encode($bug->browser_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div class="flex gap-2">
                        @foreach(['open', 'in_progress', 'resolved'] as $st)
                            <button wire:click="updateStatus('{{ $st }}')"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold shadow-sm transition-all duration-150
                                    {{ $bug->status === $st ? 'bg-indigo-600 text-white ring-1 ring-inset ring-indigo-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                                @if($bug->status === $st)
                                    <svg class="mr-1.5 h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                {{ __('debug-notary::messages.status_' . $st) }}
                            </button>
                        @endforeach
                    </div>

                    <button wire:click="deleteBug"
                            wire:confirm="{{ __('debug-notary::messages.confirm_delete_bug') }}"
                            class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-bold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-150 shadow-sm">
                        <svg class="mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('debug-notary::messages.delete_bug') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Fullscreen Image Modal -->
    <div x-show="showFullscreen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-90"
         x-on:click="showFullscreen = false"
         x-on:keydown.escape.window="showFullscreen = false"
         style="display: none;">
        <button class="absolute top-5 right-5 text-white hover:text-gray-300 transition-colors">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img :src="fullscreenImageUrl" class="w-full h-full object-contain" x-on:click.stop>
    </div>
</div>
