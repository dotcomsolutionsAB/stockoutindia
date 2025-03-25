<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Stockout')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Include Bootstrap CSS or your own stylesheet -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    @include('partials.navbar') <!-- Optional: Include your navbar -->

    <main>
        @yield('content')
    </main>

    <!-- Include Bootstrap JS or other scripts if needed -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
