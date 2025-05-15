<?php
require_once '../config/database.php';

function createTaskNotification($taskID, $taskTitle, $facultyID) {
    global $conn;
    
    try {
        // Get all personnel in the faculty
        $query = "SELECT p.AccountID 
                 FROM personnel p 
                 WHERE p.FacultyID = ?";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Create notification for each personnel
        while ($row = $result->fetch_assoc()) {
            $accountID = $row['AccountID'];
            
            $insertQuery = "INSERT INTO notifications (AccountID, Title, Message, TaskID) 
                          VALUES (?, ?, ?, ?)";
                          
            $title = "New Task Created";
            $message = "A new task has been created: " . $taskTitle;
            
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("issi", $accountID, $title, $message, $taskID);
            $insertStmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}
?> 