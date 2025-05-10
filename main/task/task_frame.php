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

$accountID = $_SESSION['AccountID'];
$facultyName = "Faculty";
$members = [];
$facultyID = null;
$facultyCoursePairs = [];
$message = "";

// Debug information
$debug = false; // Set to true to see debug info
if ($debug) {
    echo "AccountID: " . $accountID . "<br>";
    echo "UserRole: " . $userRole . "<br>";
}

// Fetch the faculty name and faculty ID based on the logged-in user
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

    // Fetch the list of members within the same faculty
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
    
    // Fetch courses that have assignments in the faculty
    $coursesQuery = "SELECT pc.ProgramID, p.ProgramCode, p.ProgramName, 
                    pc.CourseCode, c.Title, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                    per.PersonnelID
                    FROM program_courses pc
                    JOIN courses c ON pc.CourseCode = c.CourseCode
                    JOIN programs p ON pc.ProgramID = p.ProgramID
                    LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
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

// Fetch user role
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

// Process task creation form submission
if (isset($_POST['create_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['due_date'];
    $schoolYear = $_POST['school_year'];
    $term = $_POST['term'];
    
    // Insert the task
    $taskInsertSql = "INSERT INTO tasks (Title, Description, CreatedBy, FacultyID, DueDate, Status, CreatedAt, SchoolYear, Term) 
                      VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), ?, ?)";
    $taskStmt = $conn->prepare($taskInsertSql);
    $taskStmt->bind_param("ssiisss", $title, $description, $personnelID, $facultyID, $dueDate, $schoolYear, $term);
    
    if ($taskStmt->execute()) {
        $taskID = $taskStmt->insert_id;
        
        // Process assigned courses
        if (isset($_POST['assigned']) && is_array($_POST['assigned'])) {
            $assignmentInsertSql = "INSERT INTO task_assignments (TaskID, ProgramID, CourseCode, FacultyID, Status) 
                                    VALUES (?, ?, ?, ?, 'Pending')";
            $assignmentStmt = $conn->prepare($assignmentInsertSql);
            
            foreach ($_POST['assigned'] as $assignment) {
                $parts = explode('|', $assignment);
                if (count($parts) == 2) {
                    $programID = $parts[0];
                    $courseCode = $parts[1];
                    
                    $assignmentStmt->bind_param("iisi", $taskID, $programID, $courseCode, $facultyID);
                    $assignmentStmt->execute();
                }
            }
            
            $assignmentStmt->close();
        }
        
        $message = "Task created successfully!";
    } else {
        $message = "Error creating task: " . $taskStmt->error;
    }
    
    $taskStmt->close();
}

// Fetch tasks for display in the grid
if ($userRole === 'DN') {
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                COUNT(ta.CourseCode) as AssignedCourses,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
                'DN' as UserRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                WHERE t.FacultyID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("i", $facultyID);
} else {
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, t.SchoolYear, t.Term,
                COUNT(ta.CourseCode) as AssignedCourses,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedCount,
                p.Role as UserRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                LEFT JOIN personnel p ON p.AccountID = ?
                WHERE t.FacultyID = ? AND pc.PersonnelID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("iii", $accountID, $facultyID, $personnelID);
}
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

$tasks = [];
while ($taskRow = $tasksResult->fetch_assoc()) {
    // Fetch the courses and assigned professors for each task
    $coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
                  p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                  ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate
                  FROM task_assignments ta
                  JOIN courses c ON ta.CourseCode = c.CourseCode
                  JOIN programs p ON ta.ProgramID = p.ProgramID
                  LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                  LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
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

// After a file is submitted and task_assignment status is set to 'Submitted', update the parent task's status to 'In Progress' if it is currently 'Pending'.
if (isset($_POST['submit_file'])) {
    // ... existing file submission logic ...
    // Assume $taskAssignmentId is available
    $getTaskIdSql = "SELECT TaskID FROM task_assignments WHERE TaskAssignmentID = ?";
    $getTaskIdStmt = $conn->prepare($getTaskIdSql);
    $getTaskIdStmt->bind_param("i", $taskAssignmentId);
    $getTaskIdStmt->execute();
    $getTaskIdResult = $getTaskIdStmt->get_result();
    if ($getTaskIdResult && $getTaskIdResult->num_rows > 0) {
        $taskIdRow = $getTaskIdResult->fetch_assoc();
        $taskId = $taskIdRow['TaskID'];
        // Update the task status if currently 'Pending'
        $updateTaskSql = "UPDATE tasks SET Status = 'In Progress' WHERE TaskID = ? AND Status = 'Pending'";
        $updateTaskStmt = $conn->prepare($updateTaskSql);
        $updateTaskStmt->bind_param("i", $taskId);
        $updateTaskStmt->execute();
        $updateTaskStmt->close();
    }
    $getTaskIdStmt->close();
    // ... rest of file submission logic ...
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
            max-height: 250px;
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
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
        <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Tasks</h1> 
        <hr class="border-gray-400">
        <p class="text-gray-500 mt-3 mb-5 font-onest">Here you can view tasks, assign responsibilities, update statuses, and ensure your faculty members stay on track with their deliverables.</p>
        
        <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Task Grid -->
<div class="grid grid-cols-1 gap-5 w-full md:w-[80%]">
    <?php if (empty($tasks)): ?>
        <div class="bg-white p-[25px] font-overpass rounded-lg shadow-md flex justify-center items-center">
            <p class="text-gray-500">No tasks available. Create your first task!</p>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <div class="bg-white p-[25px] font-overpass rounded-lg shadow-md">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($task['Title']); ?></h3>
                <span class="px-3 py-1 rounded-full text-sm 
                    <?php 
                    if ($task['Status'] == 'Completed') {
                        echo 'bg-green-100 text-green-800';
                    } elseif ($task['Status'] == 'In Progress') {
                        echo 'bg-blue-100 text-blue-800';
                    } else {
                        echo 'bg-yellow-100 text-yellow-800';
                    }
                    ?>">
                    <?php echo $task['Status']; ?>
                </span>
            </div>
            <div class="approval-header">
                <h3>Approval Status</h3>
                <div class="complete-label">
                    Complete: <?php echo $task['CompletedCount']; ?>/<?php echo $task['AssignedCourses']; ?>
                </div>
            </div>
            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($task['Description']); ?></p>
            <div class="mt-4 text-sm text-gray-500">
                <p>Due: <?php echo date("F j, Y", strtotime($task['DueDate'])); ?></p>
                <p>School Year: <?php echo htmlspecialchars($task['SchoolYear']); ?> | Term: <?php echo htmlspecialchars($task['Term']); ?></p>
                
                <!-- Assigned Courses with Professors -->
                <div class="mt-2">
                    <p class="font-medium">Assigned Courses (<?php echo $task['AssignedCourses']; ?>):</p>
                    <?php if (!empty($task['Courses'])): ?>
                        <div class="mt-1 pl-2 border-l-2 border-gray-200 max-h-[120px] overflow-y-auto">
                            <?php 
                            $currentProgram = '';
                            foreach ($task['Courses'] as $course): 
                                if ($currentProgram != $course['ProgramName']):
                                    $currentProgram = $course['ProgramName'];
                            ?>
                                <div class="course-card <?php 
                                    if ($course['AssignmentStatus'] === 'Completed') {
                                        echo 'completed';
                                    } elseif ($course['AssignmentStatus'] === 'Submitted') {
                                        echo 'submitted';
                                    } else {
                                        echo 'pending';
                                    }
                                ?>">
                                    <div class="course-info">
                                        <p class="course-name"><?php echo htmlspecialchars($course['CourseCode'] . ' ' . $course['CourseTitle']); ?></p>
                                        <div class="course-badges">
                                            <span class="badge"></span>
                                            <span class="badge"></span>
                                        </div>
                                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($course['ProgramName']); ?></p>
                                        <p class="text-xs text-gray-600">Assigned to: <?php echo !empty($course['AssignedTo']) ? htmlspecialchars($course['AssignedTo']) : 'No assigned professor'; ?></p>
                                    </div>
                                    <div class="status">
                                        <span class="status-label <?php 
                                            if ($course['AssignmentStatus'] === 'Completed') {
                                                echo 'completed';
                                            } elseif ($course['AssignmentStatus'] === 'Submitted') {
                                                echo 'submitted';
                                            } else {
                                                echo 'pending';
                                            }
                                        ?>">
                                            <?php echo $course['AssignmentStatus']; ?>
                                        </span>
                                        
                                        <?php if ($course['AssignmentStatus'] === 'Completed' && !empty($course['ApprovalDate'])): ?>
                                            <p class="text-xs text-gray-500">Completed: <?php echo date("M j, Y", strtotime($course['ApprovalDate'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                                <div class="pl-3 py-0.5">
                                    <?php
                                    // Fetch submission details for this course
                                    $submissionSql = "SELECT ta.TaskAssignmentID, ta.SubmissionPath, ta.SubmissionDate, ta.Status as AssignmentStatus 
                                                    FROM task_assignments ta 
                                                    WHERE ta.TaskID = ? AND ta.CourseCode = ? AND ta.ProgramID = ?";
                                    $submissionStmt = $conn->prepare($submissionSql);
                                    $submissionStmt->bind_param("isi", $task['TaskID'], $course['CourseCode'], $course['ProgramID']);
                                    $submissionStmt->execute();
                                    $submissionResult = $submissionStmt->get_result();
                                    $submission = $submissionResult->fetch_assoc();
                                    $submissionStmt->close();
                                    ?>

                                    <?php if ($submission && $submission['SubmissionPath']): ?>
                                        <div class="mt-2 pl-5">
                                            <p class="text-sm text-gray-600">
                                                Submitted: <?php echo date("M j, Y g:i A", strtotime($submission['SubmissionDate'])); ?>
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <button onclick="openPreviewModal('<?php echo '../../' . htmlspecialchars($submission['SubmissionPath']); ?>', '<?php echo $submission['TaskAssignmentID']; ?>')" 
                                                        class="text-blue-600 hover:underline text-sm">
                                                    <i class="fas fa-eye"></i> Preview Submission
                                                </button>
                                                
                                                <?php if ($task['UserRole'] === 'DN' && $submission['AssignmentStatus'] === 'Submitted'): ?>
                                                    <button onclick="openRevisionModal('<?php echo $submission['TaskAssignmentID']; ?>')" 
                                                            class="text-yellow-600 hover:underline text-sm">
                                                        <i class="fas fa-undo"></i> Request Revision
                                                    </button>
                                                    <form method="POST" action="task_actions.php" class="inline">
                                                        <input type="hidden" name="task_assignment_id" value="<?php echo $submission['TaskAssignmentID']; ?>">
                                                        <input type="hidden" name="action" value="complete">
                                                        <button type="submit" class="text-green-600 hover:underline text-sm">
                                                            <i class="fas fa-check"></i> Mark as Complete
                                                        </button>
                                                    </form>
                                                <?php elseif ($submission['AssignmentStatus'] === 'Completed'): ?>
                                                    <span class="text-green-600 text-sm">
                                                        <i class="fas fa-check-circle"></i> Completed
                                                        <?php if (!empty($submission['ApprovalDate'])): ?>
                                                            on <?php echo date("M j, Y", strtotime($submission['ApprovalDate'])); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400 italic pl-3">No courses assigned</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <a href="task_details.php?id=<?php echo $task['TaskID']; ?>" class="text-blue-600 hover:underline">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
        
    <!-- Plus button -->
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
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Task Title:</label>
                            <input type="text" name="title" placeholder="Enter task title" required 
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-lg font-semibold text-gray-700">Task Description:</label>
                            <textarea name="description" placeholder="Describe the task" 
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                rows="3"></textarea>
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
                        
                        <div class="course-list bg-gray-50 p-4 rounded-lg border border-gray-200 h-[500px] flex flex-col">
                            <div class="course-filter-container flex-none">
                                <div class="flex gap-2 mb-2">
                                    <input type="text" id="courseSearch" placeholder="Search courses..." 
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                        oninput="filterCourses()">
                                    
                                    <select id="filterType" 
                                        class="p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" 
                                        onchange="filterCourses()">
                                        <option value="all">All</option>
                                        <option value="assigned">With Professor</option>
                                        <option value="unassigned">Without Professor</option>
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
                                         data-has-professor="<?= empty($pair['AssignedTo']) ? 'no' : 'yes' ?>">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="assigned[]" value="<?= $pair['ProgramID'] . '|' . $pair['CourseCode'] ?>" 
                                                class="mr-2 course-checkbox" />
                                            <span class="flex flex-col">
                                                <span>
                                                    <span class="font-medium"><?= htmlspecialchars($pair['CourseCode']) ?></span> - 
                                                    <?= htmlspecialchars($pair['Title']) ?>
                                                </span>
                                                <?php if (!empty($pair['AssignedTo'])): ?>
                                                    <span class="text-sm text-gray-600">
                                                        Assigned to: <?= htmlspecialchars($pair['AssignedTo']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-sm text-red-600">
                                                        No assigned professor
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php 
                                    // Check if the next item has a different program
                                    $nextIndex = $index + 1;
                                    if (!isset($facultyCoursePairs[$nextIndex]) || 
                                        $facultyCoursePairs[$nextIndex]['ProgramName'] != $currentProgram) {
                                        echo '</div>'; // Close program section
                                    }
                                    endforeach; 
                                ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <div class="flex-none flex justify-end gap-4 pt-4 mt-4 border-t border-gray-200">
                <button type="button" onclick="closeTaskModal()" 
                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">
                    Cancel
                </button>
                <button type="submit" name="create_task" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">
                    Create Task
                </button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 pr-12 rounded-lg shadow-lg w-[90vw] max-w-[1200px] max-h-[95vh] flex flex-col relative">
            <button onclick="closePreviewModal()" class="absolute top-4 right-4 text-gray-700 hover:text-red-600 text-4xl font-bold z-50" title="Close">&times;</button>
            <div class="flex justify-center items-center mb-4" style="position:relative;">
                <h2 class="text-2xl font-bold w-full text-center font-overpass">File Preview</h2>
            </div>
            <div class="flex-1 overflow-hidden" id="previewContent"></div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
            // Reset filters when opening
            document.getElementById('courseSearch').value = '';
            document.getElementById('filterType').value = 'all';
            filterCourses();
            
            const dropdown = document.getElementById('task-dropdown');
            dropdown.classList.remove('show'); 
        }

        function closeTaskModal() {
            document.getElementById('taskModal').classList.add('hidden');
            // Reset filters when closing
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
            const courseItems = document.querySelectorAll('.course-item');
            let visibleCount = 0;
            
            // Show/hide program titles based on if any courses in that program are visible
            const programSections = {};
            
            courseItems.forEach(item => {
                const courseCode = item.dataset.courseCode;
                const courseTitle = item.dataset.courseTitle;
                const hasProfessor = item.dataset.hasProfessor;
                const programSection = item.closest('.program-section');
                const programId = programSection ? programSection.dataset.program : null;
                
                // Match search term
                const matchesSearch = courseCode.includes(searchTerm) || courseTitle.includes(searchTerm);
                
                // Match filter type
                let matchesFilter = true;
                if (filterType === 'assigned' && hasProfessor === 'no') {
                    matchesFilter = false;
                } else if (filterType === 'unassigned' && hasProfessor === 'yes') {
                    matchesFilter = false;
                }
                
                const isVisible = matchesSearch && matchesFilter;
                item.style.display = isVisible ? 'block' : 'none';
                
                if (isVisible) {
                    visibleCount++;
                    if (programId) {
                        programSections[programId] = true;
                    }
                }
            });
            
            // Update program section visibility
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
            
            // Update the counter
            const counterText = visibleCount === 1 
                ? "Showing 1 course" 
                : `Showing ${visibleCount} courses`;
            document.getElementById('courseCounter').textContent = counterText;
        }

        function toggleSelectAll() {
            const btn = document.getElementById('selectAllBtn');
            const checkboxes = document.querySelectorAll('.course-item:not([style*="display: none"]) .course-checkbox');
            
            // Check if all visible checkboxes are checked
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            // Toggle checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            // Update button text
            btn.textContent = allChecked ? 'Select All' : 'Deselect All';
        }

        function openPreviewModal(filePath, taskId) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            // Determine file type
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

        // Close modals when clicking outside
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

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") {
                closePreviewModal();
                closeRevisionModal();
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>