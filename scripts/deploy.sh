#!/usr/bin/env bash
# Deploy des committeten Stands (git ls-files unter web/) per rsync auf Mittwald (App thierrybill-wp, a-es3wr7).
# Im Repository liegt WordPress in web/, auf dem Server direkt in html/thierrybill-wp/.
# Aufruf: scripts/deploy.sh            -> Dateien hochladen, Cache und Rewrites leeren
#         scripts/deploy.sh --dry-run  -> nur anzeigen, was uebertragen wuerde
# Datenbank und Uploads werden nicht deployt (Inhalte werden in Produktion gepflegt).
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"
HOST="ssh.isenstedt.project.host"
SSH_USER="ssh-qsz9am@a-es3wr7"
DEST="/home/p-b6amzs/html/thierrybill-wp/"
SSH_OPTS="-i $SSH_KEY -o IdentitiesOnly=yes -o BatchMode=yes"

if [ -n "$(git status --porcelain)" ]; then
	echo "Arbeitskopie ist nicht sauber. Bitte zuerst committen." >&2
	exit 1
fi

DRY=""
[ "${1:-}" = "--dry-run" ] && DRY="--dry-run -v"

FILES=$(mktemp)
trap 'rm -f "$FILES"' EXIT
# Pfade relativ zu web/; Konfigurationsdateien bleiben auf dem Server
git ls-files web | sed 's#^web/##' | grep -v -E '^(wp-config(-ddev)?\.php|\.htaccess)$' > "$FILES"

echo "Deploy $(git rev-parse --short HEAD) ($(git log -1 --format=%s)) nach $DEST"
# shellcheck disable=SC2086
rsync -az $DRY --files-from="$FILES" -e "ssh $SSH_OPTS" web/ "$SSH_USER@$HOST:$DEST"

if [ -z "$DRY" ]; then
	# shellcheck disable=SC2086
	ssh $SSH_OPTS "$SSH_USER@$HOST" 'cd html/thierrybill-wp && wp cache flush --quiet && wp rewrite flush --quiet && wp core version'
	echo "Fertig. Hinweis: im Git geloeschte Dateien werden auf dem Server nicht entfernt."
fi
