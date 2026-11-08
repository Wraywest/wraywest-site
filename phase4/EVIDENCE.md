# Evidence for copying screenshots

Local Chrome renders of public HTML captured read-only on 2026-11-08. Not a WordPress boot. Not production.

## Homepage

- Before, 1440: `/opt/cursor/artifacts/phase4-home-before-1440.png`
- After, 1440: `/opt/cursor/artifacts/phase4-home-after-1440.png`
- Before, 390: `/opt/cursor/artifacts/phase4-home-before-390.png`
- After, 390: `/opt/cursor/artifacts/phase4-home-after-390.png`
- Receipts, before: `/opt/cursor/artifacts/phase4-home-before-receipts.png`
- Receipts, after: `/opt/cursor/artifacts/phase4-home-after-receipts.png`
- n8n teaser after the correction: `/opt/cursor/artifacts/phase4-home-after-n8n-teaser.png`
- Money and Content Strategy columns: `/opt/cursor/artifacts/phase4-home-after-money-module.png`

## Blog index

- Before, 1440: `/opt/cursor/artifacts/phase4-blog-before-1440.png`
- After, 1440: `/opt/cursor/artifacts/phase4-blog-after-1440.png`
- Before, 390 top: `/opt/cursor/artifacts/phase4-blog-before-390-top.png`
- After, 390 top (chips wrap): `/opt/cursor/artifacts/phase4-blog-after-390-top.png`

## Money category

- After, 390 chips: `/opt/cursor/artifacts/phase4-money-after-390-chips.png`
- Before, 390: `/opt/cursor/artifacts/phase4-money-before-390.png`
- After, 1440: `/opt/cursor/artifacts/phase4-money-after-1440.png`

Numbers and axe output: `phase4/home-archive-results.json`. Teaser before/after sentences: `phase4/home-teaser-manifest.json`.

The UniConsent panel in these shots is the panel already in the captured HTML. It was not edited.

## Start Here and Second Income Engine

Captured-HTML fixtures, not a WordPress boot.

- Start Here before, 1440: `/opt/cursor/artifacts/phase4-start-before-1440.png`
- Start Here after, 1440: `/opt/cursor/artifacts/phase4-start-after-1440.png`
- Start Here before, 390: `/opt/cursor/artifacts/phase4-start-before-390.png`
- Start Here after, 390: `/opt/cursor/artifacts/phase4-start-after-390.png`
- Engine before, 1440: `/opt/cursor/artifacts/phase4-engine-before-1440.png`
- Engine after, 1440: `/opt/cursor/artifacts/phase4-engine-after-1440.png`
- Engine before, 390: `/opt/cursor/artifacts/phase4-engine-before-390.png`
- Engine after, 390: `/opt/cursor/artifacts/phase4-engine-after-390.png`

Numbers: `phase4/paths-results.json`. Acceptance checklist: `phase4/acceptance-criteria.md`.

## Tools, Resources, About, Contact

Captured-HTML fixtures, not a WordPress boot.

- Tools before, 1440: `/opt/cursor/artifacts/phase4-tools-before-1440.png`
- Tools after, 1440: `/opt/cursor/artifacts/phase4-tools-after-1440.png`
- Tools before, 390: `/opt/cursor/artifacts/phase4-tools-before-390.png`
- Tools after, 390: `/opt/cursor/artifacts/phase4-tools-after-390.png`
- ConvertKit label: `/opt/cursor/artifacts/phase4-tools-kit-label.png`
- Notion label: `/opt/cursor/artifacts/phase4-tools-notion-label.png`
- Repurpose.io label: `/opt/cursor/artifacts/phase4-tools-repurpose-label.png`
- Make.com label: `/opt/cursor/artifacts/phase4-tools-make-label.png`
- Resources before, 1440: `/opt/cursor/artifacts/phase4-resources-before-1440.png`
- Resources after, 1440: `/opt/cursor/artifacts/phase4-resources-after-1440.png`
- Resources before, 390: `/opt/cursor/artifacts/phase4-resources-before-390.png`
- Resources after, 390: `/opt/cursor/artifacts/phase4-resources-after-390.png`
- About before, 1440: `/opt/cursor/artifacts/phase4-about-before-1440.png`
- About after, 1440: `/opt/cursor/artifacts/phase4-about-after-1440.png`
- About before, 390: `/opt/cursor/artifacts/phase4-about-before-390.png`
- About after, 390: `/opt/cursor/artifacts/phase4-about-after-390.png`
- Contact before, 1440: `/opt/cursor/artifacts/phase4-contact-before-1440.png`
- Contact after, 1440: `/opt/cursor/artifacts/phase4-contact-after-1440.png`
- Contact before, 390: `/opt/cursor/artifacts/phase4-contact-before-390.png`
- Contact after, 390: `/opt/cursor/artifacts/phase4-contact-after-390.png`

Numbers: `phase4/four-pages-results.json`.

The hero band on these four pages already ended with a 48px gap at 1440 and a 28px gap under 700px, plus a 1px `#E6CFA8` hairline. This unit keeps that gap. The dek is ink `#3D3732`. Contrast of that dek on the band stops `#F7E3C4` and `#FBEBD0` measured 9.35 and 9.99. About H1 `#1A1A1A` on `#F7E3C4` measured 13.87. No horizontal overflow at 1440 or 390. Focus targets at 390 with reduced motion: Tools "Try the calculator" 44px, Resources "Read the framework" 44px (it was 22px before), About "Contact me" 46px, Contact "Send message" 44px. Each showed a 2px solid outline. Reduced motion sets the tools row transition to `none`. This unit does not add `transition: all`.

Grain, keylight, and the older `transition: all` rules stay in the inherited stylesheet. They are existing brand decisions, not removed here.

ConvertKit, Notion, and Repurpose.io render as Researched. Make.com stays Researched. Systeme.io, Hostinger Horizons, Wise, Robinhood, Klarna, OLSP System, LeadsLeap, and DataForSEO stay Used hands-on. No `rel` value was added or removed. The Resources Kit link stays `rel="noopener"`. Resources still says "I have not used Make hands-on" and "I have never used Zapier". Body sentences that claim first-hand Notion, Kit, Repurpose, ClickDesigns, Wise, or Klarna use were not rewritten.

The About portrait URL `dwayne-kitchen-line.webp` is still in the About and Contact HTML. Contact and Tools form markup is byte-identical to the capture. Resources, About, and Contact HTML are not rewritten. The About chef story is left for a later body pass.

UniConsent remains an unresolved blocker. See the acceptance note. These shots are not a clean accessibility pass.
