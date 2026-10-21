# wraywest-site

Source of truth for WrayWest's custom code on wraywestblog.com (Hostinger, WordPress, parent theme **GeneratePress**, no child theme).

The live custom code is **not** a theme. It is a set of must-use plugins plus a bundled CSS file:

| Repo path | Live path (under public_html/) |
|---|---|
| `mu-plugins/*.php` | `wp-content/mu-plugins/` |
| `uploads/ww-css/wraywest.css`, `manifest.json` | `wp-content/uploads/ww-css/` (enqueued as `wraywest.css?ver=<manifest ver>` by `ww-css-bundle.php`) |
| `uploads/ww-fonts/*` | `wp-content/uploads/ww-fonts/` |

The repo is a mirror of those folders, not a theme or plugin of its own. A Git deploy clones it into a **new, separate** directory, `wp-content/ww-site/`. `.htaccess` denies all web access there, and WordPress does not load PHP from that folder. Deploying the repo therefore changes nothing on the live site. Going live is a second step, described in DEPLOY.md.

## Snapshot status (2026-10-21)
Every file here matches the live file byte for byte by size, checked against a read-only Hostinger listing on 2026-10-21. CSS is version `20261020.16`, which is item W009. W010 stage 1 has **not** shipped.

These live mu-plugins are **not yet in the repo**, because no verified copy of the current version exists in the workspace. Copy them in before the repo becomes the only source:
ww-tools, ww-author, ww-410, ww-header-tweaks, ww-indexnow, ww-redirects, ww-shortcodes, ww-design-tokens, ww-category-chips, ww-craft-fixes, ww-typography-fix, ww-mobile-actions, ww-typography, ww-autop, ww-reach-embed, ww-layout-fixes, ww-img-cachebust, ww-home-rhythm, wraywest-rankmath-rest, ww-hero-composite, ww-optin-fix, ww-cluster-links.
Hostinger-owned files (hostinger-*.php) must never be committed. Check ww-indexnow.php for an IndexNow key before committing it.
Also left out: the uploads/ww-fonts files other than ww9-instrument-serif-italic.woff2, and ww-fonts.css.

## Rules
- No secrets in this repo, ever. No keys, passwords, app passwords or tokens.
- Every deploy, cache clear or activation needs Dwayne's explicit OK.
- See DEPLOY.md.
