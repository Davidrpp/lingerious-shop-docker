# Size selector repair · 22 September 2026

- Production theme: Lingerious Editorial 0.9.3. The purchase form still uses the native WooCommerce attribute values, variation IDs and stock.
- Product 4181 imported supplier terms `S(32or70ABC)` etc. The term names were made legible (`S (32 / 70 ABC)` etc) without changing term IDs, slugs or the eight variations. This is a supplier reference, **not** a verified measurement chart.
- The customer-facing PDP shows concise `S / M / L / XL` plus optional expandable supplier references. Numeric bra sizes such as `70B / 75C` retain their full values; XS–6XL are ordered logically.
- The cart labels generic `pa_cup-size` values as `Size` when product context is unavailable; known numeric-bra product 3997 retains `Bra size` in its PDP. This avoids displaying `Cup Size: S` in the cart.
- Product variation and colour selection, out-of-stock states and the existing add-to-bag mechanism are preserved.

## Reproducible QA on HAL

1. `docker cp scripts/export-size-catalog.php lingerious-preview-web-1:/tmp/export-size-catalog.php`
2. `docker exec lingerious-preview-web-1 php /tmp/export-size-catalog.php > size-catalog.json`
3. Set `PLAYWRIGHT_MODULE`, `EDGE_BINARY` and `SIZE_CATALOG_JSON` for the local browser installation and catalog path.
4. Run `node scripts/size-catalog-regression.cjs`, `node scripts/size-selector-regression.cjs` and `node scripts/size-cart-smoke.cjs`.

Verified: 30/30 published variable products at 390px; 16/16 representative tests at 320, 390, 768 and 1440px; matching cart size for product 4181. No checkout orders were placed.

The guarded `scripts/normalize-supplier-size-terms.php` migration may be reapplied after a backup on a fresh database; it rejects unexpected term slugs/names and product associations. The 31 MB pre-change database dump and copies of the modified theme files are held in HAL's `Lingerious-Backups/size-normalization-20260922-172120` directory. Do not restore that database over subsequent orders without a separate recovery decision.
