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

function getDueDateClass($dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    
    $today->setTime(0, 0, 0);
    $due->setTime(0, 0, 0);
    
    $diff = $today->diff($due);
    $daysUntilDue = $diff->days;
    
    if ($today > $due) {
        $daysUntilDue = -$daysUntilDue;
    }
    
    return $daysUntilDue <= 3 ? 'text-red-500' : 'text-gray-700';
}

function getDueDateTooltip($dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    
    $today->setTime(0, 0, 0);
    $due->setTime(0, 0, 0);
    
    $diff = $today->diff($due);
    $daysUntilDue = $diff->days;
    
    if ($today > $due) {
        return "Overdue by " . $diff->days . " day/s";
    } else if ($daysUntilDue <= 3) {
        return "Due in " . $daysUntilDue . " day/s";
    }
    return "Due in " . $daysUntilDue . " day/s";
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
$members = [];
$facultyID = null;
$facultyCoursePairs = [];
$message = "";

// Get curriculum ID from URL
$curriculumID = isset($_GET['curriculum_id']) ? intval($_GET['curriculum_id']) : null;

$debug = false;
if ($debug) {
    echo "AccountID: " . $accountID . "<br>";
    echo "UserRole: " . $userRole . "<br>";
}


$sql = "SELECT personnel.PersonnelID, personnel.FacultyID, faculties.Faculty 
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

 
    $memberQuery = "SELECT FirstName, LastName, Role 
                    FROM personnel 
                    WHERE FacultyID = ?";
    $memberStmt = $conn->prepare($memberQuery);
    $memberStmt->bind_param("i", $facultyID);
    $memberStmt->execute();
    $memberResult = $memberStmt->get_result();

    while ($memberRow = $memberResult->fetch_assoc()) {
        $members[] = $memberRow;
    }

    $memberStmt->close();
    
    $coursesQuery = "SELECT pc.ProgramID, p.ProgramCode, p.ProgramName, 
                    pc.CourseCode, c.Title, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                    per.PersonnelID, pc.CurriculumID, cu.Name
                    FROM program_courses pc
                    JOIN courses c ON pc.CourseCode = c.CourseCode
                    JOIN programs p ON pc.ProgramID = p.ProgramID
                    LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
                    LEFT JOIN curricula cu ON pc.CurriculumID = cu.ID
                    WHERE pc.FacultyID = ?
                    ORDER BY p.ProgramName, c.CourseCode";
    
    $coursesStmt = $conn->prepare($coursesQuery);
    $coursesStmt->bind_param("i", $facultyID);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    
    while ($courseRow = $coursesResult->fetch_assoc()) {
        $facultyCoursePairs[] = $courseRow;
    }
    
    $coursesStmt->close();
} else {
    $facultyName = "No Faculty Assigned";
}

$stmt->close();

$userRole = '';
$userRoleQuery = "SELECT Role FROM personnel WHERE AccountID = ?";
$roleStmt = $conn->prepare($userRoleQuery);
$roleStmt->bind_param("i", $accountID);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $userRole = $roleResult->fetch_assoc()['Role'];
}
$roleStmt->close();

// FM: Fetch ongoing tasks BEFORE HTML
if ($userRole === 'FM') {
    $ongoingTasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
        t.CreatedBy,
        COUNT(ta.CourseCode) as AssignedCourses,
        SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
        p.Role as UserRole,
        CONCAT(creator.FirstName, ' ', creator.LastName) as CreatorName
        FROM tasks t
        JOIN task_assignments ta ON t.TaskID = ta.TaskID
        JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
        LEFT JOIN personnel p ON p.AccountID = ?
        LEFT JOIN personnel creator ON t.CreatedBy = creator.PersonnelID
        WHERE pc.PersonnelID = ? AND ta.Status IN ('Pending', 'In Progress')
        GROUP BY t.TaskID
        ORDER BY t.CreatedAt DESC";
    $ongoingTasksStmt = $conn->prepare($ongoingTasksSql);
    $ongoingTasksStmt->bind_param("ii", $accountID, $personnelID);
    $ongoingTasksStmt->execute();
    $ongoingTasksResult = $ongoingTasksStmt->get_result();
    $ongoingTasks = [];
    while ($taskRow = $ongoingTasksResult->fetch_assoc()) {
        $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
            p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
            ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, ta.PersonnelID as PersonnelID,
            cu.id as CurriculumID, cu.name as CurriculumName
            FROM task_assignments ta
            JOIN courses c ON ta.CourseCode = c.CourseCode
            JOIN programs p ON ta.ProgramID = p.ProgramID
            LEFT JOIN personnel per ON ta.PersonnelID = per.PersonnelID
            LEFT JOIN curricula cu ON ta.CurriculumID = cu.id
            WHERE ta.TaskID = ?
            ORDER BY p.ProgramName, ta.CourseCode";
        $coursesStmt = $conn->prepare($coursesSql);
        $coursesStmt->bind_param("i", $taskRow['TaskID']);
        $coursesStmt->execute();
        $coursesResult = $coursesStmt->get_result();
        $courses = [];
        while ($courseRow = $coursesResult->fetch_assoc()) {
            $courses[] = $courseRow;
        }
        $coursesStmt->close();
        $taskRow['Courses'] = $courses;
        $ongoingTasks[] = $taskRow;
    }
    $ongoingTasksStmt->close();

    // Fetch completed tasks for FM
    $completedTasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
        t.CreatedBy,
        COUNT(DISTINCT ta.CourseCode) as AssignedCourses,
        SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
        ? as UserRole,
        CONCAT(p.FirstName, ' ', p.LastName) as CreatorName,
        ta.CurriculumID,
        c.name as CurriculumName
        FROM tasks t
        LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
        LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode 
            AND ta.ProgramID = pc.ProgramID 
            AND ta.CurriculumID = pc.CurriculumID
        LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
        LEFT JOIN curricula c ON ta.CurriculumID = c.id
        WHERE t.FacultyID = ? AND t.Status = 'Completed'
        GROUP BY t.TaskID, ta.CurriculumID
        ORDER BY t.CreatedAt DESC";
    $completedTasksStmt = $conn->prepare($completedTasksSql);
    $completedTasksStmt->bind_param("si", $userRole, $facultyID);
    $completedTasksStmt->execute();
    $completedTasksResult = $completedTasksStmt->get_result();
    $completedTasks = [];
    while ($taskRow = $completedTasksResult->fetch_assoc()) {
        $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
            p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
            ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, ta.PersonnelID as PersonnelID,
            cu.id as CurriculumID, cu.name as CurriculumName
            FROM task_assignments ta
            JOIN courses c ON ta.CourseCode = c.CourseCode
            JOIN programs p ON ta.ProgramID = p.ProgramID
            LEFT JOIN personnel per ON ta.PersonnelID = per.PersonnelID
            LEFT JOIN curricula cu ON ta.CurriculumID = cu.id
            WHERE ta.TaskID = ?
            ORDER BY p.ProgramName, ta.CourseCode";
        $coursesStmt = $conn->prepare($coursesSql);
        $coursesStmt->bind_param("i", $taskRow['TaskID']);
        $coursesStmt->execute();
        $coursesResult = $coursesStmt->get_result();
        
        $courses = [];
        while ($courseRow = $coursesResult->fetch_assoc()) {
            $courses[] = $courseRow;
        }
        $coursesStmt->close();
        
        $taskRow['Courses'] = $courses;
        $completedTasks[] = $taskRow;
    }
    $completedTasksStmt->close();
}

if (isset($_POST['create_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['due_date'];
    $schoolYear = $_POST['school_year'];
    $term = $_POST['term'];
    $curriculumId = $_POST['curriculum_id']; // Add this line
    
    $taskInsertSql = "INSERT INTO tasks (Title, Description, CreatedBy, FacultyID, DueDate, Status, CreatedAt, SchoolYear, Term) 
                      VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), ?, ?)";
    $taskStmt = $conn->prepare($taskInsertSql);
    $taskStmt->bind_param("ssiisss", $title, $description, $personnelID, $facultyID, $dueDate, $schoolYear, $term);
    
    if ($taskStmt->execute()) {
        $taskID = $taskStmt->insert_id;
        
        if (isset($_POST['assigned']) && is_array($_POST['assigned'])) {
            $assignmentInsertSql = "INSERT INTO task_assignments (TaskID, ProgramID, CourseCode, FacultyID, PersonnelID, Status, CurriculumID) 
                                    VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
            $assignmentStmt = $conn->prepare($assignmentInsertSql);

            foreach ($_POST['assigned'] as $assignment) {
                $parts = explode('|', $assignment);
                if (count($parts) == 2) {
                    $programID = $parts[0];
                    $courseCode = $parts[1];

                    // First get the curriculum ID from program_courses
                    $curriculumQuery = "SELECT pc.CurriculumID, pc.PersonnelID 
                                      FROM program_courses pc 
                                      WHERE pc.ProgramID = ? 
                                      AND pc.CourseCode = ? 
                                      AND pc.FacultyID = ? 
                                      ORDER BY pc.PersonnelID IS NOT NULL DESC, pc.CurriculumID DESC
                                      LIMIT 1";
                    $curriculumStmt = $conn->prepare($curriculumQuery);
                    $curriculumStmt->bind_param("isi", $programID, $courseCode, $facultyID);
                    $curriculumStmt->execute();
                    $curriculumResult = $curriculumStmt->get_result();
                    
                    while ($curriculumRow = $curriculumResult->fetch_assoc()) {
                        $curriculumId = $curriculumRow['CurriculumID'];
                        $assignedPersonnelID = $curriculumRow['PersonnelID'];
                        
                        // Debug log
                        error_log("Creating task assignment for ProgramID: $programID, CourseCode: $courseCode, FacultyID: $facultyID, CurriculumID: $curriculumId, PersonnelID: $assignedPersonnelID");
                        
                        $assignmentStmt->bind_param("iisiii", $taskID, $programID, $courseCode, $facultyID, $assignedPersonnelID, $curriculumId);
                        $assignmentStmt->execute();

                        if ($assignedPersonnelID) {
                            $accQuery = "SELECT AccountID FROM personnel WHERE PersonnelID = ?";
                            $accStmt = $conn->prepare($accQuery);
                            $accStmt->bind_param("i", $assignedPersonnelID);
                            $accStmt->execute();
                            $accResult = $accStmt->get_result();
                            if ($accRow = $accResult->fetch_assoc()) {
                                $assignedAccountID = $accRow['AccountID'];
                                if ($assignedAccountID) {
                                    $insertNotificationSql = "INSERT INTO notifications (AccountID, Title, Message, TaskID) 
                                        VALUES (?, ?, ?, ?)";
                                    $notificationTitle = "New Task Assigned";
                                    $notificationMessage = "You have been assigned a new task: " . $title . " for " . $courseCode;
                                    $insertNotificationStmt = $conn->prepare($insertNotificationSql);
                                    $insertNotificationStmt->bind_param("issi", $assignedAccountID, $notificationTitle, $notificationMessage, $taskID);
                                    $insertNotificationStmt->execute();
                                    $insertNotificationStmt->close();
                                }
                            }
                            $accStmt->close();
                        }
                    }
                    $curriculumStmt->close();
                }
            }
            $assignmentStmt->close();
        }

        $logDesc = "Created new task: " . htmlspecialchars($title);
        $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description) 
                   SELECT ?, ?, CONCAT(FirstName, ' ', LastName), ? 
                   FROM personnel WHERE PersonnelID = ?";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iisi", $facultyID, $personnelID, $logDesc, $personnelID);
        $logStmt->execute();
        $logStmt->close();
        
        $message = "Task created successfully!";
    } else {
        $message = "Error creating task: " . $taskStmt->error;
    }
    
    $taskStmt->close();
}

if ($userRole === 'DN' || $userRole === 'PH' || $userRole === 'COR') {
    $view = isset($_GET['view']) ? $_GET['view'] : 'all';
    
    if ($view === 'created') {
        $tasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                    t.CreatedBy,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID) as AssignedCourses,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID AND Status = 'Completed' AND ReviewStatus = 'Approved') as CompletedCount,
                    p.Role as UserRole,
                    CONCAT(creator.FirstName, ' ', creator.LastName) as CreatorName,
                    ta.CurriculumID,
                    c.name as CurriculumName
                    FROM tasks t
                    LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                    LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                    LEFT JOIN personnel p ON p.AccountID = ?
                    LEFT JOIN personnel creator ON t.CreatedBy = creator.PersonnelID
                    LEFT JOIN curricula c ON ta.CurriculumID = c.id
                    WHERE t.FacultyID = ? AND t.Status != 'Completed'
                    " . ($curriculumID ? "AND ta.CurriculumID = ?" : "") . "
                    GROUP BY t.TaskID
                    ORDER BY t.CreatedAt DESC";
        $tasksStmt = $conn->prepare($tasksSql);
        if ($curriculumID) {
            $tasksStmt->bind_param("iii", $accountID, $facultyID, $curriculumID);
        } else {
            $tasksStmt->bind_param("ii", $accountID, $facultyID);
        }
    } elseif ($view === 'foryou') {
        $tasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                    t.CreatedBy,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID) as AssignedCourses,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID AND Status = 'Completed' AND ReviewStatus = 'Approved') as CompletedCount,
                    p.Role as UserRole,
                    CONCAT(creator.FirstName, ' ', creator.LastName) as CreatorName,
                    ta.CurriculumID,
                    c.name as CurriculumName
                    FROM tasks t
                    LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                    LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                    LEFT JOIN personnel p ON p.AccountID = ?
                    LEFT JOIN personnel creator ON t.CreatedBy = creator.PersonnelID
                    LEFT JOIN curricula c ON ta.CurriculumID = c.id
                    WHERE t.FacultyID = ? AND pc.PersonnelID = ? AND t.Status != 'Completed'
                    " . ($curriculumID ? "AND ta.CurriculumID = ?" : "") . "
                    GROUP BY t.TaskID
                    ORDER BY t.CreatedAt DESC";
        $tasksStmt = $conn->prepare($tasksSql);
        if ($curriculumID) {
            $tasksStmt->bind_param("iiii", $accountID, $facultyID, $personnelID, $curriculumID);
        } else {
            $tasksStmt->bind_param("iii", $accountID, $facultyID, $personnelID);
        }
    } else {
        $tasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                    t.CreatedBy,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID) as AssignedCourses,
                    (SELECT COUNT(*) FROM task_assignments WHERE TaskID = t.TaskID AND Status = 'Completed' AND ReviewStatus = 'Approved') as CompletedCount,
                    p.Role as UserRole,
                    CONCAT(creator.FirstName, ' ', creator.LastName) as CreatorName,
                    ta.CurriculumID,
                    c.name as CurriculumName
                    FROM tasks t
                    LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                    LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                    LEFT JOIN personnel p ON p.AccountID = ?
                    LEFT JOIN personnel creator ON t.CreatedBy = creator.PersonnelID
                    LEFT JOIN curricula c ON ta.CurriculumID = c.id
                    WHERE t.FacultyID = ? AND t.Status != 'Completed'
                    " . ($curriculumID ? "AND ta.CurriculumID = ?" : "") . "
                    GROUP BY t.TaskID
                    ORDER BY t.CreatedAt DESC";
        $tasksStmt = $conn->prepare($tasksSql);
        if ($curriculumID) {
            $tasksStmt->bind_param("iii", $accountID, $facultyID, $curriculumID);
        } else {
            $tasksStmt->bind_param("ii", $accountID, $facultyID);
        }
    }
} else {
    // Regular faculty view remains unchanged
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                t.CreatedBy,
                COUNT(DISTINCT CASE WHEN ta.CurriculumID = ? THEN ta.CourseCode END) as AssignedCourses,
                SUM(CASE WHEN ta.Status = 'Completed' AND ta.CurriculumID = ? THEN 1 ELSE 0 END) as CompletedCount,
                p.Role as UserRole,
                CONCAT(creator.FirstName, ' ', creator.LastName) as CreatorName,
                ta.CurriculumID,
                c.name as CurriculumName
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                LEFT JOIN personnel p ON p.AccountID = ?
                LEFT JOIN personnel creator ON t.CreatedBy = creator.PersonnelID
                LEFT JOIN curricula c ON ta.CurriculumID = c.id
                WHERE t.FacultyID = ? AND t.Status != 'Completed'
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("iiii", $curriculumID, $curriculumID, $accountID, $facultyID);
}
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

$tasks = [];
while ($taskRow = $tasksResult->fetch_assoc()) {

    $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
                  p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                  ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, ta.PersonnelID as PersonnelID,
                  cu.id as CurriculumID, cu.name as CurriculumName
                  FROM task_assignments ta
                  JOIN courses c ON ta.CourseCode = c.CourseCode
                  JOIN programs p ON ta.ProgramID = p.ProgramID
                  LEFT JOIN personnel per ON ta.PersonnelID = per.PersonnelID
                  LEFT JOIN curricula cu ON ta.CurriculumID = cu.id
                  WHERE ta.TaskID = ?
                  ORDER BY p.ProgramName, ta.CourseCode";
    $coursesStmt = $conn->prepare($coursesSql);
    $coursesStmt->bind_param("i", $taskRow['TaskID']);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    
    $courses = [];
    while ($courseRow = $coursesResult->fetch_assoc()) {
        $courses[] = $courseRow;
    }
    $coursesStmt->close();
    
    $taskRow['Courses'] = $courses;
    $tasks[] = $taskRow;
}
$tasksStmt->close();

if (isset($_POST['submit_file'])) {
  
    $getTaskIdSql = "SELECT TaskID FROM task_assignments WHERE TaskAssignmentID = ?";
    $getTaskIdStmt = $conn->prepare($getTaskIdSql);
    $getTaskIdStmt->bind_param("i", $taskAssignmentId);
    $getTaskIdStmt->execute();
    $getTaskIdResult = $getTaskIdStmt->get_result();
    if ($getTaskIdResult && $getTaskIdResult->num_rows > 0) {
        $taskIdRow = $getTaskIdResult->fetch_assoc();
        $taskId = $taskIdRow['TaskID'];
  
        $updateTaskSql = "UPDATE tasks SET Status = 'In Progress' WHERE TaskID = ? AND Status = 'Pending'";
        $updateTaskStmt = $conn->prepare($updateTaskSql);
        $updateTaskStmt->bind_param("i", $taskId);
        $updateTaskStmt->execute();
        $updateTaskStmt->close();

     
        $logDesc = "Submitted file for task ID: " . $taskId;
        $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description) 
                   SELECT ?, ?, CONCAT(FirstName, ' ', LastName), ? 
                   FROM personnel WHERE PersonnelID = ?";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iisi", $facultyID, $personnelID, $logDesc, $personnelID);
        $logStmt->execute();
        $logStmt->close();
    }
    $getTaskIdStmt->close();
    // ... rest of file submission logic ...
}

// Add audit log entry for task deletion
if (isset($_POST['action']) && $_POST['action'] === 'discard') {
    $taskId = $_POST['task_id'];
    
    // Get task details before deletion for audit log
    $getTaskSql = "SELECT Title FROM tasks WHERE TaskID = ?";
    $getTaskStmt = $conn->prepare($getTaskSql);
    $getTaskStmt->bind_param("i", $taskId);
    $getTaskStmt->execute();
    $taskResult = $getTaskStmt->get_result();
    if ($taskResult && $taskResult->num_rows > 0) {
        $taskRow = $taskResult->fetch_assoc();
        $taskTitle = $taskRow['Title'];
        
        // Add audit log entry
        $logDesc = "Deleted task: " . htmlspecialchars($taskTitle);
        $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description) 
                   SELECT ?, ?, CONCAT(FirstName, ' ', LastName), ? 
                   FROM personnel WHERE PersonnelID = ?";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iisi", $facultyID, $personnelID, $logDesc, $personnelID);
        $logStmt->execute();
        $logStmt->close();
    }
    $getTaskStmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-animate {
            animation: modalFadeIn 0.2s ease-out forwards;
        }

        .task-dropdown {
            max-height: 0; 
            opacity: 0;
            transform: translateX(20px); 
            transition: max-height 0.5s ease-in-out, opacity 0.3s ease-in-out, transform 0.5s ease-in-out;
            overflow: hidden; 
        }

        .task-dropdown.show {
            max-height: 300px; 
            opacity: 1; 
            transform: translateX(0); 
        }

        .task-button.open svg {
            transform: rotate(45deg); 
        }
        
        /* Course list styling */
        .course-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }

        .course-item {
            padding: 0.375rem 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .course-item:hover {
            background-color: #f3f4f6;
        }

        .program-title {
            font-weight: 600;
            color: #1e40af;
            padding: 0.25rem 0;
            margin-top: 0.5rem;
        }

        .course-filter-container {
            position: sticky;
            top: 0;
            background-color: white;
            padding: 0.5rem 0;
            z-index: 10;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .course-card.completed {
            background-color: #dcfce7;
            border-left-color: #10b981;
        }
        
        .course-card.submitted {
            background-color: #e0f2fe;
            border-left-color: #3b82f6;
        }
        
        .course-card.pending {
            background-color: #fef3c7;
            border-left-color: #f59e0b;
        }
        
        .status-label.completed {
            color: #10b981;
        }
        
        .status-label.submitted {
            color: #3b82f6;
        }
        
        .status-label.pending {
            color: #f59e0b;
        }

        .dark body,
        .dark .flex-1,
        .dark main,
        .dark .content {
          background: #18181b !important;
          color: #f3f4f6 !important;
        }
        .dark .bg-gray-100,
        .dark .bg-gray-200,
        .dark .bg-gray-300,
        .dark .bg-gray-400,
        .dark .bg-gray-50,
        .dark .bg-white {
          background: #23232a !important;
          color: #f3f4f6 !important;
        }
        .dark .text-gray-900,
        .dark .text-gray-800,
        .dark .text-gray-700,
        .dark .text-gray-600,
        .dark .text-gray-500,
        .dark .text-sm,
        .dark .text-xs {
          color: #f3f4f6 !important;
        }
        .dark .course-card.completed {
          background: #14532d !important; /* green-900 */
          color: #bbf7d0 !important;
        }
        .dark .course-card.submitted {
          background: #1e3a8a !important; /* blue-900 */
          color: #dbeafe !important;
        }
        .dark .course-card.pending {
          background: #78350f !important; /* yellow-900 */
          color: #fef9c3 !important;
        }
        .dark .text-gray-600,
        .dark .text-gray-500 {
          color: #a1a1aa !important;
        }
        .dark .text-blue-800 {
          color: #60a5fa !important;
        }
        .dark .text-green-800 {
          color: #6ee7b7 !important;
        }
        .dark .text-yellow-800 {
          color: #fde68a !important;
        }
        .dark .text-red-600 {
          color: #f87171 !important;
        }
        .dark .border,
        .dark .border-gray-200,
        .dark .border-gray-400 {
          border-color: #374151 !important;
        }
        .dark .form-input,
        .dark input,
        .dark textarea,
        .dark select {
          background: #18181b !important;
          color: #f3f4f6 !important;
          border-color: #374151 !important;
        }
        .dark .form-input:focus,
        .dark input:focus,
        .dark textarea:focus,
        .dark select:focus {
          border-color: #2563eb !important;
          box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
        }
        .dark .bg-green-100 {
          background: #134e2e !important;
          color: #bbf7d0 !important;
        }
        .dark .bg-blue-100 {
          background: #1e293b !important;
          color: #bae6fd !important;
        }
        .dark .bg-yellow-100 {
          background: #3b2f0b !important;
          color: #fde68a !important;
        }
        .dark .text-green-700,
        .dark .text-green-600 {
          color: #6ee7b7 !important;
        }
        .dark .text-blue-600 {
          color: #60a5fa !important;
        }
        .dark .text-yellow-600 {
          color: #fde68a !important;
        }
        .dark .text-green-600 {
          color: #6ee7b7 !important;
        }
        .dark .text-gray-400 {
          color: #a1a1aa !important;
        }
        .dark .modal-animate,
        .dark .bg-white[style] {
          background: #23232a !important;
          color: #f3f4f6 !important;
        }
        .dark .bg-black.bg-opacity-50 {
          background: rgba(24,24,27,0.85) !important;
        }
        .dark .text-green-800 {
          color: #bbf7d0 !important;
        }
        .dark .text-blue-800 {
          color: #bae6fd !important;
        }
        .dark .text-yellow-800 {
          color: #fde68a !important;
        }
        .dark .course-card.completed,
        .dark .course-card.completed * {
          color: #bbf7d0 !important;
        }
        /* Extra dark mode fixes for course cards and badges */
        body.dark .course-card, body.dark .bg-yellow-50, body.dark .bg-blue-50, body.dark .bg-green-50 {
          background-color: #23232a !important;
          color: #f3f4f6 !important;
        }
        body.dark .course-card .text-gray-800, body.dark .course-card .text-gray-600, body.dark .course-card .text-gray-500, body.dark .course-card .text-xs {
          color: #f3f4f6 !important;
        }
        body.dark .status-badge, body.dark .bg-yellow-100, body.dark .bg-blue-100, body.dark .bg-green-100 {
          background-color: #374151 !important;
          color: #f3f4f6 !important;
        }
        body.dark .text-yellow-700 {
          color: #fde68a !important;
        }

        body.dark .text-blue-700{
            color: #60a5fa !important;
        }
        body.dark .text-green-700 {
            color: #6ee7b7 !important;
        }

        body.dark .text-blue-600 { color: #60a5fa !important; }
        body.dark .text-yellow-600 { color: #fde68a !important; }
        body.dark .text-green-600 { color: #6ee7b7 !important; }
        body.dark .text-red-500 { color: #f87171 !important; }
        body.dark .status-badge.completed {
          background-color: #22c55e !important; /* green-500 */
          color: #022c22 !important;
        }
        body.dark .status-badge.submitted {
          background-color: #2563eb !important; /* blue-600 */
          color: #dbeafe !important;
        }
        body.dark .status-badge.pending {
          background-color: #f59e0b !important; /* yellow-500 */
          color: #23232a !important;
        }
        body.dark .dean-action-btn.preview { color: #60a5fa !important; }
        body.dark .dean-action-btn.revision { color: #fde68a !important; }
        body.dark .dean-action-btn.complete { color: #6ee7b7 !important; }
        .task-menu-dropdown {
            min-width: 180px;
            border-radius: 14px;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.10);
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 0;
        }
        .task-menu-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0.5rem 0;
        }
        .task-menu-dropdown li {
            margin: 0;
            padding: 0;
        }
        .dropdown-item {
            display: block;
            width: 100%;
            padding: 12px 22px;
            font-size: 1rem;
            font-family: inherit;
            background: none;
            border: none;
            outline: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-align: left;
            border-radius: 8px;
        }
        .dropdown-item:active {
            background: #f3f4f6;
        }
        .dropdown-item.text-red-600 {
            color: #dc2626;
        }
        .dropdown-item.text-red-600:hover, .dropdown-item.text-red-600:focus {
            background: #fef2f2;
            color: #b91c1c;
        }
        .dropdown-item:hover, .dropdown-item:focus {
            background: #f1f5f9;
            color: #2563eb;
        }
        .three-dot-btn {
            border-radius: 50%;
            transition: background 0.15s, color 0.15s;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .three-dot-btn:hover {
            background: #f3f4f6;
        }
        .three-dot-btn:hover svg {
            color: #334155;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex-1 flex flex-col px-[50px]  pb-[15px] pt-[15px] overflow-y-auto">
        <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Tasks</h1> 
        <hr class="border-gray-400">
        <p class="text-gray-500 mt-3 mb-5 font-onest">Here you can view tasks, assign responsibilities, update statuses, and ensure your faculty members stay on track with their deliverables.</p>
        
        <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Add View Selection Filter Buttons - Only for DEAN, PROGRAM HEAD, and COORDINATOR -->
        <?php if ($userRole === 'DN' || $userRole === 'PH' || $userRole === 'COR'): ?>
        <div class="flex items-center gap-4 mb-6">
            <a href="?view=all" 
               class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2 
                    <?php echo (!isset($_GET['view']) || $_GET['view'] === 'all') 
                        ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                        : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                All Tasks
            </a>
            <a href="?view=created" 
               class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2
                    <?php echo (isset($_GET['view']) && $_GET['view'] === 'created') 
                        ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                        : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                Created
            </a>
            <a href="?view=foryou" 
               class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2
                    <?php echo (isset($_GET['view']) && $_GET['view'] === 'foryou') 
                        ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                        : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                For You
            </a>
            <a href="?view=completed" 
               class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2
                    <?php echo (isset($_GET['view']) && $_GET['view'] === 'completed') 
                        ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                        : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                Completed
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Task Grid -->
        <?php
        // Only show the main grid if NOT FM
        if ($userRole !== 'FM') :
            // Only show the default message if not on the completed tab
            $onCompletedTab = (isset($_GET['view']) && $_GET['view'] === 'completed');
        ?>
            <div class="grid grid-cols-1 w-full md:w-[80%] px-4">
                <?php if (empty($tasks) && !$onCompletedTab): ?>
                    <div class="bg-white p-[25px] font-onest rounded-lg shadow-md flex justify-center items-center">
                        <p class="text-gray-500">No tasks available. Create your first task!</p>
                    </div>
                <?php else: ?>
                    <?php if ($userRole === 'DN' || $userRole === 'PH' || $userRole === 'COR'): ?>
                        <!-- All Tasks for DN, PH, and COR -->
                        <?php if (!isset($_GET['view']) || $_GET['view'] === 'all' || $_GET['view'] === 'created'): ?>
                        <div class="mb-4">
                            <h2 class="text-2xl font-bold text-gray-800 mb-2 font-overpass">
                                <?php echo (isset($_GET['view']) && $_GET['view'] === 'created') ? 'Tasks Created by You' : 'All Tasks'; ?>
                            </h2>
                            <?php foreach ($tasks as $task): ?>
                                <div class="bg-white p-8 font-onest rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-12 relative cursor-pointer"
                                     onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                    <?php if ($userRole === 'DN' || $userRole === 'COR' || $userRole === 'PH'): ?>
                                        <!-- 3-dot menu trigger -->
                                        <div class="absolute top-3 right-3 z-10">
                                            <button type="button" class="three-dot-btn p-2 text-gray-500 hover:text-gray-700 transition-colors duration-200" onclick="event.stopPropagation(); toggleTaskMenu(this)">
                                               
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <circle cx="12" cy="5" r="1.5"/>
                                                    <circle cx="12" cy="12" r="1.5"/>
                                                    <circle cx="12" cy="19" r="1.5"/>
                                                </svg>
                                            </button>
                                            <!-- Dropdown menu (hidden by default) -->
                                            <div class="hidden task-menu-dropdown absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-xl shadow-xl z-50" onclick="event.stopPropagation();">
                                                <ul class="py-2">
                                                    <li>
                                                        <button type="button" class="dropdown-item text-red-600 hover:bg-red-50 hover:text-red-700 w-full text-left" style="display:flex;align-items:center;gap:10px;" onclick="openDiscardTaskModal(<?php echo $task['TaskID']; ?>)">
                                                            <span style="display:inline-flex;align-items:center;">
                                                                <!-- Trash Icon -->
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </span>
                                                            Discard Task
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item hover:bg-blue-50 hover:text-blue-700 w-full text-left" style="display:flex;align-items:center;gap:10px;" onclick="generateTaskReport(<?php echo $task['TaskID']; ?>)">
                                                            <span style="display:inline-flex;align-items:center;">
                                                                <!-- Document/Report Icon -->
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2V7a2 2 0 00-2-2h-1.5a1 1 0 01-1-1V3.5a.5.5 0 00-.5-.5h-3a.5.5 0 00-.5.5V4a1 1 0 01-1 1H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                            </span>
                                                            Generate Report
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item hover:bg-blue-50 hover:text-blue-700 w-full text-left" style="display:flex;align-items:center;gap:10px;" onclick="openModifyTaskModal(<?php echo $task['TaskID']; ?>)">
                                                            <span style="display:inline-flex;align-items:center;">
                                                                <!-- Edit Icon -->
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </span>
                                                            Modify Task
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-0">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1
                                                <?php 
                                                if ($task['Status'] == 'Completed') {
                                                    echo 'bg-green-100 text-green-700';
                                                } elseif ($task['Status'] == 'In Progress') {
                                                    echo 'bg-blue-100 text-blue-700';
                                                } else {
                                                    echo 'bg-yellow-100 text-yellow-700';
                                                }
                                                ?>">
                                                <?php if ($task['Status'] == 'Completed'): ?>
                                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                                                <?php elseif ($task['Status'] == 'In Progress'): ?>
                                                    <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                                <?php else: ?>
                                                    <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><circle cx="12" cy="16" r="1"/></svg>
                                                <?php endif; ?>
                                                <?php echo $task['Status']; ?>
                                            </span>
                                        </div>
                                        <div class="flex flex-col text-sm text-gray-500 space-y-1 text-right mr-3">
                                            <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                            <span class="flex items-center justify-end gap-1">Due: 
                                                <?php 
                                                echo '<span class="font-semibold ' . getDueDateClass($task['DueDate']) . ' cursor-help" title="' . getDueDateTooltip($task['DueDate']) . '">' . date("F j, Y", strtotime($task['DueDate'])) . '</span>';
                                                ?>
                                            </span>
                                            <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 -mt-2 mb-4 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-medium text-gray-700">Progress:</span>
                                        <?php $progress = ($task['AssignedCourses'] > 0) ? round(($task['CompletedCount'] / $task['AssignedCourses']) * 100) : 0; ?>
                                        <div class="flex-1 min-w-[120px] max-w-[200px] h-3 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-3 rounded-full transition-all duration-300"
                                                style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                                        </div>
                                        <span class="text-sm text-gray-500"><?php echo $task['CompletedCount']; ?>/<?php echo $task['AssignedCourses']; ?> Complete</span>
                                    </div>
                                    <div class="mt-2">
                                        <span class="font-medium text-gray-700">Assigned Courses (<?php echo $task['AssignedCourses']; ?>):</span>
                                        <?php if (!empty($task['Courses'])): ?>
                                            <div class="mt-1 pl-2 border-l-2 border-gray-100 max-h-[120px] overflow-y-auto">
                                                <?php foreach ($task['Courses'] as $course): ?>
                                                <div class="flex items-center justify-between py-1 px-2 rounded-lg mb-1 course-card
                                                    <?php 
                                                        if ($course['AssignmentStatus'] === 'Completed') {
                                                            echo 'completed bg-green-50';
                                                        } elseif ($course['AssignmentStatus'] === 'Submitted') {
                                                            echo 'submitted bg-blue-50';
                                                        } else {
                                                            echo 'pending bg-yellow-50';
                                                        }
                                                    ?>">
                                                    <div>
                                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['CourseCode']); ?></span>
                                                        <span class="text-gray-600">- <?php echo htmlspecialchars($course['CourseTitle']); ?></span>
                                                        <br> 
                                                        <span class="ml-2 text-xs text-gray-500">Assigned to: <?php echo !empty($course['AssignedTo']) ? htmlspecialchars($course['AssignedTo']) : '<span class=\'text-red-500\'>No assigned professor</span>'; ?></span>
                                                    </div>
                                                    <div class="flex flex-col items-end gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold status-badge
                                                            <?php 
                                                                if ($course['AssignmentStatus'] === 'Completed') {
                                                                    echo 'completed bg-green-100 text-green-700';
                                                                } elseif ($course['AssignmentStatus'] === 'Submitted') {
                                                                    echo 'submitted bg-blue-100 text-blue-700';
                                                                } else {
                                                                    echo 'pending bg-yellow-100 text-yellow-700';
                                                                }
                                                            ?>">
                                                            <?php echo getAssignmentStatusLabel($course['AssignmentStatus']); ?>
                                                        </span>
                                                        <?php
                                                        if ($task['UserRole'] === 'DN' || $task['UserRole'] === 'COR') {
                                                            $submissionSql = "SELECT ta.TaskAssignmentID, ta.SubmissionPath, ta.SubmissionDate, ta.Status as AssignmentStatus FROM task_assignments ta WHERE ta.TaskID = ? AND ta.CourseCode = ? AND ta.ProgramID = ?";
                                                            $submissionStmt = $conn->prepare($submissionSql);
                                                            $submissionStmt->bind_param("isi", $task['TaskID'], $course['CourseCode'], $course['ProgramID']);
                                                            $submissionStmt->execute();
                                                            $submissionResult = $submissionStmt->get_result();
                                                            $submission = $submissionResult->fetch_assoc();
                                                            $submissionStmt->close();
                                                            
                                                            if ($submission) {
                                                                $taskAssignmentId = htmlspecialchars($submission['TaskAssignmentID']);
                                                                
                                                                if ($submission['AssignmentStatus'] === 'Submitted' || $submission['AssignmentStatus'] === 'Completed') {
                                                                    if (!empty($submission['SubmissionPath'])) {
                                                                        $previewPath = '../../' . htmlspecialchars($submission['SubmissionPath']);
                                                                        echo '<button onclick="event.stopPropagation(); openPreviewModal(\'' . $previewPath . '\', ' . $taskAssignmentId . ')" class="dean-action-btn preview text-blue-600 hover:underline text-xs">Preview Submission</button>';
                                                                    }
                                                                }
                                                                if ($submission['AssignmentStatus'] === 'Submitted') {
                                                                    echo '<button onclick="event.stopPropagation(); openRevisionModal(\'' . $taskAssignmentId . '\')" class="dean-action-btn revision text-yellow-600 hover:underline text-xs">Request Revision</button>';
                                                                    echo '<form method="POST" action="task_actions.php" class="inline" onclick="event.stopPropagation();">';
                                                                    echo '<input type="hidden" name="task_assignment_id" value="' . $taskAssignmentId . '">';
                                                                    echo '<input type="hidden" name="action" value="complete">';
                                                                    echo '<button type="submit" class="dean-action-btn complete text-green-600 hover:underline text-xs">Mark as Complete</button>';
                                                                    echo '</form>';
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-400 italic pl-3">No courses assigned</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-4 flex justify-end">
                                        <span class="text-blue-600 text-sm font-medium">Click to view submission details →</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Tasks Assigned to You for DN, PH, and COR -->
                        <?php
               
                        if (!isset($_GET['view']) || $_GET['view'] === 'all' || $_GET['view'] === 'foryou'):
                        $assignedTasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                            t.CreatedBy,
                            COUNT(DISTINCT ta.CourseCode) as AssignedCourses,
                            SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
                            ? as UserRole,
                            CONCAT(p.FirstName, ' ', p.LastName) as CreatorName,
                            ta.CurriculumID,
                            c.name as CurriculumName
                            FROM tasks t
                            LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                            LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                            LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                            LEFT JOIN curricula c ON ta.CurriculumID = c.id
                            WHERE t.FacultyID = ? AND pc.PersonnelID = ? AND t.Status != 'Completed'
                            GROUP BY t.TaskID
                            ORDER BY t.CreatedAt DESC";
                        $assignedTasksStmt = $conn->prepare($assignedTasksSql);
                        $assignedTasksStmt->bind_param("sii", $userRole, $facultyID, $personnelID);
                        $assignedTasksStmt->execute();
                        $assignedTasksResult = $assignedTasksStmt->get_result();
                        
                        $assignedTasks = [];
                        while ($taskRow = $assignedTasksResult->fetch_assoc()) {
                            
                            $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
                                        p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                                        ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, ta.PersonnelID as PersonnelID,
                                        cu.id as CurriculumID, cu.name as CurriculumName
                                        FROM task_assignments ta
                                        JOIN courses c ON ta.CourseCode = c.CourseCode
                                        JOIN programs p ON ta.ProgramID = p.ProgramID
                                        LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID AND ta.CurriculumID = pc.CurriculumID
                                        LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
                                        LEFT JOIN curricula cu ON ta.CurriculumID = cu.id
                                        WHERE ta.TaskID = ?
                                        ORDER BY p.ProgramName, ta.CourseCode";
                            $coursesStmt = $conn->prepare($coursesSql);
                            $coursesStmt->bind_param("i", $taskRow['TaskID']);
                            $coursesStmt->execute();
                            $coursesResult = $coursesStmt->get_result();
                            
                            $courses = [];
                            while ($courseRow = $coursesResult->fetch_assoc()) {
                                $courses[] = $courseRow;
                            }
                            $coursesStmt->close();
                            
                            $taskRow['Courses'] = $courses;
                            $assignedTasks[] = $taskRow;
                        }
                        $assignedTasksStmt->close();
                        ?>
                        <div class="mt-0">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4 font-overpass">Tasks Assigned to You</h2>
                            <?php if (empty($assignedTasks)): ?>
                                <div class="bg-white p-[25px] font-onest rounded-lg shadow-md flex justify-center items-center">
                                    <p class="text-gray-500">No tasks have been assigned to you yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assignedTasks as $task): ?>
                                    <div class="bg-white p-6 font-onest w-full rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                                         onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-0">
                                            <div class="flex items-center gap-3">
                                                <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1
                                                    <?php 
                                                    if ($task['Status'] == 'Completed') {
                                                        echo 'bg-green-100 text-green-700';
                                                    } elseif ($task['Status'] == 'In Progress') {
                                                        echo 'bg-blue-100 text-blue-700';
                                                    } else {
                                                        echo 'bg-yellow-100 text-yellow-700';
                                                    }
                                                    ?>">
                                                    <?php if ($task['Status'] == 'Completed'): ?>
                                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                                                    <?php elseif ($task['Status'] == 'In Progress'): ?>
                                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><circle cx="12" cy="16" r="1"/></svg>
                                                    <?php endif; ?>
                                                    <?php echo $task['Status']; ?>
                                                </span>
                                            </div>
                                            <div class="flex flex-col text-sm text-gray-500 space-y-1 text-right mr-3">
                                                <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                                <span class="flex items-center justify-end gap-1">Due: 
                                                    <?php 
                                                    echo '<span class="font-semibold ' . getDueDateClass($task['DueDate']) . ' cursor-help" title="' . getDueDateTooltip($task['DueDate']) . '">' . date("F j, Y", strtotime($task['DueDate'])) . '</span>';
                                                    ?>
                                                </span>
                                                <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 -mt-2 mb-4 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="font-medium text-gray-700">Progress:</span>
                                            <?php $progress = ($task['AssignedCourses'] > 0) ? round(($task['CompletedCount'] / $task['AssignedCourses']) * 100) : 0; ?>
                                            <div class="flex-1 min-w-[120px] max-w-[200px] h-3 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-3 rounded-full transition-all duration-300"
                                                    style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                                            </div>
                                            <span class="text-sm text-gray-500"><?php echo $task['CompletedCount']; ?>/<?php echo $task['AssignedCourses']; ?> Complete</span>
                                        </div>
                                        <div class="mt-4 flex justify-end">
                                            <span class="text-blue-600 text-sm font-medium">Click to view submission details →</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Regular Faculty View -->
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4 font-overpass">Tasks Assigned to You</h2>
                            <?php foreach ($tasks as $task): ?>
                                <div class=" bg-white p-8 rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                                     onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-0">
                                        <div class="flex items-center gap-3">
                                            <?php if ($userRole === 'DN' || $userRole === 'COR'): ?>
                                                <form method="POST" action="task_actions.php" class="inline mb-2" onclick="event.stopPropagation();">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['TaskID']; ?>">
                                                    <input type="hidden" name="action" value="discard">
                                                    <button type="submit" class="p-2 text-gray-500 hover:text-red-600 transition-colors duration-200" onclick="return confirm('Are you sure you want to discard this task?')" title="Discard Task">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1
                                                <?php 
                                                if ($task['Status'] == 'Completed') {
                                                    echo 'bg-green-100 text-green-700';
                                                } elseif ($task['Status'] == 'In Progress') {
                                                    echo 'bg-blue-100 text-blue-700';
                                                } else {
                                                    echo 'bg-yellow-100 text-yellow-700';
                                                }
                                                ?>">
                                                <?php if ($task['Status'] == 'Completed'): ?>
                                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>
                                                <?php elseif ($task['Status'] == 'In Progress'): ?>
                                                    <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                                <?php else: ?>
                                                    <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><circle cx="12" cy="16" r="1"/></svg>
                                                <?php endif; ?>
                                                <?php echo $task['Status']; ?>
                                            </span>
                                        </div>
                                        <div class="flex flex-col text-sm text-gray-500 space-y-1 text-right mr-3">
                                            <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                            <span class="flex items-center justify-end gap-1">Due: 
                                                <?php 
                                                echo '<span class="font-semibold ' . getDueDateClass($task['DueDate']) . ' cursor-help" title="' . getDueDateTooltip($task['DueDate']) . '">' . date("F j, Y", strtotime($task['DueDate'])) . '</span>';
                                                ?>
                                            </span>
                                            <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 -mt-2 mb-4 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-medium text-gray-700">Progress:</span>
                                        <?php $progress = ($task['AssignedCourses'] > 0) ? round(($task['CompletedCount'] / $task['AssignedCourses']) * 100) : 0; ?>
                                        <div class="flex-1 min-w-[120px] max-w-[200px] h-3 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-3 rounded-full transition-all duration-300"
                                                style="width: <?php echo $progress; ?>%; background: linear-gradient(90deg, #22c55e, #3b82f6);"></div>
                                        </div>
                                        <span class="text-sm text-gray-500"><?php echo $task['CompletedCount']; ?>/<?php echo $task['AssignedCourses']; ?> Complete</span>
                                    </div>
                                    <div class="mt-4 flex justify-end">
                                        <span class="text-blue-600 text-sm font-medium">Click to view submission details →</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Plus button - Only show for PH, COR, and DN -->
        <?php if ($userRole === 'PH' || $userRole === 'COR' || $userRole === 'DN'): ?>
        <a href="javascript:void(0)" onclick="toggleTaskDropdown()" 
            class="task-button fixed bottom-8 right-10 w-13 h-13 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-50"
            title="Add Task">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 transition-transform duration-500 ease-in-out" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>

        <!-- Task Dropdown -->
        <div id="task-dropdown" class="font-onest task-dropdown fixed bottom-24 right-10 w-40 bg-[#51D55A] shadow-lg rounded-full hover:bg-green-800 transition-all duration-300">
            <button onclick="openTaskModal()" 
                class="w-full text-xl text-center text-white py-3 px-4 active:bg-green-900 transition-colors duration-150"> 
                Create Task
            </button>
        </div>
        <?php endif; ?>

        <!-- Task Modal  -->
        <div id="taskModal" class="hidden fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-xl shadow-2xl w-[900px] border-2 border-gray-400 font-onest modal-animate max-h-[90vh] flex flex-col">
                <div class="flex-none">
                    <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">❇️ Create Task</h2>
                    <hr class="border-gray-400 mb-6">
                </div>
                
                <form method="POST" action="" class="flex-1 overflow-y-auto pr-2">
                    <div class="grid grid-cols-2 gap-8">
                        <!--  Task Details -->
                        <div class="space-y-4">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <!-- label class="block text-lg font-semibold text-gray-700">Task Title:</label -->
                                    <input type="text" name="title" placeholder="Enter task title" required 
                                        class="w-full p-3 font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" />
                                </div>
                                
                                <div class="space-y-2">
                                    <!-- label class="block text-lg font-semibold text-gray-700">Task Description:</label -->
                                    <textarea name="description" placeholder="Enter task message..." 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                        rows="5" style="white-space: pre-wrap;"></textarea>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-lg font-semibold text-gray-700">Due Date:</label>
                                <input type="date" name="due_date" 
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                    required />
                            </div>

                            <!-- School Year and Term -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-lg font-semibold text-gray-700">School Year:</label>
                                    <input type="text" name="school_year" placeholder="e.g. 2024-2025" 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                        required />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-lg font-semibold text-gray-700">Term:</label>
                                    <select name="term" 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                        required>
                                        <option value="">Select Term</option>
                                        <option value="1st">1st</option>
                                        <option value="2nd">2nd</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Course Assignments -->
                        <div class="space-y-4">
                            <label class="block text-lg font-semibold text-gray-700">Assign to (Course + Assigned Professor):</label>
                            
                            <div class="course-list bg-gray-50 p-4 rounded-lg border border-gray-200 h-[800px] flex flex-col">
                                <div class="course-filter-container flex-none">
                                    <input type="text" id="courseSearch" placeholder="Search courses..." 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500 mb-2" 
                                        oninput="filterCourses()">
                                    
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <select id="filterType" 
                                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                            onchange="filterCourses()">
                                            <option value="all">All</option>
                                            <option value="assigned">With Professor</option>
                                            <option value="unassigned">Without Professor</option>
                                        </select>

                                        <select id="curriculumFilter" 
                                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                            onchange="filterCourses()">
                                            <option value="all">All Curricula</option>
                                            <?php
                                            $curriculaQuery = "SELECT DISTINCT cu.ID, cu.Name FROM curricula cu 
                                                             JOIN program_courses pc ON cu.ID = pc.CurriculumID 
                                                             WHERE pc.FacultyID = ? 
                                                             ORDER BY cu.Name";
                                            $curriculaStmt = $conn->prepare($curriculaQuery);
                                            $curriculaStmt->bind_param("i", $facultyID);
                                            $curriculaStmt->execute();
                                            $curriculaResult = $curriculaStmt->get_result();
                                            while ($curriculum = $curriculaResult->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($curriculum['ID']) . '">' . htmlspecialchars($curriculum['Name']) . '</option>';
                                            }
                                            $curriculaStmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-500 mb-1">
                                        <span id="courseCounter">Showing all courses</span>
                                        <button type="button" id="selectAllBtn" 
                                            class="ml-auto text-blue-600 hover:underline text-sm" 
                                            onclick="toggleSelectAll()">Select All</button>
                                    </div>
                                </div>

                                <?php if (empty($facultyCoursePairs)): ?>
                                    <p class="text-gray-500">No courses available for assignment.</p>
                                <?php else: ?>
                                    <div id="courseListContainer" class="flex-1 overflow-y-auto">
                                    <?php 
                                    $currentProgram = '';
                                    $programCounter = 0;
                                    
                                    foreach ($facultyCoursePairs as $index => $pair): 
                                        if ($currentProgram != $pair['ProgramName']) {
                                            $programCounter++;
                                            $currentProgram = $pair['ProgramName'];
                                            echo '<div class="program-section" data-program="' . htmlspecialchars($programCounter) . '">';
                                            echo '<div class="program-title text-lg font-semibold text-blue-800 py-2">' . htmlspecialchars($pair['ProgramName']) . '</div>';
                                        }
                                    ?>
                                        <div class="course-item p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200" 
                                             data-course-code="<?= strtolower(htmlspecialchars($pair['CourseCode'])) ?>"
                                             data-course-title="<?= strtolower(htmlspecialchars($pair['Title'])) ?>" 
                                             data-has-professor="<?= empty($pair['AssignedTo']) ? 'no' : 'yes' ?>"
                                             data-curriculum-id="<?= htmlspecialchars($pair['CurriculumID']) ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="assigned[]" value="<?= $pair['ProgramID'] . '|' . $pair['CourseCode'] ?>" 
                                                    class="mr-2 course-checkbox" <?= empty($pair['AssignedTo']) ? 'disabled' : '' ?> />
                                                <span class="flex flex-col">
                                                    <span>
                                                        <span class="font-medium"><?= htmlspecialchars($pair['CourseCode']) ?></span> - 
                                                        <?= htmlspecialchars($pair['Title']) ?>
                                                        <span class="text-sm text-gray-500">(<?= htmlspecialchars($pair['Name']) ?>)</span>
                                                    </span>
                                                    <?php if (!empty($pair['AssignedTo'])): ?>
                                                        <span class="text-sm text-gray-600">
                                                            Assigned to: <?= htmlspecialchars($pair['AssignedTo']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0)" 
                                                           onclick="parent.location.href='../../main/curriculum/curriculum.php?course=<?= htmlspecialchars($pair['CourseCode']) ?>&curriculum=<?= htmlspecialchars($pair['CurriculumID']) ?>'"
                                                           class="text-sm text-red-600 hover:text-red-800 hover:underline cursor-pointer">
                                                            No assigned professor
                                                        </a>
                                                    <?php endif; ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php 
                                       
                                        $nextIndex = $index + 1;
                                        if (!isset($facultyCoursePairs[$nextIndex]) || 
                                            $facultyCoursePairs[$nextIndex]['ProgramName'] != $currentProgram) {
                                            echo '</div>'; 
                                        }
                                        endforeach; 
                                    ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex-none flex justify-end gap-4 pt-4 mt-4 border-t border-gray-200">
                        <input type="hidden" name="curriculum_id" id="curriculum_id" value="">
                        <button type="button" onclick="closeTaskModal()" 
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">
                            Cancel
                        </button>
                        <button type="submit" name="create_task" onclick="return validateTaskForm()"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">
                            Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="previewModal" class="fixed inset-0 hidden z-50 flex items-center justify-center overflow-hidden">
          <div class="bg-gray-300 dark:bg-gray-800 rounded-lg shadow-xl w-full m-4 max-w-[98%] h-screen flex flex-col">
            <div class="flex justify-between items-center px-4 py-2 border-b dark:border-gray-700">
              <h3 class="text-sm font-medium dark:text-white" id="previewTitle">File Preview</h3>
              <button onclick="closePreviewModal()" class="text-gray-700 hover:text-red-600 text-2xl font-bold" title="Close">&times;</button>
            </div>
            <div class="flex-1 overflow-auto">
              <div id="previewContent" class="w-full h-full flex items-center justify-center">
                <!-- Content will be inserted here -->
              </div>
            </div>
          </div>
        </div>

        <!-- Revision Modal -->
        <div id="revisionModal" class="hidden fixed inset-0 bg-transparent bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg w-[90%] max-w-[600px]">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Request Revision</h2>
                    <button onclick="closeRevisionModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="task_actions.php" class="space-y-4">
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

        <!-- Completed Tasks Section -->
        <?php if (isset($_GET['view']) && $_GET['view'] === 'completed'): ?>
        <div class="mb-8">
            <?php
            $completedTasksSql = "SELECT DISTINCT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                t.CreatedBy,
                COUNT(DISTINCT ta.CourseCode) as AssignedCourses,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
                ? as UserRole,
                CONCAT(p.FirstName, ' ', p.LastName) as CreatorName,
                ta.CurriculumID,
                c.name as CurriculumName
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode 
                    AND ta.ProgramID = pc.ProgramID 
                    AND ta.CurriculumID = pc.CurriculumID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                LEFT JOIN curricula c ON ta.CurriculumID = c.id
                WHERE t.FacultyID = ? AND t.Status = 'Completed'
                GROUP BY t.TaskID, ta.CurriculumID
                ORDER BY t.CreatedAt DESC";
            $completedTasksStmt = $conn->prepare($completedTasksSql);
            $completedTasksStmt->bind_param("si", $userRole, $facultyID);
            $completedTasksStmt->execute();
            $completedTasksResult = $completedTasksStmt->get_result();
            
            $completedTasks = [];
            while ($taskRow = $completedTasksResult->fetch_assoc()) {
                $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
                            p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                            ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, ta.PersonnelID as PersonnelID,
                            cu.id as CurriculumID, cu.name as CurriculumName
                            FROM task_assignments ta
                            JOIN courses c ON ta.CourseCode = c.CourseCode
                            JOIN programs p ON ta.ProgramID = p.ProgramID
                            LEFT JOIN personnel per ON ta.PersonnelID = per.PersonnelID
                            LEFT JOIN curricula cu ON ta.CurriculumID = cu.id
                            WHERE ta.TaskID = ?
                            ORDER BY p.ProgramName, ta.CourseCode";
                $coursesStmt = $conn->prepare($coursesSql);
                $coursesStmt->bind_param("i", $taskRow['TaskID']);
                $coursesStmt->execute();
                $coursesResult = $coursesStmt->get_result();
                
                $courses = [];
                while ($courseRow = $coursesResult->fetch_assoc()) {
                    $courses[] = $courseRow;
                }
                $coursesStmt->close();
                
                $taskRow['Courses'] = $courses;
                $completedTasks[] = $taskRow;
            }
            $completedTasksStmt->close();
            ?>
            <?php if (!empty($completedTasks)): ?>
                <h2 class="text-2xl font-bold text-gray-800 mb-4 font-overpass">Completed Tasks</h2>
                <?php if (in_array($userRole, ['DN', 'PH', 'COR'])): ?>
                    <?php foreach ($completedTasks as $task): ?>
                        <?php
                        $completedCourses = array_filter($task['Courses'], function($course) {
                            return $course['AssignmentStatus'] === 'Completed';
                        });
                        if (count($completedCourses) > 0):
                        ?>
                        <div class="bg-white p-6 font-onest w-full md:w-[80%] rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                             onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-1 mb-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1 bg-green-100 text-green-700">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
                                            <circle cx="12" cy="12" r="9" />
                                        </svg>
                                        Completed
                                    </span>
                                </div>
                                <div class="flex flex-col text-sm text-gray-500 space-y-0.5 text-right">
                                    <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                    <span>Completed on: <span class="font-semibold text-gray-700"><?php echo date("F j, Y", strtotime($task['CreatedAt'])); ?></span></span>
                                    <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                </div>
                            </div>
                            <p class="text-gray-600 mt-1 mb-3 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                            <div class="text-blue-700 font-semibold text-base mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                                Completed Courses:
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 mb-3 shadow-sm">
                                <ul class="space-y-1">
                                    <?php foreach ($completedCourses as $course): ?>
                                        <li class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                            <span class="inline-block bg-green-200 text-green-900 font-semibold px-2 py-0.5 rounded text-xs">
                                                <?php echo htmlspecialchars($course['CourseCode']); ?>
                                            </span>
                                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($course['CourseTitle']); ?></span>
                                            <span class="text-xs text-gray-500">
                                                <?php if (!empty($course['AssignedTo'])): ?>
                                                    Assigned to: <?php echo htmlspecialchars($course['AssignedTo']); ?>
                                                <?php else: ?>
                                                    <span class="text-red-500">No assigned professor</span>
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php $renderedTaskIds = array();
                    foreach ($completedTasks as $task):
                        if (in_array($task['TaskID'], $renderedTaskIds)) continue; // Prevent duplicate cards
                        $myCompletedCourses = array_filter($task['Courses'], function($course) use ($personnelID) {
                            return $course['AssignmentStatus'] === 'Completed' && isset($course['PersonnelID']) && $course['PersonnelID'] == $personnelID;
                        });
                        if (count($myCompletedCourses) > 0):
                            $renderedTaskIds[] = $task['TaskID'];
                    ?>
                        <div class="bg-white p-6 font-onest w-full md:w-[80%] rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                             onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                            <div class="flex flex-col gap-2 mb-2 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1 bg-green-100 text-green-700">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                                            <circle cx="12" cy="12" r="9"/>
                                        </svg>
                                        Completed
                                    </span>
                                </div>
                                <div class="flex flex-col md:items-end text-sm text-gray-500">
                                    <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                    <span>Completed on: <span class="font-semibold text-gray-700"><?php echo date("F j, Y", strtotime($task['CreatedAt'])); ?></span></span>
                                    <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                </div>
                            </div>
                            <?php foreach ($myCompletedCourses as $course): ?>
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mt-2">
                                    <div class="flex items-center gap-2 text-blue-700 font-semibold text-base">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M9 12l2 2 4-4"/>
                                        </svg>
                                        <?php echo htmlspecialchars($course['CourseCode']); ?> - <?php echo htmlspecialchars($course['CourseTitle']); ?>
                                    </div>
                                    <div class="text-sm text-gray-700 font-medium md:text-right">
                                        <?php if (!empty($course['AssignedTo'])): ?>
                                            Assigned to: <?php echo htmlspecialchars($course['AssignedTo']); ?>
                                        <?php else: ?>
                                            <span class="text-red-500">No assigned professor</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif;
                    endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="grid grid-cols-1 w-full md:w-[80%] px-4">
                    <div class="bg-white p-[25px] font-onest rounded-lg shadow-md flex justify-center items-center">
                        <p class="text-gray-500">No completed tasks found. Keep going, you're almost there!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($userRole === 'FM'): ?>
            <div class="flex items-center gap-4 mb-6">
                <a href="?fmview=ongoing" 
                   class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2
                        <?php echo (!isset($_GET['fmview']) || $_GET['fmview'] === 'ongoing') 
                            ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                            : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                    Ongoing
                </a>
                <a href="?fmview=completed" 
                   class="px-4 py-2 rounded-lg font-medium transition-all duration-300 ease-in-out border-2
                        <?php echo (isset($_GET['fmview']) && $_GET['fmview'] === 'completed') 
                            ? 'border-blue-600 text-blue-600 hover:bg-blue-50' 
                            : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:text-gray-700'; ?>">
                    Completed
                </a>
            </div>
            <?php if (!isset($_GET['fmview']) || $_GET['fmview'] === 'ongoing'): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 font-overpass">Ongoing Tasks</h2>
                    <?php if (empty($ongoingTasks)): ?>
                        <div class="grid grid-cols-1 w-full md:w-[80%] px-4">
                            <div class="bg-white p-[25px] font-onest rounded-lg shadow-md flex justify-center items-center">
                                <p class="text-gray-500">No tasks for now. Tasks will appear here once they are assigned to you.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ongoingTasks as $task): ?>
                            <?php
                          
                            $myOngoingCourses = array_filter($task['Courses'], function($course) use ($personnelID) {
                                return isset($course['PersonnelID']) && $course['PersonnelID'] == $personnelID && in_array($course['AssignmentStatus'], ['Pending', 'In Progress']);
                            });
                            ?>
                            <?php foreach ($myOngoingCourses as $course): ?>
                            <div class="bg-white p-6 font-onest w-full md:w-[80%] rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                                 onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1 bg-yellow-100 text-yellow-700">
                                            <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9"/>
                                                <path d="M12 8v4l3 3"/>
                                            </svg>
                                            Ongoing
                                        </span>
                                    </div>
                                    <div class="flex flex-col md:items-end text-sm text-gray-500">
                                        <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                        <span class="flex items-center justify-end gap-1">Due: 
                                            <?php 
                                            echo '<span class="font-semibold ' . getDueDateClass($task['DueDate']) . ' cursor-help" title="' . getDueDateTooltip($task['DueDate']) . '">' . date("F j, Y", strtotime($task['DueDate'])) . '</span>';
                                            ?>
                                        </span>
                                        <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                    </div>
                                </div>
                                <div class="text-blue-700 font-semibold text-base mb-1">
                                    <?php echo htmlspecialchars($course['CourseCode']); ?> - <?php echo htmlspecialchars($course['CourseTitle']); ?>
                                </div>
                                <p class="text-gray-600 mt-1 mb-4 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php elseif ($_GET['fmview'] === 'completed'): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 font-overpass">Completed Tasks</h2>
                    <?php if (empty($completedTasks)): ?>
                        <div class="grid grid-cols-1 w-full md:w-[80%] px-4">
                            <div class="bg-white p-[25px] font-onest rounded-lg shadow-md flex justify-center items-center">
                                <p class="text-gray-500">No completed tasks found. Keep going, you're almost there!</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($completedTasks as $task): ?>
                            <?php
                            if (in_array($userRole, ['DN', 'PH', 'COR'])) {
                                $completedCourses = array_filter($task['Courses'], function($course) {
                                    return $course['AssignmentStatus'] === 'Completed';
                                });
                                if (count($completedCourses) > 0) {
                            ?>
                            <!-- DN/PH/COR card -->
                            <div class="bg-white p-6 font-onest w-full md:w-[80%] rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                                 onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1 bg-green-100 text-green-700">
                                            <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
                                                <circle cx="12" cy="12" r="9" />
                                            </svg>
                                            Completed
                                        </span>
                                    </div>
                                    <div class="flex flex-col text-sm text-gray-500 space-y-0.5 text-right">
                                        <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                        <span>Completed on: <span class="font-semibold text-gray-700"><?php echo date("F j, Y", strtotime($task['CreatedAt'])); ?></span></span>
                                        <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                    </div>
                                </div>
                                <p class="text-gray-600 mt-1 mb-4 text-base"><?php echo nl2br(htmlspecialchars($task['Description'])); ?></p>
                                <div class="text-blue-700 font-semibold text-base mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M9 12l2 2 4-4"/>
                                    </svg>
                                    Completed Courses:
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 mb-4 shadow-sm">
                                    <ul class="space-y-2">
                                        <?php foreach ($completedCourses as $course): ?>
                                            <li class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                <span class="inline-block bg-green-200 text-green-900 font-semibold px-2 py-1 rounded text-xs">
                                                    <?php echo htmlspecialchars($course['CourseCode']); ?>
                                                </span>
                                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($course['CourseTitle']); ?></span>
                                                <span class="text-xs text-gray-500">
                                                    <?php if (!empty($course['AssignedTo'])): ?>
                                                        Assigned to: <?php echo htmlspecialchars($course['AssignedTo']); ?>
                                                    <?php else: ?>
                                                        <span class="text-red-500">No assigned professor</span>
                                                    <?php endif; ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                                }
                            } else {
                                $myCompletedCourses = array_filter($task['Courses'], function($course) use ($personnelID) {
                                    return $course['AssignmentStatus'] === 'Completed' && isset($course['PersonnelID']) && $course['PersonnelID'] == $personnelID;
                                });
                                if (count($myCompletedCourses) > 0): ?>
                                    <div class="bg-white p-6 font-onest w-full md:w-[80%] rounded-2xl border border-gray-200 shadow-md hover:shadow-lg transition-shadow duration-200 mb-8 cursor-pointer"
                                 onclick="window.location.href='../../main/dashboard/submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=task_frame'">
                                    <div class="flex flex-col gap-2 mb-2 md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-2xl font-bold text-gray-900 mr-2"><?php echo htmlspecialchars($task['Title']); ?></h3>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold gap-1 bg-green-100 text-green-700">
                                                <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                                                    <circle cx="12" cy="12" r="9"/>
                                                </svg>
                                                Completed
                                            </span>
                                        </div>
                                        <div class="flex flex-col md:items-end text-sm text-gray-500">
                                            <span>Created by: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($task['CreatorName']); ?></span></span>
                                            <span>Completed on: <span class="font-semibold text-gray-700"><?php echo date("F j, Y", strtotime($task['CreatedAt'])); ?></span></span>
                                            <span>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></span>
                                        </div>
                                    </div>
                                    <?php foreach ($myCompletedCourses as $course): ?>
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mt-2">
                                            <div class="flex items-center gap-2 text-blue-700 font-semibold text-base">
                                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <path d="M9 12l2 2 4-4"/>
                                                </svg>
                                                <?php echo htmlspecialchars($course['CourseCode']); ?> - <?php echo htmlspecialchars($course['CourseTitle']); ?>
                                            </div>
                                            <div class="text-sm text-gray-700 font-medium md:text-right">
                                                <?php if (!empty($course['AssignedTo'])): ?>
                                                    Assigned to: <?php echo htmlspecialchars($course['AssignedTo']); ?>
                                                <?php else: ?>
                                                    <span class="text-red-500">No assigned professor</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif;
                            }
                        endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Report Modal -->
        <div id="reportModal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="background: rgba(0,0,0,0.15);">
            <div class="bg-white p-6 rounded-lg shadow-lg w-[90vw] max-w-[1200px] max-h-[95vh] flex flex-col relative" onclick="event.stopPropagation();">
                <button onclick="closeReportModal()" class="absolute top-4 right-4 text-gray-700 hover:text-red-600 text-4xl font-bold z-50" title="Close">&times;</button>
                <h2 class="text-2xl font-bold w-full text-center font-overpass mb-4">Task Report</h2>
                <iframe id="reportFrame" src="" style="width:100%;height:80vh;border:none;"></iframe>
            </div>
        </div>

        <!-- Discard Task Confirmation Modal -->
        <div id="discardTaskModal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="background: rgba(0,0,0,0.15);">
            <div class="bg-white p-8 rounded-xl shadow-2xl w-[400px] border-2 border-gray-400 font-onest modal-animate relative">
                <h2 class="text-2xl font-overpass font-bold mb-2 text-blue-800">Confirm Deletion</h2>
                <hr class="border-gray-400 mb-6">
                <p class="text-lg text-gray-700 mb-6" id="discardTaskMessage">Are you sure you want to discard this task?</p>
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeDiscardTaskModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                    <button id="confirmDiscardTaskBtn" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 font-semibold">Delete</button>
                </div>
                <form id="discardTaskForm" method="POST" action="task_actions.php" class="hidden">
                    <input type="hidden" name="task_id" id="discardTaskId" value="">
                    <input type="hidden" name="action" value="discard">
                </form>
            </div>
        </div>

        <!-- Modify Task Modal -->
        <div id="modifyTaskModal" class="hidden fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-xl shadow-2xl w-[900px] border-2 border-gray-400 font-onest modal-animate max-h-[90vh] flex flex-col">
                <div class="flex-none">
                    <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">✏️ Modify Task</h2>
                    <hr class="border-gray-400 mb-6">
                </div>
                
                <form method="POST" action="task_actions.php" class="flex-1 overflow-y-auto pr-2">
                    <input type="hidden" name="action" value="modify_task">
                    <input type="hidden" name="task_id" id="modifyTaskId">
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Task Title:</label>
                            <input type="text" name="title" id="modifyTaskTitle" required 
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Task Description:</label>
                            <textarea name="description" id="modifyTaskDescription" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                rows="5" style="white-space: pre-wrap;"></textarea>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Due Date:</label>
                            <input type="date" name="due_date" id="modifyTaskDueDate" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-lg font-semibold text-gray-700">School Year:</label>
                                <input type="text" name="school_year" id="modifyTaskSchoolYear" required
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-lg font-semibold text-gray-700">Term:</label>
                                <select name="term" id="modifyTaskTerm" required
                                    class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500">
                                    <option value="1st">1st</option>
                                    <option value="2nd">2nd</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Assigned Courses:</label>
                            <div id="modifyTaskCourses" class="space-y-2">
                                <!-- Course assignments will be loaded here -->
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Add Courses to Task:</label>
                            <div>
                                <button type="button" onclick="toggleAddCourseDropdown(event)" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-semibold mb-2">+ Add Course</button>
                                <div id="addCourseDropdown" class="hidden border border-gray-300 rounded-lg bg-white p-4 max-h-72 overflow-y-auto">
                                    <div class="mb-4 grid grid-cols-1 gap-2">
                                        <input type="text" id="addCourseSearch" placeholder="Search courses..." 
                                            class="w-full p-2 border border-gray-300 rounded-lg mb-2"
                                            oninput="filterAddCourseList()">
                                        <select id="addCourseProfFilter" 
                                            class="w-full p-2 border border-gray-300 rounded-lg"
                                            onchange="filterAddCourseList()">
                                            <option value="all">All Professors</option>
                                            <option value="assigned">With Professor</option>
                                            <option value="unassigned">Without Professor</option>
                                        </select>
                                        <select id="addCourseCurriculumFilter" 
                                            class="w-full p-2 border border-gray-300 rounded-lg"
                                            onchange="filterAddCourseList()">
                                            <option value="all">All Curricula</option>
                                            <?php
                                            $curriculaQuery = "SELECT DISTINCT cu.ID, cu.Name FROM curricula cu 
                                                             JOIN program_courses pc ON cu.ID = pc.CurriculumID 
                                                             WHERE pc.FacultyID = ? 
                                                             ORDER BY cu.Name";
                                            $curriculaStmt = $conn->prepare($curriculaQuery);
                                            $curriculaStmt->bind_param("i", $facultyID);
                                            $curriculaStmt->execute();
                                            $curriculaResult = $curriculaStmt->get_result();
                                            while ($curriculum = $curriculaResult->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($curriculum['Name']) . '">' . htmlspecialchars($curriculum['Name']) . '</option>';
                                            }
                                            $curriculaStmt->close();
                                            ?>
                                        </select>
                                    </div>
                                    <div id="addCourseList">
                                        <!-- Available courses will be loaded here by JS -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-none flex justify-end gap-4 pt-4 mt-4 border-t border-gray-200">
                        <input type="hidden" name="curriculum_id" id="curriculum_id" value="">
                        <button type="button" onclick="closeModifyTaskModal()" 
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">
                            Cancel
                        </button>
                        <button type="submit" onclick="return validateModifyTaskForm()"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>

            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark');
            }
            
            function toggleTaskDropdown() {
                const dropdown = document.getElementById('task-dropdown');
                const button = document.querySelector('a[title="Add Task"]');
                
                dropdown.classList.toggle('show');
                button.classList.toggle('open'); 
            }

            window.addEventListener('click', function (event) {
                const dropdown = document.getElementById('task-dropdown');
                const button = document.querySelector('a[title="Add Task"]');

                if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                    dropdown.classList.remove('show');
                    button.classList.remove('open');
                }
            });

            function openTaskModal() {
                document.getElementById('taskModal').classList.remove('hidden');

                document.getElementById('courseSearch').value = '';
                document.getElementById('filterType').value = 'all';
                filterCourses();
                
                const dropdown = document.getElementById('task-dropdown');
                dropdown.classList.remove('show'); 
            }

            function closeTaskModal() {
                document.getElementById('taskModal').classList.add('hidden');
              
                document.getElementById('courseSearch').value = '';
                document.getElementById('filterType').value = 'all';
                filterCourses();
            }

            window.addEventListener('keydown', function (e) {
                if (e.key === "Escape") closeTaskModal();
            });
            
            function filterCourses() {
                const searchTerm = document.getElementById('courseSearch').value.toLowerCase();
                const filterType = document.getElementById('filterType').value;
                const curriculumFilter = document.getElementById('curriculumFilter').value;
                const courseItems = document.querySelectorAll('.course-item');
                let visibleCount = 0;
                
               
                const programSections = {};
                
                courseItems.forEach(item => {
                    const courseCode = item.dataset.courseCode;
                    const courseTitle = item.dataset.courseTitle;
                    const hasProfessor = item.dataset.hasProfessor;
                    const curriculumId = item.dataset.curriculumId;
                    const programSection = item.closest('.program-section');
                    const programId = programSection ? programSection.dataset.program : null;
                    
                    
                    const matchesSearch = courseCode.includes(searchTerm) || courseTitle.includes(searchTerm);
                    
                    
                    let matchesFilter = true;
                    if (filterType === 'assigned' && hasProfessor === 'no') {
                        matchesFilter = false;
                    } else if (filterType === 'unassigned' && hasProfessor === 'yes') {
                        matchesFilter = false;
                    }

                   
                    let matchesCurriculum = true;
                    if (curriculumFilter !== 'all' && curriculumId !== curriculumFilter) {
                        matchesCurriculum = false;
                    }
                    
                    const isVisible = matchesSearch && matchesFilter && matchesCurriculum;
                    item.style.display = isVisible ? 'block' : 'none';
                    
                    if (isVisible) {
                        visibleCount++;
                        if (programId) {
                            programSections[programId] = true;
                        }
                    }
                });
                

                document.querySelectorAll('.program-section').forEach(section => {
                    const programId = section.dataset.program;
                    const programTitle = section.querySelector('.program-title');
                    
                    if (programSections[programId]) {
                        section.style.display = 'block';
                        if (programTitle) programTitle.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
                
               
                const counterText = visibleCount === 1 
                    ? "Showing 1 course" 
                    : `Showing ${visibleCount} courses`;
                document.getElementById('courseCounter').textContent = counterText;
            }

            function toggleSelectAll() {
                const btn = document.getElementById('selectAllBtn');
                const checkboxes = document.querySelectorAll('.course-item:not([style*="display: none"]) .course-checkbox:not([disabled])');
                
                
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
            
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                
          
                btn.textContent = allChecked ? 'Select All' : 'Deselect All';
            }

            function openPreviewModal(filePath, taskId) {
                const modal = document.getElementById('previewModal');
                const content = document.getElementById('previewContent');
               
                const ext = filePath.split('.').pop().toLowerCase();
                if (["pdf"].includes(ext)) {
                    content.innerHTML = `<embed src="${filePath}" type="application/pdf" style="width:100%;height:85vh;">`;
                } else if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
                    content.innerHTML = `<img src="${filePath}" style="max-width:100%;max-height:85vh;display:block;margin:auto;">`;
                } else {
                    content.innerHTML = `<div class='text-center text-gray-500'>Preview not available for this file type.</div>`;
                }
                modal.classList.remove('hidden');
            }

            function closePreviewModal() {
                const modal = document.getElementById('previewModal');
                const content = document.getElementById('previewContent');
                content.innerHTML = '';
                modal.classList.add('hidden');
            }

            function openRevisionModal(taskId) {
                const modal = document.getElementById('revisionModal');
                document.getElementById('revisionTaskId').value = taskId;
                modal.classList.remove('hidden');
            }

            function closeRevisionModal() {
                const modal = document.getElementById('revisionModal');
                modal.classList.add('hidden');
            }

         
            window.onclick = function(event) {
                const previewModal = document.getElementById('previewModal');
                const revisionModal = document.getElementById('revisionModal');
                
                if (event.target === previewModal) {
                    closePreviewModal();
                }
                if (event.target === revisionModal) {
                    closeRevisionModal();
                }
            }

           
            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") {
                    closePreviewModal();
                    closeRevisionModal();
                }
            });

            document.querySelectorAll('input[name="taskView"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const view = this.value;
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('view', view);
                    window.location.href = currentUrl.toString();
                });
            });

            function validateTaskForm() {
                const title = document.getElementById('taskTitle').value.trim();
                const description = document.getElementById('taskDescription').value.trim();
                const dueDate = document.getElementById('taskDueDate').value;
                const schoolYear = document.getElementById('taskSchoolYear').value.trim();
                const term = document.getElementById('taskTerm').value;
                const selectedCourses = document.querySelectorAll('input[name="assigned[]"]:checked');
                
                if (!title) {
                    alert('Please enter a task title');
                    return false;
                }
                if (!description) {
                    alert('Please enter a task description');
                    return false;
                }
                if (!dueDate) {
                    alert('Please select a due date');
                    return false;
                }
                if (!schoolYear) {
                    alert('Please enter a school year');
                    return false;
                }
                if (!term) {
                    alert('Please select a term');
                    return false;
                }
                if (selectedCourses.length === 0) {
                    alert('Please select at least one course');
                    return false;
                }

                // Set curriculum ID from first selected course
                if (selectedCourses.length > 0) {
                    const firstCourse = selectedCourses[0].closest('.course-item');
                    const curriculumId = firstCourse.dataset.curriculumId;
                    document.getElementById('curriculum_id').value = curriculumId;
                }

                return true;
            }

            function toggleTaskMenu(btn) {
                
                document.querySelectorAll('.task-menu-dropdown').forEach(menu => {
                    if (menu !== btn.nextElementSibling) menu.classList.add('hidden');
                });
            
                const menu = btn.nextElementSibling;
                menu.classList.toggle('hidden');
            }

          
            window.addEventListener('click', function(event) {
                document.querySelectorAll('.task-menu-dropdown').forEach(menu => {
                    if (!menu.contains(event.target) && !event.target.closest('button[onclick^="toggleTaskMenu"]')) {
                        menu.classList.add('hidden');
                    }
                });
            });

            function generateTaskReport(taskId) {
               
                document.getElementById('reportModal').classList.remove('hidden');
                document.getElementById('reportFrame').src = 'generate_task_report.php?task_id=' + taskId;
            }

            function closeReportModal() {
                document.getElementById('reportModal').classList.add('hidden');
                document.getElementById('reportFrame').src = '';
            }

            //closing modals
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('reportModal');
                if (event.target === modal) {
                    closeReportModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") {
                    closeReportModal();
                }
            });

            let discardTaskId = null;
            function openDiscardTaskModal(taskId) {
                discardTaskId = taskId;
                document.getElementById('discardTaskId').value = taskId;
                document.getElementById('discardTaskModal').classList.remove('hidden');
            }
            function closeDiscardTaskModal() {
                document.getElementById('discardTaskModal').classList.add('hidden');
                discardTaskId = null;
            }
            document.getElementById('confirmDiscardTaskBtn').onclick = function() {
                document.getElementById('discardTaskForm').submit();
            };

            function openModifyTaskModal(taskId) {
                // Fetch task details
                fetch('get_task_details.php?task_id=' + taskId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const task = data.task;
                            document.getElementById('modifyTaskId').value = taskId;
                            document.getElementById('modifyTaskTitle').value = task.Title;
                            document.getElementById('modifyTaskDescription').value = task.Description;
                            document.getElementById('modifyTaskDueDate').value = task.DueDate;
                            document.getElementById('modifyTaskSchoolYear').value = task.SchoolYear;
                            document.getElementById('modifyTaskTerm').value = task.Term;

                            // Populate course assignments
                            const coursesContainer = document.getElementById('modifyTaskCourses');
                            coursesContainer.innerHTML = '';
                            
                            task.Courses.forEach(course => {
                                const courseDiv = document.createElement('div');
                                courseDiv.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                                courseDiv.innerHTML = `
                                    <div class="flex-1">
                                        <span class="font-medium">${course.CourseCode}</span> - 
                                        <span class="text-gray-600">${course.CourseTitle}</span>
                                        <br>
                                        <span class="text-sm text-gray-500">
                                            ${course.AssignedTo ? `Assigned to: ${course.AssignedTo}` : 'No assigned professor'}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="remove_assignment[]" 
                                                value="${course.ProgramID}|${course.CourseCode}"
                                                class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">Remove Assignment</span>
                                        </label>
                                    </div>
                                `;
                                coursesContainer.appendChild(courseDiv);
                            });

                            document.getElementById('modifyTaskModal').classList.remove('hidden');
                        } else {
                            alert('Error loading task details');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error loading task details');
                    });
            }

            function closeModifyTaskModal() {
                document.getElementById('modifyTaskModal').classList.add('hidden');
            }

            function validateModifyTaskForm() {
                const title = document.getElementById('modifyTaskTitle').value.trim();
                const description = document.getElementById('modifyTaskDescription').value.trim();
                const dueDate = document.getElementById('modifyTaskDueDate').value;
                const schoolYear = document.getElementById('modifyTaskSchoolYear').value.trim();
                const term = document.getElementById('modifyTaskTerm').value;

                if (!title) {
                    alert('Please enter a task title');
                    return false;
                }
                if (!description) {
                    alert('Please enter a task description');
                    return false;
                }
                if (!dueDate) {
                    alert('Please select a due date');
                    return false;
                }
                if (!schoolYear) {
                    alert('Please enter a school year');
                    return false;
                }
                if (!term) {
                    alert('Please select a term');
                    return false;
                }

                return true;
            }

            // Add to existing window.onclick event handler
            window.onclick = function(event) {
                const previewModal = document.getElementById('previewModal');
                const revisionModal = document.getElementById('revisionModal');
                const modifyTaskModal = document.getElementById('modifyTaskModal');
                
                if (event.target === previewModal) {
                    closePreviewModal();
                }
                if (event.target === revisionModal) {
                    closeRevisionModal();
                }
                if (event.target === modifyTaskModal) {
                    closeModifyTaskModal();
                }
            }

            // Add to existing keydown event handler
            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") {
                    closePreviewModal();
                    closeRevisionModal();
                    closeModifyTaskModal();
                }
            });

            function toggleAddCourseDropdown(event) {
                if (event) {
                    event.stopPropagation();
                }
                const dropdown = document.getElementById('addCourseDropdown');
                dropdown.classList.toggle('hidden');
            }

            // Add click event listener to close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dropdown = document.getElementById('addCourseDropdown');
                const addCourseBtn = document.querySelector('button[onclick="toggleAddCourseDropdown(event)"]');
                if (dropdown && !dropdown.contains(event.target) && !addCourseBtn.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            // Store loaded courses for filtering
            let _addCourseListData = [];

            function loadAvailableCoursesForTask(taskId) {
                fetch('get_available_courses_for_task.php?task_id=' + taskId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.courses.length > 0) {
                            _addCourseListData = data.courses;
                            renderAddCourseList(_addCourseListData);
                        } else {
                            _addCourseListData = [];
                            document.getElementById('addCourseList').innerHTML = '<div class="text-gray-500">No available courses to add.</div>';
                        }
                    });
            }

            function renderAddCourseList(courses) {
                const addCourseList = document.getElementById('addCourseList');
                addCourseList.innerHTML = '';
                if (!courses.length) {
                    addCourseList.innerHTML = '<div class="text-gray-500">No available courses to add.</div>';
                    return;
                }
                courses.forEach(course => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-2 mb-2 p-2 rounded hover:bg-gray-50';
                    div.innerHTML = `
                        <input type="checkbox" name="add_assignment[]" value="${course.ProgramID}|${course.CourseCode}|${course.CurriculumID}" class="mr-2">
                        <span class="font-medium">${course.CourseCode}</span> -
                        <span>${course.Title}</span>
                        <span class="text-sm text-gray-500 ml-2">(${course.CurriculumName})</span>
                        <span class="text-sm ml-2 ${course.AssignedTo ? 'text-gray-600' : 'text-red-600'}">
                            ${course.AssignedTo ? 'Assigned to: ' + course.AssignedTo : 'No assigned professor'}
                        </span>
                    `;
                    addCourseList.appendChild(div);
                });
            }

            function filterAddCourseList() {
                const search = document.getElementById('addCourseSearch').value.toLowerCase();
                const profFilter = document.getElementById('addCourseProfFilter').value;
                const curriculumFilter = document.getElementById('addCourseCurriculumFilter').value;
                let filtered = _addCourseListData.filter(course => {
                    const matchesSearch = course.CourseCode.toLowerCase().includes(search) || course.Title.toLowerCase().includes(search);
                    let matchesProf = true;
                    if (profFilter === 'assigned') matchesProf = !!course.AssignedTo;
                    else if (profFilter === 'unassigned') matchesProf = !course.AssignedTo;
                    let matchesCurriculum = true;
                    if (curriculumFilter !== 'all' && course.CurriculumName !== curriculumFilter) matchesCurriculum = false;
                    return matchesSearch && matchesProf && matchesCurriculum;
                });
                renderAddCourseList(filtered);
            }

            // Patch openModifyTaskModal to also load available courses
            const _openModifyTaskModal = openModifyTaskModal;
            openModifyTaskModal = function(taskId) {
                _openModifyTaskModal(taskId);
                loadAvailableCoursesForTask(taskId);
            }

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
        </script>
    </div>
</body>
</html>
<?php
$conn->close();
?>