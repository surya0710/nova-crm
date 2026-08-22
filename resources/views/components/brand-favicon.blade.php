@php
    $logo = asset(ltrim((string) config('branding.logo', 'konnect-logo.png'), '/'));
@endphp
<link rel="icon" type="image/png" href="{{ $logo }}">
<link rel="apple-touch-icon" href="{{ $logo }}">
