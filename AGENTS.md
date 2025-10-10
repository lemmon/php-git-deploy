# Repository Guidelines

## Project Structure & Module Organization
PHP Git Deploy keeps the deployment engine inside `_deploy/`. `webhook.php` receives GitHub events, `config.php` stores environment-specific settings, and `tools/` holds setup scripts (`system-check.php`, `ssh-keygen.php`, `setup-composer.php`, `find-php.php`). Provision `_deploy/keys/` for SSH credentials and `_deploy/logs/` for runtime logs; both are ignored by git and created on demand. Root-level docs (`README.md`, `TECHNICAL.md`, `ROADMAP.md`) describe architecture and roadmap—update them when altering behaviour.

## Build, Test, and Development Commands
- `php _deploy/tools/system-check.php` — Verify server permissions, PHP extensions, and git availability before deploying.
- `php -l _deploy/webhook.php` — Run a lint pass after code changes; repeat for any modified PHP file.
- `php _deploy/tools/setup-composer.php` — Download `composer.phar` when your post-deploy commands depend on Composer.
- `php _deploy/tools/find-php.php` — Output the absolute PHP binary path to reuse in cron jobs or remote hooks.

## Coding Style & Naming Conventions
Follow PSR-12-style PHP formatting: 4-space indents, brace-on-next-line for control structures, and snake_case globals with camelCase functions as seen in `webhook.php`. Keep lines under 120 characters, prefer explicit arrays, and always return early on validation failures. Markdown files use 4-space indents per `.editorconfig`; wrap prose at logical points and retain intentional trailing spaces in tables.

## Testing Guidelines
There is no automated suite yet; rely on targeted checks. Lint every touched PHP file and run `system-check.php` against the target host. To simulate a webhook, start a local PHP server (`php -S 127.0.0.1:9000 -t .`) and send a signed payload with `curl` by mirroring GitHub headers; capture the response in `_deploy/logs/deploy.log`. Document manual test steps in your PR.

## Commit & Pull Request Guidelines
Commit messages follow Conventional Commits (`feat:`, `docs:`, `fix:`) per repository history; keep the subject under 72 characters and describe scope in the body when needed. Each PR should explain the change, link related issues, call out config migrations, and note any security implications. Include before/after logs or screenshots when UI or webhook responses change, and tick off manual test evidence.

## Security & Configuration Tips
Never commit real tokens or keys—use `_deploy/config.example.php` as a template and provide redacted snippets in docs. Rotate `deploy_token` values before sharing test URLs, and ensure `_deploy/keys/` and `_deploy/logs/` remain outside web root indexing. When adding new post-deploy commands, run them with absolute paths and log outputs for traceability.
