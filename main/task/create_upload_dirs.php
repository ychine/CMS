<?php
// Define the base upload directory
$baseUploadDir = __DIR__ . '/../../uploads/tasks';

// Create the base uploads directory if it doesn't exist
if (!file_exists($baseUploadDir)) {
    if (mkdir($baseUploadDir, 0777, true)) {
        echo "Created base uploads directory: $baseUploadDir<br>";
    } else {
        echo "Failed to create base uploads directory<br>";
    }
}

// Create a .htaccess file to allow access to PDFs and other documents
$htaccessContent = "Options +Indexes\n<FilesMatch \"\\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>";
$htaccessFile = $baseUploadDir . '/.htaccess';
if (!file_exists($htaccessFile)) {
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "Created .htaccess file<br>";
    } else {
        echo "Failed to create .htaccess file<br>";
    }
}

// Create a test directory for task ID 1
$taskDir = $baseUploadDir . '/1';
if (!file_exists($taskDir)) {
    if (mkdir($taskDir, 0777, true)) {
        echo "Created task directory: $taskDir<br>";
    } else {
        echo "Failed to create task directory<br>";
    }
}

// Set permissions for all created directories
$directories = [
    $baseUploadDir,
    $taskDir
];

foreach ($directories as $dir) {
    if (file_exists($dir)) {
        chmod($dir, 0777);
        echo "Set permissions for: $dir<br>";
    }
}

echo "<br>Directory structure setup complete!";
?> 