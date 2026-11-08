# Remaining Phase 4 schema and token scope

This is a scope note from the repo and the public HTML captured read-only on 8 Nov 2026. It does not authorize a deploy, a cache clear, a hash refresh, or a schema deletion.

## What the bundle will do today

`mu-plugins/ww-css-bundle.php` replaces 15 inline `<style id="...">` blocks with one stylesheet only when every id appears exactly once and its md5 matches `uploads/ww-css/manifest.json`. If any id is missing, duplicated, or hashed differently, `filter()` returns the HTML unchanged.

The 15 ids, in order:

1. `ww-design-tokens` (`369d75f97f7e56a084a24ebff85770b1`)
2. `wraywest-layout-fix`
3. `wraywest-custom-css`
4. `wraywest-adapt-polish`
5. `ww-header-tweaks`
6. `ww-typography-fix`
7. `wp-custom-css`
8. `ww-layout-fixes`
9. `ww-mobile-actions-css`
10. `ww-redesign-pass`
11. `ww-typography`
12. `ww-redesign-fixes-r2`
13. `ww-archive-hero-height`
14. `ww-craft-fixes`
15. `ww-hero-masthead`

On the captured homepage, none of those ids is present. `php phase4/verify-calculators.php` still requires that absence and still requires the `ww-design-tokens` md5 above. The manifest `ver` is `20261108.4post`. The md5 map is still the 20 Oct 2026 inline-block capture. Do not edit the md5s to force a match. Do not delete the count check.

The captured pages already load `wraywest.css?ver=20261104.51` as `wraywest-bundle-css`. That link is the live file. The bundle filter is not what is painting these captures, because the 15 inline blocks are already gone. Copying this branch's `wraywest.css` over the live uploads file would change what those pages load. That copy is a go-live step and is not part of this branch.

## Deployment path, from the repo only

`README.md` and `DEPLOY.md` describe two steps.

1. A merge to `main` does not change the site. Hostinger `hosting_git_deploy-website-repository` clones the repo into `wp-content/ww-site`. That directory is not executed. `.htaccess` denies web access there.
2. Going live is a later move from `wp-content/ww-site` into `wp-content/mu-plugins` and `wp-content/uploads/ww-css`, by a loader mu-plugin, SSH `rsync`, or the current file-write path, then `hosting_cache_clear-website`. Each of those needs Dwayne's OK. None of them is run from this branch.

This PR is unpublished. PUBLISH has not been given. No cache, form, or WordPress setting was changed.

## Schema

No PHP file in this repo prints `application/ld+json`.

Captured pages carry two kinds of JSON-LD that this repo does not own:

- A Rank Math graph in the document head (`class="rank-math-schema"` on posts), with types such as Organization, WebSite, WebPage, BreadcrumbList, and ImageObject.
- In-content scripts inside the page body. The OLSP review contains a Review graph. The Engine page and the freelance calculator contain an FAQPage graph. The freelance FAQPage is still byte-identical after this unit.

Those emitters are the Rank Math plugin and the stored page content. Neither file is in this repo. Do not delete either graph from a transform on a guess that it is a duplicate.

## Token cleanup, not this unit

The stylesheet still has `--purple` set to the copper hex, a second warm-band set under `--w38-*` and `--ti-*`, grain and keylight, and older `transition: all` rules. Those stay. A later token pass can collapse an alias only when a captured-HTML screenshot shows the computed color did not change, and only without editing the manifest md5s or the homepage `#wwl-h` rule.

## Next smallest unit

One captured post, `https://wraywestblog.com/blog/olsp-system-review-2026/`. List each JSON-LD script, its `@type` values, and whether it is the Rank Math head graph or an in-content block. Write the keep list. Do not delete a block, do not refresh a manifest hash, and do not open Rank Math settings.
