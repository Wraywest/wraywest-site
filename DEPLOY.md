# Deploying WrayWest custom files (no browser login)

## Hard rules
1. **Never log in to the Hostinger login page (auth.hostinger.com) or hPanel through a browser. Bots, never.** All work goes through the Hostinger MCP/API connectors.
2. **On any Cloudflare error 1015, stop.** Do not retry. Wait **at least one hour**, longer is better, before another request to that host. Retries extend the block.
3. Dwayne approves every deploy, cache clear, activation and delete.
4. No secrets in the repo or in chat.

## One-time setup (already done / Dwayne)
- The Hostinger account's Git SSH key exists. Bots read it with `hosting_git_ssh-public-key` (username `u421506720`).
- Dwayne adds that public key to this repo: GitHub > Settings > Deploy keys > Add, with **Allow write access unchecked**.

## Release flow
1. A bot edits files on a branch and opens a PR. Dwayne reviews and merges into `main`.
2. With Dwayne's OK, run the **stage** step (pull the repo into `wp-content/ww-site`, which is not served):
   ```
   connector: user-Hostinger-hosting  execute
   operation: hosting_git_deploy-website-repository
   params: {"username":"u421506720","domain":"wraywestblog.com",
            "repository_url":"git@github.com:Wraywest/wraywest-site.git",
            "branch":"main","directory":"wp-content/ww-site"}
   ```
   The target directory must be empty, missing, or already hold this repo and branch. Check first with
   `hosting_files_list-website-and-directories` (directory `wp-content/ww-site`) and `hosting_git_list-website-repositories`.
   Use the directory string exactly as `hosting_git_list-website-repositories` returns it on later pulls.
   Check `is_success` and the log.
3. **Go live.** The Hostinger API has no file copy, so the files still have to move from `wp-content/ww-site/` into their live folders. The options, in order of preference:
   - a) **Loader mu-plugin (once, then automatic):** a one-time install of `wp-content/mu-plugins/ww-site-loader.php`. It `require`s `WP_CONTENT_DIR.'/ww-site/mu-plugins/*.php'`, and the CSS URL is pointed at `/wp-content/ww-site/uploads/ww-css/` (the `.htaccess` must then allow `*.css`, `*.woff2` and `*.json`). The live copies in `mu-plugins/` must be removed at the same moment, or functions get declared twice and cause a fatal error. Needs a separate approved change.
   - b) **SSH/SFTP** (after Dwayne enables SSH in hPanel, see RATE-LIMIT-FIX.md): `rsync` from `ww-site/` into the live folders.
   - c) Whichever file-write path the bots use today, with this repo's contents as the source.
4. With Dwayne's OK: `hosting_cache_clear-website` (username, domain). Verify the pages.

## Rollback
- **Git level:** revert the commit on `main` (a `git revert` PR), then run step 2 again. Tracked files are reset to `main`.
- **Live files:** the previous versions are kept as `wp-content/ww-backups/<file>.bak-<date>` before every go-live, and in the workspace backups (e.g. `design-audit/ship/item-W009/backup-20261020/server/`). Restore them over the live file, then clear the cache.
- **Stage directory:** deleting `wp-content/ww-site` does not affect the live site while the loader (option a) is off.
- **Stop auto-deploys:** `hosting_git_delete-auto-deployment-settings`.
