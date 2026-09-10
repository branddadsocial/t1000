$ErrorActionPreference = 'Stop'

$sshKey = 'C:\Users\iamtd\Documents\Codex\branddad-seoestore\deploy\_secrets\cp_migrate_ed25519'
$hostName = 'branddad.social@ssh.gb.stackcp.com'
$remoteCommand = '/usr/bin/sh /home/sites/41a/c/c0a8249376/recovery-backups/20260909-codex/run-social-cron.sh'

ssh -i $sshKey -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=accept-new $hostName $remoteCommand
