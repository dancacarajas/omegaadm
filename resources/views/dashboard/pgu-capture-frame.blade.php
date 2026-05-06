<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PGU Capture</title>
    @vite(['resources/css/app.css'])
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 1366px;
            height: 768px;
            overflow: hidden;
            background: #ffffff;
        }

        body > .capture-root {
            width: 1366px;
            height: 768px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="capture-root">
        <div class="{{ $captureWrapperClass ?? '' }}">
            @include($capturePartialView, $captureData ?? [])
        </div>
    </div>
</body>
</html>

