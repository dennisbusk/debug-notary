@php($layout = config('debug-notary.layout'))
@extends($layout ?: 'debug-notary::blank-layout')

@section('content')
    @if($layout)
        <livewire:bug-detail :bug-id="$id"/>
    @else
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Debug Notary - Detaljer</title>
            <script src="https://cdn.tailwindcss.com"></script>
            @livewireStyles
        </head>
        <body class="bg-gray-100 dark:bg-gray-900">
        <livewire:bug-detail :bug-id="$id"/>
        @livewireScripts
        </body>
        </html>
    @endif
@endsection
