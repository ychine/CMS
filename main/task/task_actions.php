<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$accountID = $_SESSION['AccountID'];
$checkRoleSql = "SELECT Role FROM personnel WHERE AccountID = ?";
$checkStmt = $conn->prepare($checkRoleSql);
$checkStmt->bind_param("i", $accountID);
$checkStmt->execute();
$roleResult = $checkStmt->get_result();
$roleData = $roleResult->fetch_assoc();

if ($roleData['Role'] !== 'DN' && $roleData['Role'] !== 'COR') {
    header("Location: task_frame.php");
    exit();
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'discard') {
        
        $personnelSql = "SELECT PersonnelID FROM personnel WHERE AccountID = ?";
        $personnelStmt = $conn->prepare($personnelSql);
        $personnelStmt->bind_param("i", $accountID);
        $personnelStmt->execute();
        $personnelResult = $personnelStmt->get_result();
        $personnelData = $personnelResult->fetch_assoc();
        $personnelID = $personnelData['PersonnelID'];
 
        $taskID = null;
        if (isset($_POST['task_id'])) {
            $taskID = $_POST['task_id'];
        } else if (isset($_POST['task_assignment_id'])) {
            $getTaskSql = "SELECT TaskID FROM task_assignments WHERE TaskAssignmentID = ?";
            $getTaskStmt = $conn->prepare($getTaskSql);
            $getTaskStmt->bind_param("i", $_POST['task_assignment_id']);
            $getTaskStmt->execute();
            $taskResult = $getTaskStmt->get_result();
            $taskData = $taskResult->fetch_assoc();
            $taskID = $taskData['TaskID'];
            $getTaskStmt->close();
        }
        
        if ($taskID) {
       
            $deleteSubmissionsSql = "DELETE FROM submissions WHERE TaskID = ?";
            $deleteSubmissionsStmt = $conn->prepare($deleteSubmissionsSql);
            $deleteSubmissionsStmt->bind_param("i", $taskID);
            $deleteSubmissionsStmt->execute();
            $deleteSubmissionsStmt->close();
            
      
            $deleteAssignmentsSql = "DELETE FROM task_assignments WHERE TaskID = ?";
            $deleteAssignmentsStmt = $conn->prepare($deleteAssignmentsSql);
            $deleteAssignmentsStmt->bind_param("i", $taskID);
            $deleteAssignmentsStmt->execute();
            $deleteAssignmentsStmt->close();
            
            $deleteTaskSql = "DELETE FROM tasks WHERE TaskID = ?";
            $deleteTaskStmt = $conn->prepare($deleteTaskSql);
            $deleteTaskStmt->bind_param("i", $taskID);
            if ($deleteTaskStmt->execute()) {
                $_SESSION['message'] = "Task has been permanently deleted.";
            } else {
                $_SESSION['message'] = "Error deleting task: " . $deleteTaskStmt->error;
            }
            $deleteTaskStmt->close();
        } else {
            $_SESSION['message'] = "Error: Task ID not found.";
        }
    }
    else if (isset($_POST['task_assignment_id'])) {
        $taskAssignmentID = $_POST['task_assignment_id'];
        
        if ($action === 'revise') {
         
            $revisionReason = isset($_POST['revision_reason']) ? $_POST['revision_reason'] : '';
       
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
            
            $personnelSql = "SELECT PersonnelID FROM personnel WHERE AccountID = ?";
            $personnelStmt = $conn->prepare($personnelSql);
            $personnelStmt->bind_param("i", $accountID);
            $personnelStmt->execute();
            $personnelResult = $personnelStmt->get_result();
            $personnelData = $personnelResult->fetch_assoc();
            $personnelID = $personnelData['PersonnelID'];
            
        
            $getTaskSql = "SELECT TaskID FROM task_assignments WHERE TaskAssignmentID = ?";
            $getTaskStmt = $conn->prepare($getTaskSql);
            $getTaskStmt->bind_param("i", $taskAssignmentID);
            $getTaskStmt->execute();
            $taskResult = $getTaskStmt->get_result();
            $taskData = $taskResult->fetch_assoc();
            $taskID = $taskData['TaskID'];
            $getTaskStmt->close();
            
         
            $updateSql = "UPDATE task_assignments 
                         SET Status = 'Completed', 
                             ApprovalDate = NOW(),
                             ApprovedBy = ?,
                             ReviewStatus = 'Approved'
                         WHERE TaskAssignmentID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ii", $personnelID, $taskAssignmentID);
            
            if ($updateStmt->execute()) {
                
                $checkAllCompletedSql = "SELECT COUNT(*) as total, 
                                       SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed 
                                       FROM task_assignments WHERE TaskID = ?";
                $checkStmt = $conn->prepare($checkAllCompletedSql);
                $checkStmt->bind_param("i", $taskID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $checkData = $checkResult->fetch_assoc();
                $checkStmt->close();
                
          
                if ($checkData['total'] == $checkData['completed']) {
                    $updateTaskSql = "UPDATE tasks SET Status = 'Completed' WHERE TaskID = ?";
                    $updateTaskStmt = $conn->prepare($updateTaskSql);
                    $updateTaskStmt->bind_param("i", $taskID);
                    $updateTaskStmt->execute();
                    $updateTaskStmt->close();
                } else {
                  
                    $updateTaskSql = "UPDATE tasks SET Status = 'In Progress' WHERE TaskID = ?";
                    $updateTaskStmt = $conn->prepare($updateTaskSql);
                    $updateTaskStmt->bind_param("i", $taskID);
                    $updateTaskStmt->execute();
                    $updateTaskStmt->close();
                }
                
                $_SESSION['message'] = "Task has been marked as completed.";
            } else {
                $_SESSION['message'] = "Error updating task status: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
    }
}

$conn->close();
header("Location: task_frame.php");
exit();
?> 