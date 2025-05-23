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
            // First delete team members records
            $deleteTeamMembersSql = "DELETE tm FROM teammembers tm 
                                   INNER JOIN submissions s ON tm.SubmissionID = s.SubmissionID 
                                   WHERE s.TaskID = ?";
            $deleteTeamMembersStmt = $conn->prepare($deleteTeamMembersSql);
            $deleteTeamMembersStmt->bind_param("i", $taskID);
            $deleteTeamMembersStmt->execute();
            $deleteTeamMembersStmt->close();
            
            // Then delete submissions
            $deleteSubmissionsSql = "DELETE FROM submissions WHERE TaskID = ?";
            $deleteSubmissionsStmt = $conn->prepare($deleteSubmissionsSql);
            $deleteSubmissionsStmt->bind_param("i", $taskID);
            $deleteSubmissionsStmt->execute();
            $deleteSubmissionsStmt->close();
            
            // Then delete task assignments
            $deleteAssignmentsSql = "DELETE FROM task_assignments WHERE TaskID = ?";
            $deleteAssignmentsStmt = $conn->prepare($deleteAssignmentsSql);
            $deleteAssignmentsStmt->bind_param("i", $taskID);
            $deleteAssignmentsStmt->execute();
            $deleteAssignmentsStmt->close();
            
            // Finally delete the task
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
    else if ($_POST['action'] === 'modify_task') {
        $taskId = $_POST['task_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $dueDate = $_POST['due_date'];
        $schoolYear = $_POST['school_year'];
        $term = $_POST['term'];
        
        // Update task details
        $updateTaskSql = "UPDATE tasks SET 
                         Title = ?, 
                         Description = ?, 
                         DueDate = ?, 
                         SchoolYear = ?, 
                         Term = ? 
                         WHERE TaskID = ?";
        $updateTaskStmt = $conn->prepare($updateTaskSql);
        $updateTaskStmt->bind_param("sssssi", $title, $description, $dueDate, $schoolYear, $term, $taskId);
        
        if ($updateTaskStmt->execute()) {
            // Handle course assignment removals
            if (isset($_POST['remove_assignment']) && is_array($_POST['remove_assignment'])) {
                foreach ($_POST['remove_assignment'] as $assignment) {
                    list($programId, $courseCode) = explode('|', $assignment);
                    // Delete the task assignment for this course and task
                    $deleteAssignmentSql = "DELETE FROM task_assignments WHERE TaskID = ? AND ProgramID = ? AND CourseCode = ?";
                    $deleteAssignmentStmt = $conn->prepare($deleteAssignmentSql);
                    $deleteAssignmentStmt->bind_param("iis", $taskId, $programId, $courseCode);
                    $deleteAssignmentStmt->execute();
                    $deleteAssignmentStmt->close();
                }
            }
            // Handle course assignment additions
            if (isset($_POST['add_assignment']) && is_array($_POST['add_assignment'])) {
                foreach ($_POST['add_assignment'] as $assignment) {
                    list($programId, $courseCode) = explode('|', $assignment);
                    // Get PersonnelID and FacultyID for this course
                    $profQuery = "SELECT PersonnelID, FacultyID FROM program_courses WHERE ProgramID = ? AND CourseCode = ?";
                    $profStmt = $conn->prepare($profQuery);
                    $profStmt->bind_param("is", $programId, $courseCode);
                    $profStmt->execute();
                    $profResult = $profStmt->get_result();
                    if ($profRow = $profResult->fetch_assoc()) {
                        $personnelId = $profRow['PersonnelID'];
                        $facultyId = $profRow['FacultyID'];
                        // Insert new task assignment
                        $insertAssignmentSql = "INSERT INTO task_assignments (TaskID, ProgramID, CourseCode, FacultyID, Status) VALUES (?, ?, ?, ?, 'Pending')";
                        $insertAssignmentStmt = $conn->prepare($insertAssignmentSql);
                        $insertAssignmentStmt->bind_param("iisi", $taskId, $programId, $courseCode, $facultyId);
                        $insertAssignmentStmt->execute();
                        $insertAssignmentStmt->close();
                    }
                    $profStmt->close();
                }
            }
            
            // Add audit log entry
            $logDesc = "Modified task: " . htmlspecialchars($title);
            $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description) 
                       SELECT ?, ?, CONCAT(FirstName, ' ', LastName), ? 
                       FROM personnel WHERE PersonnelID = ?";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("iisi", $facultyID, $personnelID, $logDesc, $personnelID);
            $logStmt->execute();
            $logStmt->close();
            
            header("Location: task_frame.php?message=Task modified successfully");
        } else {
            header("Location: task_frame.php?error=Failed to modify task");
        }
        
        $updateTaskStmt->close();
        exit();
    }
}

$conn->close();
header("Location: task_frame.php");
exit();
?> 