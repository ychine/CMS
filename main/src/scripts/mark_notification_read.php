<?php
session_start();

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['AccountID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID is required']);
    exit();
}

$notificationID = $_POST['notification_id'];
$accountID = $_SESSION['AccountID'];

try {
    $query = "UPDATE notifications 
              SET is_read = 1 
              WHERE NotificationID = ? AND AccountID = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notificationID, $accountID);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?> 