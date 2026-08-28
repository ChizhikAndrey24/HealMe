<!DOCTYPE html>
<html lang="en" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'HealMe') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="bg-hm-bg text-hm-fg antialiased">
        <div id="app"></div>
        <script>
            (() => {
                try {
                    const saved = localStorage.getItem('healme-theme');
                    const theme = saved === 'light' || saved === 'dark' ? saved : 'dark';
                    document.documentElement.dataset.theme = theme;
                } catch (_) {
                    document.documentElement.dataset.theme = 'dark';
                }
            })();
        </script>
    </body>
</html>
