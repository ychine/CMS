<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['AccountID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$notificationId = (int)$_POST['notification_id'];
$accountId = $_SESSION['AccountID'];

// Verify the notification belongs to the user and update it
$query = "UPDATE notifications SET is_read = 1 WHERE NotificationID = ? AND AccountID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $notificationId, $accountId);
$success = $stmt->execute();

$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
?> 