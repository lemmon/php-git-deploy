<?php
/**
 * PHP Git Deploy - Simple Webhook Handler
 *
 * @author Jakub Pelák
 * @link https://github.com/lemmon/php-git-deploy
 */

// Set content type for plain text output
header('Content-Type: text/plain; charset=utf-8');

// Logging function
function logMessage($message) {
    global $config;

    $timestamp = date('c');
    $logEntry = "[{$timestamp}] {$message}";

    // Always output to browser/webhook
    echo $logEntry . "\n";

    // Log to file if configured
    $logFile = $config['logging']['log_file'] ?? '';
    if (!empty($logFile)) {
        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Load configuration
$configFile = './config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    logMessage("ERROR: Configuration file not found: {$configFile}");
    exit(1);
}

$config = require $configFile;
if (!is_array($config)) {
    http_response_code(500);
    logMessage("ERROR: Invalid configuration file format");
    exit(1);
}

// Execute command function
function executeCommand($command, $workingDir = null) {
    logMessage("Executing: {$command}");

    $output = [];
    $result = 0;

    if ($workingDir && is_dir($workingDir)) {
        $oldDir = getcwd();
        chdir($workingDir);
        exec($command . ' 2>&1', $output, $result);
        chdir($oldDir);
    } else {
        exec($command . ' 2>&1', $output, $result);
    }

    if (!empty($output)) {
        foreach ($output as $line) {
            logMessage("  > {$line}");
        }
    }

    if ($result !== 0) {
        logMessage("ERROR: Command failed with exit code: {$result}");
        return false;
    }

    return true;
}

// Get configuration values
$repoUrl = $config['repository']['url'] ?? '';
$branch = $config['repository']['branch'] ?? 'main';
$targetDir = $config['deployment']['target_directory'] ?? './deployment';
$sshKeyPath = $config['ssh']['key_path'] ?? '';

if (empty($repoUrl)) {
    logMessage("ERROR: Repository URL not configured");
    exit(1);
}

$secret = $config['security']['deploy_token'] ?? '';
if (empty($secret)) {
    logMessage("ERROR: Deploy token not configured");
    exit(1);
}

// Check GitHub event type
$githubEvent = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

if (empty($githubEvent)) {
    http_response_code(400);
    logMessage("ERROR: Missing GitHub event header");
    exit(1);
}

logMessage("GitHub event: {$githubEvent}");

// Handle different event types
if ($githubEvent === 'ping') {
    // Ping event - no signature validation needed
    logMessage("Ping event received - webhook is working!");
    http_response_code(200);
    exit(0);
} elseif ($githubEvent !== 'push') {
    // Unsupported event type
    http_response_code(200); // GitHub expects 200 for unsupported events
    logMessage("Unsupported event type: {$githubEvent}");
    exit(0);
}

// Push event - validate signature
$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($signature)) {
    http_response_code(400);
    logMessage("ERROR: Missing signature for push event");
    exit(1);
}

// Compute HMAC SHA256 hash
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// Securely compare signatures
if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    logMessage("ERROR: Invalid signature");
    exit(1);
}

// Push event with valid signature - proceed with deployment
logMessage("Push event signature validated successfully");

// Convert relative paths to absolute
$scriptDir = __DIR__;
logMessage("Script directory: {$scriptDir}");

// Handle target directory path
if ($targetDir === '..') {
    $targetDir = dirname($scriptDir);
} elseif (substr($targetDir, 0, 1) !== '/') {
    $targetDir = $scriptDir . '/' . ltrim($targetDir, './');
}

logMessage("Target directory: {$targetDir}");
logMessage("Repository: {$repoUrl}");
logMessage("Branch: {$branch}");

// Handle SSH key path
if (!empty($sshKeyPath)) {
    if (substr($sshKeyPath, 0, 1) !== '/') {
        $sshKeyPath = $scriptDir . '/' . ltrim($sshKeyPath, './');
    }

    if (!file_exists($sshKeyPath)) {
        logMessage("ERROR: SSH key not found: {$sshKeyPath}");
        exit(1);
    }

    logMessage("Using SSH key: {$sshKeyPath}");
}

// Perform Git operations
$isInitialClone = !is_dir($targetDir) || !is_dir($targetDir . '/.git');

if ($isInitialClone) {
    logMessage("Performing initial clone...");

    $gitCommand = "git clone -b {$branch}";

    if (!empty($sshKeyPath)) {
        $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
        $gitCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$gitCommand}";
    }

    $gitCommand .= " {$repoUrl} {$targetDir}";

    if (!executeCommand($gitCommand)) {
        logMessage("ERROR: Git clone failed");
        exit(1);
    }

    logMessage("Initial clone completed");
} else {
    logMessage("Performing incremental update...");

    $gitCommand = "git pull origin {$branch}";

    if (!empty($sshKeyPath)) {
        $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
        $gitCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$gitCommand}";
    }

    if (!executeCommand($gitCommand, $targetDir)) {
        logMessage("ERROR: Git pull failed");
        exit(1);
    }

    logMessage("Incremental update completed");
}

// Run post-deployment commands
$postCommands = $config['deployment']['post_commands'] ?? [];
if (!empty($postCommands)) {
    logMessage("Running post-deployment commands...");

    foreach ($postCommands as $command) {
        // Replace placeholders
        $command = str_replace('{DIR}', $targetDir, $command);

        if (!executeCommand($command, $targetDir)) {
            logMessage("WARNING: Post-deployment command failed: {$command}");
            // Continue with other commands
        }
    }

    logMessage("Post-deployment commands completed");
}

logMessage("✅ Deployment completed successfully!");
