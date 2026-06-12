<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Formulaire' }}</title>

    <style>
        :root {
            --ff-bg: #{{ $bg ?? 'ffffff' }};
            --ff-color: #{{ $color ?? '1a1a1a' }};
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            overflow: hidden; /* masque la scrollbar sans contraindre le layout */
        }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--ff-bg);
            color: var(--ff-color);
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 1rem;
            line-height: 1.5;
        }

        body { padding: 1rem; }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid color-mix(in srgb, var(--ff-color) 30%, transparent);
            border-radius: 0.375rem;
            background: color-mix(in srgb, var(--ff-bg) 95%, var(--ff-color));
            color: var(--ff-color);
            font-size: 0.9375rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, textarea:focus, select:focus {
            border-color: color-mix(in srgb, var(--ff-color) 60%, transparent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--ff-color) 10%, transparent);
        }

        .fi-fo-field-wrp { margin-bottom: 1rem; }

        .fi-fo-field-wrp-error-message {
            color: #dc2626;
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        button[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.5rem;
            background: var(--ff-color);
            color: var(--ff-bg);
            border: none;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
            margin-top: 0.5rem;
        }

        button[type="submit"]:hover { opacity: 0.85; }
        button[type="submit"]:disabled { opacity: 0.5; cursor: not-allowed; }

        .ff-success {
            padding: 1rem;
            border-radius: 0.5rem;
            background: color-mix(in srgb, var(--ff-color) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--ff-color) 20%, transparent);
            text-align: center;
        }

        .ff-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
    </style>

    @filamentStyles
    @livewireStyles
</head>
<body>

    <div id="ff-content">{{ $slot }}</div>

    @filamentScripts
    @livewireScripts

    {{-- Redimensionnement automatique de l'iframe parente --}}
    <script>
        // ancestorOrigins disponible sur Chromium/Safari, pas Firefox → fallback '*'
        const targetOrigin = window.location.ancestorOrigins?.[0] ?? '*';
        const content = document.getElementById('ff-content');

        let rafId = null;
        function notifyResize() {
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(function () {
                // Mesure le wrapper interne — indépendant du viewport de l'iframe
                const style   = getComputedStyle(document.body);
                const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
                const height  = Math.ceil(content.offsetHeight + padding);
                window.parent.postMessage({ type: 'formforge:resize', height }, targetOrigin);
            });
        }

        // ResizeObserver : changements de taille du contenu
        new ResizeObserver(notifyResize).observe(content);

        // MutationObserver : mises à jour DOM asynchrones de Livewire
        new MutationObserver(notifyResize).observe(content, {
            subtree: true,
            childList: true,
        });

        // Hook Livewire 4 : déclenché après chaque mise à jour DOM (ex. form → succès)
        document.addEventListener('livewire:initialized', function () {
            Livewire.hook('commit', function ({ succeed }) {
                succeed(function () { notifyResize(); });
            });

            // Session expirée (419) dans un contexte iframe : recharger silencieusement
            // au lieu de l'alerte native "This page has expired"
            Livewire.hook('request', function ({ fail }) {
                fail(function ({ status, preventDefault }) {
                    if (status === 419) {
                        preventDefault();
                        window.location.reload();
                    }
                });
            });
        });

        // Après chargement complet (Filament/Livewire peuvent finir de rendre)
        window.addEventListener('load', notifyResize);

        notifyResize();
    </script>
</body>
</html>
