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

$statements = [];

try {
    $accountID = $_SESSION['AccountID'];

    $query = "SELECT p.PersonnelID, p.FirstName, p.LastName, p.Role, f.FacultyID, f.Faculty
              FROM personnel p
              INNER JOIN faculties f ON p.FacultyID = f.FacultyID
              WHERE p.AccountID = ?";

    $stmt = $conn->prepare($query);
    $statements[] = $stmt;
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $personnelId = $userData['PersonnelID'];
        $facultyId = $userData['FacultyID'];
        $facultyName = $userData['Faculty'];
        $userRole = $userData['Role'];
    } else {
        $personnelId = 0;
        $facultyId = 0;
        $facultyName = "Unknown Faculty";
        $userRole = "DN";
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
    $statements[] = $roleStmt;
    $roleStmt->bind_param("i", $facultyId);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();

    $roleCounts = ['DN' => 0, 'COR' => 0, 'PH' => 0, 'FM' => 0];
    while ($row = $roleResult->fetch_assoc()) {
        $roleCounts[$row['Role']] = $row['count'];
    }

    //total faculty count
    $totalQuery = "SELECT COUNT(*) as total FROM personnel WHERE FacultyID = ?";
    $totalStmt = $conn->prepare($totalQuery);
    $statements[] = $totalStmt;
    $totalStmt->bind_param("i", $facultyId);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalFaculty = $totalRow['total'];

    // ongoing task
    $ongoingTaskTitle = null;
    $ongoingTaskId = null;
    $ongoingTaskSql = "SELECT TaskID, Title FROM tasks WHERE FacultyID = ? AND Status = 'Pending' ORDER BY CreatedAt DESC LIMIT 1";
    $ongoingTaskStmt = $conn->prepare($ongoingTaskSql);
    $statements[] = $ongoingTaskStmt;
    $ongoingTaskStmt->bind_param("i", $facultyId);
    $ongoingTaskStmt->execute();
    $ongoingTaskResult = $ongoingTaskStmt->get_result();
    if ($ongoingTaskResult && $ongoingTaskResult->num_rows > 0) {
        $ongoingTaskRow = $ongoingTaskResult->fetch_assoc();
        $ongoingTaskTitle = $ongoingTaskRow['Title'];
        $ongoingTaskId = $ongoingTaskRow['TaskID'];
    }

    //submission counts
    if ($userRole === 'DN') {
        $sqlPending = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.FacultyID = ? AND ta.Status = 'Submitted' AND ta.ReviewStatus = 'Not Reviewed'";
        $stmtPending = $conn->prepare($sqlPending);
        $statements[] = $stmtPending;
        $stmtPending->bind_param("i", $facultyId);
        $stmtPending->execute();
        $res = $stmtPending->get_result();
        $pendingCount = $res->fetch_assoc()['cnt'];

        $sqlComplete = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.FacultyID = ? AND ta.Status = 'Completed'";
        $stmtComplete = $conn->prepare($sqlComplete);
        $statements[] = $stmtComplete;
        $stmtComplete->bind_param("i", $facultyId);
        $stmtComplete->execute();
        $res = $stmtComplete->get_result();
        $completeCount = $res->fetch_assoc()['cnt'];

        $sqlUnaccomplished = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            WHERE t.FacultyID = ? AND ta.Status = 'Pending'";
        $stmtUnaccomplished = $conn->prepare($sqlUnaccomplished);
        $statements[] = $stmtUnaccomplished;
        $stmtUnaccomplished->bind_param("i", $facultyId);
        $stmtUnaccomplished->execute();
        $res = $stmtUnaccomplished->get_result();
        $unaccomplishedCount = $res->fetch_assoc()['cnt'];
    } else {
        $sqlPending = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
            WHERE t.FacultyID = ? AND ta.Status = 'Submitted' AND pc.PersonnelID = ?";
        $stmtPending = $conn->prepare($sqlPending);
        $statements[] = $stmtPending;
        $stmtPending->bind_param("ii", $facultyId, $personnelId);
        $stmtPending->execute();
        $res = $stmtPending->get_result();
        $pendingCount = $res->fetch_assoc()['cnt'];

        $sqlComplete = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
            WHERE t.FacultyID = ? AND ta.Status = 'Completed' AND pc.PersonnelID = ?";
        $stmtComplete = $conn->prepare($sqlComplete);
        $statements[] = $stmtComplete;
        $stmtComplete->bind_param("ii", $facultyId, $personnelId);
        $stmtComplete->execute();
        $res = $stmtComplete->get_result();
        $completeCount = $res->fetch_assoc()['cnt'];

        $sqlUnaccomplished = "SELECT COUNT(*) as cnt FROM task_assignments ta
            JOIN tasks t ON ta.TaskID = t.TaskID
            JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
            WHERE t.FacultyID = ? AND ta.Status = 'Pending' AND pc.PersonnelID = ? AND t.Status = 'Pending'";
        $stmtUnaccomplished = $conn->prepare($sqlUnaccomplished);
        $statements[] = $stmtUnaccomplished;
        $stmtUnaccomplished->bind_param("ii", $facultyId, $personnelId);
        $stmtUnaccomplished->execute();
        $res = $stmtUnaccomplished->get_result();
        $unaccomplishedCount = $res->fetch_assoc()['cnt'];
    }

    $totalSubmissions = $pendingCount + $unaccomplishedCount + $completeCount;
    $progress = $totalSubmissions > 0 ? round(($completeCount / $totalSubmissions) * 100) : 0;

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

    $courseQuery = "SELECT DISTINCT c.CourseCode, c.Title, COUNT(DISTINCT ta.TaskID) as TaskCount
                    FROM program_courses pc
                    JOIN courses c ON pc.CourseCode = c.CourseCode
                    LEFT JOIN task_assignments ta ON pc.CourseCode = ta.CourseCode AND pc.ProgramID = ta.ProgramID
                    WHERE pc.PersonnelID = ?
                    GROUP BY c.CourseCode, c.Title
                    ORDER BY TaskCount DESC
                    LIMIT 5";
    $courseStmt = $conn->prepare($courseQuery);
    $statements[] = $courseStmt;
    $courseStmt->bind_param("i", $personnelId);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();

    $activityQuery = "SELECT ta.TaskID, t.Title, ta.Status, t.CreatedAt as UpdatedAt, c.CourseCode
                    FROM task_assignments ta
                    JOIN tasks t ON ta.TaskID = t.TaskID
                    JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                    JOIN courses c ON ta.CourseCode = c.CourseCode
                    WHERE pc.PersonnelID = ?
                    ORDER BY t.CreatedAt DESC
                    LIMIT 5";
    $activityStmt = $conn->prepare($activityQuery);
    $statements[] = $activityStmt;
    $activityStmt->bind_param("i", $personnelId);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result();

    $taskQuery = "SELECT DISTINCT t.TaskID, t.Title, t.DueDate, ta.Status, ta.CourseCode, c.Title AS CourseTitle 
                FROM tasks t 
                JOIN task_assignments ta ON t.TaskID = ta.TaskID 
                JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID 
                JOIN courses c ON ta.CourseCode = c.CourseCode
                WHERE pc.PersonnelID = ? 
                AND ta.Status != 'Completed'
                ORDER BY t.DueDate ASC";
    $taskStmt = $conn->prepare($taskQuery);
    $statements[] = $taskStmt;
    $taskStmt->bind_param("i", $personnelId);
    $taskStmt->execute();
    $taskResult = $taskStmt->get_result();

    foreach ($statements as $stmt) {
        if ($stmt) {
            $stmt->close();
        }
    }

    if ($conn) {
        $conn->close();
        $conn = null; 
    }

} catch (Exception $e) {
   
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching data. Please try again later.");
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
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col pr-[10px] px-[50px] md:px-[50px] pt-[15px]  overflow-y-auto">
        <h1 class="py-[10px] text-[35px] font-overpass font-bold" style="letter-spacing: -0.03em;">Dashboard</h1>
        <hr class="border-gray-300 py-[10px]">
        <div class="relative w-full">
            <div class="flex w-full gap-5 justify-between">
                <!-- Left Column: Tasks, Faculty, and New Sections -->
                <div class="flex-1 max-w-[900px] flex flex-col gap-5">
                    <!-- Top Row: Tasks and Faculty -->
                    <div class="flex gap-5">
                        <!-- Tasks -->
                        <div class="flex-1 select-none bg-white p-[30px] pb-[20px] font-overpass rounded-sm shadow-md h-[210px] relative" id="tasksContainer">
                            <div class="flex items-center justify-between mb-0">
                                <h2 class="text-[20px] font-semibold">Tasks</h2>
                                <a href="../task/task_frame.php" class="text-sm font-onest text-blue-600 hover:underline">See all tasks</a>
                                
                            </div>
                            <div class="flex justify-center">
                                <hr class="border-gray-100 mb-3 mt-1 w-full">
                            </div>
                            <?php
                            ?>

                            <?php if ($taskResult->num_rows > 0): ?>
                                <div class="space-y-3  max-h-[120px] overflow-y-auto pr-2" id="tasksList">
                                    <?php while ($task = $taskResult->fetch_assoc()): ?>
                                        <a href="submissionspage.php?task_id=<?php echo $task['TaskID']; ?>&from=fm-dash" class="block">
                                            <?php
                                            $borderColor = '';
                                            switch ($task['Status']) {
                                                case 'Submitted':
                                                    $borderColor = '#f59e0b'; // amber
                                                    break;
                                                case 'Completed':
                                                    $borderColor = '#10b981'; // emerald
                                                    break;
                                                default:
                                                    $borderColor = '#ef4444'; // rose
                                            }
                                            ?>
                                            <div class="bg-gray-100 border select-none border-gray-200 rounded-lg p-3 hover:bg-gray-200 transition-all duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1 hover:shadow-md" style="border-bottom: 4px solid <?php echo $borderColor; ?>;">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1">
                                                        <div class="font-medium font-onest text-sm"><?php echo htmlspecialchars($task['Title']); ?></div>
                                                        <div class="text-xs text-blue-700 font-semibold mt-1">
                                                            <?php echo htmlspecialchars($task['CourseCode']); ?> - <?php echo htmlspecialchars($task['CourseTitle']); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            Due: <?php echo date('M d, Y', strtotime($task['DueDate'])); ?>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <?php
                                                        $statusColor = '';
                                                        switch ($task['Status']) {
                                                            case 'Submitted':
                                                                $statusColor = 'bg-amber-100 text-amber-800';
                                                                break;
                                                            case 'Completed':
                                                                $statusColor = 'bg-emerald-100 text-emerald-800';
                                                                break;
                                                            default:
                                                                $statusColor = 'bg-rose-100 text-rose-800';
                                                        }
                                                        ?>
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                                            <?php echo $task['Status']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="h-[calc(100%-4.1rem)] font-onest flex items-center justify-center">
                                    <span class="text-lg text-gray-500">No active tasks</span>
                                </div>
                            <?php endif; ?>
                            <!-- Resize handle -->
                            <div class="absolute bottom-0 left-0 right-0 h-4 cursor-ns-resize flex items-center justify-center" id="resizeHandle">
                                <div class="w-12 h-1 bg-gray-300 rounded-full"></div>
                            </div>
                        </div>
                        <!-- Faculty -->
                        <div class="w-[300px] bg-white p-[30px] pb-[20px] rounded-sm shadow-md font-overpass h-[210px]">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-[20px] font-semibold">Faculty</h2>
                                <a href="../faculty/faculty_frame.php" class="text-xs font-onest text-blue-600 hover:underline">
                                    Total: <?php echo $totalFaculty; ?> members
                                </a>
                            </div>

                            <div class="flex items-start gap-4">
                                <!-- Donut Chart (Left) -->
                                <div class="faculty-chart-container" style="width: 100px; height: 100px;">
                                    <canvas id="facultyDonutChart"></canvas>
                                </div>

                                <!-- Role Labels (Right) -->
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

                    

                    <!-- Recent Activity -->
                    <div class="bg-white p-[30px] mt-[50px] pb-[20px] font-overpass rounded-sm shadow-md">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h2 class="text-[20px] font-semibold">Recent Activity</h2>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <hr class="border-gray-100 mb-3 mt-1 w-full">
                        </div>
                        <div class="overflow-x-auto rounded-md border border-gray-300">
                            <table class="w-full ">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Task</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Course</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if ($activityResult->num_rows > 0): ?>
                                        <?php while ($activity = $activityResult->fetch_assoc()): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium font-onest text-sm"><?php echo htmlspecialchars($activity['Title']); ?></div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['CourseCode']); ?></div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($activity['UpdatedAt'])); ?></div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                    $statusColor = '';
                                                    switch ($activity['Status']) {
                                                        case 'Submitted':
                                                            $statusColor = 'bg-amber-100 text-amber-800';
                                                            break;
                                                        case 'Completed':
                                                            $statusColor = 'bg-emerald-100 text-emerald-800';
                                                            break;
                                                        default:
                                                            $statusColor = 'bg-rose-100 text-rose-800';
                                                    }
                                                    ?>
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                                        <?php echo $activity['Status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-center text-gray-500">No recent activity</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Pinboard -->
                <div class="w-[300px] shrink-0">
                    <?php include 'pinboard.php'; ?>
                </div>
            </div>
        </div>
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

        const tasksContainer = document.getElementById('tasksContainer');
        const tasksList = document.getElementById('tasksList');
        const resizeHandle = document.getElementById('resizeHandle');
        let startY, startHeight;

        resizeHandle.addEventListener('mousedown', initResize);

        function initResize(e) {
            startY = e.clientY;
            startHeight = parseInt(document.defaultView.getComputedStyle(tasksContainer).height, 10);
            document.documentElement.style.cursor = 'ns-resize';
            document.addEventListener('mousemove', resize);
            document.addEventListener('mouseup', stopResize);
        }

        function resize(e) {
            const newHeight = startHeight + (e.clientY - startY);
            if (newHeight > 210) {
                tasksContainer.style.height = newHeight + 'px';
                tasksList.style.maxHeight = (newHeight - 90) + 'px'; 
            }
        }

        function stopResize() {
            document.documentElement.style.cursor = '';
            document.removeEventListener('mousemove', resize);
            document.removeEventListener('mouseup', stopResize);
        }

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark');
        }
    });
    </script>
</body>
</html>
