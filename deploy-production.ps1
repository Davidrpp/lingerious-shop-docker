$ErrorActionPreference = 'Stop'
$live = 'C:\Users\OpenClawUser\Documents\Lingerious-Preview'
$repo = 'C:\Users\OpenClawUser\Documents\Lingerious-Editorial-Experiment'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "C:\Users\OpenClawUser\Documents\Lingerious-Backups\$stamp-redesign-prod"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

Write-Output "Backup dir: $backup"
Copy-Item "$live\compose.yml" "$backup\compose.before.yml" -Force

# Remove failed isolated preview containers from the previous attempt.
cmd /c 'docker rm -f lingerious-redesign-web3 lingerious-redesign-db3 lingerious-redesign-db2 lingerious-redesign-db-1 >NUL 2>NUL'

# Database backup before touching production.
docker cp "$live\_dump_lingerious.sh" lingerious-preview-db-1:/tmp/dump_lingerious.sh
if ($LASTEXITCODE -ne 0) { throw 'Could not copy DB dump helper.' }
docker exec lingerious-preview-db-1 sh /tmp/dump_lingerious.sh
if ($LASTEXITCODE -ne 0) { throw 'Database dump failed.' }
docker cp lingerious-preview-db-1:/tmp/lingerious-preview.sql "$backup\db-before-redesign.sql"
if ($LASTEXITCODE -ne 0) { throw 'Could not copy DB dump.' }
$dbSize = (Get-Item "$backup\db-before-redesign.sql").Length
if ($dbSize -lt 1000000) { throw "DB backup looks too small: $dbSize" }
Write-Output "DB backup bytes: $dbSize"

# Deploy versioned theme into the runtime folder.
New-Item -ItemType Directory -Force -Path "$live\themes" | Out-Null
if (Test-Path "$live\themes\lingerious-editorial") {
    Remove-Item "$live\themes\lingerious-editorial" -Recurse -Force
}
Copy-Item "$repo\themes\lingerious-editorial" "$live\themes\lingerious-editorial" -Recurse -Force
Copy-Item "$repo\scripts\activate-lingerious-editorial.php" "$backup\activate-lingerious-editorial.php" -Force
Copy-Item "$repo\scripts\production-cleanup.php" "$backup\production-cleanup.php" -Force

# Mount child theme and switch WordPress environment to production.
$composePath = "$live\compose.yml"
$compose = Get-Content $composePath -Raw

$compose = $compose.Replace("define('WP_ENVIRONMENT_TYPE', 'staging');", "define('WP_ENVIRONMENT_TYPE', 'production');")
$mountLine = '      - ./themes/lingerious-editorial:/var/www/html/wp-content/themes/lingerious-editorial:ro'
if ($compose -notlike '*lingerious-editorial:/var/www/html/wp-content/themes/lingerious-editorial*') {
    $compose = $compose.Replace('      - ./apache-preview.conf:/etc/apache2/conf-enabled/preview.conf:ro', "      - ./apache-preview.conf:/etc/apache2/conf-enabled/preview.conf:ro`r`n$mountLine")
}
Set-Content -Path $composePath -Value $compose -Encoding UTF8

docker compose -f "$live\compose.yml" config --quiet
if ($LASTEXITCODE -ne 0) { throw 'Compose validation failed.' }

# Recreate web container with the mounted theme.
docker compose -f "$live\compose.yml" up -d web
if ($LASTEXITCODE -ne 0) { throw 'Could not recreate WordPress web container.' }

Start-Sleep -Seconds 8

# Activate theme and production cleanup.
docker cp "$repo\scripts\activate-lingerious-editorial.php" lingerious-preview-web-1:/tmp/activate-lingerious-editorial.php
docker exec lingerious-preview-web-1 php /tmp/activate-lingerious-editorial.php
if ($LASTEXITCODE -ne 0) { throw 'Theme activation failed.' }

docker cp "$repo\scripts\production-cleanup.php" lingerious-preview-web-1:/tmp/production-cleanup.php
docker exec lingerious-preview-web-1 php /tmp/production-cleanup.php
if ($LASTEXITCODE -ne 0) { throw 'Production cleanup failed.' }

# Disable migration safety layer: it was intentionally noindexing and blocking checkout.
docker exec lingerious-preview-web-1 sh -lc 'if [ -f /var/www/html/wp-content/mu-plugins/preview-safety.php ]; then mv /var/www/html/wp-content/mu-plugins/preview-safety.php /var/www/html/wp-content/mu-plugins/preview-safety.php.disabled-20260921; fi'
if ($LASTEXITCODE -ne 0) { throw 'Could not disable preview safety layer.' }

docker exec lingerious-preview-web-1 sh -lc 'rm -rf /var/www/html/wp-content/litespeed/* /var/www/html/wp-content/cache/* 2>/dev/null || true'

# Basic public validation.
$urls = @('/', '/shop/', '/bras/', '/bottoms/', '/sets-two-pieces/', '/bodysuits/', '/privacy-policy/', '/terms-and-conditions/', '/returns/', '/faq/', '/size-guide/', '/cart/', '/my-account/')
foreach ($u in $urls) {
    $code = curl.exe -k -sS -L -o NUL -w '%{http_code}' "https://lingerious.shop$u"
    Write-Output "$code $u"
}

$headers = curl.exe -k -sS -D - -o NUL 'https://lingerious.shop/'
$headers | Select-String -Pattern 'HTTP/|x-robots-tag|X-Robots-Tag|Content-Type'

Write-Output "DEPLOYED_BACKUP=$backup"
