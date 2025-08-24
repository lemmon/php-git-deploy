<?php
/**
 * PHP Git Deploy - Composer Setup Tool
 *
 * Downloads composer.phar and makes it executable
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP GIT DEPLOY - COMPOSER SETUP ===\n";
echo "Date: " . date('c') . "\n\n";

$composerPath = '../composer.phar';

// Check if composer.phar already exists
if (file_exists($composerPath)) {
    echo "✅ composer.phar already exists\n";
    echo "Size: " . filesize($composerPath) . " bytes\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($composerPath)), -4) . "\n";
    echo "Modified: " . date('c', filemtime($composerPath)) . "\n\n";

    // Test if it's executable
    if (is_executable($composerPath)) {
        echo "✅ composer.phar is already executable\n";

        // Test if it works
        exec($composerPath . ' --version 2>&1', $output, $result);
        if ($result === 0 && !empty($output)) {
            echo "✅ composer.phar works: " . $output[0] . "\n";
            echo "\n=== SETUP COMPLETE ===\n";
            echo "Your composer.phar is ready to use!\n";
            echo "It's configured in your deploy config as: {SCRIPT_DIR}/composer.phar\n";
            exit;
        } else {
            echo "❌ composer.phar exists but doesn't work properly\n";
            echo "Downloading fresh copy...\n\n";
        }
    } else {
        echo "⚠️ composer.phar exists but is not executable\n";
        echo "Making it executable...\n";

        if (chmod($composerPath, 0755)) {
            echo "✅ Made composer.phar executable\n";

            // Test if it works now
            exec($composerPath . ' --version 2>&1', $output, $result);
            if ($result === 0 && !empty($output)) {
                echo "✅ composer.phar now works: " . $output[0] . "\n";
                echo "\n=== SETUP COMPLETE ===\n";
                echo "Your composer.phar is ready to use!\n";
                echo "It's configured in your deploy config as: {SCRIPT_DIR}/composer.phar\n";
                exit;
            }
        } else {
            echo "❌ Failed to make composer.phar executable\n";
        }
        echo "Downloading fresh copy...\n\n";
    }
}

echo "=== DOWNLOADING COMPOSER ===\n";
echo "Downloading from getcomposer.org...\n";

// Download composer installer
$installerUrl = 'https://getcomposer.org/installer';
echo "Fetching installer from: {$installerUrl}\n";

$installer = file_get_contents($installerUrl);
if ($installer === false) {
    echo "❌ Failed to download composer installer\n";
    echo "Please check your internet connection or download manually:\n";
    echo "curl -sS https://getcomposer.org/installer | php\n";
    exit(1);
}

echo "✅ Downloaded installer (" . strlen($installer) . " bytes)\n";

// Save installer temporarily
$installerPath = '../composer-installer.php';
if (file_put_contents($installerPath, $installer) === false) {
    echo "❌ Failed to save composer installer\n";
    exit(1);
}

echo "✅ Saved installer temporarily\n";

// Run installer
echo "Running composer installer...\n";
$installCommand = "php {$installerPath} --install-dir=.. --filename=composer.phar";

$output = [];
$result = 0;
exec($installCommand . ' 2>&1', $output, $result);

// Clean up installer
unlink($installerPath);

if ($result !== 0) {
    echo "❌ Composer installation failed\n";
    if (!empty($output)) {
        echo "Error output:\n";
        foreach ($output as $line) {
            echo "  {$line}\n";
        }
    }
    exit(1);
}

echo "✅ Composer installer completed\n";

// Verify composer was installed
if (!file_exists($composerPath)) {
    echo "❌ composer.phar not found after installation\n";
    exit(1);
}

echo "✅ composer.phar created (" . filesize($composerPath) . " bytes)\n";

// Make it executable
echo "Making composer.phar executable...\n";
if (chmod($composerPath, 0755)) {
    echo "✅ Set executable permissions (755)\n";
} else {
    echo "⚠️ Failed to set executable permissions\n";
    echo "You may need to run: chmod +x ../composer.phar\n";
}

// Test the installation
echo "Testing composer.phar...\n";
exec($composerPath . ' --version 2>&1', $testOutput, $testResult);

if ($testResult === 0 && !empty($testOutput)) {
    echo "✅ SUCCESS: " . $testOutput[0] . "\n";
} else {
    echo "❌ composer.phar test failed\n";
    if (!empty($testOutput)) {
        foreach ($testOutput as $line) {
            echo "  {$line}\n";
        }
    }
}

echo "\n=== SETUP COMPLETE ===\n";
echo "Current directory: " . getcwd() . "\n";
echo "Composer path: " . realpath($composerPath) . "\n";
echo "File size: " . filesize($composerPath) . " bytes\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($composerPath)), -4) . "\n";
echo "Executable: " . (is_executable($composerPath) ? 'Yes' : 'No') . "\n";

echo "\n=== CONFIGURATION ===\n";
echo "Your deploy config is already set up to use:\n";
echo "post_commands[] = \"{SCRIPT_DIR}/composer.phar install --no-dev --optimize-autoloader --no-interaction\"\n";

echo "\n=== USAGE ===\n";
echo "You can now run deployments that will automatically:\n";
echo "1. Clone/update your repository\n";
echo "2. Run composer install with the downloaded composer.phar\n";
echo "3. Execute any other post-deployment commands\n";

echo "\n=== MANUAL ALTERNATIVE ===\n";
echo "If this script doesn't work, download manually:\n";
echo "cd _deploy\n";
echo "curl -sS https://getcomposer.org/installer | php\n";
echo "chmod +x composer.phar\n";

echo "\nSetup completed at: " . date('c') . "\n";
