# PHP Git Deploy – Technical Documentation

This document explains the moving parts of PHP Git Deploy so contributors can extend or troubleshoot the system with confidence.

## Architecture Overview
- **`_deploy/webhook.php`**: Receives GitHub events, validates signatures, and drives deployments.
- **`_deploy/config.php`**: PHP array configuration that defines repository details, security settings, and post-deploy commands.
- **`_deploy/tools/`**: Self-contained utilities for environment checks, SSH key generation, Composer setup, and PHP discovery.
- Supporting directories (`keys/`, `logs/`) are created at runtime and excluded from version control.

Refer to `README.md` for the full directory tree used in production installs.

## Runtime Requirements
- PHP 7.4+ with `exec`, `hash_hmac`, `hash_equals`, and JSON support enabled.
- Git CLI accessible to the web server user.
- File-system permissions that allow the deployment target, `keys/`, and `logs/` directories to be created and written.

## Request Processing Flow
1. **Entry**: All requests terminate in `webhook.php`.
2. **Authentication**:
   - If a `token` query parameter is present and `allow_token_deployment` is true, the raw token is compared using `hash_equals`.
   - Otherwise the handler expects GitHub webhook headers. `ping` events return immediately; only `push` events proceed.
3. **Signature Validation**: Payloads are read from `php://input`, signed with HMAC-SHA256, and compared to `X-Hub-Signature-256`.
4. **Dispatch**: After authentication, deployment is queued immediately; no background workers are used, so responses stream logs as the process runs.

## Deployment Pipeline
1. **Preparation**: Calculate absolute paths from configuration values and confirm SSH key availability when configured.
2. **Repository Sync**:
   - If the target directory is empty, run `git clone -b <branch> <url> <target>`.
   - Otherwise execute `git pull origin <branch>` inside the target.
3. **Post Commands**: Iterate through `deployment.post_commands`, substituting `{DIR}` with the target directory. Each command is executed synchronously; on failure the handler logs the exit code and stops execution.
4. **Logging**: Every step emits ISO 8601 timestamps to STDOUT and, if configured, to a log file.

## Configuration Reference
```php
return [
    'repository' => [
        'url' => 'git@github.com:username/repo.git',
        'branch' => 'main',
    ],
    'deployment' => [
        'target_directory' => '..',
        'post_commands' => [
            __DIR__ . '/composer.phar install --no-dev',
        ],
    ],
    'ssh' => [
        'key_path' => './keys/deploy_key',
    ],
    'security' => [
        'deploy_token' => 'secret',
        'allow_token_deployment' => false,
    ],
    'logging' => [
        'log_file' => './logs/deploy.log',
    ],
];
```

- Relative paths are resolved from the `_deploy/` directory.
- Empty strings disable optional features (for example, omitting `log_file` turns off file logging).
- Commands should use absolute binaries or rely on placeholders to avoid PATH ambiguity.

## Logging
- Format: `[ISO-8601 timestamp] message`.
- Destination: always streamed to the HTTP response; optionally appended to `logging.log_file`.
- Prefixes: command output is logged with `> `, errors use `ERROR:` to simplify grepping.

## SSH Key Handling
- When `ssh.key_path` is set, the handler exports `GIT_SSH_COMMAND` with `-i <key>` and `StrictHostKeyChecking=no`.
- The path is canonicalised before use; missing keys abort deployment with a clear log message.
- Keys are expected inside `_deploy/keys/` with filesystem permissions managed by the operator.

## Error Handling and Diagnostics
- Missing configuration or unset secrets return HTTP 500 with explanatory logs.
- Signature mismatches yield HTTP 403 responses.
- Git command failures bubble up with captured stdout/stderr to assist debugging.
- All exceptions fall back to a generic error message plus stack context written to the log stream.

## Extension Points
- **Event handling**: The current switch statement can be extended to handle additional GitHub events (e.g., `release`).
- **Post-processing**: Additional hooks can be inserted after git operations to support cache invalidation or notifications.
- **Tooling**: `tools/` is intentionally decoupled, making it easy to add environment scripts without touching the handler.

This document should give maintainers enough context to evolve PHP Git Deploy, audit security behaviour, and troubleshoot deployments quickly.
