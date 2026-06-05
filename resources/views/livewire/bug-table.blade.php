<div class="mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                {{ __('debug-notary::messages.recorded_bugs') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('debug-notary::messages.manage_and_track_bugs') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[240px]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="{{ __('debug-notary::messages.search_placeholder') }}"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out">
            </div>

            <select wire:model.live="status"
                    class="block pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('debug-notary::messages.all_statuses') }}</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}">{{ __('debug-notary::messages.status_' . $s) }}</option>
                @endforeach
            </select>

            <select wire:model.live="logType"
                    class="block pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('debug-notary::messages.all_types') }}</option>
                <option value="system">{{ __('debug-notary::messages.system') }}</option>
                <option value="notary">{{ __('debug-notary::messages.notary') }}</option>
                <option value="javascript">{{ __('debug-notary::messages.javascript') }}</option>
            </select>

            <select wire:model.live="severity"
                    class="block pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('debug-notary::messages.all_severities') }}</option>
                @foreach($severities as $sev)
                    <option value="{{ $sev }}">{{ __('debug-notary::messages.severity_' . $sev) }}</option>
                @endforeach
            </select>

            <select wire:model.live="tag"
                    class="block pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('debug-notary::messages.all_tags') }}</option>
                @foreach($allTags as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>

            @if($search || $status || $severity || $tag || $logType)
                <button wire:click="resetFilters"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition duration-150 ease-in-out">
                    {{ __('debug-notary::messages.clear_filters') }}
                </button>
            @endif
        </div>
    </div>

    @if(session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <livewire:notary-bulk-actions/>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-xl rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
                <th class="px-6 py-4 text-left">
                    <input type="checkbox"
                           wire:model.live="selectAll"
                           wire:click="toggleSelectAllPage"
                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.type') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.status') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.trend') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.last') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.severity') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.count') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.message') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.tag') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.file_line') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.user') }}</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('debug-notary::messages.role') }}</th>
                <th class="px-6 py-4"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($bugs as $bug)
                <livewire:bug-row :bug="$bug" :selected="in_array($bug->id, $selected)" :key="'bug-row-'.$bug->id"/>
            @empty
                <tr>
                    <td colspan="13" class="p-6 text-center text-gray-500">
                        {{ __('debug-notary::messages.no_bugs_found') }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $bugs->links() }}
    </div>

    <livewire:bug-modal/>
</div>
