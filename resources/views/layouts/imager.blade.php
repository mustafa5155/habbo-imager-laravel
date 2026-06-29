<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @livewireStyles
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Habbo Imager')</title>
    <style>
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-card: #1e1e1e;
            --bg-surface: #252525;
            --text-primary: #e0e0e0;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent: #3b82f6;
            --accent-hover: #60a5fa;
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255, 255, 255, 0.16);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            overflow-x: hidden;
            color: var(--text-primary);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background: var(--bg-primary);
        }

        a { text-decoration: none; color: var(--accent); }
        a:hover { color: var(--accent-hover); }

        .app-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            border-bottom: 1px solid var(--border);
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 700;
        }

        .app-links {
            display: flex;
            gap: 8px;
        }

        .app-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 13px;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .app-link:hover {
            background: var(--bg-surface);
            color: var(--text-primary);
            border-color: var(--border-hover);
        }

        .app-main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 28px;
        }

        .app-footer {
            border-top: 1px solid var(--border);
            padding: 20px 28px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
        }

        @media (max-width: 640px) {
            .app-nav {
                flex-direction: column;
                gap: 10px;
                padding: 12px 16px;
            }
            .app-main { padding: 16px; }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="app-shell">
        <nav class="app-nav">
            <div class="app-brand">Habbo Imager</div>
            <div class="app-links">
                <a class="app-link" href="{{ route('imager') }}">Normal</a>
                <a class="app-link" href="{{ route('imager.advanced') }}">Advanced</a>
            </div>
        </nav>

        <main class="app-main">
            @yield('content')
        </main>

        <footer class="app-footer">
            Habbo Imager &mdash; Not affiliated with Sulake Corporation Oy.
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
