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

## 🔗 Webhook URL

```
https://yoursite.com/_deploy/webhook.php?token=YOUR_TOKEN
```

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
