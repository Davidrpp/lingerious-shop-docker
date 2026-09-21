# Lingerious editorial redesign experiment

This branch adds a versioned WordPress child theme for a premium editorial storefront direction.

## What changed

- Adds `themes/lingerious-editorial`, a child theme of `twentytwentyfour`.
- Adds warm neutral design tokens, editorial typography, a large premium hero, category tiles, and a cleaner WooCommerce product grid.
- Updates `compose.yml` so the theme is mounted read-only into the WordPress container.
- Adds manual activation and rollback helpers under `scripts/`.

## Design direction

The goal is not to copy Intimissimi literally. The goal is to move Lingerious toward the same e-commerce quality tier: elegant spacing, a calm palette, editorial hierarchy, strong category entry points, and a less default-WooCommerce product presentation.

Do not use Intimissimi images, logos, copy, or exact layouts. Replace the current abstract hero placeholders with owned campaign photography when available.

## Local preview on HAL10000

From the runtime folder containing this branch:

```powershell
docker compose config
docker compose up -d web
```

Then copy and run the activation helper inside the WordPress container only when ready to test visually:

```powershell
docker cp scripts/activate-lingerious-editorial.php lingerious-preview-web-1:/tmp/activate-lingerious-editorial.php
docker exec lingerious-preview-web-1 php /tmp/activate-lingerious-editorial.php
```

Rollback:

```powershell
docker cp scripts/restore-twentytwentyfour.php lingerious-preview-web-1:/tmp/restore-twentytwentyfour.php
docker exec lingerious-preview-web-1 php /tmp/restore-twentytwentyfour.php
```

## Notes

Checkout, payment gateways, and outbound email are intentionally still controlled by the existing preview safety layer. This branch does not add secrets, credentials, `.env` files, or third-party protected assets.
