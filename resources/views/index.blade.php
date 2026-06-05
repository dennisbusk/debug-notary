@php($layout = config('debug-notary.layout'))

@if($layout)
    @extends($layout)
    @section('content')
        <livewire:bug-table/>
    @endsection
@else
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Debug Notary</title>
        <script src="https://cdn.tailwindcss.com"></script>
        @livewireStyles
    </head>
    <body class="bg-gray-100 dark:bg-gray-900">
    <livewire:bug-table/>
    @livewireScripts
    </body>
    </html>
@endif
