<div>
    @if(count($selected) > 0 || $allMatchingSelected)
        <div class="mb-4 space-y-3">
            @if(!$allMatchingSelected && count($selected) > 0)
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm text-indigo-700 dark:text-indigo-300 text-center">
                    {{ __('debug-notary::messages.all_on_page_selected', ['count' => count($selected)]) }}
                    <button type="button"
                            wire:click="$parent.selectAllMatching"
                            class="font-bold underline ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                        {{ __('debug-notary::messages.select_all_matching', ['total' => '']) }}
                    </button>
                </div>
            @endif

            @if($allMatchingSelected)
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm text-indigo-700 dark:text-indigo-300 text-center">
                    {{ __('debug-notary::messages.all_matching_selected', ['total' => '']) }}
                    <button type="button"
                            wire:click="$parent.clearSelection"
                            class="font-bold underline ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                        {{ __('debug-notary::messages.clear_selection') }}
                    </button>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="deleteSelected"
                        wire:confirm="{{ __('debug-notary::messages.confirm_delete', ['count' => $allMatchingSelected ? 'all' : count($selected)]) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-150">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    @if($allMatchingSelected)
                        {{ __('debug-notary::messages.delete_all_matching', ['total' => '']) }}
                    @else
                        {{ __('debug-notary::messages.delete_selected', ['count' => count($selected)]) }}
                    @endif
                </button>
            </div>
        </div>
    @endif
</div>
