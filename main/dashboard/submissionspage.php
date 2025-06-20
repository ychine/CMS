<?php
session_start();

function getAssignmentStatusLabel($status) {
    switch ($status) {
        case 'Pending':
            return 'No Submission';
        case 'Submitted':
            return 'Submitted for Review';
        case 'Completed':
            return 'Reviewed & Approved';
        default:
            return $status;
    }
}

function createNotification($accountID, $title, $message, $taskID = null) {
    global $conn;
    $insertQuery = "INSERT INTO notifications (AccountID, Title, Message, TaskID) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("issi", $accountID, $title, $message, $taskID);
    $insertStmt->execute();
    $insertStmt->close();
}

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$accountID = $_SESSION['AccountID'];
$facultyName = "Faculty";
$userRole = "";
$facultyID = null;
$message = "";

$roleMap = [
    'DN' => 'College Dean',
    'PH' => 'Program Head',
    'FM' => 'Faculty Member',
    'COR' => 'Courseware Coordinator'
];

$sql = "SELECT personnel.PersonnelID, personnel.FacultyID, personnel.Role, faculties.Faculty 
        FROM personnel 
        JOIN faculties ON personnel.FacultyID = faculties.FacultyID
        WHERE personnel.AccountID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $facultyID = $row['FacultyID'];
    $facultyName = $row['Faculty'];
    $personnelID = $row['PersonnelID'];
    $userRole = $row['Role'];
}
$stmt->close();

$coauthors = [];
if ($facultyID) {
    $coauthorQuery = "SELECT PersonnelID, FirstName, LastName FROM personnel WHERE FacultyID = ? AND PersonnelID != ? ORDER BY FirstName, LastName";
    $coauthorStmt = $conn->prepare($coauthorQuery);
    $coauthorStmt->bind_param("ii", $facultyID, $personnelID);
    $coauthorStmt->execute();
    $coauthorResult = $coauthorStmt->get_result();
    while ($row = $coauthorResult->fetch_assoc()) {
        $coauthors[] = $row;
    }
    $coauthorStmt->close();
}

if (isset($_POST['submit_file']) && isset($_POST['task_id']) && isset($_FILES['task_file'])) {
    $taskID = $_POST['task_id'];
    $courseCode = $_POST['course_code'];
    $programID = $_POST['program_id'];
    $selectedCoauthors = isset($_POST['coauthors']) ? $_POST['coauthors'] : [];
    
    $uploadDir = "../../uploads/tasks/{$taskID}/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = basename($_FILES["task_file"]["name"]);
    $targetFilePath = $uploadDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
  
    if(move_uploaded_file($_FILES["task_file"]["tmp_name"], $targetFilePath)) {
        $relativePath = "uploads/tasks/{$taskID}/" . $fileName;
       
        $taskInfoSql = "SELECT SchoolYear, Term, Title FROM tasks WHERE TaskID = ?";
        $taskInfoStmt = $conn->prepare($taskInfoSql);
        $taskInfoStmt->bind_param("i", $taskID);
        $taskInfoStmt->execute();
        $taskInfoResult = $taskInfoStmt->get_result();
        $taskInfo = $taskInfoResult->fetch_assoc();
        $schoolYear = $taskInfo['SchoolYear'];
        $term = $taskInfo['Term'];
        $taskInfoStmt->close();

        $deleteTeamSql = "DELETE FROM teammembers WHERE SubmissionID IN (SELECT SubmissionID FROM submissions WHERE TaskID = ? AND CourseCode = ? AND ProgramID = ?)";
        $deleteTeamStmt = $conn->prepare($deleteTeamSql);
        $deleteTeamStmt->bind_param("isi", $taskID, $courseCode, $programID);
        $deleteTeamStmt->execute();
        $deleteTeamStmt->close();
       
        $deleteSql = "DELETE FROM submissions WHERE TaskID = ? AND CourseCode = ? AND ProgramID = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("isi", $taskID, $courseCode, $programID);
        $deleteStmt->execute();
        $deleteStmt->close();

        $insertSql = "INSERT INTO submissions (FacultyID, TaskID, CourseCode, ProgramID, SubmissionPath, SubmittedBy, SubmissionDate, SchoolYear, Term) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iisssiss", $facultyID, $taskID, $courseCode, $programID, $relativePath, $personnelID, $schoolYear, $term);
        if($insertStmt->execute()) {
            $submissionID = $conn->insert_id;
           
            if (!empty($selectedCoauthors)) {
                $tmStmt = $conn->prepare("INSERT INTO teammembers (SubmissionID, MembersID) VALUES (?, ?)");
                foreach ($selectedCoauthors as $coauthorID) {
                    $tmStmt->bind_param("ii", $submissionID, $coauthorID);
                    $tmStmt->execute();
                }
                $tmStmt->close();
            }
            
            $notifyQuery = "SELECT p.AccountID, p.Role 
                           FROM personnel p 
                           WHERE p.FacultyID = ? AND p.Role IN ('DN', 'PH', 'COR')";
            $notifyStmt = $conn->prepare($notifyQuery);
            $notifyStmt->bind_param("i", $facultyID);
            $notifyStmt->execute();
            $notifyResult = $notifyStmt->get_result();
            
            $profQuery = "SELECT p.FirstName, p.LastName, c.Title as CourseTitle
                         FROM personnel p 
                         JOIN courses c ON c.CourseCode = ? 
                         WHERE p.PersonnelID = ?";
            $profStmt = $conn->prepare($profQuery);
            $profStmt->bind_param("si", $courseCode, $personnelID);
            $profStmt->execute();
            $profResult = $profStmt->get_result();
            $profInfo = $profResult->fetch_assoc();
            $profStmt->close();
            
            while ($row = $notifyResult->fetch_assoc()) {
                $title = "✅ New Submission for " . $taskInfo['Title'];
                $message = $profInfo['FirstName'] . " " . $profInfo['LastName'] . " submitted for " . $profInfo['CourseTitle'];
                createNotification($row['AccountID'], $title, $message, $taskID);
            }
            $notifyStmt->close();
            
            $message = "File uploaded successfully.";
        } else {
            $message = "Error inserting submission: " . $insertStmt->error;
        }
        $insertStmt->close();
       
        $updateSql = "UPDATE task_assignments SET Status = 'Submitted', ReviewStatus = 'Not Reviewed', SubmissionDate = NOW(), SubmissionPath = ? WHERE TaskID = ? AND CourseCode = ? AND ProgramID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sisi", $relativePath, $taskID, $courseCode, $programID);
        $updateStmt->execute();
        $updateStmt->close();
     
        $updateTaskSql = "UPDATE tasks SET Status = 'Submitted' WHERE TaskID = ?";
        $updateTaskStmt = $conn->prepare($updateTaskSql);
        $updateTaskStmt->bind_param("i", $taskID);
        $updateTaskStmt->execute();
        $updateTaskStmt->close();
    } else {
        $message = "Error uploading file.";
    }
}

if (isset($_POST['approve_task']) && $userRole == 'DN') {
    $taskAssignmentID = $_POST['task_assignment_id'];
    
    $approveSql = "UPDATE task_assignments 
                   SET Status = 'Completed', ApprovedBy = ?, ApprovalDate = NOW() 
                   WHERE TaskAssignmentID = ?";
    $approveStmt = $conn->prepare($approveSql);
    $approveStmt->bind_param("ii", $personnelID, $taskAssignmentID);
    
    if($approveStmt->execute()) {
      
        $checkAllCompletedSql = "SELECT TaskID FROM task_assignments 
                                WHERE TaskAssignmentID = ?";
        $checkStmt = $conn->prepare($checkAllCompletedSql);
        $checkStmt->bind_param("i", $taskAssignmentID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $taskData = $checkResult->fetch_assoc();
        $taskID = $taskData['TaskID'];
        $checkStmt->close();
        
    
        $countSql = "SELECT COUNT(*) as total, 
                    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed 
                    FROM task_assignments WHERE TaskID = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $taskID);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countData = $countResult->fetch_assoc();
        $countStmt->close();
        
        if ($countData['total'] == $countData['completed']) {
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
        
        $message = "Task approved successfully.";
    } else {
        $message = "Error approving task: " . $approveStmt->error;
    }
    $approveStmt->close();
}

if (isset($_POST['action']) && $_POST['action'] === 'revise') {
    $taskAssignmentID = $_POST['task_assignment_id'];
    $revisionReason = $_POST['revision_reason'];
  
    $detailsQuery = "SELECT ta.TaskID, ta.PersonnelID, t.Title, c.CourseCode, c.Title as CourseTitle 
                    FROM task_assignments ta 
                    JOIN tasks t ON ta.TaskID = t.TaskID 
                    JOIN courses c ON ta.CourseCode = c.CourseCode 
                    WHERE ta.TaskAssignmentID = ?";
    $detailsStmt = $conn->prepare($detailsQuery);
    $detailsStmt->bind_param("i", $taskAssignmentID);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();
    $details = $detailsResult->fetch_assoc();
    
    $updateSql = "UPDATE task_assignments 
                  SET Status = 'Pending', 
                      ReviewStatus = 'Not Reviewed',
                      RevisionReason = ? 
                  WHERE TaskAssignmentID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $revisionReason, $taskAssignmentID);
    
    if($updateStmt->execute()) {
      
        $title = "Revision Requested";
        $message = "A revision has been requested for your submission in " . $details['CourseCode'] . " - " . $details['CourseTitle'];
        createNotification($details['PersonnelID'], $title, $message, $details['TaskID']);
        
        $message = "Revision request sent successfully.";
    } else {
        $message = "Error requesting revision: " . $updateStmt->error;
    }
    $updateStmt->close();
    $detailsStmt->close();
}

$tasks = [];

if (isset($_GET['task_id'])) {
    $taskID = $_GET['task_id'];
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, 
                t.SchoolYear, t.Term, COUNT(ta.TaskAssignmentID) as TotalAssignments,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedAssignments,
                p.FirstName as CreatorFirstName, p.LastName as CreatorLastName, p.Role as CreatorRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                WHERE t.TaskID = ? AND t.FacultyID = ?
                GROUP BY t.TaskID";
    
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("ii", $taskID, $facultyID);
} else if ($userRole == 'DN' || $userRole == 'PH' || $userRole == 'COR') {
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, 
                t.SchoolYear, t.Term, COUNT(ta.TaskAssignmentID) as TotalAssignments,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedAssignments,
                p.FirstName as CreatorFirstName, p.LastName as CreatorLastName, p.Role as CreatorRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                WHERE t.FacultyID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("i", $facultyID);
} else {
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, 
                t.SchoolYear, t.Term, COUNT(ta.TaskAssignmentID) as TotalAssignments,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedAssignments,
                p.FirstName as CreatorFirstName, p.LastName as CreatorLastName, p.Role as CreatorRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                WHERE ta.PersonnelID = ? AND t.FacultyID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("ii", $personnelID, $facultyID);
}

$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

while ($taskRow = $tasksResult->fetch_assoc()) {
    $assignmentsSql = "SELECT ta.TaskAssignmentID, ta.CourseCode, ta.ProgramID, ta.Status as AssignmentStatus, 
                    ta.ReviewStatus, ta.SubmissionPath, ta.SubmissionDate, ta.ApprovalDate, ta.RevisionReason,
                    c.Title as CourseTitle, p.ProgramName, p.ProgramCode,
                    CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                    per.PersonnelID as PersonnelID,
                    CONCAT(apr.FirstName, ' ', apr.LastName) as ApprovedBy
                    FROM task_assignments ta
                    LEFT JOIN courses c ON ta.CourseCode = c.CourseCode
                    LEFT JOIN programs p ON ta.ProgramID = p.ProgramID
                    LEFT JOIN personnel per ON ta.PersonnelID = per.PersonnelID
                    LEFT JOIN personnel apr ON ta.ApprovedBy = apr.PersonnelID
                    WHERE ta.TaskID = ?";
    
    if ($userRole != 'DN' && $userRole != 'PH' && $userRole != 'COR') {
        $assignmentsSql .= " AND ta.PersonnelID = ?";
    }
    
    $assignmentsSql .= " ORDER BY p.ProgramName, ta.CourseCode";
    
    $assignmentsStmt = $conn->prepare($assignmentsSql);
    
    if ($userRole != 'DN' && $userRole != 'PH' && $userRole != 'COR') {
        $assignmentsStmt->bind_param("ii", $taskRow['TaskID'], $personnelID);
    } else {
        $assignmentsStmt->bind_param("i", $taskRow['TaskID']);
    }
    
    $assignmentsStmt->execute();
    $assignmentsResult = $assignmentsStmt->get_result();
    
    $assignments = [];
    while ($assignmentRow = $assignmentsResult->fetch_assoc()) {
        $assignments[] = $assignmentRow;
    }
    $assignmentsStmt->close();
    
    $taskRow['Assignments'] = $assignments;
    $tasks[] = $taskRow;
}
$tasksStmt->close();

$conn->close();

$backUrl = '../../main/dashboard/dn-dash.php';
if (isset($_GET['return_to'])) {
    switch ($_GET['return_to']) {
        case 'curriculum.php':
            $backUrl = '../../main/curriculum/curriculum_frame.php';
            break;
        case 'faculty.php':
            $backUrl = '../../main/faculty/faculty_frame.php';
            break;
        case 'tasks.php':
            $backUrl = '../../main/task/task_frame.php';
            break;
        case 'audit_log.php':
            $backUrl = '../../main/auditlog/audit_log_frame.php';
            break;
        case 'settings.php':
            $backUrl = '../../main/settings/settings_frame.php';
            break;
        case 'profile.php':
            $backUrl = '../../main/profile/profile_frame.php';
            break;
    }
} else if (isset($_GET['from'])) {
    switch ($_GET['from']) {
        
        case 'dn-dash':
            $backUrl = '../../main/dashboard/dn-dash.php';
            break;
        case 'fm-dash':
            $backUrl = '../../main/dashboard/fm-dash.php';
            break;
        case 'ph-dash':
            $backUrl = '../../main/dashboard/ph-dash.php';
            break;
    }
} else {
    switch ($userRole) {
        case 'FM':
            $backUrl = '../../main/dashboard/fm-dash.php';
            break;
        case 'PH':
            $backUrl = '../../main/dashboard/ph-dash.php';
            break;
        case 'COR':
            $backUrl = '../../main/dashboard/ph-dash.php';
            break;
        case 'DN':
        default:
            $backUrl = '../../main/dashboard/dn-dash.php';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COURSEDOCK - Submissions</title>
  <link href="../../src/tailwind/output.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-overpass { font-family: 'Overpass', sans-serif; }
    .font-onest { font-family: 'Onest', sans-serif; }
    
    .content {
      display: flex;
      min-height: calc(100vh - 60px);
    }
    
    .tasks-section {
      flex: 3;
      padding: 20px;
      border-right: 1px solid #e5e7eb;
    }
    
    .files-section {
      flex: 1;
      padding: 40px;
      background: transparent;
    }
    
    .files-section .bg-white {
      background: white;
      color: #1f2937;
    }
    
    .files-section .text-gray-800 {
      color: #1f2937;
    }
    
    .files-section .text-gray-600 {
      color: #4b5563;
    }
    
    .files-section .text-gray-500 {
      color: #6b7280;
    }
    
    .files-section .border-gray-300 {
      border-color: #d1d5db;
    }
    
    .files-section .bg-blue-50 {
      background: #eff6ff;
    }
    
    .files-section .hover\:bg-blue-100:hover {
      background: #dbeafe;
    }
    
    .files-section .text-blue-500 {
      color: #3b82f6;
    }
    
    .files-section .bg-blue-100 {
      background: #dbeafe;
    }
    
    .files-section .bg-blue-600 {
      background: #2563eb;
    }
    
    .files-section .hover\:bg-blue-700:hover {
      background: #1d4ed8;
    }
    
    .files-section .border-blue-300 {
      border-color: #93c5fd;
    }
    
    .files-section .bg-green-100 {
      background: #dcfce7;
    }
    
    .files-section .text-green-500 {
      color: #22c55e;
    }
    
    .files-section .bg-yellow-50 {
      background: #fefce8;
    }
    
    .files-section .border-yellow-200 {
      border-color: #fef08a;
    }
    
    .files-section .text-yellow-800 {
      color: #854d0e;
    }
    
    .files-section .text-yellow-700 {
      color: #a16207;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .task-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 16px;
      margin-bottom: 20px;
    }
    
    .task-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }
    
    .faculty-info {
      display: flex;
      align-items: center;
    }
    
    .faculty-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #e5e7eb;
      margin-right: 12px;
    }
    
    .faculty-name {
      font-weight: 600;
      margin: 0;
    }
    
    .faculty-role {
      color: #6b7280;
      font-size: 0.85rem;
      margin: 0;
    }
    
    .deadline {
      color: #ef4444;
      font-size: 0.85rem;
    }
    
    .approval-section {
      margin-top: 30px;
    }
    
    .approval-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .complete-label {
      color: #6b7280;
      font-size: 0.9rem;
    }
    
    .course-card {
      display: flex;
      justify-content: space-between;
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 10px;
      border-left: 4px solid transparent;
    }
    
    .course-card.pending {
      background-color: #fef3c7;
      border-left-color: #f59e0b;
    }
    
    .course-card.submitted {
      background-color: #e0f2fe;
      border-left-color: #3b82f6;
    }
    
    .course-card.completed {
      background-color: #dcfce7;
      border-left-color: #10b981;
    }
    
    .course-name {
      font-weight: 500;
      margin: 0 0 4px 0;
    }
    
    .course-badges {
      display: flex;
      gap: 6px;
    }
    
    .badge {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #9ca3af;
    }
    
    .status {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }
    
    .status-label {
      font-weight: 500;
      font-size: 0.85rem;
    }
    
    .status-label.pending {
      color: #f59e0b;
    }
    
    .status-label.submitted {
      color: #3b82f6;
    }
    
    .status-label.completed {
      color: #10b981;
    }
    
    .signed-by {
      font-size: 0.75rem;
      color: #4b5563;
      margin: 2px 0 0 0;
    }
    
    .file-placeholder {
      height: 80px;
      background-color: #e5e7eb;
      border-radius: 6px;
      margin-bottom: 12px;
    }
    
    .submit-btn {
      width: 100%;
      background-color: #2563eb;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 10px;
    }
    
    .submit-btn:hover {
      background-color: #1d4ed8;
    }
    
    .task-floating-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
    }
    
    .task-floating-button button {
      background-color: #2563eb;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 8px 16px;
      font-weight: 500;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .task-list {
      margin-bottom: 30px;
    }
    
    .upload-form {
      margin-top: 15px;
    }
    
    .file-input-container {
      position: relative;
      margin-bottom: 15px;
    }
    
    .file-input-label {
      display: block;
      background-color: #f3f4f6;
      border: 1px dashed #d1d5db;
      border-radius: 6px;
      padding: 30px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .file-input-label:hover {
      background-color: #e5e7eb;
    }
    
    .file-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .selected-file {
      background: #dbeafe;
      border-radius: 9999px;
      padding: 0.5rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
      max-width: 320px;
      min-width: 220px;
      width: 100%;
      box-sizing: border-box;
    }
    
    .selected-file .file-name {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 180px;
      display: inline-block;
    }
    
    .remove-file {
      color: #ef4444;
      cursor: pointer;
    }
    
    .approval-btn {
      background-color: #10b981;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 4px 8px;
      font-size: 0.75rem;
      cursor: pointer;
      margin-top: 5px;
    }
    
    .approval-btn:hover {
      background-color: #059669;
    }
    
    .no-tasks {
      text-align: center;
      color: #6b7280;
      padding: 40px 0;
    }
    
    /* File preview */
    .file-preview {
      margin-top: 10px;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
    }
    
    .pdf-preview {
      width: 100%;
      height: 300px;
    }
    
    .image-preview {
      max-width: 100%;
      max-height: 300px;
      margin: 0 auto;
      display: block;
    }
    
    .doc-preview {
      padding: 15px;
      text-align: center;
    }
    
    body.dark {
      background: #18181b !important;
      color: #f3f4f6 !important;
    }
    .dark .bg-white,
    .dark .files-section .bg-white {
      background: #26272b !important;
      color: #f3f4f6 !important;
      box-shadow: 0 4px 32px rgba(0,0,0,0.32) !important;
      border: 1px solid #34343c !important;
    }
    .dark .shadow-lg, .dark .shadow-2xl {
      box-shadow: 0 4px 24px rgba(0,0,0,0.32) !important;
    }
    .dark .text-gray-800, .dark .text-gray-700, .dark .text-gray-600 {
      color: #e5e7eb !important;
    }
    .dark .text-gray-500 {
      color: #a1a1aa !important;
    }
    .dark .border-gray-300, .dark .border {
      border-color: #374151 !important;
    }
    .dark .bg-blue-50, .dark .bg-blue-100, .dark .files-section .bg-blue-50, .dark .files-section .bg-blue-100 {
      background: #1e293b !important;
    }
    .dark .file-input-label, .dark .files-section .file-input-label {
      background: #23232a !important;
      border-color: #374151 !important;
      color: #e5e7eb !important;
    }
    .dark .bg-gray-100,
    .dark .bg-gray-200,
    .dark .files-section .bg-gray-100,
    .dark .files-section .bg-gray-200 {
      background: #23232a !important;
      color: #f3f4f6 !important;
    }
    .dark .border,
    .dark .border-gray-300,
    .dark .border-gray-200,
    .dark .files-section .border,
    .dark .files-section .border-gray-300,
    .dark .files-section .border-gray-200 {
      border-color: #374151 !important;
    }
    .dark .text-gray-900,
    .dark .text-gray-800,
    .dark .text-gray-700,
    .dark .text-gray-600,
    .dark .text-gray-500,
    .dark .text-sm,
    .dark .text-xs,
    .dark .files-section .text-gray-900,
    .dark .files-section .text-gray-800,
    .dark .files-section .text-gray-700,
    .dark .files-section .text-gray-600,
    .dark .files-section .text-gray-500,
    .dark .files-section .text-sm,
    .dark .files-section .text-xs {
      color: #f3f4f6 !important;
    }
    .dark .hover\:bg-gray-200:hover,
    .dark .files-section .hover\:bg-gray-200:hover {
      background: #2d2d36 !important;
    }
    .dark .task-card,
    .dark .bg-white,
    .dark .bg-gray-100,
    .dark .bg-gray-200,
    .dark .file-placeholder,
    .dark .files-section .task-card,
    .dark .files-section .bg-white,
    .dark .files-section .bg-gray-100,
    .dark .files-section .bg-gray-200,
    .dark .files-section .file-placeholder {
      background: #23232a !important;
      color: #f3f4f6 !important;
    }
    .dark .course-card.completed {
      background: #134e2e !important;
      border-left-color: #22d3ee !important;
      color: #bbf7d0 !important;
    }
    .dark .course-card.submitted {
      background: #1e293b !important;
      border-left-color: #60a5fa !important;
      color: #bae6fd !important;
    }
    .dark .course-card.pending {
      background: #3b2f0b !important;
      border-left-color: #f59e0b !important;
      color: #fde68a !important;
    }
    .dark .faculty-role,
    .dark .deadline,
    .dark .status-label,
    .dark .signed-by,
    .dark .text-xs,
    .dark .text-sm,
    .dark .files-section .faculty-role,
    .dark .files-section .deadline,
    .dark .files-section .status-label,
    .dark .files-section .signed-by,
    .dark .files-section .text-xs,
    .dark .files-section .text-sm {
      color: #f3f4f6 !important;
    }
    .dark .status-label.completed {
      color: #6ee7b7 !important;
    }
    .dark .status-label.submitted {
      color: #38bdf8 !important;
    }
    .dark .status-label.pending {
      color: #fde68a !important;
    }
    .dark .submit-btn {
      background: #2563eb !important;
      color: #fff !important;
    }
    .dark .submit-btn:hover {
      background: #1d4ed8 !important;
    }
    .dark .remove-file {
      color: #f87171 !important;
    }
    .dark .fa-check-circle {
      color: #22d3ee !important;
    }
    .dark .no-tasks {
      background: transparent !important;
      color: #a1a1aa !important;
    }
    .dark .files-section .bg-white {
      background: #26272b !important;
      color: #f3f4f6 !important;
      box-shadow: 0 4px 32px rgba(0,0,0,0.32) !important;
      border: 1px solid #34343c !important;
    }
    .dark .text-gray-500,
    .dark .text-gray-800,
    .dark .files-section .text-gray-500,
    .dark .files-section .text-gray-800 {
      color: #a1a1aa !important;
    }
    .dark .text-blue-500,
    .dark .files-section .text-blue-500 {
      color: #38bdf8 !important;
    }
    /* Dark mode for select and option in files-section */
    .dark .files-section select,
    .dark .files-section select:focus {
      background: #23232a !important;
      color: #f3f4f6 !important;
      border-color: #60a5fa !important;
    }
    .dark .files-section option {
      background: #23232a !important;
      color: #f3f4f6 !important;
    }
    /* --- Modern UI/UX Improvements --- */
    .task-card, .course-card {
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      transition: box-shadow 0.2s, transform 0.2s;
    }
    /* Only course-card has hover effect */
    .course-card:hover {
      box-shadow: 0 8px 32px rgba(0,0,0,0.16);
      transform: translateY(-2px) scale(1.01);
    }
    .section-header h3 {
      font-size: 1.5rem;
      font-weight: 400;
      letter-spacing: -0.5px;
    }
    .section-header .course-code {
      font-size: 1rem;
      font-weight: 500;
      color: #64748b;
    }
    .submit-btn, .approval-btn {
      border-radius: 9999px;
      padding: 0.75rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(37,99,235,0.08);
      transition: background 0.2s, box-shadow 0.2s;
    }
    .submit-btn:focus, .approval-btn:focus {
      outline: 2px solid #2563eb;
      outline-offset: 2px;
    }
    input, select {
      border-radius: 8px;
      padding: 0.75rem 1rem;
      border: 1px solid #d1d5db;
      font-size: 1rem;
      transition: border 0.2s, box-shadow 0.2s;
    }
    input:focus, select:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 2px #2563eb33;
    }
    .file-input-label {
      border-radius: 16px;
      border-width: 2px;
      border-style: dashed;
      border-color: #60a5fa;
      background: #f0f9ff;
      padding: 2.5rem 1.5rem;
      font-size: 1.1rem;
    }
    .file-input-label i {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }
    .selected-file {
      border-radius: 9999px;
      background: #dbeafe;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
    }
    @media (max-width: 900px) {
      .content {
        flex-direction: column;
      }
      .files-section {
        padding: 20px;
        margin-top: 2rem;
      }
    }
    /* --- End Modern UI/UX Improvements --- */
    .dark .files-section {
      background: #18181b !important;
    }
    .back-arrow-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: auto;
      height: auto;
      background: none;
      border-radius: 0;
      box-shadow: none;
      color: #2563eb;
      font-size: 1.7rem;
      transition: color 0.2s;
      border: none;
      outline: none;
      text-decoration: none;
      cursor: pointer;
      padding: 0;
    }
    .back-arrow-btn:hover {
      color: #1d4ed8;
      background: none;
      box-shadow: none;
    }
    .section-header .task-title {
      font-family: 'Overpass', 'Poppins', 'Inter', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: black;
      letter-spacing: -1px;
      line-height: 1.1;
    }
    .dark .section-header .task-title {
      color: #f3f4f6;
    }
  </style>
</head>
<body class="px-[50px]">
<?php if ($userRole == 'DN'): ?>
<script>
  if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark');
  }
</script>
<?php endif; ?>
  <div class="content">
    <div class="tasks-section">
      <?php if (!empty($message)): ?>
      <div class="bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded mb-6">
          <?php echo $message; ?>
      </div>
      <?php endif; ?>
      
      <?php if (empty($tasks)): ?>
      <div class="no-tasks">
        <p>No tasks available. <?php echo ($userRole == 'DN') ? 'Create your first task!' : 'Tasks assigned to you will appear here.'; ?></p>
      </div>
      <?php else: ?>
        <?php if (!empty($tasks)): ?>
          <div class="section-header mb-8" style="position: relative; min-height: 48px; display: flex; align-items: center;">
            <a href="<?php echo $backUrl; ?>" class="back-arrow-btn" title="Back" style="position: absolute; left: -40px; top: 50%; transform: translateY(-55%); margin: 0;"><i class="fas fa-arrow-left"></i></a>
            <h3 class="task-title font-onest font-semibold" style="font-family: 'Onest', sans-serif; margin-left: 0;">
              <?php echo htmlspecialchars($tasks[0]['Title']); ?>
            </h3>
            <span class="course-code" style="margin-left: 1rem;">
              <?php echo htmlspecialchars($tasks[0]['SchoolYear'] . ' ' . $tasks[0]['Term']); ?>
            </span>
          </div>
        <?php endif; ?>
        <?php foreach ($tasks as $task): ?>
          <div class="task-list mb-8">
            <div class="task-card mb-4">
              <div class="task-header mb-2">
                <div class="faculty-info">
                  <div class="faculty-avatar"></div>
                  <div class="faculty-details">
                    <p class="faculty-name text-lg font-onest font-semibold"><?php echo htmlspecialchars($task['CreatorFirstName'] . ' ' . $task['CreatorLastName']); ?></p>
                    <p class="faculty-role font-light"><?php echo htmlspecialchars($roleMap[$task['CreatorRole']] ?? $task['CreatorRole']); ?></p>
                  </div>
                </div>
                <div class="deadline">
                  <p>Deadline: <?php echo date("F j, g:i a", strtotime($task['DueDate'])); ?></p>
                </div>
              </div>
              <div class="task-content mb-2 p-[10px]">
                <p class="text-base font-onest font-light"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                <?php if (!empty($task['RevisionReason'])): ?>
                  <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                      <h4 class="font-medium text-yellow-800 mb-2">Revision Requested</h4>
                      <p class="text-yellow-700"><?php echo nl2br(htmlspecialchars($task['RevisionReason'])); ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="approval-section">
              <div class="approval-header mb-2">
                <h3 class="font-semibold">Approval Status</h3>
                <div class="complete-label font-light">Complete: <?php echo $task['CompletedAssignments']; ?>/<?php echo $task['TotalAssignments']; ?></div>
              </div>
              
              <?php if (!empty($task['Assignments'])): ?>
                <?php foreach ($task['Assignments'] as $assignment): ?>
                  <div class="course-card <?php echo $assignment['AssignmentStatus'] == 'Completed' ? 'completed' : ($assignment['AssignmentStatus'] == 'Submitted' ? 'submitted' : 'pending'); ?> mb-3" <?php if (!empty($assignment['SubmissionPath'])): ?> onclick="previewSubmission('<?php echo '../../' . htmlspecialchars($assignment['SubmissionPath']); ?>', '<?php echo htmlspecialchars($assignment['CourseCode'] . ' - ' . $assignment['CourseTitle']); ?>')" style="cursor: pointer;" <?php endif; ?>>
                    <div class="course-info">
                      <p class="course-name font-semibold"><?php echo htmlspecialchars($assignment['CourseCode'] . ' ' . $assignment['CourseTitle']); ?></p>
                      <div class="course-badges">
                        <span class="badge"></span>
                        <span class="badge"></span>
                      </div>
                      <p class="text-xs text-gray-600 font-light"><?php echo htmlspecialchars($assignment['ProgramName']); ?></p>
                      <p class="text-xs text-gray-600 font-light">Assigned to: <?php echo !empty($assignment['AssignedTo']) ? htmlspecialchars($assignment['AssignedTo']) : 'No assigned professor'; ?></p>
                      <?php if (!empty($assignment['CoAuthors'])): ?>
                        <p class="text-xs text-gray-600 font-light">Co-Authors: <?php echo htmlspecialchars(implode(', ', $assignment['CoAuthors'])); ?></p>
                      <?php endif; ?>
                      <?php if (!empty($assignment['RevisionReason'])): ?>
                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                          <span class="font-medium text-yellow-800">Revision Requested:</span>
                          <span class="text-yellow-700"><?php echo nl2br(htmlspecialchars($assignment['RevisionReason'])); ?></span>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="status">
                      <span class="status-label <?php echo strtolower($assignment['AssignmentStatus']); ?>">
                        <?php echo getAssignmentStatusLabel($assignment['AssignmentStatus']); ?>
                      </span>
                      
                      <?php if ($assignment['AssignmentStatus'] == 'Completed'): ?>
                        <br>
                        <p class="signed-by font-light">Signed by: <?php echo htmlspecialchars($assignment['ApprovedBy']); ?></p>
                        <p class="text-xs text-gray-500 font-light"><?php echo date("M j, Y", strtotime($assignment['ApprovalDate'])); ?></p>
                      <?php elseif ($assignment['AssignmentStatus'] == 'Submitted' && ($userRole == 'DN' || $userRole == 'PH' || $userRole == 'COR')): ?>
                        <div class="flex flex-col gap-1 mt-1">
                          <form method="POST" action="../task/task_actions.php" class="inline">
                            <input type="hidden" name="task_assignment_id" value="<?php echo $assignment['TaskAssignmentID']; ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="text-emerald-600 hover:text-emerald-700 font-medium text-xs font-overpass hover:underline transition-all duration-200">
                              Mark as Complete
                            </button>
                          </form>
                          <button onclick="event.stopPropagation(); openRevisionModal('<?php echo $assignment['TaskAssignmentID']; ?>', '<?php echo htmlspecialchars($assignment['CourseCode'] . ' - ' . $assignment['CourseTitle']); ?>')" class="text-amber-600 hover:text-amber-700 font-medium text-xs font-overpass hover:underline transition-all duration-200">
                            Request Revision
                          </button>
                        </div>
                      <?php elseif ($assignment['AssignmentStatus'] == 'Submitted'): ?>
                        <p class="text-xs text-gray-500 font-light">Submitted: <?php echo date("M j, Y", strtotime($assignment['SubmissionDate'])); ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-center text-gray-500 my-4 font-light">No courses assigned to this task.</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <div class="files-section">
      <div class="bg-white rounded-xl shadow-lg p-8 max-w-md mx-auto mt-8">
        <h3 class="text-2xl font-bold mb-8 text-gray-800 font-overpass" style="font-family: 'Overpass', sans-serif;">Your files</h3>
        <?php 
        $uploadableTasks = [];
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                foreach ($task['Assignments'] as $assignment) {
                    // Only include if assigned to the current user and not completed
                    if (
                        $assignment['AssignmentStatus'] != 'Completed' &&
                        isset($assignment['PersonnelID']) &&
                        $assignment['PersonnelID'] == $personnelID
                    ) {
                        $uploadableTasks[] = [
                            'taskID' => $task['TaskID'],
                            'taskTitle' => $task['Title'],
                            'courseCode' => $assignment['CourseCode'],
                            'courseTitle' => $assignment['CourseTitle'],
                            'programID' => $assignment['ProgramID'],
                            'status' => $assignment['AssignmentStatus'],
                            'submissionPath' => $assignment['SubmissionPath']
                        ];
                    }
                }
            }
        }
        ?>
        <?php if (!empty($uploadableTasks)): ?>
          <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
            <div class="mb-6">
              <label class="block mb-2 font-semibold text-gray-700">Select Task:</label>
              <select name="task_selector" id="taskSelector" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none transition">
                <option value="">-- Select Task --</option>
                <?php foreach ($uploadableTasks as $index => $task): ?>
                  <option value="<?php echo $index; ?>">
                    <?php echo htmlspecialchars($task['taskTitle'] . ' - ' . $task['courseCode']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="uploadFormFields" class="hidden">
              <input type="hidden" name="task_id" id="taskID">
              <input type="hidden" name="course_code" id="courseCode">
              <input type="hidden" name="program_id" id="programID">
              
              <div class="file-input-container mb-6">
                <label for="taskFile" class="file-input-label flex flex-col items-center justify-center border-2 border-dashed border-blue-300 bg-blue-50 hover:bg-blue-100 transition rounded-lg py-8 cursor-pointer">
                  <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2"></i>
                  <span class="text-gray-600 font-medium">Drag and drop your file here<br>or click to browse</span>
                </label>
                <input type="file" name="task_file" id="taskFile" class="file-input" onchange="displayFileName()">
              </div>
              <div id="selectedFile" class="selected-file hidden flex items-center justify-between bg-blue-100 rounded-full px-4 py-2 mb-4">
                <div class="flex items-center gap-2">
                  <i class="fas fa-file-alt text-blue-500"></i>
                  <span id="fileName" class="file-name font-medium text-gray-800 cursor-pointer hover:underline" title="Preview file"></span>
                </div>
                <span class="remove-file ml-2 hover:text-red-600 transition" onclick="removeFile()">
                  <i class="fas fa-times"></i>
                </span>
              </div>
              <!-- Co-author checkboxes -->
              <?php if (!empty($coauthors)): ?>
              <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-700">Select Co-Authors:</label>
                <div class="rounded-2xl shadow-lg bg-white border border-gray-200 p-4" style="max-width: 350px;">
                  <div class="sticky top-0 z-10 bg-white pb-2">
                    <input type="text" id="coauthorSearch" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition text-base" placeholder="Search co-authors...">
                  </div>
                  <div class="mb-2 mt-2">
                    <label class="flex items-center space-x-3 px-2 py-2 rounded-lg cursor-pointer transition bg-blue-50 hover:bg-blue-100 font-semibold">
                      <input type="checkbox" id="coauthorNone" name="coauthors_none" value="none" class="form-checkbox h-5 w-5 text-blue-600 transition-all duration-150">
                      <span class="text-blue-700">None</span>
                    </label>
                  </div>
                  <div id="coauthorList" class="flex flex-col gap-y-1 overflow-y-auto pr-1" style="min-width: 200px; max-height: 220px;">
                    <?php foreach ($coauthors as $coauthor): ?>
                      <label class="flex items-center space-x-3 px-2 py-2 rounded-lg cursor-pointer transition hover:bg-blue-50 coauthor-item">
                        <input type="checkbox" name="coauthors[]" value="<?php echo $coauthor['PersonnelID']; ?>" class="form-checkbox h-5 w-5 text-blue-600 coauthor-checkbox transition-all duration-150">
                        <span class="text-gray-800 coauthor-name text-base font-medium"><?php echo htmlspecialchars($coauthor['FirstName'] . ' ' . $coauthor['LastName']); ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              <!-- End co-author checkboxes -->
              <button type="submit" name="submit_file" class="submit-btn mt-4 w-full py-3 text-lg font-semibold font-onest rounded-lg bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50" id="submitBtn" disabled>
                Submit
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="text-center py-8">
            <i class="fas fa-info-circle text-4xl text-blue-500 mb-3"></i>
            <p>No tasks assigned to you yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script>
   
    document.addEventListener('DOMContentLoaded', function() {
     
      if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
      }
    
      const taskSelector = document.getElementById('taskSelector');
      if (taskSelector) {
        taskSelector.addEventListener('change', updateTaskSelection);
      }
     
      setInterval(function() {
        
        console.log('Checking for task updates...');
      }, 30000); // Every 30 seconds
    });

    function updateTaskSelection() {
      const selector = document.getElementById('taskSelector');
      const uploadForm = document.getElementById('uploadFormFields');
      const submitBtn = document.getElementById('submitBtn');
      const fileInput = document.getElementById('taskFile');
      const selectedFile = document.getElementById('selectedFile');

      if (selector.value === '') {
        uploadForm.classList.add('hidden');
        if (fileInput) fileInput.value = '';
        if (selectedFile) selectedFile.classList.add('hidden');
        submitBtn.disabled = true;
        return;
      }

      const taskData = <?php echo json_encode($uploadableTasks); ?>[selector.value];

      document.getElementById('taskID').value = taskData.taskID;
      document.getElementById('courseCode').value = taskData.courseCode;
      document.getElementById('programID').value = taskData.programID;

      if (taskData.submissionPath) {
        const pathParts = taskData.submissionPath.split('/');
        const file = pathParts[pathParts.length - 1];
        document.getElementById('fileName').textContent = file;
        selectedFile.classList.remove('hidden');
        submitBtn.disabled = true;
      } else {
        selectedFile.classList.add('hidden');
        submitBtn.disabled = true;
      }

      uploadForm.classList.remove('hidden');
    }

    function displayFileName() {
      const fileInput = document.getElementById('taskFile');
      const fileName = document.getElementById('fileName');
      const selectedFile = document.getElementById('selectedFile');
      const submitBtn = document.getElementById('submitBtn');

      if (fileInput.files.length > 0) {
        fileName.textContent = fileInput.files[0].name;
        selectedFile.classList.remove('hidden');
        submitBtn.disabled = false;
      } else {
        selectedFile.classList.add('hidden');
        submitBtn.disabled = true;
      }
    }

    function removeFile() {
      const fileInput = document.getElementById('taskFile');
      const selectedFile = document.getElementById('selectedFile');
      const submitBtn = document.getElementById('submitBtn');
      const fileName = document.getElementById('fileName');
      selectedFile.classList.add('hidden');
      fileInput.value = '';
      submitBtn.disabled = true;
      if (fileName) fileName.textContent = '';
    }

    function previewSubmission(path, title) {
      const modal = document.getElementById('previewModal');
      const previewContent = document.getElementById('previewContent');
      const previewTitle = document.getElementById('previewTitle');
  
      previewTitle.textContent = title;
    
      const extension = path.split('.').pop().toLowerCase();
      
      previewContent.innerHTML = '';
   
      modal.classList.remove('hidden');
      
      if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        previewContent.innerHTML = `<img src="${path}" class="max-w-full h-[93%] object-contain mx-auto" onerror="handleImageError(this)">`;
      } else if (extension === 'pdf') {
        previewContent.innerHTML = `<embed src="${path}" type="application/pdf" class="w-full h-full" onerror="handlePdfError(this)">`;
      } else {
        previewContent.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-file-alt text-4xl text-blue-500 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-300">Preview not available for this file type</p>
            <a href="${path}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              Download File
            </a>
          </div>
        `;
      }
    }

    function handleImageError(img) {
      img.onerror = null;
      img.parentElement.innerHTML = `
        <div class="text-center py-8">
          <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
          <p class="text-gray-600 dark:text-gray-300">Unable to load image preview</p>
          <a href="${img.src}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Download File
          </a>
        </div>
      `;
    }

    function handlePdfError(embed) {
      embed.onerror = null; 
      embed.parentElement.innerHTML = `
        <div class="text-center py-8">
          <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
          <p class="text-gray-600 dark:text-gray-300">Unable to load PDF preview</p>
          <a href="${embed.src}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Download File
          </a>
        </div>
      `;
    }

   
    function toggleTaskDetails(taskId) {
      const detailsSection = document.getElementById('task-details-' + taskId);
      if (detailsSection.classList.contains('hidden')) {
        detailsSection.classList.remove('hidden');
      } else {
        detailsSection.classList.add('hidden');
      }
    }

  
    function notifyTaskUpdate(taskId, status) {
     
      const notification = document.createElement('div');
      notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded';
      notification.innerText = `Task ${taskId} has been ${status}`;
      document.body.appendChild(notification);
      setTimeout(function() {
        notification.remove();
      }, 3000);
    }

 
    const coauthorSearch = document.getElementById('coauthorSearch');
    const coauthorList = document.getElementById('coauthorList');
    coauthorSearch.addEventListener('input', function() {
      const term = this.value.toLowerCase();
      coauthorList.querySelectorAll('.coauthor-item').forEach(function(label) {
        const name = label.querySelector('.coauthor-name').textContent.toLowerCase();
        label.style.display = name.includes(term) ? '' : 'none';
      });
    });

    const noneBox = document.getElementById('coauthorNone');
    const coauthorCheckboxes = coauthorList.querySelectorAll('.coauthor-checkbox');
    noneBox.addEventListener('change', function() {
      if (this.checked) {
        coauthorCheckboxes.forEach(cb => { cb.checked = false; cb.disabled = true; });
      } else {
        coauthorCheckboxes.forEach(cb => { cb.disabled = false; });
      }
    });
    coauthorCheckboxes.forEach(cb => {
      cb.addEventListener('change', function() {
        if (this.checked) {
          noneBox.checked = false;
          noneBox.dispatchEvent(new Event('change'));
        }
      });
    });

    function previewSubmission(path, title) {
      const modal = document.getElementById('previewModal');
      const previewContent = document.getElementById('previewContent');
      const previewTitle = document.getElementById('previewTitle');
  
      previewTitle.textContent = title;
    
      const extension = path.split('.').pop().toLowerCase();
      
      previewContent.innerHTML = '';
   
      modal.classList.remove('hidden');
      
      if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        previewContent.innerHTML = `<img src="${path}" class="max-w-full h-[93%] object-contain mx-auto" onerror="handleImageError(this)">`;
      } else if (extension === 'pdf') {
        previewContent.innerHTML = `<embed src="${path}" type="application/pdf" class="w-full h-full" onerror="handlePdfError(this)">`;
      } else {
        previewContent.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-file-alt text-4xl text-blue-500 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-300">Preview not available for this file type</p>
            <a href="${path}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              Download File
            </a>
          </div>
        `;
      }
    }

    function closePreview() {
      const modal = document.getElementById('previewModal');
      modal.classList.add('hidden');
    }


    document.getElementById('previewModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closePreview();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closePreview();
      }
    });
  </script>

  <div id="previewModal" class="fixed inset-0 hidden z-50 flex items-center justify-center overflow-hidden">
    <div class="bg-gray-300 dark:bg-gray-800 rounded-lg shadow-xl w-full m-4 max-w-[98%] h-screen flex flex-col">
      <div class="flex justify-between items-center px-4 py-2 border-b dark:border-gray-700">
        <h3 class="text-sm font-medium dark:text-white" id="previewTitle">File Preview</h3>
        <button onclick="closePreview()" class="text-gray-700 hover:text-red-600 text-2xl font-bold" title="Close">&times;</button>
      </div>
      <div class="flex-1 overflow-auto">
        <div id="previewContent" class="w-full h-full flex items-center justify-center">
          <!-- contents here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Revision Modal -->
  <div id="revisionModal" class="hidden fixed inset-0 bg-transparent bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-[90%] max-w-[600px]">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold font-overpass">Request Revision</h2>
            <button onclick="closeRevisionModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="../task/task_actions.php" class="space-y-4">
            <input type="hidden" name="task_assignment_id" id="revisionTaskId">
            <input type="hidden" name="action" value="revise">
            <div>
                <label class="block mb-1 font-medium">Reason for Revision:</label>
                <textarea name="revision_reason" required 
                          class="w-full p-2 border rounded" 
                          rows="4" 
                          placeholder="Please provide specific reasons for requesting a revision..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRevisionModal()" 
                        class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    Submit Revision Request
                </button>
            </div>
        </form>
    </div>
  </div>

  <script>
    function openRevisionModal(taskId, title) {
        const modal = document.getElementById('revisionModal');
        const revisionTaskId = document.getElementById('revisionTaskId');
        
        revisionTaskId.value = taskId;
        modal.classList.remove('hidden');
    }

    function closeRevisionModal() {
        const modal = document.getElementById('revisionModal');
        modal.classList.add('hidden');
        document.getElementById('revisionReason').value = '';
    }

    document.getElementById('revisionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRevisionModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRevisionModal();
        }
    });
  </script>

  <!-- Add the preview modal if not already present -->
  <div id="uploadPreviewModal" class="fixed inset-0 hidden z-50 flex items-center justify-center overflow-hidden">
    <div class="bg-gray-300 dark:bg-gray-800 rounded-lg shadow-xl w-full m-4 max-w-[98%] h-screen flex flex-col">
      <div class="flex justify-between items-center px-4 py-2 border-b dark:border-gray-700">
        <h3 class="text-sm font-medium dark:text-white" id="uploadPreviewTitle">File Preview</h3>
        <button onclick="closeUploadPreviewModal()" class="text-gray-700 hover:text-red-600 text-2xl font-bold" title="Close">&times;</button>
      </div>
      <div class="flex-1 overflow-auto">
        <div id="uploadPreviewContent" class="w-full h-full flex items-center justify-center">
          <!-- Content  here -->
        </div>
      </div>
    </div>
  </div>

  <script>

    const fileNameElem = document.getElementById('fileName');
    if (fileNameElem) {
      fileNameElem.onclick = function() {
        const fileInput = document.getElementById('taskFile');
        if (fileInput.files.length > 0) {
          previewUploadFile(fileInput.files[0]);
        }
      };
    }

    function previewUploadFile(file) {
      const modal = document.getElementById('uploadPreviewModal');
      const content = document.getElementById('uploadPreviewContent');
      const title = document.getElementById('uploadPreviewTitle');
      title.textContent = file.name;
      content.innerHTML = '';
      modal.classList.remove('hidden');
      const fileType = file.type;
      const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
      if (validImageTypes.includes(fileType)) {
        const reader = new FileReader();
        reader.onload = function(e) {
          content.innerHTML = `<img src="${e.target.result}" class="max-w-full h-[93%] object-contain mx-auto" onerror="handleImageError(this)">`;
        };
        reader.readAsDataURL(file);
      } else if (fileType === 'application/pdf') {
        const objectUrl = URL.createObjectURL(file);
        content.innerHTML = `<embed src="${objectUrl}" type="application/pdf" class="w-full h-full" onerror="handlePdfError(this)">`;
      } else {
        content.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-file-alt text-4xl text-blue-500 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-300">Preview not available for this file type</p>
            <a href="#" onclick="downloadUploadFile()" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Download File</a>
          </div>
        `;
      }
    }

    function closeUploadPreviewModal() {
      const modal = document.getElementById('uploadPreviewModal');
      const content = document.getElementById('uploadPreviewContent');
      content.innerHTML = '';
      modal.classList.add('hidden');
    }

    function downloadUploadFile() {
      const fileInput = document.getElementById('taskFile');
      if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const url = URL.createObjectURL(file);
        const a = document.createElement('a');
        a.href = url;
        a.download = file.name;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
          document.body.removeChild(a);
          URL.revokeObjectURL(url);
        }, 100);
      }
    }
  </script>
</body>
</html>