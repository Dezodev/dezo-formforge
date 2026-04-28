# FormForge

Application Laravel de construction et d'hébergement de formulaires réutilisables, intégrables sur d'autres sites via iframe.

## Vision

FormForge permet de définir des formulaires en PHP (classe + schéma), de les héberger sur `forms.dezo.dev`, et de les intégrer sur n'importe quel site via une balise `<iframe>`. Chaque soumission déclenche une notification email. Tous les formulaires sont protégés par Cloudflare Turnstile (captcha gratuit, invisible).

---

## Stack technique

| Composant | Choix | Raison |
|---|---|---|
| Framework | Laravel (dernière version LTS) | Base du projet |
| Rendu formulaire | Filament Forms standalone + Livewire | Schéma PHP → HTML auto, sans panel admin |
| Captcha | Cloudflare Turnstile | Gratuit, invisible, compatible iframe |
| Email | Laravel Mail (SMTP configurable) | Simple, pas de dépendance externe |
| Front | Alpine.js (inclus avec Livewire) | Au plus simple, pas de Vue/React |
| Intégration | `<iframe>` | Isolation CSS, pas de conflits JS, hauteur via postMessage |
| Runtime (dev) | PHP 8.4-Apache + Docker | Cohérent avec les autres projets |
| Gestionnaire de paquets JS | pnpm | Cohérent avec les autres projets |

---

## Environnement de développement (Docker)

La stack Docker est identique aux projets `yemv-www` et `zone-cine-www`.

### Structure `.docker/`

```
.docker/
├── apache/
│   └── default.conf        # VirtualHost Apache, DocumentRoot → /var/www/html/public
├── php.ini                 # Overrides PHP (upload_max, memory_limit, opcache…)
├── crontab                 # Laravel scheduler : * * * * * www-data php artisan schedule:run
├── supervisord.conf        # Lance Apache en foreground (pas de queue worker pour FormForge v1)
└── entrypoint.sh           # Bootstrap : .env, composer, APP_KEY, puis supervisord
```

> **Différence avec zone-cine-www** : pas de MySQL ni de queue worker en v1 (pas de base de données, emails envoyés de façon synchrone). Le service Redis est conservé pour le cache Laravel (optionnel).

### `docker-compose.yaml` (synthèse)

```yaml
services:
  app:
    build: { context: ., dockerfile: Dockerfile, args: { WWWUSER: 1001, WWWGROUP: 1001 } }
    container_name: formforge_app
    restart: unless-stopped
    ports:
      - "${APP_PORT:-8082}:80"
    volumes:
      - .:/var/www/html
    environment:
      APP_ENV: local

networks:
  default:
    external: true
    name: scoobydoo
```

> Le réseau `scoobydoo` est le réseau Docker partagé entre tous les projets sur le VPS de développement.

### `Dockerfile` (synthèse)

- `php:8.4-apache` comme image de base
- Extensions PHP : `pdo_mysql mbstring xml zip exif pcntl gd bcmath intl opcache redis imagick`
- Node.js 22 + pnpm
- Locale `fr_FR.UTF-8`, timezone `Europe/Paris`
- Supervisor pour gérer Apache en foreground

### `entrypoint.sh` (logique)

1. Copie `.env.example` → `.env` si absent
2. `composer install` si `vendor/` absent
3. `php artisan key:generate` si `APP_KEY` vide
4. Lance `supervisord` (Apache)

> Pas d'attente MySQL ni de migration automatique (pas de base de données en v1).

---

## Déploiement en production (HestiaCP)

Le serveur de production tourne sous **HestiaCP** (Nginx + PHP-FPM 8.4).  
Deux scripts bash gèrent le cycle de vie, à la racine du projet.

### `setup.sh` — Premier déploiement

| Étape | Action |
|---|---|
| 0 | Vérification des prérequis (git, php, composer, pnpm) |
| 1 | `git clone` du dépôt dans `APP_DIR` |
| 2 | Création du `.env` depuis `.env.example` + pause pour édition manuelle |
| 3 | `chown`/`chmod` pour HestiaCP (`PHP_USER:www-data`, storage 775) |
| 4 | `composer install --no-dev --optimize-autoloader` |
| 5 | `php artisan key:generate` |
| 6 | `pnpm install && pnpm run build` |
| 7 | `php artisan optimize` |

> Pas de migrations ni de Supervisor en v1 (sans base de données, sans queue worker).

**Variables à adapter dans `setup.sh` :**

```bash
REPO_URL="https://github.com/Dezodev/dezo-formforge.git"
APP_DIR="/home/<hestia_user>/web/forms.dezo.dev/public_html"
PHP_BIN="/usr/bin/php8.4"
PHP_USER="<hestia_user>"
```

### `deploy.sh` — Mises à jour

| Étape | Action |
|---|---|
| 1 | `git pull` |
| 2 | `php artisan down` (mode maintenance) |
| 3 | `composer install --no-dev` |
| 4 | `pnpm install && pnpm run build` (sauf `--skip-assets`) |
| 5 | `php artisan optimize:clear && optimize` |
| 6 | Correction des permissions |
| 7 | `php artisan up` |

**Options disponibles :**

```bash
./deploy.sh                   # Déploiement complet
./deploy.sh --skip-assets     # Sans recompilation JS/CSS
```

---

## Architecture

### Définition d'un formulaire

Chaque formulaire est une classe PHP dans `app/Forms/` qui implémente `FormInterface` et définit :
- un **slug** unique (utilisé dans l'URL)
- un **titre**
- le **destinataire email** des notifications
- le **schéma Filament** (liste de champs)

```php
// app/Forms/ContactForm.php
class ContactForm extends BaseForm
{
    public string $site = 'dezo';
    public string $slug = 'contact';
    public string $title = 'Formulaire de contact';
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

### URL d'intégration

```
https://forms.dezo.dev/f/{site}/{slug}?bg=ffffff&color=333333
```

Paramètres query string :
- `bg` : couleur de fond (hex sans `#`, défaut `ffffff`)
- `color` : couleur du texte (hex sans `#`, défaut `1a1a1a`)

### Intégration côté site cible

```html
<iframe
  src="https://forms.dezo.dev/f/dezo/contact?bg=f5f5f5&color=222222"
  id="formforge-contact"
  style="width:100%; border:none; min-height:400px;"
  loading="lazy"
></iframe>

<script>
  window.addEventListener('message', function(e) {
    if (e.origin !== 'https://forms.dezo.dev') return;
    if (e.data?.type === 'formforge:resize') {
      document.getElementById('formforge-contact').style.height = e.data.height + 'px';
    }
  });
</script>
```

### Redimensionnement automatique de l'iframe

Après chaque rendu Livewire, le composant envoie la hauteur réelle via `postMessage` au parent pour que l'iframe s'ajuste sans scroll interne.

---

## Champs supportés

| Type Filament | Rendu HTML |
|---|---|
| `TextInput` | `<input type="text">` / `type="email"` / `type="tel"` |
| `Textarea` | `<textarea>` |
| `Select` | `<select>` |
| `Checkbox` | `<input type="checkbox">` |
| `CheckboxList` | Liste de checkboxes |
| `Radio` | `<input type="radio">` |
| `DatePicker` | `<input type="date">` |

---

## Captcha Turnstile

- Intégré dans le composant Livewire de base
- Widget rendu côté client, validation côté serveur avant envoi email
- Package PHP : `coderflex/laravel-turnstile` ou implémentation directe via HTTP
- Clés configurées dans `.env` : `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`

---

## Notification email

- Déclenchée uniquement à la soumission valide (captcha vérifié)
- Template Markdown Laravel (`resources/views/mail/form-submission.blade.php`)
- Contenu : nom du formulaire, date, liste des champs/valeurs
- Pas de stockage en base de données (sans DB dans un premier temps)

---

## Variables d'environnement

```env
APP_URL=https://forms.dezo.dev

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@dezo.dev
MAIL_FROM_NAME="FormForge"
```

---

## Liste des tâches

### Phase 0 — Infrastructure Docker & déploiement

- [x] Créer le `Dockerfile` (PHP 8.4-Apache, Node 22, pnpm, extensions, supervisor)
- [x] Créer `.docker/apache/default.conf` (VirtualHost → `/var/www/html/public`)
- [x] Créer `.docker/php.ini` (memory_limit, upload, opcache)
- [x] Créer `.docker/crontab` (Laravel scheduler)
- [x] Créer `.docker/supervisord.conf` (Apache uniquement, pas de queue worker en v1)
- [x] Créer `.docker/entrypoint.sh` (bootstrap : .env, composer, key:generate, supervisord)
- [x] Créer `docker-compose.yaml` (service `formforge_app`, réseau `scoobydoo`, port 8082)
- [x] Créer `.env.example` avec toutes les variables nécessaires
- [x] Créer `setup.sh` (premier déploiement HestiaCP, adapté sans DB ni Supervisor)
- [x] Créer `deploy.sh` (mise à jour HestiaCP avec maintenance mode, sans migrations)

### Phase 1 — Socle Laravel

- [x] Initialiser le projet Laravel
- [x] Installer Livewire (`livewire/livewire`)
- [x] Installer Filament Forms standalone (`filament/forms`) sans le panel admin
- [x] Configurer le layout minimal pour l'iframe (`resources/views/components/iframe-layout.blade.php`)
- [x] Créer `FormInterface` et `BaseForm` (slug, title, notifyEmail, schema)
- [x] Mettre en place le registre de formulaires (`FormRegistry`) qui mappe site/slug → classe
- [x] Créer la route `GET /f/{site}/{slug}` qui charge et affiche le formulaire correspondant

### Phase 2 — Composant Livewire

- [x] Créer le composant Livewire `FormRenderer` (`app/Livewire/FormRenderer.php`)
- [x] Gérer la soumission du formulaire (validation des règles Filament)
- [x] Afficher un message de succès après soumission
- [x] Émettre `postMessage` avec la hauteur réelle via `ResizeObserver` (resize iframe)

### Phase 3 — Captcha Turnstile

- [x] Intégrer le widget Turnstile dans la vue du formulaire
- [x] Valider le token Turnstile côté serveur avant d'envoyer l'email (via `Http::asForm()->post`)
- [x] Bloquer la soumission si le captcha échoue (message d'erreur)
- [x] Bypass automatique en `local` si `TURNSTILE_SECRET_KEY` est vide (dev sans clé)

### Phase 4 — Notification email

- [x] Créer le Mailable `FormSubmissionMail` (`app/Mail/FormSubmissionMail.php`)
- [x] Créer le template email Markdown avec champs/valeurs dynamiques
- [x] Déclencher l'envoi depuis le composant Livewire après validation captcha
- [ ] Tester l'envoi en local avec Mailpit ou log driver

### Phase 5 — Personnalisation visuelle

- [x] Lire les paramètres `bg` et `color` depuis la query string
- [x] Appliquer via CSS custom properties dans le layout iframe
- [x] Valider/sanitiser les valeurs hex pour éviter toute injection CSS

### Phase 6 — Formulaire d'exemple et documentation

- [x] Créer `ContactForm` comme formulaire d'exemple (`app/Forms/ContactForm.php`)
- [ ] Documenter la création d'un nouveau formulaire dans ce fichier (voir section ci-dessous)

---

## Créer un nouveau formulaire

1. Créer une classe dans `app/Forms/` qui étend `BaseForm` :

```php
// app/Forms/MonSite/DevisForm.php
class DevisForm extends BaseForm
{
    public string $site = 'monsite';
    public string $slug = 'devis';
    public string $title = 'Demande de devis';
    public string $notifyEmail = 'contact@monsite.fr';

    public function schema(): array
    {
        return [
            TextInput::make('name')->label('Nom')->required(),
            TextInput::make('email')->label('Email')->email()->required(),
            Select::make('service')->label('Service')->options([
                'web' => 'Site web',
                'app' => 'Application',
            ])->required(),
            Textarea::make('description')->label('Description')->required(),
        ];
    }
}
```

2. Enregistrer la classe dans `app/Providers/FormServiceProvider.php` :

```php
FormRegistry::register('monsite', DevisForm::class);
```

3. Le formulaire est accessible à l'URL `/f/monsite/devis`.

---

## Décisions techniques notées

- **Pas de base de données** pour les soumissions dans un premier temps (email uniquement).
- **Pas d'interface admin** : les formulaires sont créés et modifiés uniquement via le code PHP.
- **iframe choisi plutôt que script JS** : isolation CSS totale, pas de conflits avec les styles/scripts du site hôte.
- **Turnstile bypass en local** : si `TURNSTILE_SECRET_KEY` est vide en environnement `local`, la vérification est ignorée pour faciliter le développement.
- **Filament v5 (filament/forms ^5)** : la signature de la méthode `form()` utilise `Filament\Schemas\Schema` (renommé depuis `Filament\Forms\Form` en v4). La propriété `formDefinition` de type `BaseForm` ne peut pas être une propriété publique Livewire — on stocke uniquement le `slug` avec `#[Locked]` et on résout via `FormRegistry`.
- **SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync** en développement (pas de base de données ni Redis nécessaires).
