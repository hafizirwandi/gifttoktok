<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GiftTokTok') }} — Preview Live</title>

        <link rel="icon" href="{{ asset('logo.webp') }}" type="image/webp">

        {{-- Sama persis dgn layouts/live.blade.php (biar preview akurat mewakili tampilan
             asli, termasuk font App\Enums\SeatFont) - TAPI file terpisah (bukan reuse
             layouts.live) krn di bawah ada <x-toast/> yang SENGAJA TIDAK boleh nempel di
             layouts.live (halaman Live asli = browser source OBS, toast popup akan
             kelihatan mengganggu di siaran kalau ada fitur lain yang notify() di sana). --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|poppins:400,600,700|montserrat:400,700,800|playfair-display:400,700|oswald:400,600|bebas-neue:400|pacifico:400&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-black text-white">
        {{ $slot }}

        <x-toast />
    </body>
</html>
