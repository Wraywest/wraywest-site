# JSON-LD inventory of the captured corpus

Read-only. 58 captured HTML files. Not a WordPress boot. Nothing was deleted. The generator is `phase4/inventory-jsonld.py`. The full list is `phase4/jsonld-inventory.json`.

119 `application/ld+json` scripts:

- 58 Rank Math head graphs (`class="rank-math-schema"`, before `</head>`).
- 60 in-content scripts (after `</head>`, no Rank Math class).
- 1 other head script: an `ItemList` on the captured Tools page. It has no `rank-math-schema` class. This repo does not contain the file that prints it.

In-content types that the direction note wants inside Rank Math, still outside it in these captures:

- Review: 10 posts (ConvertKit, Hostinger Horizons, Klarna, LeadsLeap, Notion, OLSP, Repurpose.io, Robinhood, Systeme.io, Wise).
- FAQPage: 45 blocks, including About, the Engine, the three calculators, Resources, Start Here, and most posts.
- ProfilePage: About only.
- WebApplication: the three calculator pages.

## OLSP, exact

File `blog_olsp-system-review-2026.html`. URL `https://wraywestblog.com/blog/olsp-system-review-2026/`.

1. Rank Math head, 4541 bytes. Types: Organization, ImageObject, WebSite, BreadcrumbList, ListItem, WebPage, Person, BlogPosting.
2. In-content Review, 688 bytes. Types: Review, SoftwareApplication, Person, Rating, Organization.
3. In-content FAQPage, 1297 bytes. Types: FAQPage, Question, Answer.

The Review and FAQPage blocks are not in any PHP file in this repo. They were not removed.

## DataForSEO

The captured `blog_dataforseo-review.html` has the Rank Math head graph only. No in-content Review and no FAQPage script. The HTML does contain a visible "Frequently asked questions" section. Adding an FAQPage block would be a new emitter. It was not added.

## Not in this corpus

The scoreboard lists 63 URLs. These five were not in the capture, so they are not in the inventory: `/author/dwayne/`, `/legal/`, `/privacy-policy/`, `/terms-of-service/`, `/scoring-methodology/`.

## Rank Math-only checklist, unresolved

Do not treat any line as done. None of these settings were opened.

1. Confirm in Rank Math, on a fresh read-only capture, whether the head graph already includes Review, FAQPage, ProfilePage, or WebApplication. This inventory only sees the public HTML.
2. The in-content Review, FAQPage, ProfilePage, and WebApplication scripts above have no emitter file in this repo. Do not delete them until that file, or the post content that contains them, is in front of you.
3. About has a visible FAQ and an in-content FAQPage plus ProfilePage. The direction note says remove the About FAQPage only when the FAQ is not the one Rank Math should keep. That choice is not made here.
4. DataForSEO has a visible FAQ and no FAQPage script. Adding one belongs in Rank Math or the post, not in this change-set.
5. The Tools `ItemList` head script has no Rank Math class and no repo emitter. Leave it.
6. Author archive sitemap and the http to https check are Rank Math and host settings. Not touched.
7. Manifest md5s stay the 20 Oct 2026 capture. The 15 style ids are still absent, so `ww-css-bundle.php` still returns the page unchanged.
