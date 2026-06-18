@component('mail::message')
    # Ny aktivitet på fejl #{{ $bug->id }}

    @if($activityType === 'new_message')
        **{{ $data['sender'] }}** har skrevet en besked:

        @component('mail::panel')
            {{ $data['message'] }}
        @endcomponent
    @elseif($activityType === 'assigned')
        Du er blevet tildelt denne fejl af **{{ $data['user'] }}**.
    @endif

    **Fejlbesked:**
    {{ $bug->message }}

    @component('mail::button', ['url' => route('debug-notary.show', $bug->id)])
        Se fejlen i dashboardet
    @endcomponent

    Tak,<br>
    {{ config('app.name') }}
@endcomponent
