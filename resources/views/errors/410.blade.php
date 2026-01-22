<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name') }} · Link expired</title>
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
            rel="stylesheet"
        />
        <style>
            :root {
                color-scheme: light dark;
            }
            body {
                margin: 0;
                font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
                background: radial-gradient(circle at top, #eff6ff, #ffffff 55%);
                color: #0f172a;
            }
            @media (prefers-color-scheme: dark) {
                body {
                    background: radial-gradient(circle at top, #0b2540, #020617 55%);
                    color: #e2e8f0;
                }
            }
            .wrap {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2.5rem 1.5rem;
            }
            .card {
                max-width: 38rem;
                width: 100%;
                background: rgba(255, 255, 255, 0.92);
                border-radius: 1.25rem;
                padding: 2rem;
                border: 1px solid rgba(148, 163, 184, 0.3);
                box-shadow: 0 24px 64px rgba(15, 23, 42, 0.08);
            }
            @media (prefers-color-scheme: dark) {
                .card {
                    background: rgba(15, 23, 42, 0.9);
                    border-color: rgba(51, 65, 85, 0.6);
                    box-shadow: 0 24px 64px rgba(2, 6, 23, 0.5);
                }
            }
            .badge {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.35rem 0.75rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
                color: #1d4ed8;
                background: rgba(59, 130, 246, 0.12);
            }
            @media (prefers-color-scheme: dark) {
                .badge {
                    color: #93c5fd;
                    background: rgba(59, 130, 246, 0.2);
                }
            }
            h1 {
                margin: 1rem 0 0.75rem;
                font-size: 2rem;
                line-height: 1.1;
            }
            p {
                margin: 0;
                font-size: 1rem;
                color: inherit;
                opacity: 0.85;
            }
            a {
                display: inline-flex;
                align-items: center;
                margin-top: 1.5rem;
                text-decoration: none;
                font-weight: 600;
                color: #0f172a;
            }
            @media (prefers-color-scheme: dark) {
                a {
                    color: #e2e8f0;
                }
            }
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="card">
                <span class="badge">410 · Link expired</span>
                <h1>This short link is no longer available.</h1>
                <p>
                    The link you tried has expired or was deleted by its owner.
                    Double-check the URL or create a fresh one.
                </p>
                <a href="{{ route('home') }}">Create a new link →</a>
            </div>
        </div>
    </body>
</html>
