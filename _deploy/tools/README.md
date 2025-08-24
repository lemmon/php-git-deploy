# PHP Git Deploy - Tools

Helper utilities for PHP Git Deploy system.

## 🛠️ Available Tools

### `system-check.php`
**Check server compatibility and requirements**

- Tests Git availability
- Tests PHP functions (exec, shell_exec, etc.)
- Checks directory permissions
- Shows server information
- Validates deployment requirements

**Usage:** Visit in browser for interactive check

---

### `ssh-keygen.php`
**Generate SSH keys for GitHub authentication**

- Creates ed25519 or RSA key pairs
- Saves keys to `../keys/` directory
- Sets proper file permissions automatically
- Provides copy-to-clipboard functionality
- Shows step-by-step GitHub setup instructions

**Usage:** Visit in browser, fill form, follow instructions

---

### `setup-composer.php`
**Download and setup composer.phar**

- Downloads latest Composer from getcomposer.org
- Makes composer.phar executable
- Tests installation
- Handles existing installations
- Provides manual setup instructions

**Usage:** Visit in browser for automatic setup

---

### `find-php.php`
**Find correct PHP executable path**

- Detects PHP CLI path automatically
- Tests multiple common locations
- Filters out non-CLI binaries (php-fpm, php-cgi)
- Shows version information
- Provides hosting-specific hints

**Usage:** Visit in browser to see all available PHP paths

## 🔗 Quick Links

- `system-check.php` - Start here to verify your server
- `ssh-keygen.php` - Generate keys for private repositories
- `setup-composer.php` - Set up Composer for dependency management
- `find-php.php` - Troubleshoot PHP path issues

## 📋 Typical Setup Flow

1. **System Check** - Verify server compatibility
2. **SSH Keys** - Generate keys for repository access
3. **Composer Setup** - Install Composer if needed
4. **PHP Path** - Find correct PHP executable if auto-detection fails

## 🔧 Configuration

All tools work with the main `../config.php` file and automatically:
- Create necessary directories (`../keys/`, `../logs/`)
- Set proper file permissions
- Update configuration paths
- Provide next-step instructions

## 💡 Tips

- Run `system-check.php` first to identify any issues
- Tools create directories automatically when needed
- All tools provide clear error messages and solutions
- Use browser developer tools to see any JavaScript errors

---

**Back to:** [Main Documentation](../../README.md) | [Deploy Directory](../README.md)
