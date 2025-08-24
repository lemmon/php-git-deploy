<?php
/**
 * PHP Git Deploy - SSH Key Generator
 *
 * Simple SSH key generation tool
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Configuration
$keyName = isset($_POST['key_name']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['key_name']) : 'deploy_key';
$keyType = isset($_POST['key_type']) ? $_POST['key_type'] : 'ed25519';

if (empty($keyName)) $keyName = 'deploy_key';

function logMessage($message, $level = 'INFO') {
    $colors = [
        'INFO' => '#d4edda',
        'ERROR' => '#f8d7da',
        'SUCCESS' => '#d1ecf1',
        'WARNING' => '#fff3cd'
    ];

    $color = $colors[$level] ?? '#f8f9fa';
    echo "<div style='background: {$color}; padding: 8px; margin: 5px 0; border-radius: 4px; font-family: monospace;'>";
    echo "[" . date('H:i:s') . "] [{$level}] " . htmlspecialchars($message);
    echo "</div>\n";

    if (ob_get_level()) ob_flush();
    flush();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Git Deploy - SSH Key Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
        .generate-btn { background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .generate-btn:hover { background: #218838; }
        .key-display { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 2px solid #28a745; }
        .key-content { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #dee2e6; }
        .public-key { width: 100%; height: 80px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; }
        .copy-btn { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .copy-btn:hover { background: #0056b3; }
        .instructions { background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
    </style>
    <script>
        function copyToClipboard() {
            const textarea = document.querySelector('.public-key');
            textarea.select();
            document.execCommand('copy');

            const btn = document.querySelector('.copy-btn');
            btn.textContent = '✅ Copied!';
            btn.style.background = '#28a745';

            setTimeout(() => {
                btn.textContent = '📋 Copy Public Key';
                btn.style.background = '#007bff';
            }, 2000);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>🔑 SSH Key Generator</h1>
        <p>Generate SSH keys for secure Git repository access.</p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>

            <?php
            logMessage("Starting SSH key generation...");
            logMessage("Key name: {$keyName}");
            logMessage("Key type: {$keyType}");

            // Create keys directory if it doesn't exist
            $keysDir = '../keys';
            if (!is_dir($keysDir)) {
                if (mkdir($keysDir, 0755, true)) {
                    logMessage("Created keys directory: {$keysDir}");
                } else {
                    logMessage("Failed to create keys directory", 'ERROR');
                    exit;
                }
            }

            $keyFile = $keysDir . "/{$keyName}";

            // Backup existing keys
            if (file_exists($keyFile) || file_exists($keyFile . '.pub')) {
                $backup = '.backup-' . date('Ymd-His');
                if (file_exists($keyFile)) {
                    rename($keyFile, $keyFile . $backup);
                    logMessage("Backed up existing private key", 'WARNING');
                }
                if (file_exists($keyFile . '.pub')) {
                    rename($keyFile . '.pub', $keyFile . '.pub' . $backup);
                    logMessage("Backed up existing public key", 'WARNING');
                }
            }

            // Generate key using proven method
            if ($keyType === 'ed25519') {
                $command = "ssh-keygen -t ed25519 -C \"php-git-deploy-{$keyName}\" -f {$keyFile} -N \"\"";
            } else {
                $command = "ssh-keygen -t rsa -b {$keyType} -C \"php-git-deploy-{$keyName}\" -f {$keyFile} -N \"\"";
            }

            logMessage("Executing: ssh-keygen command");
            exec($command, $output, $result);

            if ($result === 0) {
                logMessage("SUCCESS: SSH keys generated!", 'SUCCESS');

                // Set permissions
                chmod($keyFile, 0600);
                chmod($keyFile . '.pub', 0644);
                logMessage("Set file permissions (600 for private, 644 for public)");

                // Display the keys
                if (file_exists($keyFile) && file_exists($keyFile . '.pub')) {
                    $publicKey = file_get_contents($keyFile . '.pub');
                    $privateKeySize = filesize($keyFile);
                    $publicKeySize = filesize($keyFile . '.pub');

                    echo "<div class='key-display'>";
                    echo "<h3>✅ SSH Keys Generated Successfully!</h3>";

                    echo "<div class='key-content'>";
                    echo "<h4>🔒 Private Key: <code>{$keyName}</code></h4>";
                    echo "<p><strong>Size:</strong> {$privateKeySize} bytes</p>";
                    echo "<p><strong>Path:</strong> " . realpath($keyFile) . "</p>";
                    echo "<p style='color: #dc3545; font-weight: bold;'>⚠️ Keep this file secure! Never share or expose it publicly.</p>";
                    echo "</div>";

                    echo "<div class='key-content'>";
                    echo "<h4>🔓 Public Key: <code>{$keyName}.pub</code></h4>";
                    echo "<p><strong>Size:</strong> {$publicKeySize} bytes</p>";
                    echo "<p><strong>Content (copy this to GitHub):</strong></p>";
                    echo "<textarea readonly class='public-key'>" . htmlspecialchars(trim($publicKey)) . "</textarea>";
                    echo "<button onclick='copyToClipboard()' class='copy-btn'>📋 Copy Public Key</button>";
                    echo "</div>";
                    echo "</div>";

                    echo "<div class='instructions'>";
                    echo "<h3>🚀 Next Steps</h3>";
                    echo "<ol>";
                    echo "<li><strong>Add to GitHub:</strong> Copy the public key above and add it to GitHub → Settings → SSH and GPG keys</li>";
                    echo "<li><strong>Update deploy config:</strong> The key is already configured in <code>../config.php</code></li>";
                    echo "<li><strong>Test connection:</strong> <code>ssh -i {$keyFile} -T git@github.com</code></li>";
                    echo "</ol>";
                    echo "</div>";
                }

            } else {
                logMessage("ERROR: SSH key generation failed", 'ERROR');
                if (!empty($output)) {
                    logMessage("Error details: " . implode(", ", $output), 'ERROR');
                }
            }
            ?>

            <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="color: #007bff;">← Generate Another Key</a></p>

        <?php else: ?>

            <form method="post">
                <div class="form-group">
                    <label for="key_name">Key Name:</label>
                    <input type="text" id="key_name" name="key_name" value="<?php echo htmlspecialchars($keyName); ?>" placeholder="deploy_key">
                    <small>Files will be saved as: keys/[key_name] and keys/[key_name].pub</small>
                </div>

                <div class="form-group">
                    <label for="key_type">Key Type:</label>
                    <select id="key_type" name="key_type">
                        <option value="ed25519" selected>ed25519 (recommended - modern, fast, secure)</option>
                        <option value="2048">RSA 2048 (compatible)</option>
                        <option value="4096">RSA 4096 (compatible, more secure)</option>
                    </select>
                </div>

                <button type="submit" class="generate-btn">🔑 Generate SSH Keys</button>
            </form>

            <div style="background: #e7f3ff; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <h4>ℹ️ About SSH Keys</h4>
                <p>This will generate a key pair in the <code>../keys/</code> directory:</p>
                <ul>
                    <li><strong>Private Key:</strong> Keep this secret and secure (permissions: 600)</li>
                    <li><strong>Public Key:</strong> Add this to GitHub/GitLab (safe to share)</li>
                </ul>
            </div>

        <?php endif; ?>

        <hr>
        <p><small>Generated at: <?php echo date('c'); ?></small></p>
        <p><small><a href="../webhook.php">← Back to Deployment</a> | <a href="system-check.php">🔧 System Check</a></small></p>
    </div>
</body>
</html>
