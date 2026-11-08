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

## Fail conditions

- Any new public URL, offer, or receipt figure.
- A deleted disclosure on a post that still has `rel="sponsored"`.
- A changed calculator formula, legal page, or consent panel.
- A manifest edit that makes the bundle apply without a fresh inline-block capture.
