# _deploy Directory

This directory contains the PHP Git Deploy system files.

## 📁 Structure

```
_deploy/
├── webhook.php         # Main webhook handler
├── config.php          # Configuration file
├── README.md           # This file
├── tools/              # Helper utilities
├── keys/               # SSH keys (create this directory)
└── logs/               # Deployment logs (auto-created)
```

## ⚡ Quick Setup

1. **Configure repository** in `config.php`
2. **Set secure token** in `config.php`
3. **Create keys directory**: `mkdir keys`
4. **Generate SSH keys** using `tools/ssh-keygen.php`
5. **Add public key** to your GitHub repository

## 🔗 GitHub Webhook Setup

**Webhook URL**: `https://yoursite.com/_deploy/webhook.php`

Configure in GitHub:
1. Repository **Settings → Webhooks → Add webhook**
2. **Content type**: `application/x-www-form-urlencoded`
3. **Secret**: Set to match your `deploy_token` in config
4. **Events**: Just the push event

> **Note**: Manual URL access is disabled for security. Only GitHub webhooks with proper signature validation are supported.

## 🛠️ Tools

- `tools/system-check.php` - Check server compatibility
- `tools/ssh-keygen.php` - Generate SSH keys
- `tools/setup-composer.php` - Setup composer.phar
- `tools/find-php.php` - Find PHP executable

## 📋 Configuration

Edit `config.php` to match your repository and requirements.

## 🔒 Security

- Keep your `deploy_token` secret
- SSH keys in `keys/` directory are ignored by git
- Logs in `logs/` directory are ignored by git

---

For detailed documentation, see the main [README.md](../README.md) file.
