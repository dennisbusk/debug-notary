@component('mail::message')
    # Ny fejl registreret

    En ny fejl er blevet logget af Debug Notary.

    **Besked:** {{ $bug->message }}

    **Detaljer:**
    - **Fil:** {{ $bug->file }}
    - **Linje:** {{ $bug->line }}
    - **Alvorlighed:** {{ ucfirst($bug->severity) }}
    - **Log Type:** {{ $bug->log_type }}

    @if($bug->url)
        **URL:** [{{ $bug->url }}]({{ $bug->url }})
    @endif

    @component('mail::button', ['url' => route('debug-notary.index')])
        Se alle fejl i Debug Notary
    @endcomponent

    Tak,<br>
    {{ config('app.name') }}
@endcomponent
