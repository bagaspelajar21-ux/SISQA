<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#060e22">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SISQA Pro">
    <title>SISQA — Software Quality Assurance System</title>
    <link rel="icon" type="image/jpeg" href="/images/sisqa-logo.jpg">
    <link rel="apple-touch-icon" href="/images/sisqa-logo.jpg">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
    @endphp
    @if (is_array($manifest))
        @if (!empty($manifest['resources/css/app.css']['file']))
            <link rel="stylesheet" href="/build/{{ $manifest['resources/css/app.css']['file'] }}">
        @endif
        @if (!empty($manifest['resources/js/app.js']['css']))
            @foreach ($manifest['resources/js/app.js']['css'] as $cssFile)
                <link rel="stylesheet" href="/build/{{ $cssFile }}">
            @endforeach
        @endif
        @if (!empty($manifest['resources/js/app.js']['file']))
            <script type="module" src="/build/{{ $manifest['resources/js/app.js']['file'] }}"></script>
        @endif
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#F8FAFC] text-[#111827] font-sans antialiased selection:bg-blue-600 selection:text-white min-h-screen">
    <div id="app"></div>
</body>
</html>
