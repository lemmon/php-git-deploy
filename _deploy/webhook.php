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

function updateGitSubmodules($targetDir, $sshKeyPath) {
    $submoduleCommand = 'git submodule update --init --recursive';

    if (!empty($sshKeyPath)) {
        $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
        $submoduleCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$submoduleCommand}";
    }

    if (!executeCommand($submoduleCommand, $targetDir)) {
        return false;
    }

    logMessage("Git submodules updated");
    return true;
}

// Get configuration values
$repoUrl = $config['repository']['url'] ?? '';
$branch = $config['repository']['branch'] ?? 'main';
$targetDir = $config['deployment']['target_directory'] ?? './deployment';
$sshKeyPath = $config['ssh']['key_path'] ?? '';

if (empty($repoUrl)) {
    http_response_code(500);
    logMessage("ERROR: Repository URL not configured");
    exit(1);
}

$secret = $config['security']['deploy_token'] ?? '';
if (empty($secret)) {
    http_response_code(500);
    logMessage("ERROR: Deploy token not configured");
    exit(1);
}

// Security check: Ensure the default token has been changed
if ($secret === 'your-secret-token-here') {
    http_response_code(500);
    logMessage("SECURITY ERROR: You are using the default deploy token. Please change it in your config.php file.");
    exit(1);
}

// Handle different authentication methods
$allowTokenDeployment = $config['security']['allow_token_deployment'] ?? false;
$tokenFromUrl = $_GET['token'] ?? '';

if ($allowTokenDeployment && !empty($tokenFromUrl)) {
    // Token-based deployment
    logMessage("Attempting deployment with URL token");

    if (!hash_equals($secret, $tokenFromUrl)) {
        http_response_code(403);
        logMessage("ERROR: Invalid deployment token");
        exit(1);
    }

    logMessage("URL token validated successfully");
} else {
    // GitHub webhook signature validation
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

    logMessage("Push event signature validated successfully");
}

// If we reach here, authentication was successful
logMessage("Authentication successful, proceeding with deployment...");

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
        http_response_code(500);
        logMessage("ERROR: SSH key not found: {$sshKeyPath}");
        exit(1);
    }

    logMessage("Using SSH key: {$sshKeyPath}");
}

// Perform Git operations
$isInitialClone = !is_dir($targetDir) || !is_dir($targetDir . '/.git');

if ($isInitialClone) {
    logMessage("Performing initial setup...");

    // Check if directory is empty (except for our scripts)
    $files = glob($targetDir . '/*');
    $isEmpty = empty($files);

    if ($isEmpty) {
        // Directory is empty, use git clone
        logMessage("Directory is empty, using git clone");

        $gitCommand = "git clone --recurse-submodules -b {$branch}";

        if (!empty($sshKeyPath)) {
            $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
            $gitCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$gitCommand}";
        }

        $gitCommand .= " {$repoUrl} {$targetDir}";

        if (!executeCommand($gitCommand)) {
            http_response_code(500);
            logMessage("ERROR: Git clone failed");
            exit(1);
        }

        if (!updateGitSubmodules($targetDir, $sshKeyPath)) {
            http_response_code(500);
            logMessage("ERROR: Git submodule update failed");
            exit(1);
        }
    } else {
        // Directory is not empty, use git init + remote + pull
        logMessage("Directory is not empty, using git init + remote + pull");

        // Initialize git repository
        if (!executeCommand("git init", $targetDir)) {
            http_response_code(500);
            logMessage("ERROR: Git init failed");
            exit(1);
        }

        // Add remote
        if (!executeCommand("git remote add origin {$repoUrl}", $targetDir)) {
            http_response_code(500);
            logMessage("ERROR: Git remote add failed");
            exit(1);
        }

        // Pull with SSH key if configured
        $pullCommand = "git pull origin {$branch}";
        if (!empty($sshKeyPath)) {
            $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
            $pullCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$pullCommand}";
        }

        if (!executeCommand($pullCommand, $targetDir)) {
            http_response_code(500);
            logMessage("ERROR: Initial git pull failed");
            exit(1);
        }

        if (!updateGitSubmodules($targetDir, $sshKeyPath)) {
            http_response_code(500);
            logMessage("ERROR: Git submodule update failed");
            exit(1);
        }
    }

    logMessage("Initial setup completed");
} else {
    logMessage("Performing incremental update...");

    $gitCommand = "git pull origin {$branch}";

    if (!empty($sshKeyPath)) {
        $sshCommand = "ssh -i {$sshKeyPath} -o StrictHostKeyChecking=no";
        $gitCommand = "GIT_SSH_COMMAND='{$sshCommand}' {$gitCommand}";
    }

    if (!executeCommand($gitCommand, $targetDir)) {
        http_response_code(500);
        logMessage("ERROR: Git pull failed");
        exit(1);
    }

    if (!updateGitSubmodules($targetDir, $sshKeyPath)) {
        http_response_code(500);
        logMessage("ERROR: Git submodule update failed");
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
