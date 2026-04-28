# FormForge

Application Laravel d'hébergement de formulaires réutilisables, intégrables sur n'importe quel site via `<iframe>`.

## Principe

Chaque formulaire est défini par une classe PHP. FormForge l'héberge sur `forms.dezo.dev` et l'expose via une URL dédiée. Le site cible l'intègre en une ligne avec une balise `<iframe>`. Chaque soumission valide déclenche une notification email. Tous les formulaires sont protégés par Cloudflare Turnstile (captcha invisible, gratuit).

## Stack

- **Laravel 12** + **Livewire 4** + **Filament Forms v5** (standalone, sans panel admin)
- **Cloudflare Turnstile** — captcha invisible
- **Laravel Mail** — notification SMTP par soumission
- **Alpine.js** — inclus avec Livewire, aucune dépendance supplémentaire
- **PHP 8.4** / **Node 22** / **pnpm**

---

## Démarrage rapide (Docker)

```bash
docker compose up -d --build
```

L'application est disponible sur [http://localhost:8082](http://localhost:8082).

Le formulaire d'exemple est accessible sur [http://localhost:8082/f/contact](http://localhost:8082/f/contact).

> Le réseau Docker `scoobydoo` doit exister : `docker network create scoobydoo`

---

## Créer un formulaire

**1. Créer la classe dans `app/Forms/` :**

```php
<?php

namespace App\Forms;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactForm extends BaseForm
{
    public string $slug        = 'contact';
    public string $title       = 'Formulaire de contact';
    public string $notifyEmail = 'hello@dezo.dev';

    public function schema(): array
    {
        return [
            TextInput::make('name')->label('Nom')->required(),
            TextInput::make('email')->label('Email')->email()->required(),
            Textarea::make('message')->label('Message')->required(),
        ];
    }
}
```

**2. Enregistrer dans `app/Providers/FormServiceProvider.php` :**

```php
FormRegistry::register(ContactForm::class);
```

**3. Le formulaire est accessible à `/f/contact`.**

---

## Intégration iframe

```html
<iframe
  src="https://forms.dezo.dev/f/contact?bg=f5f5f5&color=222222"
  id="formforge-contact"
  style="width:100%; border:none; min-height:400px;"
  loading="lazy"
></iframe>

<script>
  window.addEventListener('message', function (e) {
    if (e.origin !== 'https://forms.dezo.dev') return;
    if (e.data?.type === 'formforge:resize') {
      document.getElementById('formforge-contact').style.height = e.data.height + 'px';
    }
  });
</script>
```

### Paramètres query string

| Paramètre | Description | Défaut |
|---|---|---|
| `bg` | Couleur de fond (hex sans `#`) | `ffffff` |
| `color` | Couleur du texte (hex sans `#`) | `1a1a1a` |

---

## Variables d'environnement

Copier `.env.example` en `.env` et renseigner :

```env
APP_URL=https://forms.dezo.dev

# Cloudflare Turnstile (laisser vide en local pour bypass automatique)
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=

# SMTP
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@dezo.dev
MAIL_FROM_NAME="FormForge"
```

---

## Déploiement en production (HestiaCP)

**Premier déploiement :**

```bash
sudo ./setup.sh
```

**Mise à jour :**

```bash
sudo ./deploy.sh              # déploiement complet
sudo ./deploy.sh --skip-assets  # sans recompilation JS/CSS
```

> Adapter `APP_DIR`, `PHP_USER` et `REPO_URL` dans les deux scripts avant utilisation.

---

## Structure du projet

```
app/
├── Forms/
│   ├── BaseForm.php          # Classe abstraite parente
│   ├── FormInterface.php     # Interface
│   ├── FormRegistry.php      # Registre slug → classe
│   └── ContactForm.php       # Exemple
├── Http/Controllers/
│   └── FormController.php    # Route GET /f/{slug}
├── Livewire/
│   └── FormRenderer.php      # Composant Livewire + Filament
├── Mail/
│   └── FormSubmissionMail.php
└── Providers/
    └── FormServiceProvider.php

resources/views/
├── components/
│   └── iframe-layout.blade.php   # Layout minimal pour l'iframe
├── form/
│   └── show.blade.php
├── livewire/
│   └── form-renderer.blade.php
└── mail/
    └── form-submission.blade.php
```

---

## Licence

Usage privé — [Dezodev](https://dezo.dev)
