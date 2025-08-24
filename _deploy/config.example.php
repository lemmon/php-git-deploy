<?php
/**
 * PHP Git Deploy - Configuration
 *
 * This file is secure from web access (returns PHP code, not config data)
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

return [
    'repository' => [
        'url' => 'git@github.com:username/your-website.git',
        'branch' => 'main',
    ],

    'ssh' => [
        // Path to SSH private key (leave empty to disable SSH key authentication)
        'key_path' => './keys/deploy_key',
    ],

    'deployment' => [
        // Target directory for deployment
        'target_directory' => '..',

        // Commands to run after git pull
        // Use {DIR} placeholder for deployment directory
        'post_commands' => [
            // Option 1: Use executable composer.phar (recommended)
            __DIR__ . '/composer.phar install --no-dev --optimize-autoloader --no-interaction',

            // Option 2: Use system composer
            // 'composer install --no-dev --optimize-autoloader --no-interaction',

            // Option 3: Use specific PHP path (find with tools/find-php.php)
            // '/usr/bin/php ' . __DIR__ . '/composer.phar install --no-dev --optimize-autoloader --no-interaction',
            // '/usr/bin/php artisan migrate --force',
            // '/usr/bin/php artisan config:cache',
        ],
    ],

    'security' => [
        // Secret token for webhook authentication
        // For GitHub webhooks: Set this as your webhook secret in GitHub settings
        // For manual triggers: Use this token in URL (?token=your-secret-token)
        // ^ Currently not implemented. Perhaps find something more secure?
        // Change this to something secure!
        'deploy_token' => 'your-secret-token-here',
    ],

    'logging' => [
        // Path to log file (leave empty to disable file logging)
        'log_file' => './logs/deploy.log',
    ],
];
