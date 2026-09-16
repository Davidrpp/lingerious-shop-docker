# lingerious-shop-docker

Docker runtime notes for the Hostinger to HAL10000 migration of `lingerious.shop`.

## Current state

- WordPress and MariaDB are restored from the private Hostinger backup created on 2026-09-16.
- The Docker runtime is running on HAL10000.
- Cloudflare Tunnel routes are configured for `lingerious.shop` and `www.lingerious.shop` to `http://host.docker.internal:8098`.
- Hostinger nameservers were replaced with Cloudflare nameservers: `candy.ns.cloudflare.com` and `clint.ns.cloudflare.com`.
- Checkout, payment gateways, and outbound email are disabled by `preview-safety.php` so the restored WooCommerce site cannot create real orders or send real mail while this emergency migration is stabilized.

## Backup evidence

See `backup-manifest-2026-09-16.json` for the verified backup filenames, sizes, hashes, and exclusions.

The private backup archives are not committed here because they contain WordPress secrets and are larger than normal GitHub file limits. They remain on HAL10000 under:

`C:\Users\OpenClawUser\Documents\Lingerious-Backups\2026-09-16-private`

## Local runtime

The active runtime directory on HAL10000 is:

`C:\Users\OpenClawUser\Documents\Lingerious-Preview`

The active containers currently use the Docker Compose project name `lingerious-preview` even though the service has been promoted for public Cloudflare routing.
