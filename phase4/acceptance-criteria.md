# Acceptance criteria for a later integration check

This is a read-only checklist. It does not authorize copying files onto the live site, purging cache, changing WordPress settings, or opening a form.

The publication blocker stays as measured on 8 Nov 2026: the live homepage HTML does not contain the 15 style ids listed in `uploads/ww-css/manifest.json` (`ww-design-tokens` through `ww-hero-masthead`). `ww-css-bundle.php` returns the page unchanged when any of those ids is missing or its md5 does not match. Do not edit those md5s to force a match, and do not delete the check. A later publish has to re-capture the inline blocks, or the bundle will keep failing closed. Copying `wraywest.css` over the live file is a separate go-live step and is not allowed until PUBLISH.

## How to validate against the public pages

1. Fetch these URLs read-only, once, and stop for at least an hour on a Cloudflare 1015:
   - `https://wraywestblog.com/start-here/`
   - `https://wraywestblog.com/the-second-income-engine/`
   - `https://wraywestblog.com/`
   - the 42 post URLs already listed in the Phase 3 baseline
2. Run `php phase4/verify-paths.php` with those captures in `/tmp/ww-captures/`. Also run `php phase4/verify-home-archive.php` and `php phase4/verify-transform.php`.
3. Open the captured HTML in a local browser with the branch CSS. Label every shot as a captured-HTML fixture.

## Pass conditions

- Start Here still contains the bio sentence "I'm a full-time chef on the 6:30 AM to 3:00 PM shift" once, and not the second "I build WrayWest around a 6:30 AM to 3:00 PM kitchen shift."
- The six Start Here article links are still in the page, including `/blog/why-side-hustles-fail/`.
- The path quiz is reachable at `#sh-quiz` with the existing heading "Which path fits you right now?"
- The Start Here deck element (`ww-deck-promo`) is byte-identical. `$17.77` count is unchanged.
- Engine pillars `#p1` through `#p6` and the nav links stay. The six "Picture this" paragraphs stay inside closed `details`.
- The Engine sentence "Use Make.com or similar for cross-platform automation" appears once. No new n8n or Zapier sentence is added.
- The Engine offer section (`fw-worksheet`) is byte-identical, including its form.
- JSON-LD blocks on both pages, including the Engine FAQPage block, are byte-identical.
- Homepage headline remains "A second income, built after the shift." Receipts remain `$810.95` and `$17.77` with their source lines. The homepage deck element is byte-identical.
- The 42 post captures still pass the receipt and JSON-LD regression, and the Start Here and Engine transforms do not change those posts.
- The 15 style ids are still absent from the live homepage capture.

## Tools, Resources, About, Contact

Add these URLs to the read-only fetch list:

- `https://wraywestblog.com/tools/`
- `https://wraywestblog.com/resources/`
- `https://wraywestblog.com/about/`
- `https://wraywestblog.com/contact/`

Also run `php phase4/verify-four-pages.php`.

- Tools labels: ConvertKit, Notion, and Repurpose.io say Researched. Make.com stays Researched. OLSP System says Affiliate receipts. Wise and Klarna stay Used hands-on. Used-hands-on count on the captured tools page goes from 11 to 7.
- `$17.77` count on Tools is unchanged. Tools forms and JSON-LD blocks are byte-identical.
- No `rel="sponsored"` value is added or removed on Tools. The Resources Kit link stays `rel="noopener"` with no sponsored token. Resources sponsored-token count is unchanged.
- Resources still contains "I have not used Make hands-on". No new n8n sentence is added.
- About and Contact still reference `dwayne-kitchen-line.webp`. Their HTML is not rewritten by the tools or post transform. The contact form bytes match the capture.
- Homepage headline is still "A second income, built after the shift."
- The 42 post captures still pass the receipt and JSON-LD regression, and the tools transform does not change those posts.
- The 15 style ids are still absent. The manifest md5 map is still the October capture.

## Calculators

Add these URLs to the read-only fetch list:

- `https://wraywestblog.com/tools/quit-9-to-5-calculator/`
- `https://wraywestblog.com/tools/freelance-rate-calculator/`
- `https://wraywestblog.com/tools/lead-magnet-builder/`

Also run `php phase4/verify-calculators.php`.

- Quit inputs stay `value="75000"`, `28`, `500`, `5`, `30`, `3500`, `6`, and `0`. The helper no longer says "typical for $75k". It still says "Default 28%".
- Freelance inputs stay `60000`, `25`, `6`, `6000`, and `30`. The helper no longer says "a common starting point".
- Every `<input>` tag and every `<script>` block on the three pages is byte-identical to the capture. JSON-LD blocks are byte-identical. The Lead Magnet Builder page HTML is byte-identical.
- Running the captured script on the before and after files, without submitting a form, produces the same result text for the cases in `phase4/calculator-results.json`.
- The CFPB sentence and the "3% = passive" growth scale stay. They were not given a new source.

## UniConsent, read-only

The captured pages load `https://cmp.uniconsent.com/v2/cmp.js`. The license attribute is already in the public HTML. There is no consent stylesheet or banner markup in this repo. The dashboard belongs to Dwayne.

axe WCAG 2 A/AA reports one serious `color-contrast` node on Tools, Resources, About, and Contact, before and after this unit. The failing control is a UniConsent button (`unic-bar-sheet__btn`, classes include `bg-gray-300` and `text-gray-800`). Measured colors on that node are foreground `#ffffff` and background `#4a90e2`, contrast 3.29, below the 4.5:1 expected for 14px normal text. The separate "Opt out personal data" button measured `#333333` on `#e2e8f0` (10.25) and is not the failing node. At 390 the same panel covers the first content under the hero, including the Tools calculators and the Contact portrait.

A repo CSS rule could recolor that button. It would not accept consent and it would not change the UniConsent dashboard. It is not a safe fix to ship from this change-set: the widget's own stylesheet can beat it, and shrinking or hiding the panel would change whether the choice is seen. No consent CSS was added. No consent button was clicked. This is not a clean accessibility pass.

## Fail conditions

- Any new public URL, offer, or receipt figure.
- A deleted disclosure on a post that still has `rel="sponsored"`.
- A changed calculator formula, legal page, or consent panel.
- A manifest edit that makes the bundle apply without a fresh inline-block capture.
