# PHP Git Deploy - Technical Documentation

## Project Overview

**PHP Git Deploy** is a lightweight, self-hosted Git deployment system written in PHP. It enables automatic deployment of Git repositories via GitHub webhooks with minimal server requirements.

## Architecture

### Core Components

1. **Webhook Handler** (`_deploy/webhook.php`) - Main entry point for GitHub webhooks
2. **Configuration** (`_deploy/config.php`) - PHP-based configuration file
3. **Utility Tools** (`_deploy/tools/`) - Helper scripts for setup and maintenance

### File Structure

```
webdeploy/
├── _deploy/                    # Deployment system directory
│   ├── webhook.php            # Main webhook handler
│   ├── config.php             # Configuration file
│   ├── README.md              # Quick setup guide
│   └── tools/                 # Utility tools
│       ├── system-check.php   # Server compatibility check
│       ├── ssh-keygen.php     # SSH key generator
│       ├── setup-composer.php # Composer installer
│       ├── find-php.php       # PHP executable finder
│       └── README.md          # Tools documentation
├── README.md                  # Main project documentation
├── ROADMAP.md                 # Development roadmap
├── LICENSE                    # MIT License
└── .editorconfig             # Editor configuration
```

## Technical Specifications

### Requirements

- **PHP**: 7.4+ (uses `hash_equals()`, array syntax)
- **Git**: Any recent version
- **Web Server**: Apache, Nginx, or any PHP-capable server
- **Functions**: `exec()`, `file_get_contents()`, `hash_hmac()`

### Security Model

#### Webhook Authentication
- **HMAC-SHA256 signature validation** using GitHub's `X-Hub-Signature-256` header
- **Timing-safe comparison** via `hash_equals()` to prevent timing attacks
- **Event filtering** - only processes `push` events, handles `ping` events

#### Configuration Security
- **PHP-based config** - not readable via HTTP (returns PHP code)
- **No plain-text secrets** in web-accessible locations
- **SSH key authentication** for private repositories

### Event Handling

#### Supported GitHub Events
- **`push`**: Triggers deployment with full signature validation
- **`ping`**: Returns "pong" without signature validation (webhook testing)
- **Other events**: Gracefully ignored with HTTP 200 response

#### HTTP Response Codes
- **200**: Successful deployment or handled event
- **400**: Missing required headers or payload
- **403**: Invalid signature
- **500**: Configuration or system errors

### Deployment Process

#### Initial Clone
1. Check if target directory exists and is empty
2. If empty: `git clone -b {branch} {repo} {target}`
3. If not empty: `git init` + `git remote add` + `git pull`

#### Incremental Updates
1. Execute `git pull origin {branch}` in target directory
2. Handle SSH authentication if configured
3. Run post-deployment commands

#### Path Resolution
- **Relative to absolute conversion** for SSH keys and target directories
- **Parent directory support** (`..`) for deploying from subdirectory to parent
- **Script directory detection** via `__DIR__`

### Configuration Schema

```php
[
    'repository' => [
        'url' => 'string',      // Git repository URL (SSH or HTTPS)
        'branch' => 'string',   // Target branch (default: 'main')
    ],
    'deployment' => [
        'target_directory' => 'string',  // Deployment target (relative or absolute)
        'post_commands' => [             // Commands to run after deployment
            'string', // Command with {DIR} placeholder support
        ],
    ],
    'ssh' => [
        'key_path' => 'string', // Path to SSH private key (empty = disabled)
    ],
    'security' => [
        'deploy_token' => 'string',      // Webhook secret or manual deployment token
        'allow_token_deployment' => 'bool', // Opt-in for manual deployment (default: false)
    ],
    'logging' => [
        'log_file' => 'string', // Log file path (empty = disabled)
    ],
]
```

### Logging System

#### Log Format
- **ISO 8601 timestamps** (`date('c')`) with timezone information
- **Structured format**: `[timestamp] message`
- **Dual output**: Console (for webhook response) + file (if configured)

#### Log Levels
- **INFO**: Normal operation messages
- **WARNING**: Non-fatal issues (failed post-commands)
- **ERROR**: Fatal errors requiring intervention
- **SUCCESS**: Successful completion messages

### Command Execution

#### Security Features
- **Working directory isolation** - commands run in target directory
- **Output capture** - all command output logged with `> ` prefix
- **Exit code checking** - non-zero exits trigger error handling
- **Shell escaping** - relies on proper command construction

#### Placeholder System
- **`{DIR}`**: Replaced with absolute target directory path
- **Direct PHP evaluation**: Uses `__DIR__` in config for script directory

### SSH Key Management

#### Key Path Resolution
- **Relative paths** converted to absolute using script directory
- **File existence validation** before Git operations
- **Permission checking** (logs current permissions)

#### Git SSH Configuration
- **`GIT_SSH_COMMAND`** environment variable for SSH options
- **StrictHostKeyChecking=no** for automated deployment
- **Per-operation SSH key specification**

## Error Handling

### Common Error Scenarios
1. **Missing configuration** - HTTP 500, clear error message
2. **Invalid webhook signature** - HTTP 403, security log entry
3. **Git operation failures** - Logged with full command output
4. **SSH key issues** - Path resolution and permission diagnostics
5. **Post-command failures** - Warnings, continue with remaining commands

### Diagnostic Information
- **Script directory** and **target directory** paths
- **SSH key** existence and permissions
- **Git repository** status and remote configuration
- **Command execution** with full output capture

## Performance Characteristics

### Resource Usage
- **Memory**: Minimal - no large data structures
- **CPU**: Low - mostly I/O bound operations
- **Disk**: Log files grow over time (rotation recommended)
- **Network**: Depends on repository size and frequency

### Scalability
- **Single-threaded** - one deployment at a time
- **File locking** on log writes for concurrent webhook safety
- **Stateless** - no persistent connections or sessions

## Integration Points

### GitHub Webhook Configuration
- **Payload URL**: `https://domain.com/_deploy/webhook.php`
- **Content Type**: `application/json` or `application/x-www-form-urlencoded`
- **Secret**: Must match `deploy_token` in configuration
- **Events**: Select "Just the push event" or "Send me everything"

### Server Configuration
- **Document root**: Can be anywhere PHP can execute
- **File permissions**: Write access for target directory and logs
- **PHP execution**: CLI access for post-deployment commands
- **Git access**: SSH key or HTTPS authentication

## Development Notes

### Code Style
- **PSR-compatible** PHP formatting (4-space indentation)
- **Minimal dependencies** - uses only PHP standard library
- **Error-first design** - explicit error handling throughout
- **Logging-centric** - all operations logged for debugging

### Extension Points
- **Post-deployment commands** - flexible command execution system
- **Event handling** - easily extensible for additional GitHub events
- **Configuration format** - PHP arrays allow complex structures
- **Tool ecosystem** - modular utility scripts in `tools/` directory

This documentation provides comprehensive technical details for understanding, maintaining, and extending the PHP Git Deploy system.
tion provides comprehensive technical details for understanding, maintaining, and extending the PHP Git Deploy system.
