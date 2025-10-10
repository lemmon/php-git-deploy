<?php
/**
 * PHP Git Deploy - System Check Tool
 *
 * Checks server compatibility and requirements
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Git Deploy - System Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔧 PHP Git Deploy - System Check</h1>
    <p>Testing if this server can run PHP Git Deploy...</p>

    <?php
    /**
     * Test if a command exists and is executable
     */
    function testCommand($command, $name) {
        echo "<div class='test'>";
        echo "<h3>Testing {$name}</h3>";

        // Test if command exists
        $whichCommand = "which {$command} 2>/dev/null";
        $output = [];
        $returnCode = 0;

        exec($whichCommand, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            echo "<div class='status fail'>❌ {$name} is NOT available</div>";
            echo "<p>Command '{$command}' not found in PATH</p>";
            echo "</div>";
            return false;
        }

        $commandPath = $output[0];
        echo "<div class='status pass'>✅ {$name} is available</div>";
        echo "<p><strong>Location:</strong> {$commandPath}</p>";

        // Test version
        $versionCommand = "{$command} --version 2>&1";
        $versionOutput = [];
        exec($versionCommand, $versionOutput, $versionReturnCode);

        if ($versionReturnCode === 0 && !empty($versionOutput)) {
            echo "<p><strong>Version:</strong></p>";
            echo "<pre>" . htmlspecialchars(implode("\n", $versionOutput)) . "</pre>";
        } else {
            echo "<p><strong>Version:</strong> Could not determine version</p>";
        }

        echo "</div>";
        return true;
    }

    /**
     * Test PHP functions that might be disabled
     */
    function testPHPFunctions() {
        echo "<div class='test'>";
        echo "<h3>Testing PHP Functions</h3>";

        $requiredFunctions = ['exec', 'shell_exec', 'proc_open', 'system'];
        $allAvailable = true;

        foreach ($requiredFunctions as $function) {
            if (function_exists($function)) {
                echo "<div class='status pass'>✅ {$function}() is available</div>";
            } else {
                echo "<div class='status fail'>❌ {$function}() is disabled</div>";
                $allAvailable = false;
            }
        }

        if ($allAvailable) {
            echo "<p>All required PHP functions are available for command execution.</p>";
        } else {
            echo "<p><strong>Warning:</strong> Some PHP functions are disabled. The deployment script may not work properly.</p>";
        }

        echo "</div>";
        return $allAvailable;
    }

    /**
     * Test PHP CLI binary availability
     */
    function testPHPBinary() {
        echo "<div class='test'>";
        echo "<h3>Testing PHP CLI Binary</h3>";

        $phpPaths = [];
        $workingPaths = [];

        // Method 1: PHP_BINARY constant
        if (defined('PHP_BINARY') && !empty(PHP_BINARY)) {
            $phpPaths[] = ['path' => PHP_BINARY, 'source' => 'PHP_BINARY constant'];
        }

        // Method 2: PHP_BINDIR + /php
        if (defined('PHP_BINDIR') && !empty(PHP_BINDIR)) {
            $phpPaths[] = ['path' => PHP_BINDIR . '/php', 'source' => 'PHP_BINDIR'];
        }

        // Method 3: Version-specific paths
        $majorMinor = implode('.', array_slice(explode('.', PHP_VERSION), 0, 2));
        $versionNum = str_replace('.', '', $majorMinor);

        $versionedPaths = [
            "/usr/bin/php{$majorMinor}" => "Version-specific ({$majorMinor})",
            "/usr/local/bin/php{$majorMinor}" => "Local version-specific ({$majorMinor})",
            "/opt/cpanel/ea-php{$versionNum}/root/usr/bin/php" => "cPanel EA-PHP{$versionNum}",
            "/opt/remi/php{$versionNum}/root/usr/bin/php" => "Remi PHP{$versionNum}",
            "/opt/plesk/php/{$majorMinor}/bin/php" => "Plesk PHP {$majorMinor}"
        ];

        foreach ($versionedPaths as $path => $source) {
            $phpPaths[] = ['path' => $path, 'source' => $source];
        }

        // Method 4: Common fallback paths
        $fallbackPaths = [
            '/usr/bin/php' => 'System PHP',
            '/usr/local/bin/php' => 'Local PHP',
            'php' => 'PATH php'
        ];

        foreach ($fallbackPaths as $path => $source) {
            $phpPaths[] = ['path' => $path, 'source' => $source];
        }

        // Test each PHP path
        foreach ($phpPaths as $phpInfo) {
            $path = $phpInfo['path'];
            $source = $phpInfo['source'];

            // Test if the binary exists and works
            $testCommand = "{$path} --version 2>&1";
            $output = [];
            $returnCode = 0;

            exec($testCommand, $output, $returnCode);

            if ($returnCode === 0 && !empty($output)) {
                $version = $output[0] ?? '';

                // Check if it's CLI (not php-fpm or php-cgi)
                $isCli = strpos($version, 'cli') !== false;
                $isFpm = strpos($path, 'php-fpm') !== false || strpos($version, 'php-fpm') !== false;
                $isCgi = strpos($path, 'php-cgi') !== false || strpos($version, 'php-cgi') !== false;

                if ($isCli && !$isFpm && !$isCgi) {
                    echo "<div class='status pass'>✅ {$path}</div>";
                    echo "<p><strong>Source:</strong> {$source}</p>";
                    echo "<p><strong>Version:</strong> {$version}</p>";
                    $workingPaths[] = $path;
                } else {
                    echo "<div class='status fail'>⚠️ {$path}</div>";
                    echo "<p><strong>Source:</strong> {$source}</p>";
                    echo "<p><strong>Issue:</strong> ";
                    if ($isFpm) echo "This is php-fpm (not CLI)";
                    elseif ($isCgi) echo "This is php-cgi (not CLI)";
                    elseif (!$isCli) echo "Not detected as CLI version";
                    echo "</p>";
                    echo "<p><strong>Version:</strong> {$version}</p>";
                }
            } else {
                // Don't show failed attempts for cleaner output, unless it's an important one
                if (in_array($source, ['PHP_BINARY constant', 'PHP_BINDIR', 'System PHP', 'PATH php'])) {
                    echo "<div class='status fail'>❌ {$path}</div>";
                    echo "<p><strong>Source:</strong> {$source}</p>";
                    echo "<p><strong>Issue:</strong> Not found or not executable</p>";
                }
            }
        }

        // Summary
        if (!empty($workingPaths)) {
            echo "<div class='status pass'>🎉 Found " . count($workingPaths) . " working PHP CLI binary/binaries</div>";
            echo "<p><strong>Recommended for use:</strong> <code>" . $workingPaths[0] . "</code></p>";
            if (count($workingPaths) > 1) {
                echo "<p><strong>Other options:</strong></p>";
                echo "<ul>";
                for ($i = 1; $i < count($workingPaths); $i++) {
                    echo "<li><code>{$workingPaths[$i]}</code></li>";
                }
                echo "</ul>";
            }
            echo "<p><strong>Usage in post_commands:</strong></p>";
            echo "<pre>post_commands[] = \"{$workingPaths[0]} {SCRIPT_DIR}/composer.phar install --no-dev\"</pre>";
        } else {
            echo "<div class='status fail'>❌ No working PHP CLI binary found</div>";
            echo "<p>This means you cannot use PHP-based post-deployment commands.</p>";
            echo "<p>Contact your hosting provider or use the <code>find-php.php</code> tool for more detailed detection.</p>";
        }

        echo "</div>";
        return !empty($workingPaths);
    }

    /**
     * Test directory permissions
     */
    function testPermissions() {
        echo "<div class='test'>";
        echo "<h3>Testing Directory Permissions</h3>";

        $testDir = '../test-deploy-permissions';

        // Try to create a directory
        if (mkdir($testDir, 0755, true)) {
            echo "<div class='status pass'>✅ Can create directories</div>";

            // Try to write a file
            $testFile = $testDir . '/test.txt';
            if (file_put_contents($testFile, 'test') !== false) {
                echo "<div class='status pass'>✅ Can write files</div>";
                unlink($testFile);
            } else {
                echo "<div class='status fail'>❌ Cannot write files</div>";
            }

            rmdir($testDir);
        } else {
            echo "<div class='status fail'>❌ Cannot create directories</div>";
            echo "<p>The deployment script needs write permissions to create deployment directories.</p>";
        }

        echo "</div>";
    }

    /**
     * Display server information
     */
    function showServerInfo() {
        echo "<div class='test info'>";
        echo "<h3>Server Information</h3>";
        echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
        echo "<p><strong>Operating System:</strong> " . PHP_OS . "</p>";
        echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
        echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
        echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

        // Show PATH environment variable
        $path = getenv('PATH');
        if ($path) {
            echo "<p><strong>PATH:</strong></p>";
            echo "<pre>" . htmlspecialchars($path) . "</pre>";
        }

        echo "</div>";
    }

    // Run all tests
    showServerInfo();

    $phpFunctionsOK = testPHPFunctions();
    $phpBinaryAvailable = testPHPBinary();
    $gitAvailable = testCommand('git', 'Git');
    $composerAvailable = testCommand('composer', 'Composer');
    $nodeAvailable = testCommand('node', 'Node.js');
    $npmAvailable = testCommand('npm', 'npm');

    testPermissions();

    // Final summary
    echo "<div class='test'>";
    echo "<h3>📋 Summary</h3>";

    if ($phpFunctionsOK && $gitAvailable) {
        echo "<div class='status pass'>🎉 SUCCESS: This server can run PHP Git Deploy!</div>";
        echo "<p>You can safely use the deployment system on this server.</p>";

        if (!$composerAvailable) {
            echo "<p><strong>Note:</strong> Composer is not available system-wide, but you can use <code>tools/setup-composer.php</code> to install composer.phar locally.</p>";
        }

        if (!$phpBinaryAvailable) {
            echo "<p><strong>Note:</strong> No PHP CLI binary found - you won't be able to run PHP-based post-deployment commands. Use <code>tools/find-php.php</code> for detailed PHP binary detection.</p>";
        }

        if (!$nodeAvailable) {
            echo "<p><strong>Note:</strong> Node.js is not available - you won't be able to run Node.js-based post-deployment commands.</p>";
        }

        if (!$npmAvailable) {
            echo "<p><strong>Note:</strong> npm is not available - you won't be able to install Node.js dependencies or run npm scripts.</p>";
        }
    } else {
        echo "<div class='status fail'>⚠️ ISSUES FOUND: This server may not support PHP Git Deploy</div>";
        echo "<p>Missing components:</p>";
        echo "<ul>";
        if (!$phpFunctionsOK) echo "<li>Required PHP functions are disabled</li>";
        if (!$gitAvailable) echo "<li>Git is not available</li>";
        echo "</ul>";
        echo "<p>Contact your hosting provider to install missing components or enable required PHP functions.</p>";
    }

    echo "</div>";
    ?>

    <hr>
    <p><small>Check completed at: <?php echo date('c'); ?></small></p>
    <p><small><a href="../webhook.php">← Back to Deployment</a> | <a href="https://github.com/lemmon/php-git-deploy">📖 Documentation</a></small></p>
</body>
</html>
