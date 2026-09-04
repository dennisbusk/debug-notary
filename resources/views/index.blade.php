@php($layout = config('debug-notary.layout'))
@extends($layout ?: 'debug-notary::blank-layout')

@section('content')
    @if($layout)
        <livewire:bug-table/>
    @else
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Debug Notary</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    darkMode: 'class',
                }
                if ((window.parent && window.parent !== window && window.parent.document.documentElement.classList.contains('dark')) ||
                    localStorage.getItem('nova.theme') === 'dark' ||
                    localStorage.getItem('theme') === 'dark' ||
                    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            </script>
            @livewireStyles
        </head>
        <body class="bg-gray-100 dark:bg-gray-900">
        <livewire:bug-table/>
        @livewireScripts
        </body>
        </html>
    @endif
@endsection
