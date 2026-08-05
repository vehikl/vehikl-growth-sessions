<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Apply the persisted light/dark theme before hydration to avoid a flash of the wrong theme. --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('gs-theme');
                    var theme = stored === 'light' || stored === 'dark'
                        ? stored
                        : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    }
                    document.documentElement.style.backgroundColor = theme === 'dark' ? '#14161c' : '#f9fafb';
                } catch (e) {}
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
