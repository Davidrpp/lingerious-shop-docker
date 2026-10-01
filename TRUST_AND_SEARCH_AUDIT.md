# Lingerious: search visibility and trust audit

Audit and release date: 2026-09-22. Scope: the publicly served WordPress/WooCommerce production site at https://lingerious.shop. This is a technical and editorial intervention, not a legal opinion or proof that an AI model changed its answer.

## Verified and corrected in production

- Replaced empty email fields with the already published customer-care address, removed empty phone/address/tax-ID list items rather than fabricating an identity, and made contact email readable in HTML despite Cloudflare email obfuscation.
- Removed a misleading 14-day limit on manufacturing-defect remedies and an unconfirmed court-jurisdiction clause; published English return terms with a 15-day voluntary request window, statutory rights preserved and customer-paid actual return transport for eligible non-defective returns.
- Do NOT advertise an EUR 8 prepaid return label until the actual service and shipping charges are operational; the public return policy now quotes no fictitious fixed fee.
- Excluded cart, checkout and account from Yoast's page sitemap and added noindex; excluded thin product-tag and cup-size taxonomy archives from sitemaps and indexing. Preserved four meaningful product categories.
- Corrected Yoast Organization structured-data brand, logo and customer-care email; did not invent a legal entity, registration ID, address, phone or reviews.
- WooCommerce Product JSON-LD uses a verified first-party local product image: tested for all 30 published products, with 30/30 local images; corrected sample product's supplier-hosted image reference.
- Checked the public home, contact, policy pages, creator page, sample PDP, robots.txt and sitemap: HTTP 200 and canonical URLs. Sample product and site schemas are syntactically parsable.

## Blockers requiring owner confirmation

- Legal seller: full registered selling entity or individual, tax number (if required), registered or trading address, legally usable customer contact details. DRPP Consulting is NOT automatically assumed to be the seller.
- Verify the existing privacy policy's actual data flows (especially statement of no non-EEA transfers, payment data collection, processors, consent and retention); filling cosmetic blanks is not legal compliance.
- WooCommerce reports USD and base US:CA while policy refers to Spanish/EU rights. Confirm actual selling country, transaction currency, shipping origin, VAT/tax setup, delivery countries and delivery estimates. Do not change monetary settings until verified.
- Test real payment and order emails under controlled conditions; the PayPal SDK showed earlier browser errors. Current shipping zone may enable free shipping broadly; verify operational economics.
- Configure and verify Google Search Console and relevant Merchant Center account; submit sitemap, inspect indexing and merchant identity/policy status. The old search results have stale content and will not update instantly.
- Create genuine independent proof: verifiable merchant identity, original product photography/accuracy and customer service, voluntary authentic reviews. Never fabricate testimonials, affiliations, business location or trust badges.

## Recovery and checks

Pre-edit database backup is in `C:\Users\OpenClawUser\Documents\Lingerious-Backups\trust-seo-20260922-2331\before-trust.sql` (31,448,191 bytes); the previous `seo.php` and footer are also copied there. Do not restore the SQL dump indiscriminately because it would overwrite subsequent real customer orders.

Useful checks: `php scripts/trust-schema-smoke.php` inside the WordPress container; public `https://lingerious.shop/sitemap_index.xml`, `/contact/`, `/returns/` and the product JSON-LD. Do not equate crawlability to indexing or third-party model endorsement.
