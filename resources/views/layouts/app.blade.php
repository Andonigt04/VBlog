<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body>
    @yield('title')

    @extends('layouts.header')

    @yield('content')

    @extends('layouts.footer')
</body>
</html>