# PHP Git Deploy

A lightweight, self-hosted Git deployment system written in PHP. Deploy your code via webhooks or manual triggers and keep any web project in sync.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-blue.svg)](https://php.net)

## Features

- **GitHub webhook support** - Automatic deployments on push
- **Manual deployment triggers** - Deploy via URL with token
- **SSH key authentication** - Secure private repository access
- **Incremental updates** - Smart git pull for existing repositories
- **Configurable commands** - Run composer, artisan, or any post-deploy scripts
- **Plain text logging** - Perfect for webhook logs and debugging
- **No dependencies** - Pure PHP, works on shared hosting

## Directory Structure

```
your-website/
├── index.html              # Your website files
├── assets/
├── .git/                   # Git repository
└── _deploy/                # Deployment system
    ├── webhook.php         # Main webhook handler
    ├── config.php          # Configuration file
    ├── tools/              # Helper tools
    │   ├── system-check.php
    │   ├── ssh-keygen.php
    │   ├── setup-composer.php
    │   └── find-php.php
    ├── keys/               # SSH keys (create this)
    │   ├── deploy_key
    │   └── deploy_key.pub
    └── logs/               # Logs (auto-created)
        └── deploy.log
```

## Quick Start

1. **Download** the latest release or clone this repository
2. **Upload** the `_deploy/` folder to your web root
3. **Configure** your repository in `_deploy/config.php`
4. **Generate SSH keys** using `_deploy/tools/ssh-keygen.php`
5. **Add public key** to your GitHub repository
6. **Test deployment** by visiting the webhook URL

## Configuration

Edit `_deploy/config.php`:

```php
<?php
return [
    'repository' => [
        'url' => 'git@github.com:username/repo.git',
        'branch' => 'main',
    ],

    'deployment' => [
        'target_directory' => '..',
        'post_commands' => [
            __DIR__ . '/composer.phar install --no-dev --optimize-autoloader --no-interaction',
        ],
    ],

    'security' => [
        'deploy_token' => 'your-secret-token-here',
    ],

    'ssh' => [
        'key_path' => './keys/deploy_key',
    ],

    'logging' => [
        'log_file' => './logs/deploy.log',
    ],
];
```

## Usage

### GitHub Webhook Setup
1. Go to your repository **Settings → Webhooks → Add webhook**
2. Set **Payload URL**: `https://yoursite.com/_deploy/webhook.php`
3. Set **Content type**: `application/x-www-form-urlencoded` (tested) or `application/json`
4. Set **Secret**: Use the same value as `deploy_token` in your config
5. Select **Just the push event**
6. Click **Add webhook**

### Manual Deployment (via URL)

For manual deployments or integration with systems that don't support GitHub's webhooks, you can trigger a deployment by visiting a special URL. This method is less secure than the default webhook validation and must be explicitly enabled.

1.  In `_deploy/config.php`, set `allow_token_deployment` to `true`.
2.  Make sure you have set a strong, secret `deploy_token`.
3.  Trigger a deployment by visiting:
    `https://yoursite.com/_deploy/webhook.php?token=your-secure-token-here`

> **Security Warning**: Anyone with this URL can trigger a deployment. Use a long, random token and only enable this feature if you understand the risks.

## Tools

- **`tools/system-check.php`** - Check server compatibility
- **`tools/ssh-keygen.php`** - Generate SSH keys for GitHub
- **`tools/setup-composer.php`** - Download and setup composer.phar
- **`tools/find-php.php`** - Find correct PHP executable path

## Requirements

- PHP 7.0+
- Git (command line access)
- SSH or HTTPS repository access
- Web server with PHP support

## Security

- Token-based authentication
- SSH key support for private repositories
- No credentials stored in web-accessible files
- Configurable deployment directory

## Why PHP Git Deploy?

- **Lightweight** - No complex setup or dependencies
- **Shared hosting friendly** - Works on basic PHP hosting
- **Self-contained** - All tools included
- **GitHub integrated** - Perfect webhook support
- **Developer friendly** - Clean logs and error messages

## Documentation

See individual tool files for detailed usage instructions.

## Contributing

Contributions welcome! Please feel free to submit a Pull Request.

## License

MIT License - see the [LICENSE](LICENSE) file for details.

## Author

Created by **Jakub Pelák**

- GitHub: [@lemmon](https://github.com/lemmon)
- Repository: [php-git-deploy](https://github.com/lemmon/php-git-deploy)

---

Deploy your code with confidence.
