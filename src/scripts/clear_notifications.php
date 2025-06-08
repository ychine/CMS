<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Debug information
$debug = [
    'session' => $_SESSION,
    'account_id' => isset($_SESSION['AccountID']) ? $_SESSION['AccountID'] : 'not set'
];

if (!isset($_SESSION['AccountID'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Not authenticated',
        'debug' => $debug
    ]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $conn->connect_error,
        'debug' => $debug
    ]);
    exit();
}

$accountID = $_SESSION['AccountID'];

try {
    // First check if there are any notifications to delete
    $checkQuery = "SELECT COUNT(*) as count FROM notifications WHERE AccountID = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $accountID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];
    $checkStmt->close();

    if ($count == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No notifications to clear',
            'debug' => $debug
        ]);
        exit();
    }

    // Delete all notifications for this user
    $query = "DELETE FROM notifications WHERE AccountID = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $accountID);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'message' => "Successfully cleared $affected_rows notifications",
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear notifications: ' . $e->getMessage(),
        'debug' => $debug
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 