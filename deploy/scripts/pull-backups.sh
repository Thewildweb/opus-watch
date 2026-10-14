#!/bin/bash
# Off-site copy of the opus.watches nightly backups — runs on big (Tailscale),
# from erik's crontab at 04:00, after the server's 02:30 backup:
#
#   0 4 * * * $HOME/bin/opus-pull-backups >> /mnt/sas14tb/backups/opus-watch/pull.log 2>&1
#
# Uses ~/.ssh/opus-backup, a key the server only accepts for a read-only
# rrsync of /home/erik/backups (see roles/opuswatch, backup_pull_key).
# Keeps KEEP_DAYS of nightly folders; the server itself keeps 7.
set -euo pipefail

DEST=/mnt/sas14tb/backups/opus-watch
KEEP_DAYS=30
SSH="ssh -i $HOME/.ssh/opus-backup -o BatchMode=yes -o IdentitiesOnly=yes"

mkdir -p "$DEST"
chmod 700 "$DEST"

# Only the finished nightly folders (in-progress ones are hidden .partial dirs)
# and the manual pre-change dumps; no --delete, so the server's 7-day
# pruning never removes anything here.
rsync -a --chmod=D700,F600 -e "$SSH" \
  --include='full-*/***' --include='*.sql.gz' --include='*.tar.gz' --exclude='*' \
  erik@85.17.236.215:/ "$DEST/"

# Each copied folder must match its own checksums.
for d in "$DEST"/full-*/; do
  [ -f "$d/.verified" ] && continue
  (cd "$d" && sha256sum -c --quiet SHA256SUMS) && touch "$d/.verified"
done

find "$DEST" -maxdepth 1 -type d -name 'full-*' -mtime +"$KEEP_DAYS" -exec rm -rf {} +

echo "$(date -u +%FT%TZ) ok $(ls -d "$DEST"/full-*/ | wc -l) nightly folders, $(du -sh "$DEST" | cut -f1)"
