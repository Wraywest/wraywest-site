# CI quick wins (staged)

The bot token cannot write to `.github/workflows/` (it lacks the `workflow` scope), so the three workflows are staged here.

To activate, move them into place (GitHub web editor or locally):

    git mv ci-staging/workflows/link-check.yml .github/workflows/link-check.yml
    git mv ci-staging/workflows/lighthouse.yml .github/workflows/lighthouse.yml
    git mv ci-staging/workflows/pa11y.yml .github/workflows/pa11y.yml

Then run each once from the Actions tab (Run workflow). Config files used by them live at the repo root: `lychee.toml`, `lighthouserc.json`, `lighthouse-budget.json`, `.pa11yci.json`. None of these files are served by WordPress or touched by the Hostinger stage step's go-live.
