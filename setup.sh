#!/usr/bin/env bash
# setup.sh — Premier déploiement depuis zéro (HestiaCP : Nginx + PHP-FPM)
# Usage : ./setup.sh [--repo=<url_git>]
set -euo pipefail

# ── Configuration ──────────────────────────────────────────────────────────────
REPO_URL="https://github.com/Dezodev/dezo-formforge.git"
APP_DIR="/home/<hestia_user>/web/forms.dezo.dev/public_html"   # à adapter
PHP_BIN="/usr/bin/php8.4"
COMPOSER_BIN="$(command -v composer 2>/dev/null || true)"
PNPM_BIN="$(command -v pnpm 2>/dev/null || echo '/usr/local/bin/pnpm')"
PHP_USER="<hestia_user>"    # utilisateur HestiaCP propriétaire du site
PHP_GROUP="www-data"
# ──────────────────────────────────────────────────────────────────────────────

for arg in "$@"; do
    case $arg in
        --repo=*) REPO_URL="${arg#*=}" ;;
        *) echo "Option inconnue : $arg" >&2; exit 1 ;;
    esac
done

if [ "$(id -u)" != "0" ]; then
    echo "Ce script doit être exécuté en tant que root." >&2; exit 1
fi

BLUE='\033[1;34m'; GREEN='\033[1;32m'; YELLOW='\033[1;33m'; RED='\033[1;31m'; RESET='\033[0m'
step() { echo -e "\n${BLUE}▶  $1${RESET}"; }
ok()   { echo -e "${GREEN}✓  $1${RESET}"; }
warn() { echo -e "${YELLOW}⚠  $1${RESET}"; }
fail() { echo -e "${RED}✗  $1${RESET}" >&2; }

as_webuser() {
    [ "$(id -un)" = "$PHP_USER" ] && "$@" || sudo -u "$PHP_USER" "$@"
}
ARTISAN() { as_webuser "$PHP_BIN" -d memory_limit=-1 "$APP_DIR/artisan" "$@"; }

echo -e "\n${BLUE}═══════════════════════════════════════════${RESET}"
echo -e "${BLUE}   Premier déploiement — $(date '+%Y-%m-%d %H:%M:%S')${RESET}"
echo -e "${BLUE}═══════════════════════════════════════════${RESET}"

# ── 0. Prérequis ───────────────────────────────────────────────────────────────
step "Vérification des prérequis"
for cmd in git "$PHP_BIN"; do
    command -v "$cmd" >/dev/null 2>&1 \
        || { fail "Commande requise introuvable : $cmd"; exit 1; }
done
test -x "$COMPOSER_BIN" || { fail "composer introuvable"; exit 1; }
test -x "$PNPM_BIN"     || { fail "pnpm introuvable"; exit 1; }
ok "git, php, composer, pnpm disponibles"

# ── 1. Clonage ─────────────────────────────────────────────────────────────────
step "Clonage du dépôt Git"
if [ -d "$APP_DIR/.git" ]; then
    warn "Dépôt déjà présent — clonage ignoré"
else
    git clone "$REPO_URL" "$APP_DIR"
    ok "Dépôt cloné dans $APP_DIR"
fi
[ -f "$APP_DIR/artisan" ] || { fail "artisan introuvable — vérifiez APP_DIR"; exit 1; }

# ── 2. Fichier .env ────────────────────────────────────────────────────────────
step "Configuration du fichier .env"
if [ ! -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    warn "Fichier .env créé depuis .env.example"
    echo ""
    echo -e "  ${YELLOW}Valeurs à renseigner dans $APP_DIR/.env :${RESET}"
    echo "    APP_ENV=production"
    echo "    APP_DEBUG=false"
    echo "    APP_URL=https://forms.dezo.dev"
    echo "    MAIL_MAILER=smtp"
    echo "    MAIL_HOST=…"
    echo "    MAIL_USERNAME=…"
    echo "    MAIL_PASSWORD=…"
    echo "    TURNSTILE_SITE_KEY=…"
    echo "    TURNSTILE_SECRET_KEY=…"
    echo ""
    read -r -p "  Éditez le fichier, puis appuyez sur Entrée pour continuer…"
else
    ok ".env déjà présent"
fi

# ── 3. Permissions ─────────────────────────────────────────────────────────────
step "Permissions"
chown -R "$PHP_USER:$PHP_GROUP" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permissions définies ($PHP_USER:$PHP_GROUP)"

# ── 4. Dépendances PHP ─────────────────────────────────────────────────────────
step "Installation des dépendances PHP"
as_webuser "$PHP_BIN" "$COMPOSER_BIN" install \
    --working-dir="$APP_DIR" --no-dev --optimize-autoloader
ok "Dépendances PHP installées"

# ── 5. Clé d'application ───────────────────────────────────────────────────────
step "Génération de la clé d'application"
ARTISAN key:generate --force
ok "APP_KEY générée"

# ── 6. Assets frontend ─────────────────────────────────────────────────────────
step "Compilation des assets frontend (pnpm)"
NODE_BIN_DIR="$(dirname "$PNPM_BIN")"
as_webuser env PATH="$NODE_BIN_DIR:$PATH" "$PNPM_BIN" --dir "$APP_DIR" install --frozen-lockfile
as_webuser env PATH="$NODE_BIN_DIR:$PATH" "$PNPM_BIN" --dir "$APP_DIR" run build
ok "Assets compilés"

# ── 7. Optimisation ────────────────────────────────────────────────────────────
step "Optimisation (config, routes, vues)"
ARTISAN optimize
ok "Caches reconstruits"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════${RESET}"
echo -e "${GREEN}   Installation terminée avec succès !${RESET}"
echo -e "${GREEN}═══════════════════════════════════════════${RESET}"
echo ""
echo "  Pour les mises à jour suivantes : ./deploy.sh"
echo ""
