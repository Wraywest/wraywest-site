# Phase 4 closeout

Same unpublished branch. No PUBLISH, no merge, no deploy. Checks below are captured-HTML fixtures unless a row says otherwise. Real WordPress was not booted.

## Matrix

| Work | In this PR | Fixture checked | Real WordPress | Blocked or deferred |
|---|---|---|---|---|
| Single-post chrome, disclosures, n8n labels, receipts | Yes | 42 captured posts | Unverified | |
| Homepage teasers, Money and Content Strategy titles, blog dates, category chips | Yes | Homepage, blog, five categories | Unverified | |
| Start Here and Engine folds | Yes | Both captured pages | Unverified | |
| Tools, Resources, About, Contact layout | Yes | Four captured pages | Unverified | |
| Kit, Notion, Repurpose researched. Make researched. OLSP Affiliate receipts. Wise and Klarna Used | Yes | Tools index and the 42 post bylines | Unverified | |
| Robinhood and DataForSEO post bylines set to Used | Yes | Both captured posts, plus the other 40 | Unverified | Agentic-account sentence on the Robinhood post is still a Phase 5 claim |
| Calculator helper text and 44px controls | Yes | Three calculators. Scripts, inputs, and sample outputs match | Unverified | |
| JSON-LD inventory | Record only | 58 files, 119 scripts. OLSP mapped | Unverified | Deletion deferred. Emitters are not in the repo |
| `transition:all` replaced | Yes. 8 declarations | Homepage type lock measured | Unverified | Direction note said 9. This file had 8. The other three hits are comments |
| Duplicate token merge | No | Conflicting `:root` values recorded | Unverified | Deferred. Merging would pick a winner among `#1A1714` / `#1A1A1A`, `#F8F6F2` / `#FFF`, and `#9E5E1E` / `#8A4A12` |
| UniConsent contrast 3.29 and the mobile panel | No | Still failing where the widget paints | Unverified | Dwayne's UniConsent dashboard. No CSS override, no accept, no hide |
| CSS bundle fail-safe | Unchanged | 15 style ids absent. `ww-design-tokens` md5 still `369d75f97f7e56a084a24ebff85770b1` | Unverified | Do not refresh hashes. Copying `wraywest.css` onto the live uploads file is a go-live step |
| Legal pages, deck form, portrait, parked W-052 through W-056 | Preserved | Deck and portrait bytes still in the fixtures that contain them | Unverified | Legal URLs were not in the capture |
| Phase 5 body rewrites | No | Quotes only | Unverified | Stop here for review |

## Token deferral

`DIRECTION` lists duplicate token systems. In `uploads/ww-css/wraywest.css` the same names are set to different values in later `:root` blocks: `--ink` is `#1A1714` and `#1A1A1A`, `--bg` is `#F8F6F2` and `#FFF`, `--ww-copper` is `#9E5E1E` and `#8A4A12`. The homepage headline rule was not edited. Grain, keylight, and the gold decorative token stay.

The eight `transition:all` rules now name color, background-color, border-color, box-shadow, transform, and opacity, with the original durations. On the captured blog index the pager already computed to that property list before the edit, because an older rule set the property with higher specificity. Homepage type is unchanged: "A second income, built after the shift.", Instrument Serif, 86.4px, `#1A1714` at 1440. The navy "Get the weekly email" link was rendering copper `#9E5E1E` on `#0F1729` (3.46:1). It is now white, which is what the earlier button rule already asked for. Existing `(hover: hover) and (pointer: fine)` gates were left in place.

## Phase 5 sequence

Do not start these in the same review. Order is the outline list, then the cluster map, then the claim-conflict pages that are not already in that list. URLs are the live paths. Redirect plan: no 301 is applied, and none of the three URLs change. Phase 2 in `DIRECTION` names one pair: `/blog/creator-business-blueprint-second-income-2026/` to `/blog/how-to-start-creator-business-no-audience/`. That source page does contain "The 12-Month Revenue Roadmap" and "Step 2: Choose Your Revenue Model (Stack, Don't Scatter)". An earlier note inspected `/blog/second-income-product-blueprint/` instead, and that post does not contain those headings. After reading the Phase 2 pair, the recommendation is retain, not absorb and not redirect: Step 2 and the roadmap are unsourced dollar bands, and the how-to post's own Step 2 is a different section.

Outline order, from `OUTLINES` and the scoreboard REWRITE rows:

1. `/blog/make-vs-n8n-side-hustlers/`
2. `/blog/n8n-side-hustle-automation/`
3. `/blog/ai-side-hustles-that-work/`
4. `/blog/email-deliverability-small-senders/`
5. `/blog/systeme-vs-convertkit/`
6. `/blog/creator-burnout-truth/`
7. `/blog/mindset-shift-creator-business/`
8. `/blog/repurpose-io-content-automation/`
9. `/blog/convertkit-alternatives/`
10. `/scoring-methodology/`
11. Merge survivor `/blog/how-to-start-creator-business-no-audience/` absorbing the parked blueprint sentences. Not a live redirect yet.

Cluster map after that, skipping URLs already listed:

- Engine and start: `/the-second-income-engine/`, `/start-here/`, `/blog/why-side-hustles-fail/`, `/blog/find-your-creator-niche/`, `/blog/profitable-creator-niches-full-time-employees-2026/`, `/blog/second-income-product-blueprint/`, `/blog/content-calendar-publish-working-full-time/`, `/blog/solo-creator-metrics-that-matter/`, `/blog/ab-testing-one-person-businesses/`, `/tools/quit-9-to-5-calculator/`
- Email: `/blog/email-marketing-playbook-solo-creators/`, `/blog/email-list-building-100-subscribers/`, `/blog/lead-magnet-ideas/`, `/blog/welcome-email-sequence-template/`, `/blog/how-to-write-emails-people-want-to-open/`, `/blog/convertkit-review-email-marketing-creators/`, `/tools/lead-magnet-builder/`
- Tools: `/tools/`, `/resources/`, `/blog/leadsleap-review-honest-2026/`, `/blog/olsp-system-review-2026/`, `/blog/systeme-io-review-all-in-one-platform/`, `/blog/dataforseo-review/`, `/blog/hostinger-horizons-review-ai-website-builder/`, `/blog/notion-review-creator-workspace-2026/`, `/blog/google-analytics-creators-setup-guide/`
- Side income: `/blog/best-side-hustles-2026/`, `/blog/affiliate-marketing-side-hustle/`, `/blog/freelance-writing-side-hustle/`, `/blog/freelancing-as-creator-trading-time-for-money/`, `/blog/how-to-price-freelance-services-day-job/`, `/tools/freelance-rate-calculator/`
- Money: `/blog/managing-creator-income-taxes-tools/`, `/blog/1099-k-explained-creators/`, `/blog/investing-your-side-income-practical-starting-point/`, `/blog/robinhood-review/`, `/blog/klarna-review/`, `/blog/wise-international-money-transfers/`

Claim-conflict sentences that still sit on those URLs, exact text in `phase4/phase5-claim-conflicts.md`:

- `/resources/` Notion outline, ClickDesigns "I use it", Repurpose "90 minutes", the free-tier "I have tested" sentence, Wise and Klarna use sentences (those two stay hands-on).
- `/tools/` Notion "The backbone of my daily workflow."
- `/blog/robinhood-review/` the Agentic account and Claude sentence.
- `/blog/olsp-system-review-2026/` stays Affiliate receipts. Do not upgrade it to product use.

Freshness mentions from the direction note, still not edited: `/blog/how-to-write-emails-people-want-to-open/`, `/blog/robinhood-review/`, `/blog/creator-burnout-truth/`, `/blog/1099-k-explained-creators/`.

Parked: W-052, W-053, W-054, W-056. The six planning briefs stay briefs. No social calendar.
