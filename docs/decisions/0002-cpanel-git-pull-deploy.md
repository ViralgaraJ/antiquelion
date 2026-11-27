# 0002: Deploy by cPanel git pull into public_html

Status: accepted (drafted from code, needs review)

## Context
The LankaHost cPanel account has no SSH or Terminal. FTP uploads from the developer's Mac were slow and unreliable (IPv6/NAT64 passive-mode time-outs).

## Decision
- `public_html` is a git checkout of `main`.
- Deploy = cPanel → Git Version Control → Pull or Deploy → Update from Remote.
- `config.php`, `config.live.php` and `ftp_config.php` are git-ignored. Local helpers live in the git-ignored `_local_tools/`.

## Consequences
- Every tracked file is publicly served. Dev-only files need blocking (ANTIQUE-7).
- Credentials committed before 2026-10-18 are still in git history. They have been rotated (ANTIQUE-2).
- A tracked file edited on the server blocks the pull, and cPanel shows a misleading "could not contact the remote repository" error.
- Roll back with a revert commit. A server reset isn't possible.

Evidence: README "Deploy", `.gitignore`, commit `9762ba0`.
