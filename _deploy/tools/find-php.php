<?php
/**
 * PHP Git Deploy - PHP Path Finder
 *
 * Helps find the correct PHP executable path
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP GIT DEPLOY - PHP PATH FINDER ===\n";
echo "Date: " . date('c') . "\n\n";

echo "=== CURRENT PHP INFO ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "PHP Binary (PHP_BINARY): " . (defined('PHP_BINARY') ? PHP_BINARY : 'Not defined') . "\n";
echo "PHP Executable (PHP_BINDIR): " . (defined('PHP_BINDIR') ? PHP_BINDIR : 'Not defined') . "\n";

// Try to get PHP executable path from phpinfo
ob_start();
phpinfo(INFO_GENERAL);
$phpinfo = ob_get_clean();

// Extract PHP executable path from phpinfo
if (preg_match('/Server API.*?(\S+)/', $phpinfo, $matches)) {
    echo "Server API: " . $matches[1] . "\n";
}

// Look for PHP executable in phpinfo
if (preg_match('/PHP executable.*?(\S+php\S*)/', $phpinfo, $matches)) {
    echo "PHP Executable (from phpinfo): " . $matches[1] . "\n";
}

echo "\n=== SMART PHP PATH DETECTION ===\n";
$detectedPaths = [];

// Method 1: PHP_BINARY constant (most reliable)
if (defined('PHP_BINARY') && !empty(PHP_BINARY)) {
    echo "✅ PHP_BINARY constant: " . PHP_BINARY . "\n";
    $detectedPaths[] = PHP_BINARY;
}

// Method 2: Construct from PHP_BINDIR
if (defined('PHP_BINDIR') && !empty(PHP_BINDIR)) {
    $phpFromBinDir = PHP_BINDIR . '/php';
    echo "✅ Constructed from PHP_BINDIR: " . $phpFromBinDir . "\n";
    $detectedPaths[] = $phpFromBinDir;
}

// Method 3: Use current PHP version to find versioned binaries
$majorMinor = implode('.', array_slice(explode('.', PHP_VERSION), 0, 2));
$versionedPaths = [
    "/usr/bin/php{$majorMinor}",
    "/usr/local/bin/php{$majorMinor}",
    "/opt/cpanel/ea-php" . str_replace('.', '', $majorMinor) . "/root/usr/bin/php"
];

foreach ($versionedPaths as $path) {
    echo "🔍 Version-based path: {$path}\n";
    $detectedPaths[] = $path;
}

echo "\n";

echo "=== TESTING PHP PATHS ===\n";

// Combine detected paths with common fallbacks
$allPhpPaths = array_merge($detectedPaths, [
    'php',
    '/usr/bin/php',
    '/usr/local/bin/php',
    '/opt/php/bin/php'
]);

// Add version-specific paths for multiple PHP versions
$phpVersions = ['8.0', '8.1', '8.2', '8.3', '8.4', '7.4'];
foreach ($phpVersions as $version) {
    $versionNum = str_replace('.', '', $version);
    $allPhpPaths[] = "/usr/bin/php{$version}";
    $allPhpPaths[] = "/usr/local/bin/php{$version}";
    $allPhpPaths[] = "/opt/cpanel/ea-php{$versionNum}/root/usr/bin/php";
    $allPhpPaths[] = "/opt/plesk/php/{$version}/bin/php";
    $allPhpPaths[] = "/opt/remi/php{$versionNum}/root/usr/bin/php";
}

// Remove duplicates and test each path
$allPhpPaths = array_unique($allPhpPaths);
$workingPaths = [];

foreach ($allPhpPaths as $phpPath) {
    echo "Testing: {$phpPath} ... ";

    $output = [];
    $result = 0;
    exec("{$phpPath} --version 2>&1", $output, $result);

    if ($result === 0 && !empty($output)) {
        // Check if it's CLI (not php-fpm)
        $versionLine = $output[0];
        if (strpos($versionLine, 'cli') !== false && strpos($phpPath, 'php-fpm') === false) {
            echo "✅ WORKS (CLI)\n";
            echo "  Version: {$versionLine}\n";
            $workingPaths[] = $phpPath;
        } else {
            echo "⚠️ Works but not CLI\n";
            echo "  Version: {$versionLine}\n";
        }
    } else {
        echo "❌ Failed\n";
    }
}

echo "\n=== ENVIRONMENT VARIABLES ===\n";
$envVars = ['PATH', 'PHP_PATH', 'PHPRC'];
foreach ($envVars as $var) {
    $value = getenv($var);
    if ($value) {
        echo "{$var}: {$value}\n";
    } else {
        echo "{$var}: Not set\n";
    }
}

echo "\n=== WHICH COMMAND TEST ===\n";
$whichCommands = ['which php', 'whereis php', 'type php'];
foreach ($whichCommands as $cmd) {
    echo "Running: {$cmd}\n";
    $output = [];
    $result = 0;
    exec("{$cmd} 2>&1", $output, $result);

    if ($result === 0 && !empty($output)) {
        foreach ($output as $line) {
            echo "  Result: {$line}\n";
        }
    } else {
        echo "  No result\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
if (!empty($workingPaths)) {
    echo "✅ Found working PHP CLI paths:\n";
    foreach ($workingPaths as $path) {
        echo "  - {$path}\n";
    }
    echo "\nTo use the best path, update your ../config.php:\n";
    echo "[php]\n";
    echo "php_path = {$workingPaths[0]}\n";
    echo "\nOr leave php_path empty for auto-detection.\n";
} else {
    echo "❌ No working PHP CLI paths found.\n";
    echo "\nOptions:\n";
    echo "1. Contact your hosting provider for the correct PHP CLI path\n";
    echo "2. Check your hosting control panel for PHP settings\n";
    echo "3. Look for PHP version selector in cPanel/hosting panel\n";
}

echo "\n=== HOSTING PROVIDER HINTS ===\n";
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
echo "Server Software: {$serverSoftware}\n";

if (strpos($serverSoftware, 'Apache') !== false) {
    echo "Detected: Apache server\n";
    echo "Common paths: /usr/bin/php, /usr/local/bin/php\n";
}

if (file_exists('/usr/local/cpanel')) {
    echo "Detected: cPanel hosting\n";
    echo "Common paths: /opt/cpanel/ea-php*/root/usr/bin/php\n";
    echo "Check: cPanel → Software → Select PHP Version\n";
}

echo "\n=== AUTO-DETECTION INFO ===\n";
echo "PHP Git Deploy has built-in PHP path auto-detection.\n";
echo "If you leave 'php_path' empty in config.php, it will:\n";
echo "1. Try PHP_BINARY constant\n";
echo "2. Try version-specific paths\n";
echo "3. Fall back to common locations\n";
echo "4. Filter out non-CLI binaries (like php-fpm)\n";

echo "\n=== CURRENT WORKING DIRECTORY ===\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script directory: " . __DIR__ . "\n";

echo "\n=== FIND COMPLETE ===\n";
echo "Check completed at: " . date('c') . "\n";
