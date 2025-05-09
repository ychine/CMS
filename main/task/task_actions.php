<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is Dean
$accountID = $_SESSION['AccountID'];
$checkRoleSql = "SELECT Role FROM personnel WHERE AccountID = ?";
$checkStmt = $conn->prepare($checkRoleSql);
$checkStmt->bind_param("i", $accountID);
$checkStmt->execute();
$roleResult = $checkStmt->get_result();
$roleData = $roleResult->fetch_assoc();

if ($roleData['Role'] !== 'DN') {
    header("Location: task_frame.php");
    exit();
}

if (isset($_POST['task_assignment_id']) && isset($_POST['action'])) {
    $taskAssignmentID = $_POST['task_assignment_id'];
    $action = $_POST['action'];
    
    if ($action === 'revise') {
        // Get the revision reason
        $revisionReason = isset($_POST['revision_reason']) ? $_POST['revision_reason'] : '';
        
        // Update status to Pending for revision
        $updateSql = "UPDATE task_assignments 
                     SET Status = 'Pending', 
                         SubmissionPath = NULL, 
                         SubmissionDate = NULL,
                         ApprovalDate = NULL,
                         ApprovedBy = NULL,
                         RevisionReason = ?
                     WHERE TaskAssignmentID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $revisionReason, $taskAssignmentID);
        
        if ($updateStmt->execute()) {
            $_SESSION['message'] = "Task has been marked for revision.";
        } else {
            $_SESSION['message'] = "Error updating task status: " . $updateStmt->error;
        }
        $updateStmt->close();
    } 
    else if ($action === 'complete') {
        // Get the personnel ID of the Dean
        $personnelSql = "SELECT PersonnelID FROM personnel WHERE AccountID = ?";
        $personnelStmt = $conn->prepare($personnelSql);
        $personnelStmt->bind_param("i", $accountID);
        $personnelStmt->execute();
        $personnelResult = $personnelStmt->get_result();
        $personnelData = $personnelResult->fetch_assoc();
        $personnelID = $personnelData['PersonnelID'];
        
        // First get the TaskID for this assignment
        $getTaskSql = "SELECT TaskID FROM task_assignments WHERE TaskAssignmentID = ?";
        $getTaskStmt = $conn->prepare($getTaskSql);
        $getTaskStmt->bind_param("i", $taskAssignmentID);
        $getTaskStmt->execute();
        $taskResult = $getTaskStmt->get_result();
        $taskData = $taskResult->fetch_assoc();
        $taskID = $taskData['TaskID'];
        $getTaskStmt->close();
        
        // Update task_assignments status to Completed
        $updateSql = "UPDATE task_assignments 
                     SET Status = 'Completed', 
                         ApprovalDate = NOW(),
                         ApprovedBy = ?,
                         ReviewStatus = 'Approved'
                     WHERE TaskAssignmentID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $personnelID, $taskAssignmentID);
        
        if ($updateStmt->execute()) {
            // Update the task status to Completed
            $updateTaskSql = "UPDATE tasks SET Status = 'Completed' WHERE TaskID = ?";
            $updateTaskStmt = $conn->prepare($updateTaskSql);
            $updateTaskStmt->bind_param("i", $taskID);
            $updateTaskStmt->execute();
            $updateTaskStmt->close();
            
            $_SESSION['message'] = "Task has been marked as completed.";
        } else {
            $_SESSION['message'] = "Error updating task status: " . $updateStmt->error;
        }
        $updateStmt->close();
    }
}

$conn->close();
header("Location: task_frame.php");
exit();
?> 