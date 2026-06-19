<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150 ease-in-out">
    <td class="px-6 py-4 whitespace-nowrap">
        <input type="checkbox"
               wire:model.live="$parent.selected"
               value="{{ $bug->id }}"
               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
    </td>

    @if($columns['type'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                {{ __('debug-notary::messages.' . $bug->log_type) }}
            </span>
        </td>
    @endif

    @if($columns['status'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-sm">
            <select wire:change="updateStatus($event.target.value)"
                    class="block w-full pl-2 pr-8 py-1 text-xs border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 rounded-md bg-white dark:bg-gray-700 dark:text-white transition duration-150 ease-in-out">
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ $bug->status === $st ? 'selected' : '' }}>
                        {{ $st->label() }}
                    </option>
                @endforeach
            </select>
        </td>
    @endif

    @if($columns['trend'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap">
            @php
                $trend = $bug->trend_data ?? [];
                $max = count($trend) ? max($trend) : 1;
            @endphp
            <div class="flex gap-1 h-6 items-end">
                @foreach(range(0, 6) as $i)
                    @php
                        $date = now()->subDays(6 - $i)->format('Y-m-d');
                        $val = $trend[$date] ?? 0;
                        $height = ($val / $max) * 100;
                    @endphp
                    <div class="w-1.5 bg-indigo-400 dark:bg-indigo-500 rounded-t-sm" style="height: {{ max(10, $height) }}%" title="{{ $date }}: {{ $val }}"></div>
                @endforeach
            </div>
        </td>
    @endif

    @if($columns['last_seen'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-medium">
            {{ $bug->last_seen_at ? $bug->last_seen_at->format('d/m H:i') : '—' }}
        </td>
    @endif

    @if($columns['severity'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-sm">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                {{ match($bug->severity->value ?? $bug->severity) {
                    'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800',
                    'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200 dark:border-orange-800',
                    'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800',
                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800'
                } }}">
                {{ $bug->severity instanceof \Dennisbusk\DebugNotary\Enums\BugSeverity ? $bug->severity->label() : __('debug-notary::messages.severity_' . $bug->severity) }}
            </span>
        </td>
    @endif

    @if($columns['count'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
            {{ number_format($bug->count) }}
        </td>
    @endif

    @if($columns['message'] ?? true)
        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $bug->message }}">
            {{ $bug->message }}
        </td>
    @endif

    @if($columns['tags'] ?? true)
        <td class="px-6 py-4">
            <div class="flex flex-wrap gap-1">
                @foreach($bug->tags ?? [] as $t)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                        {{ $t }}
                    </span>
                @endforeach
            </div>
        </td>
    @endif

    @if($columns['file_line'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]">
            <span class="font-mono">{{ basename($bug->file) }}:{{ $bug->line }}</span>
        </td>
    @endif

    @if($columns['user'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-medium">
            {{ $bug->user->name ?? __('debug-notary::messages.guest') }}
        </td>
    @endif

    @if($columns['role'] ?? true)
        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
            {{ $bug->user_role ?? '—' }}
        </td>
    @endif

    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <a href="{{ route('debug-notary.show', $bug->id) }}"
           class="inline-flex items-center text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150 relative">
            <span>{{ __('debug-notary::messages.view') }}</span>
            @if($bug->messages()->where('is_read', false)->where('user_id', '!=', auth()->id())->exists())
                <span class="absolute -top-1 -right-1 flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
            @endif
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="9 5l7 7-7 7"/>
            </svg>
        </a>
    </td>
</tr>
