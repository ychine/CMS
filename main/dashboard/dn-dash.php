<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header('Location: ../../index.php');
    exit;
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$accountID = $_SESSION['AccountID'];

$query = "SELECT p.PersonnelID, p.FirstName, p.LastName, p.Role, f.FacultyID, f.Faculty
          FROM personnel p
          INNER JOIN faculties f ON p.FacultyID = f.FacultyID
          WHERE p.AccountID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    $personnelId = $userData['PersonnelID'];
    $facultyId = $userData['FacultyID'];
    $facultyName = $userData['Faculty'];
} else {
    $personnelId = 0;
    $facultyId = 0;
    $facultyName = "Unknown Faculty";
}

$roleCounts = [];
$roleLabels = [
    'DN' => 'Dean',
    'COR' => 'Coordinator',
    'PH' => 'Program Head',
    'FM' => 'Faculty Member'
];

$roleQuery = "SELECT Role, COUNT(*) as count FROM personnel WHERE FacultyID = ? GROUP BY Role";
$roleStmt = $conn->prepare($roleQuery);
$roleStmt->bind_param("i", $facultyId);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();

$roleCounts = ['DN' => 0, 'COR' => 0, 'PH' => 0, 'FM' => 0];
while ($row = $roleResult->fetch_assoc()) {
    $roleCounts[$row['Role']] = $row['count'];
}

$totalQuery = "SELECT COUNT(*) as total FROM personnel WHERE FacultyID = ?";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param("i", $facultyId);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalFaculty = $totalRow['total'];

$pendingSubmissionsStmt = $conn->prepare("SELECT ta.TaskID, ta.CourseCode, ta.SubmissionDate, 
                          c.Title as CourseTitle, t.Title as TaskTitle,
                          p.ProgramName, CONCAT(per.FirstName, ' ', per.LastName) as SubmittedBy
                          FROM task_assignments ta
                          JOIN tasks t ON ta.TaskID = t.TaskID
                          JOIN courses c ON ta.CourseCode = c.CourseCode
                          JOIN programs p ON ta.ProgramID = p.ProgramID
                          JOIN personnel per ON ta.PersonnelID = per.PersonnelID
                          WHERE t.FacultyID = ? 
                          AND ta.Status = 'Submitted' 
                          AND ta.ReviewStatus = 'Not Reviewed'
                          ORDER BY ta.SubmissionDate DESC");
$pendingSubmissionsStmt->bind_param("i", $facultyId);
$pendingSubmissionsStmt->execute();
$pendingSubmissionsResult = $pendingSubmissionsStmt->get_result();
$pendingSubmissions = [];
while ($row = $pendingSubmissionsResult->fetch_assoc()) {
    $pendingSubmissions[] = $row;
}
$pendingSubmissionsStmt->close();

$activityQuery = "SELECT ta.TaskID, t.Title, ta.Status, t.CreatedAt as UpdatedAt, c.CourseCode,
                CONCAT(p.FirstName, ' ', p.LastName) as PersonnelName
                FROM task_assignments ta
                JOIN tasks t ON ta.TaskID = t.TaskID
                JOIN courses c ON ta.CourseCode = c.CourseCode
                JOIN personnel p ON ta.PersonnelID = p.PersonnelID
                WHERE t.FacultyID = ?
                ORDER BY t.CreatedAt DESC
                LIMIT 5";
$activityStmt = $conn->prepare($activityQuery);
$activityStmt->bind_param("i", $facultyId);
$activityStmt->execute();
$activityResult = $activityStmt->get_result();
$activities = [];
while ($row = $activityResult->fetch_assoc()) {
    $activities[] = $row;
}
$activityStmt->close();

$ongoingTaskTitle = null;
$ongoingTaskId = null;
$ongoingTaskSql = "SELECT DISTINCT t.TaskID, t.Title, t.DueDate 
                   FROM tasks t 
                   JOIN task_assignments ta ON t.TaskID = ta.TaskID 
                   WHERE t.FacultyID = ? 
                   AND EXISTS (
                       SELECT 1 FROM task_assignments 
                       WHERE TaskID = t.TaskID 
                       AND Status != 'Completed'
                   )
                   ORDER BY t.CreatedAt DESC";
$ongoingTaskStmt = $conn->prepare($ongoingTaskSql);
$ongoingTaskStmt->bind_param("i", $facultyId);
$ongoingTaskStmt->execute();
$ongoingTaskResult = $ongoingTaskStmt->get_result();
$allTasks = [];
if ($ongoingTaskResult && $ongoingTaskResult->num_rows > 0) {
    while ($row = $ongoingTaskResult->fetch_assoc()) {
        $taskId = $row['TaskID'];
        $taskTitle = $row['Title'];
        $dueDate = $row['DueDate'];
     
        $sqlPending = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.TaskID = ? AND ta.Status = 'Submitted' AND ta.ReviewStatus = 'Not Reviewed'";
        $stmtPending = $conn->prepare($sqlPending);
        $stmtPending->bind_param("i", $taskId);
        $stmtPending->execute();
        $pendingCount = $stmtPending->get_result()->fetch_assoc()['cnt'];
        $stmtPending->close();

        $sqlComplete = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.TaskID = ? AND ta.Status = 'Completed'";
        $stmtComplete = $conn->prepare($sqlComplete);
        $stmtComplete->bind_param("i", $taskId);
        $stmtComplete->execute();
        $completeCount = $stmtComplete->get_result()->fetch_assoc()['cnt'];
        $stmtComplete->close();

        $sqlUnaccomplished = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.TaskID = ? AND ta.Status = 'Pending'";
        $stmtUnaccomplished = $conn->prepare($sqlUnaccomplished);
        $stmtUnaccomplished->bind_param("i", $taskId);
        $stmtUnaccomplished->execute();
        $unaccomplishedCount = $stmtUnaccomplished->get_result()->fetch_assoc()['cnt'];
        $stmtUnaccomplished->close();

        $totalSubmissions = $pendingCount + $unaccomplishedCount + $completeCount;
        $progress = $totalSubmissions > 0 ? round(($completeCount / $totalSubmissions) * 100) : 0;

        $allTasks[] = [
            'TaskID' => $taskId,
            'Title' => $taskTitle,
            'PendingCount' => $pendingCount,
            'CompleteCount' => $completeCount,
            'UnaccomplishedCount' => $unaccomplishedCount,
            'Progress' => $progress,
            'DueDate' => $dueDate
        ];
    }

    if (!empty($allTasks)) {
        $ongoingTaskTitle = $allTasks[0]['Title'];
        $ongoingTaskId = $allTasks[0]['TaskID'];
    }
}
$ongoingTaskStmt->close();

$roleColors = [
    'DN' => '#4F46E5',
    'COR' => '#10B981',
    'PH' => '#F59E0B',
    'FM' => '#EF4444'
];

$formattedRoleData = [];
foreach ($roleLabels as $code => $label) {
    $formattedRoleData[] = [
        'name' => $label,
        'value' => $roleCounts[$code] ?? 0,
        'color' => $roleColors[$code]
    ];
}
$roleDataJSON = json_encode($formattedRoleData);

$stmt->close();
$roleStmt->close();
$totalStmt->close();
$conn->close();

function getDueDateClass($dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $today->diff($due);
    $daysUntilDue = $diff->days;
    
    if ($today > $due) {
        $daysUntilDue = -$daysUntilDue;
    }
    
    return $daysUntilDue <= 3 ? 'text-red-500' : 'text-gray-500';
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }
        body.dark {
            background: #18181b !important;
            color: #f3f4f6 !important;
        }
        .dark .bg-white {
            background: #23232a !important;
            color: #f3f4f6 !important;
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
        .dark .bg-blue-50, .dark .bg-blue-100 {
            background: #1e293b !important;
        }
        .dark .file-input-label {
            background: #23232a !important;
            border-color: #374151 !important;
            color: #e5e7eb !important;
        }
        .dark .bg-gray-100,
        .dark .bg-gray-200 {
            background: #23232a !important;
            color: #f3f4f6 !important;
        }
        .dark .border,
        .dark .border-gray-300,
        .dark .border-gray-200 {
            border-color: #374151 !important;
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
        .dark .hover\:bg-gray-200:hover {
            background: #2d2d36 !important;
        }
        /* Dark mode styles for pending submissions */
        .dark .bg-gray-50 {
            background: #1e1e24 !important;
        }
        .dark .hover\:bg-gray-100:hover {
            background: #2d2d36 !important;
        }
        .dark .bg-blue-100 {
            background: #1e3a8a !important;
        }
        .dark .text-blue-800 {
            color: #93c5fd !important;
        }
        .task-gallery {
            position: relative;
            overflow: hidden;
        }
        .task-slide {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .task-slide.active {
            display: block;
            opacity: 1;
        }
  
        @keyframes progressFill {
            from { width: 0; }
            to { width: var(--progress-width); }
        }
        .progress-bar-fill {
            animation: progressFill 1s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col px-[50px] md:px-[50px] pt-[15px] overflow-y-auto">
        <h1 class="py-[10px] text-[35px] font-overpass font-bold" style="letter-spacing: -0.03em;">Dashboard</h1>
        <hr class="border-gray-300 py-[10px]">
        <div class="relative w-full">
            <div class="flex w-full gap-5 justify-between">
                <!-- Left group: Submission + Faculty -->
                <div class="flex gap-5 flex-1 max-w-[900px] flex-col">
                    <div class="flex gap-5">
                        <!-- Submissions -->
                        <div class="flex-1 bg-white rounded-sm font-overpass shadow-lg h-[230px] flex">
                            <?php if (!empty($allTasks)): ?>
                                <?php if (count($allTasks) > 1): ?>
                                    <div class="w-8 h-full bg-white hover:bg-gradient-to-l hover:from-white hover:to-gray-100 transition-all duration-300 ease-in-out flex items-center justify-center rounded-sm">
                                        <button class="w-full h-full flex items-center justify-center" onclick="prevTask()">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-1 flex flex-col">
                                    <div class="<?php echo count($allTasks) > 1 ? 'pt-[30px]' : 'px-[30px] pt-[30px]'; ?>">
                                        <div class="flex items-center justify-between mb-0">
                                            <h2 class="text-[20px] font-semibold">Submissions</h2>
                                            <div id="ongoingTaskTitle" class="text-sm font-onest">
                                                Task: 
                                                <?php if (!empty($allTasks)): ?>
                                                    <a href="submissionspage.php?task_id=<?php echo $allTasks[0]['TaskID']; ?>&from=dn-dash" class="text-blue-600 hover:underline">
                                                        <?php echo htmlspecialchars($allTasks[0]['Title']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0 mb-3 font-onest">
                                        <hr class="border-gray-100 flex-1">
                                        <span class="text-xs whitespace-nowrap <?php echo getDueDateClass($allTasks[0]['DueDate']); ?> cursor-help" 
                                              title="<?php echo getDueDateTooltip($allTasks[0]['DueDate']); ?>">
                                            Due: <?php echo date('M j, Y', strtotime($allTasks[0]['DueDate'])); ?>
                                        </span>
                                    </div>
                                    </div>
                                    

                                    <div class="flex-1 task-gallery <?php echo count($allTasks) > 1 ? '' : 'px-[30px]'; ?>">
                                        <?php foreach ($allTasks as $index => $task): ?>
                                            <div class="task-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                data-task-id="<?php echo $task['TaskID']; ?>" 
                                                data-task-title="<?php echo htmlspecialchars($task['Title']); ?>"
                                                data-due-date="<?php echo date('M j, Y', strtotime($task['DueDate'])); ?>"
                                                data-is-near-deadline="<?php echo (strtotime($task['DueDate']) - time()) <= (3 * 24 * 60 * 60) ? 'true' : 'false'; ?>"
                                                data-due-date-tooltip="<?php echo getDueDateTooltip($task['DueDate']); ?>">
                                                <div class="flex space-x-4">
                                                    <a href="submissionspage.php?type=pending&task_id=<?php echo $task['TaskID']; ?>&from=dn-dash" class="flex-1">
                                                        <div class="bg-gray-100 rounded-lg p-1 hover:bg-gray-200 transition-all duration-300 ease-in-out cursor-pointer h-[80px] flex items-center transform hover:-translate-y-1 hover:shadow-md" style="border-bottom: 4px solid #f59e0b;">
                                                            <div class="flex items-center w-full">
                                                                <div class="text-2xl font-bold mr-2 font-onest ml-3"><?php echo $task['PendingCount']; ?></div>
                                                                <div class="text-xs font-onest whitespace-nowrap">Pending Review</div>
                                                                <div class="ml-auto">
                                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>

                                                    <a href="submissionspage.php?type=unaccomplished&task_id=<?php echo $task['TaskID']; ?>&from=dn-dash" class="flex-1">
                                                        <div class="bg-gray-100 rounded-lg p-1 hover:bg-gray-200 transition-all duration-300 ease-in-out cursor-pointer h-[80px] flex items-center transform hover:-translate-y-1 hover:shadow-md" style="border-bottom: 4px solid #ef4444;">
                                                            <div class="flex items-center w-full">
                                                                <div class="text-2xl font-bold mr-2 font-onest ml-3"><?php echo $task['UnaccomplishedCount']; ?></div>
                                                                <div class="text-xs font-onest whitespace-nowrap">Unaccomplished</div>
                                                                <div class="ml-auto">
                                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>

                                                    <a href="submissionspage.php?type=complete&task_id=<?php echo $task['TaskID']; ?>&from=dn-dash" class="flex-1">
                                                        <div class="bg-gray-100 rounded-lg p-1 hover:bg-gray-200 transition-all duration-300 ease-in-out cursor-pointer h-[80px] flex items-center transform hover:-translate-y-1 hover:shadow-md" style="border-bottom: 4px solid #10b981;">
                                                            <div class="flex items-center w-full">
                                                                <div class="text-2xl font-bold mr-2 font-onest ml-3"><?php echo $task['CompleteCount']; ?></div>
                                                                <div class="text-xs font-onest whitespace-nowrap">Complete</div>
                                                                <div class="ml-auto">
                                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>

                                                <div class="flex items-center mt-4">
                                                    <div class="text-xs mr-2 font-medium"><?php echo $task['Progress']; ?>%</div>
                                                    <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                                        <div class="progress-bar-fill bg-green-500 h-2" style="--progress-width: <?php echo $task['Progress']; ?>%"></div>
                                                    </div>
                                                    <div class="ml-2">
                                                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                             
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <?php if (count($allTasks) > 1): ?>
                                    <div class="w-8 h-full bg-white hover:bg-gradient-to-r hover:from-white hover:to-gray-100 transition-all duration-300 ease-in-out flex items-center justify-center rounded-sm">
                                        <button class="w-full h-full flex items-center justify-center" onclick="nextTask()">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="flex-1 flex flex-col items-center justify-center gap-4">
                                    <span class="text-lg text-gray-500">No tasks active</span>
                                    <a href="../task/task_frame.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        Create a Task
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Faculty -->
                        <div class="w-[300px] bg-white p-[30px] rounded-sm shadow-lg font-overpass h-[230px]">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-[20px] font-semibold">Faculty</h2>
                                <a href="../faculty/faculty_frame.php" class="text-xs text-blue-600 font-onest hover:underline">
                                    Total: <?php echo $totalFaculty; ?> members
                                </a>
                            </div>

                            <div class="flex items-start gap-4">
                                <!-- Donut Chart-->
                                <div class="faculty-chart-container" style="width: 100px; height: 100px;">
                                    <canvas id="facultyDonutChart"></canvas>
                                </div>

                                <!-- Role Labels -->
                                <div class="grid grid-cols-1 gap-1 flex-1">
                                    <?php foreach($roleLabels as $code => $label): ?>
                                        <div class="flex items-center bg-gray-100 rounded px-2 py-1">
                                            <div class="w-3 h-3 rounded-full mr-2 shrink-0" style="background-color: <?php echo $roleColors[$code]; ?>"></div>
                                            <div class="text-xs flex-1 truncate"><?php echo $label; ?></div>
                                            <div class="text-xs font-semibold"><?php echo $roleCounts[$code] ?? 0; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Submissions -->
                    <h2 class="pl-2 text-2xl pt-5 font-overpass font-bold">Pending Submissions</h2>
                    <div class="bg-white p-[30px] rounded-lg shadow-md font-overpass">
                        <?php if (!empty($pendingSubmissions)): ?>
                            <div class="space-y-3">
                                <?php foreach ($pendingSubmissions as $submission): ?>
                                    <a href="submissionspage.php?task_id=<?php echo $submission['TaskID']; ?>&from=dn-dash" 
                                       class="block p-3 border border-gray-200 bg-gray-50 rounded-lg hover:bg-gray-100 transition-all duration-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($submission['CourseCode'] . ' - ' . $submission['CourseTitle']); ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($submission['TaskTitle']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($submission['ProgramName']); ?> • 
                                                    Submitted by: <?php echo htmlspecialchars($submission['SubmittedBy']); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Pending Review
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('M j, Y', strtotime($submission['SubmissionDate'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex justify-end mt-4">
                                <a href="../task/task_frame.php" class="text-sm text-blue-600 hover:underline">View All Tasks</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <p>No pending submissions at the moment</p>
                            </div>
                        <?php endif; ?>
                    </div>

                   

                    
                </div>
                <!-- Pinboard sa right -->
                <?php include 'pinboard.php'; ?>
            </div>

            
        </div>
         <hr class="border-gray-300 w-full my-[50px]">
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleData = <?php echo $roleDataJSON; ?>;
        const colors = roleData.map(item => item.color);
        const labels = roleData.map(item => item.name);
        const values = roleData.map(item => item.value);

        const ctx = document.getElementById('facultyDonutChart').getContext('2d');
        const facultyChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw} members`;
                            }
                        }
                    }
                }
            }
        });
    });

    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
    }

    let currentTaskIndex = 0;
    const taskSlides = document.querySelectorAll('.task-slide');
    const totalTasks = taskSlides.length;
    const ongoingTaskTitle = document.getElementById('ongoingTaskTitle');

    function showTask(index) {
        taskSlides.forEach(slide => slide.classList.remove('active'));
        taskSlides[index].classList.add('active');
        currentTaskIndex = index;
       
        const currentTask = taskSlides[index];
        const taskId = currentTask.dataset.taskId;
        const taskTitle = currentTask.dataset.taskTitle;
        const taskDueDate = currentTask.dataset.dueDate;
        const isNearDeadline = currentTask.dataset.isNearDeadline === 'true';
        const dueDateTooltip = currentTask.dataset.dueDateTooltip;
        
        ongoingTaskTitle.innerHTML = `Task: <a href="submissionspage.php?task_id=${taskId}&from=dn-dash" class="text-blue-600 hover:underline">${taskTitle}</a>`;
        const dueDateSpan = document.querySelector('.text-xs.whitespace-nowrap');
        dueDateSpan.innerHTML = `Due: ${taskDueDate}`;
        dueDateSpan.className = `text-xs whitespace-nowrap ${isNearDeadline ? 'text-red-500' : 'text-gray-500'} cursor-help`;
        dueDateSpan.title = dueDateTooltip;
    }

    function nextTask() {
        const nextIndex = (currentTaskIndex + 1) % totalTasks;
        showTask(nextIndex);
    }

    function prevTask() {
        const prevIndex = (currentTaskIndex - 1 + totalTasks) % totalTasks;
        showTask(prevIndex);
    }

    if (taskSlides.length > 0) {
        showTask(0);
    }
    </script>
</body>
</html>
