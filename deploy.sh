#!/usr/bin/env bash
# deploy.sh — Met à jour l'application en production (HestiaCP : Nginx + PHP-FPM)
# Usage : ./deploy.sh [--skip-assets]
set -euo pipefail

# ── Configuration ─────────────────────────────────────────────────────────────
APP_DIR="/home/<hestia_user>/web/forms.dezo.dev/public_html"   # à adapter
PHP_BIN="/usr/bin/php8.4"
COMPOSER_BIN="$(command -v composer 2>/dev/null || true)"
PNPM_BIN="$(command -v pnpm 2>/dev/null || echo '/usr/local/bin/pnpm')"
PHP_USER="<hestia_user>"
PHP_GROUP="www-data"
# ─────────────────────────────────────────────────────────────────────────────

SKIP_ASSETS=false
for arg in "$@"; do
    case $arg in
        --skip-assets) SKIP_ASSETS=true ;;
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

[ -d "$APP_DIR" ]      || { fail "Répertoire introuvable — lancez setup.sh d'abord."; exit 1; }
[ -f "$APP_DIR/artisan" ] || { fail "artisan introuvable — lancez setup.sh d'abord."; exit 1; }

echo -e "\n${BLUE}═══════════════════════════════════════════${RESET}"
echo -e "${BLUE}   Déploiement — $(date '+%Y-%m-%d %H:%M:%S')${RESET}"
echo -e "${BLUE}═══════════════════════════════════════════${RESET}"

# ── 1. Git pull ───────────────────────────────────────────────────────────────
step "Mise à jour du code (git pull)"
git -C "$APP_DIR" pull
ok "Code mis à jour — $(git -C "$APP_DIR" log -1 --format='%h %s')"

# ── 2. Mode maintenance ───────────────────────────────────────────────────────
step "Activation du mode maintenance"
ARTISAN down --retry=15
trap 'fail "Erreur durant le déploiement !"; ARTISAN up 2>/dev/null || true' ERR

# ── 3. Dépendances PHP ────────────────────────────────────────────────────────
step "Installation des dépendances PHP"
as_webuser "$PHP_BIN" "$COMPOSER_BIN" install \
    --working-dir="$APP_DIR" --no-dev --optimize-autoloader --no-interaction --quiet
ok "Dépendances PHP installées"

# ── 4. Assets frontend ────────────────────────────────────────────────────────
if [ "$SKIP_ASSETS" = false ]; then
    step "Compilation des assets frontend (pnpm)"
    NODE_BIN_DIR="$(dirname "$PNPM_BIN")"
    as_webuser env PATH="$NODE_BIN_DIR:$PATH" "$PNPM_BIN" --dir "$APP_DIR" install --frozen-lockfile --silent
    as_webuser env PATH="$NODE_BIN_DIR:$PATH" "$PNPM_BIN" --dir "$APP_DIR" run build
    ok "Assets compilés"
else
    warn "Assets ignorés (--skip-assets)"
fi

# ── 5. Caches ─────────────────────────────────────────────────────────────────
step "Nettoyage et reconstruction des caches"
ARTISAN optimize:clear
ARTISAN optimize
ok "Caches reconstruits"

# ── 6. Permissions ────────────────────────────────────────────────────────────
step "Correction des permissions"
chown -R "$PHP_USER:$PHP_GROUP" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permissions OK"

# ── 7. Fin du mode maintenance ────────────────────────────────────────────────
trap - ERR
step "Désactivation du mode maintenance"
ARTISAN up

echo ""
echo -e "${GREEN}═══════════════════════════════════════════${RESET}"
echo -e "${GREEN}   Déploiement terminé avec succès !${RESET}"
echo -e "${GREEN}═══════════════════════════════════════════${RESET}"
echo ""
