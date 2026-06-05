@php($layout = config('debug-notary.layout'))
@if($layout)
@extends(config('debug-notary.layout'))
@section('content')
@else
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Notary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
@endif
<div class="mx-auto px-4 py-8" x-data="{
    selected: [],
    selectAll: false,
    allMatchingSelected: false,
    toggleAll() {
        if (this.selectAll) {
            this.selected = {{ json_encode($bugs->pluck('id')->toArray()) }};
        } else {
            this.selected = [];
            this.allMatchingSelected = false;
        }
    }
}" x-init="$watch('selected', value => {
    selectAll = (value.length > 0 && value.length === {{ count($bugs) }});
    if (value.length < {{ count($bugs) }}) allMatchingSelected = false;
})">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('debug-notary::messages.recorded_bugs') }}</h1>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <form action="{{ route('debug-notary.index') }}" method="GET" class="w-full flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-80">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('debug-notary::messages.search_bugs') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors"/>
            </div>

            <div class="w-full sm:w-48">
                <select name="tag" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">{{ __('debug-notary::messages.all_tags') }}</option>
                    @foreach($allTags as $t)
                        <option value="{{ $t }}" {{ $tag == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-40">
                <select name="severity" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">{{ __('debug-notary::messages.all_severities') }}</option>
                    @foreach($severities as $s)
                        <option value="{{ $s }}" {{ $severity == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-32">
                <select name="log_type" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">{{ __('debug-notary::messages.all_types') }}</option>
                    <option value="system" {{ $logType == 'system' ? 'selected' : '' }}>{{ __('debug-notary::messages.system') }}</option>
                    <option value="notary" {{ $logType == 'notary' ? 'selected' : '' }}>{{ __('debug-notary::messages.notary') }}</option>
                    <option value="javascript" {{ $logType == 'javascript' ? 'selected' : '' }}>{{ __('debug-notary::messages.javascript') }}</option>
                </select>
            </div>

            <div class="w-full sm:w-40">
                <select name="status" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">{{ __('debug-notary::messages.all_statuses') }}</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ $status == $st ? 'selected' : '' }}>{{ __('debug-notary::messages.status_'.$st) }}</option>
                    @endforeach
                </select>
            </div>

            @if($search || $tag || $severity || $logType || $status)
                <a href="{{ route('debug-notary.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center whitespace-nowrap">{{ __('debug-notary::messages.clear_filters') }}</a>
            @endif
        </form>
    </div>

    <div x-show="selected.length > 0 || allMatchingSelected" class="mb-4" x-cloak>
        <div x-show="selectAll && !allMatchingSelected && {{ $bugs->total() }} > {{ count($bugs) }}" class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-700 text-center" x-cloak>
            {{ __('debug-notary::messages.all_on_page_selected', ['count' => count($bugs)]) }}
            <button type="button" @click="allMatchingSelected = true" class="font-bold underline ml-1 hover:text-indigo-900">
                {{ __('debug-notary::messages.select_all_matching', ['total' => $bugs->total()]) }}
            </button>
        </div>

        <div x-show="allMatchingSelected" class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-700 text-center" x-cloak>
            {{ __('debug-notary::messages.all_matching_selected', ['total' => $bugs->total()]) }}
            <button type="button" @click="selected = []; selectAll = false; allMatchingSelected = false" class="font-bold underline ml-1 hover:text-indigo-900">
                {{ __('debug-notary::messages.clear_selection') }}
            </button>
        </div>

        <form action="{{ route('debug-notary.bulk-destroy') }}" method="POST"
              @submit.prevent="if(confirm('{{ __('debug-notary::messages.confirm_delete', ['count' => 'COUNT_PLACEHOLDER']) }}'.replace('COUNT_PLACEHOLDER', allMatchingSelected ? '{{ $bugs->total() }}' : selected.length))) $el.submit()">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <input type="hidden" name="delete_all" :value="allMatchingSelected">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="tag" value="{{ $tag }}">

            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <span x-show="allMatchingSelected">{{ __('debug-notary::messages.delete_all_matching', ['total' => $bugs->total()]) }}</span>
                <span x-show="!allMatchingSelected">{!! str_replace(':count', '<span x-text="selected.length"></span>', __('debug-notary::messages.delete_selected', ['count' => ':count'])) !!}</span>
            </button>
        </form>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">
                    <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.type') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.status') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.trend') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.last_seen') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.severity') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.count') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.message') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.tags') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.file_line') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.user') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('debug-notary::messages.markdown_role') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($bugs as $bug)
                <tr x-data="{ open: false }" @click="open = true" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer">
                    <td class="px-4 py-3 text-sm" @click.stop>
                        <input type="checkbox" value="{{ $bug->id }}" x-model="selected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $bug->log_type === 'notary' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ __('debug-notary::messages.' . $bug->log_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm" @click.stop x-data="{
                        currentStatus: '{{ $bug->status }}',
                        updateStatus(newStatus) {
                            fetch('{{ route('debug-notary.update-status', $bug->id) }}', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ status: newStatus })
                            }).then(res => {
                                if(res.ok) this.currentStatus = newStatus;
                            });
                        }
                    }">
                        <select @change="updateStatus($event.target.value)" :class="{
                            'bg-gray-100 text-gray-800': currentStatus === 'open',
                            'bg-blue-100 text-blue-800': currentStatus === 'in_progress',
                            'bg-green-100 text-green-800': currentStatus === 'resolved'
                        }" class="text-xs font-medium rounded-full px-2 py-1 border-none focus:ring-0 cursor-pointer">
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" :selected="currentStatus === '{{ $st }}'">{{ __('debug-notary::messages.status_'.$st) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-end space-x-0.5 h-6">
                            @php
                                $trend = $bug->trend_data ?? [];
                                $maxCount = count($trend) > 0 ? max($trend) : 1;
                                $last7Days = [];
                                for($i = 6; $i >= 0; $i--) {
                                    $date = now()->subDays($i)->format('Y-m-d');
                                    $last7Days[$date] = $trend[$date] ?? 0;
                                }
                            @endphp
                            @foreach($last7Days as $date => $count)
                                @php
                                    $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                    $color = $count > 0 ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-700';
                                @endphp
                                <div class="w-2 {{ $color }} rounded-t-sm" style="height: {{ max(2, $height) }}%" title="{{ $date }}: {{ $count }}"></div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300 whitespace-nowrap">
                        {{ $bug->last_seen_at ? $bug->last_seen_at->format('d/m/Y H:i:s') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $bug->severity === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                            {{ $bug->severity === 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                            {{ $bug->severity === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                            {{ $bug->severity === 'low' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $bug->severity === 'info' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        ">
                            {{ ucfirst($bug->severity) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                        {{ $bug->count }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $bug->message }}">
                        {{ $bug->message }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($bug->tags)
                            @foreach($bug->tags as $t)
                                <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px]">{{ $t }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">
                        {{ basename($bug->file) }}:{{ $bug->line }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                        {{ $bug->user ? $bug->user->name : __('debug-notary::messages.guest') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">
                        {{ $bug->user_role ? $bug->user_role : '—' }}

                        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-cloak @click.stop>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-auto shadow-xl text-left" @click.stop>
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('debug-notary::messages.details') }}</h3>
                                    <div class="flex items-center space-x-4">
                                        <button
                                            x-data="{ copied: false }"
                                            @click.stop="
                                                navigator.clipboard.writeText({{ json_encode($bug->message) }});
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="text-indigo-600 hover:underline text-sm font-medium flex items-center space-x-1"
                                            title="{{ __('debug-notary::messages.copy_error_only_title') }}"
                                        >
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                            </svg>
                                            <svg x-show="copied" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span x-text="copied ? '{{ __('debug-notary::messages.copied') }}' : '{{ __('debug-notary::messages.copy_to_clipboard') }}'"></span>
                                        </button>

                                        <button
                                            x-data="{ copied: false }"
                                            @click.stop="
                                                const text = {{ json_encode(
                                                    __('debug-notary::messages.markdown_report_title') . "\n" .
                                                    __('debug-notary::messages.markdown_report_intro') . "\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_error_message') . ":**\n> " . $bug->message . "\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_location') . ":**\n" .
                                                    "- **" . __('debug-notary::messages.markdown_file') . ":** `" . $bug->file . "`\n" .
                                                    "- **" . __('debug-notary::messages.markdown_line') . ":** `" . $bug->line . "`\n" .
                                                    "- **" . __('debug-notary::messages.markdown_url') . ":** " . ($bug->url ?? __('debug-notary::messages.not_provided')) . "\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_context') . ":**\n" .
                                                    "- **" . __('debug-notary::messages.markdown_severity') . ":** " . ucfirst($bug->severity) . "\n" .
                                                    "- **" . __('debug-notary::messages.markdown_count') . ":** " . $bug->count . "\n" .
                                                    "- **" . __('debug-notary::messages.markdown_last_seen') . ":** " . ($bug->last_seen_at ? $bug->last_seen_at->format('d/m/Y H:i:s') : '—') . "\n" .
                                                    "- **" . __('debug-notary::messages.markdown_user') . ":** " . ($bug->user ? $bug->user->name : __('debug-notary::messages.guest')) . " (" . ($bug->user ? $bug->user->email : 'N/A') . ")\n" .
                                                    "- **" . __('debug-notary::messages.markdown_role') . ":** " . ($bug->user_role ?? '—') . "\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_user_note') . ":**\n> " . ($bug->user_note ?? __('debug-notary::messages.markdown_no_note')) . "\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_system_info') . ":**\n" .
                                                    "- **" . __('debug-notary::messages.markdown_tags') . ":** " . ($bug->tags ? implode(', ', $bug->tags) : __('debug-notary::messages.markdown_none')) . "\n" .
                                                    "- **" . __('debug-notary::messages.markdown_browser_data') . ":** `" . json_encode($bug->browser_data) . "`\n\n" .
                                                    "**" . __('debug-notary::messages.markdown_stack_trace') . ":**\n" .
                                                    "```text\n" . $bug->stack_trace . "\n```"
                                                ) }};
                                                navigator.clipboard.writeText(text);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="text-indigo-600 hover:underline text-sm font-medium flex items-center space-x-1"
                                            title="{{ __('debug-notary::messages.copy_markdown_report_title') }}"
                                        >
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                            </svg>
                                            <svg x-show="copied" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span x-text="copied ? '{{ __('debug-notary::messages.copied') }}' : '{{ __('debug-notary::messages.copy_llm_format') }}'"></span>
                                        </button>
                                        <form action="{{ route('debug-notary.destroy', $bug->id) }}" method="POST" onsubmit="return confirm('{{ __('debug-notary::messages.are_you_sure') }}');" @click.stop>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm font-medium">
                                                {{ __('debug-notary::messages.delete') }}
                                            </button>
                                        </form>
                                        <button @click="open = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200 text-2xl">&times;</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.message') }}:</p>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $bug->message }}</p>
                                    </div>
                                    @if($bug->url)
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.url') }}:</p>
                                            <p class="text-sm text-gray-900 dark:text-gray-100"><a href="{{ $bug->url }}" target="_blank" class="text-indigo-600 hover:underline">{{ $bug->url }}</a></p>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.markdown_file') }}:</p>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $bug->file }}:{{ $bug->line }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.user') }}:</p>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $bug->user ? $bug->user->name : __('debug-notary::messages.guest') }} ({{ $bug->user ? $bug->user->email : 'N/A' }})</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.markdown_role') }}:</p>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $bug->user_role ? $bug->user_role : '—' }}</p>
                                    </div>
                                    @if($bug->tags)
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.tags') }}:</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($bug->tags as $t)
                                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded text-xs">{{ $t }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('debug-notary::messages.last_seen') }}:</p>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $bug->last_seen_at ? $bug->last_seen_at->format('d/m/Y H:i:s') : '—' }}</p>
                                    </div>
                                </div>

                                @if($bug->user_note)
                                    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-400 mb-1">{{ __('debug-notary::messages.user_note') }}:</p>
                                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $bug->user_note }}</p>
                                    </div>
                                @endif

                                @if($bug->screenshot || $bug->screenshot_path)
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('debug-notary::messages.screenshot') }}:</p>
                                        @if($bug->screenshot)
                                            <a href="{{ $bug->screenshot }}" target="_blank">
                                                <img src="{{ $bug->screenshot }}" class="w-full border rounded-lg shadow-sm hover:opacity-95 transition-opacity">
                                            </a>
                                        @else
                                            <a href="{{ asset('storage/' . $bug->screenshot_path) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $bug->screenshot_path) }}" class="w-full border rounded-lg shadow-sm hover:opacity-95 transition-opacity">
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                @if($bug->log_type === 'system')
                                    <div x-data="{ accordionOpen: false }" class="mt-4">
                                        <button @click.stop="accordionOpen = !accordionOpen"
                                                class="flex items-center justify-between w-full p-3 text-sm font-semibold text-left text-gray-700 bg-gray-100 rounded-lg dark:bg-gray-900 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                                            <span>Stack Trace</span>
                                            <svg :class="{'rotate-180': accordionOpen}" class="w-4 h-4 transition-transform transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div x-show="accordionOpen" x-cloak class="mt-2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100">
                                            <pre
                                                class="text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-black p-4 rounded-lg whitespace-pre-wrap overflow-x-auto border border-gray-200 dark:border-gray-700 shadow-inner">{{ $bug->stack_trace }}</pre>
                                        </div>
                                    </div>
                                @endif

                                @if($bug->browser_data)
                                    <div x-data="{ browserOpen: false }" class="mt-4">
                                        <button @click.stop="browserOpen = !browserOpen"
                                                class="flex items-center justify-between w-full p-3 text-sm font-semibold text-left text-gray-700 bg-gray-100 rounded-lg dark:bg-gray-900 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                                            <span>{{ __('debug-notary::messages.browser_metadata') }}</span>
                                            <svg :class="{'rotate-180': browserOpen}" class="w-4 h-4 transition-transform transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div x-show="browserOpen" x-cloak class="mt-2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100">
                                            <pre
                                                class="text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-black p-4 rounded-lg whitespace-pre-wrap overflow-x-auto border border-gray-200 dark:border-gray-700 shadow-inner">{{ json_encode($bug->browser_data, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-300">{{ __('debug-notary::messages.no_bugs_found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $bugs->appends([
            'search' => $search,
            'tag' => $tag,
            'severity' => $severity,
            'log_type' => $logType,
            'status' => $status
        ])->links() }}
    </div>
</div>
@if($layout)
    @endsection
@else
</body>
</html>
@endif
