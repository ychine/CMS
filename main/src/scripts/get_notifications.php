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

$accountID = $_SESSION['AccountID'];

try {
    $query = "SELECT n.NotificationID, n.Title, n.Message, n.created_at, n.is_read, t.TaskID 
              FROM notifications n 
              LEFT JOIN tasks t ON n.TaskID = t.TaskID 
              WHERE n.AccountID = ? 
              ORDER BY n.created_at DESC 
              LIMIT 10";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['NotificationID'],
            'title' => $row['Title'],
            'message' => $row['Message'],
            'created_at' => $row['created_at'],
            'is_read' => (bool)$row['is_read'],
            'task_id' => $row['TaskID']
        ];
    }
    
    echo json_encode(['notifications' => $notifications]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?> 