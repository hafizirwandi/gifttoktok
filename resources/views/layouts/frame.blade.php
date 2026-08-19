<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GiftTokTok') }} — Frame Host</title>

        <link rel="icon" href="{{ asset('logo.webp') }}" type="image/webp">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Background transparan (bukan bg-black seperti layouts.live) — halaman ini
             dipakai sebagai OBS Browser Source terpisah, cuma buat nampilin frame
             border-nya; area di luar/di dalam border harus tembus pandang. --}}
        <style>html, body { background: transparent !important; }</style>
    </head>
    <body class="font-sans antialiased text-white">
        {{ $slot }}
    </body>
</html>
