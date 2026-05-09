<!DOCTYPE html>
<html lang="en" class="bg-zinc-900 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VBlog — Technical cybersecurity blog covering vulnerabilities, offensive tooling, malware analysis, and secure development practices.">
    <title>@yield('title', config('app.name')) — VBlog</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='80'>🔒</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-zinc-900 text-zinc-100">
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
