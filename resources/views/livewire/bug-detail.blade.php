<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
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
     }">

    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('debug-notary.index') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('debug-notary::messages.back_to_list') }}
        </a>

        <div class="flex items-center gap-2">
            @if($bug->url)
                <a href="{{ $bug->url }}" target="_blank"
                   class="inline-flex items-center text-xs bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm font-bold">
                    <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    {{ __('debug-notary::messages.go_to_error_page') }}
                </a>
            @endif

            <button x-on:click="copyToClipboard(@js($bug->message))"
                    class="inline-flex items-center text-xs bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm font-medium">
                <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                {{ __('debug-notary::messages.copy_message') }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Venstre kolonne: Detaljer -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ __('debug-notary::messages.bug_details') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">ID: #{{ $bug->id }} • {{ $bug->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="p-6 space-y-6">
                    <div x-show="copied" x-transition class="fixed top-4 right-4 z-50 bg-indigo-600 text-white text-xs px-4 py-2 rounded-lg shadow-lg" style="display: none;">
                        {{ __('debug-notary::messages.copied') }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.severity') }}</span>
                            <div class="flex items-center">
                                <span class="flex-shrink-0 inline-block h-2 w-2 rounded-full mr-2
                                    {{ $bug->severity instanceof \Dennisbusk\DebugNotary\Enums\BugSeverity ? 'bg-'.$bug->severity->color().'-500' : (in_array($bug->severity, ['critical', 'alert', 'emergency']) ? 'bg-red-500' : (in_array($bug->severity, ['high', 'error']) ? 'bg-orange-500' : 'bg-blue-500')) }}"></span>
                                <div class="text-sm font-medium dark:text-gray-200">
                                    {{ $bug->severity instanceof \Dennisbusk\DebugNotary\Enums\BugSeverity ? $bug->severity->label() : __('debug-notary::messages.severity_' . $bug->severity) }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.status') }}</span>
                            <div class="text-sm font-medium dark:text-gray-200">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $bug->status === \Dennisbusk\DebugNotary\Enums\BugStatus::RESOLVED ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $bug->status instanceof \Dennisbusk\DebugNotary\Enums\BugStatus ? $bug->status->label() : __('debug-notary::messages.status_' . $bug->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.assigned_to') }}</span>
                            <div class="text-sm font-medium dark:text-gray-200">
                                {{ $bug->assignedTo->name ?? __('debug-notary::messages.nobody') }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.count') }}</span>
                            <div class="text-sm font-medium dark:text-gray-200">{{ number_format($bug->count) }}</div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.message') }}</span>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-sm break-words whitespace-pre-wrap dark:text-gray-300">
                            {{ $bug->message }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.location') }}</span>
                        <div class="text-sm dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <code>{{ $bug->file }}</code> : <code>{{ $bug->line }}</code>
                        </div>
                    </div>

                    @if($bug->stack_trace)
                        <div class="space-y-2" x-data="{ showTrace: false }">
                            <button x-on:click="showTrace = !showTrace" class="flex items-center text-xs text-indigo-600 hover:text-indigo-500 font-bold uppercase tracking-wider transition-colors">
                                <span x-show="!showTrace">{{ __('debug-notary::messages.show_stack_trace') }}</span>
                                <span x-show="showTrace">{{ __('debug-notary::messages.hide_stack_trace') }}</span>
                                <svg :class="{'rotate-180': showTrace}" class="ml-1 h-4 w-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="showTrace" class="mt-2 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-[10px] overflow-x-auto dark:text-gray-400" style="display: none;">
                                <pre>{{ $bug->stack_trace }}</pre>
                            </div>
                        </div>
                    @endif

                    @if($bug->screenshot || $bug->screenshot_path)
                        <div class="space-y-2">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.screenshot') }}</span>
                            <div class="mt-2">
                                @if($bug->screenshot)
                                    <img src="{{ $bug->screenshot }}"
                                         x-on:click="openFullscreen('{{ $bug->screenshot }}')"
                                         class="rounded-lg shadow-md max-w-full cursor-pointer hover:opacity-95 transition-all">
                                @elseif($bug->screenshot_path)
                                    @php $url = Storage::disk('public')->url($bug->screenshot_path); @endphp
                                    <img src="{{ $url }}"
                                         x-on:click="openFullscreen('{{ $url }}')"
                                         class="rounded-lg shadow-md max-w-full cursor-pointer hover:opacity-95 transition-all">
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($bug->browser_data)
                        <div class="space-y-2" x-data="{ showData: false }">
                            <button x-on:click="showData = !showData" class="flex items-center text-xs text-indigo-600 hover:text-indigo-500 font-bold uppercase tracking-wider transition-colors">
                                <span x-show="!showData">{{ __('debug-notary::messages.show_browser_data') }}</span>
                                <span x-show="showData">{{ __('debug-notary::messages.hide_browser_data') }}</span>
                                <svg :class="{'rotate-180': showData}" class="ml-1 h-4 w-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="showData" class="mt-2 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-[10px] dark:text-gray-400 overflow-x-auto" style="display: none;">
                                <pre>{{ json_encode($bug->browser_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-6">
                        <!-- Status Selector -->
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.change_status') }}</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['open', 'in_progress', 'pending', 'resolved', 'wont_fix'] as $st)
                                    <button wire:click="updateStatus('{{ $st }}')"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider transition-all duration-150
                                            {{ $bug->status === $st ? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        {{ __('debug-notary::messages.status_' . $st) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Assignee Selector -->
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">{{ __('debug-notary::messages.assign_to') }}</span>
                            <select wire:change="updateAssignee($event.target.value)"
                                    class="block w-48 pl-3 pr-10 py-1 text-[11px] border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md dark:bg-gray-800 dark:text-gray-300">
                                <option value="">{{ __('debug-notary::messages.nobody') }}</option>
                                @foreach($this->users as $u)
                                    <option value="{{ $u->id }}" {{ $bug->assigned_to_id == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button wire:click="deleteBug"
                            wire:confirm="{{ __('debug-notary::messages.confirm_delete_bug') }}"
                            class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md text-xs font-bold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-150 shadow-sm self-end">
                        <svg class="mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('debug-notary::messages.delete_bug') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Højre kolonne: Chat -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col h-[600px] lg:sticky lg:top-8">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ __('debug-notary::messages.messages') }}
                    </h3>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-white dark:bg-gray-800">
                    @forelse($bug->messages as $msg)
                        @if($msg->user_id === null)
                            <!-- System Message -->
                            <div class="flex justify-center my-2">
                                <div class="px-4 py-1 bg-gray-100 dark:bg-gray-900/50 rounded-full border border-gray-200 dark:border-gray-700">
                                    <span class="text-[10px] text-gray-500 italic">{{ $msg->message }}</span>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col {{ $msg->user_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                <div class="max-w-[85%] rounded-2xl px-4 py-2 {{ $msg->user_id === auth()->id() ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-gray-100 dark:bg-gray-700 dark:text-white rounded-tl-none shadow-sm' }}">
                                    @if($msg->message)
                                        <div class="text-sm prose prose-sm dark:prose-invert max-w-none prose-p:leading-relaxed prose-pre:bg-black/20">
                                            {!! \Illuminate\Support\Str::markdown($msg->message) !!}
                                        </div>
                                    @endif

                                    @if($msg->attachment_path)
                                        <div class="mt-2 pt-2 border-t {{ $msg->user_id === auth()->id() ? 'border-indigo-400' : 'border-gray-200 dark:border-gray-600' }}">
                                            @if(in_array(strtolower($msg->attachment_type), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                                <img src="{{ Storage::disk('public')->url($msg->attachment_path) }}"
                                                     x-on:click="openFullscreen('{{ Storage::disk('public')->url($msg->attachment_path) }}')"
                                                     class="rounded-lg max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity">
                                            @else
                                                <a href="{{ Storage::disk('public')->url($msg->attachment_path) }}" target="_blank"
                                                   class="flex items-center gap-2 text-xs {{ $msg->user_id === auth()->id() ? 'text-indigo-100 hover:text-white' : 'text-indigo-600 hover:text-indigo-500' }} font-bold transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    {{ basename($msg->attachment_path) }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center mt-1 gap-1.5 px-1">
                                    @if($msg->user_id !== auth()->id())
                                        <span class="text-[10px] text-gray-500 font-medium">{{ $msg->user->name ?? 'System' }}</span>
                                        <span class="text-[10px] text-gray-400">•</span>
                                    @endif
                                    <span class="text-[10px] text-gray-400">{{ $msg->created_at->diffForHumans() }}</span>

                                    @if($msg->user_id !== auth()->id() && config('debug-notary.impersonate.enabled', true))
                                        @php
                                            $canImpersonate = false;
                                            try {
                                                $canImpersonate = method_exists(auth()->user(), 'canImpersonate') && auth()->user()->canImpersonate();
                                            } catch (\Exception $e) {}
                                        @endphp

                                        @if($canImpersonate && $msg->user_id)
                                            <span class="text-[10px] text-gray-400">•</span>
                                            <a href="{{ config('debug-notary.impersonate.prefix', '/impersonate/take/') }}{{ $msg->user_id }}"
                                               class="text-[10px] text-indigo-600 hover:underline font-bold">
                                                {{ __('debug-notary::messages.impersonate_user', ['user' => $msg->user->name ?? '']) }}
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="flex flex-col items-center justify-center h-full text-center p-4">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('debug-notary::messages.no_messages_yet') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    @if($attachment)
                        <div class="mb-2 p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span class="text-[10px] text-indigo-700 dark:text-indigo-300 font-bold truncate max-w-[200px]">{{ $attachment->getClientOriginalName() }}</span>
                            </div>
                            <button type="button" wire:click="$set('attachment', null)" class="text-indigo-400 hover:text-indigo-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    <form wire:submit.prevent="sendMessage"
                          x-data="{
                              resize() {
                                  $refs.input.style.height = 'auto';
                                  $refs.input.style.height = $refs.input.scrollHeight + 'px';
                              }
                          }"
                          x-init="resize()"
                          x-on:submit="$nextTick(() => resize())"
                          class="relative flex items-end gap-2 p-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 focus-within:bg-white dark:focus-within:bg-gray-900 focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all duration-200 shadow-sm">

                        <!-- Attachment button -->
                        <div class="mb-0.5">
                            <label class="inline-flex items-center justify-center h-9 w-9 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 rounded-xl cursor-pointer transition-all active:scale-95">
                                <input type="file" wire:model="attachment" class="hidden">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </label>
                        </div>

                        <div class="flex-1">
                            <textarea wire:model="newMessage"
                                      x-ref="input"
                                      x-on:input="resize()"
                                      x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage().then(() => { resize(); }); }"
                                      rows="1"
                                      class="block w-full bg-transparent border-0 focus:ring-0 dark:text-white text-sm resize-none py-2 px-1 min-h-[40px] max-h-48"
                                      placeholder="{{ __('debug-notary::messages.write_message') }}"></textarea>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center h-9 w-9 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all shadow-sm shrink-0 active:scale-95 disabled:opacity-50 disabled:pointer-events-none mb-0.5"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage, attachment">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                    <div class="flex justify-between items-center mt-2 px-2">
                        <span class="text-[10px] text-gray-400">
                            {{ __('debug-notary::messages.press_enter_to_send') }}
                        </span>
                    </div>
                </div>
            </div>
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
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-90 p-4"
         x-on:click="showFullscreen = false"
         x-on:keydown.escape.window="showFullscreen = false"
         style="display: none;">
        <button class="absolute top-5 right-5 text-white hover:text-gray-300 transition-colors">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img :src="fullscreenImageUrl" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" x-on:click.stop>
    </div>
</div>
