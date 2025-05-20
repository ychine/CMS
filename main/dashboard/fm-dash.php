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

// Fetch the ongoing (pending) task for this faculty
$ongoingTaskTitle = null;
$ongoingTaskId = null;
$ongoingTaskSql = "SELECT TaskID, Title FROM tasks WHERE FacultyID = ? AND Status = 'Pending' ORDER BY CreatedAt DESC LIMIT 1";
$ongoingTaskStmt = $conn->prepare($ongoingTaskSql);
$ongoingTaskStmt->bind_param("i", $facultyId);
$ongoingTaskStmt->execute();
$ongoingTaskResult = $ongoingTaskStmt->get_result();
if ($ongoingTaskResult && $ongoingTaskResult->num_rows > 0) {
    $ongoingTaskRow = $ongoingTaskResult->fetch_assoc();
    $ongoingTaskTitle = $ongoingTaskRow['Title'];
    $ongoingTaskId = $ongoingTaskRow['TaskID'];
}
$ongoingTaskStmt->close();

// Dynamic submissions counts
if ($userRole === 'DN') {
    // Dean sees all
    $sqlPending = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        WHERE t.FacultyID = ? AND ta.Status = 'Submitted'";
    $stmtPending = $conn->prepare($sqlPending);
    $stmtPending->bind_param("i", $facultyId);
    $stmtPending->execute();
    $res = $stmtPending->get_result();
    $pendingCount = $res->fetch_assoc()['cnt'];
    $stmtPending->close();

    $sqlComplete = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        WHERE t.FacultyID = ? AND ta.Status = 'Completed'";
    $stmtComplete = $conn->prepare($sqlComplete);
    $stmtComplete->bind_param("i", $facultyId);
    $stmtComplete->execute();
    $res = $stmtComplete->get_result();
    $completeCount = $res->fetch_assoc()['cnt'];
    $stmtComplete->close();

    $sqlUnaccomplished = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        WHERE t.FacultyID = ? AND ta.Status = 'Pending'";
    $stmtUnaccomplished = $conn->prepare($sqlUnaccomplished);
    $stmtUnaccomplished->bind_param("i", $facultyId);
    $stmtUnaccomplished->execute();
    $res = $stmtUnaccomplished->get_result();
    $unaccomplishedCount = $res->fetch_assoc()['cnt'];
    $stmtUnaccomplished->close();
} else {
    // Other roles see only their assigned tasks
    $sqlPending = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
        WHERE t.FacultyID = ? AND ta.Status = 'Submitted' AND pc.PersonnelID = ?";
    $stmtPending = $conn->prepare($sqlPending);
    $stmtPending->bind_param("ii", $facultyId, $personnelId);
    $stmtPending->execute();
    $res = $stmtPending->get_result();
    $pendingCount = $res->fetch_assoc()['cnt'];
    $stmtPending->close();

    $sqlComplete = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
        WHERE t.FacultyID = ? AND ta.Status = 'Completed' AND pc.PersonnelID = ?";
    $stmtComplete = $conn->prepare($sqlComplete);
    $stmtComplete->bind_param("ii", $facultyId, $personnelId);
    $stmtComplete->execute();
    $res = $stmtComplete->get_result();
    $completeCount = $res->fetch_assoc()['cnt'];
    $stmtComplete->close();

    $sqlUnaccomplished = "SELECT COUNT(*) as cnt FROM task_assignments ta
        JOIN tasks t ON ta.TaskID = t.TaskID
        JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
        WHERE t.FacultyID = ? AND ta.Status = 'Pending' AND pc.PersonnelID = ? AND t.Status = 'Pending'";
    $stmtUnaccomplished = $conn->prepare($sqlUnaccomplished);
    $stmtUnaccomplished->bind_param("ii", $facultyId, $personnelId);
    $stmtUnaccomplished->execute();
    $res = $stmtUnaccomplished->get_result();
    $unaccomplishedCount = $res->fetch_assoc()['cnt'];
    $stmtUnaccomplished->close();
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

$stmt->close();
$roleStmt->close();
$totalStmt->close();
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

                
                <div class="flex gap-5 flex-1 max-w-[900px]">
                    <!-- Tasks -->
                    <div class="flex-1 bg-white p-[30px] pb-[20px] font-overpass rounded-lg shadow-md h-[210px]">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold">Tasks</h2>
                            <a href="../task/task_frame.php" class="text-sm text-blue-600 hover:underline">See all tasks</a>
                        </div>

                        <?php
                        $taskQuery = "SELECT DISTINCT t.TaskID, t.Title, t.DueDate, ta.Status, ta.CourseCode, c.Title AS CourseTitle 
                                    FROM tasks t 
                                    JOIN task_assignments ta ON t.TaskID = ta.TaskID 
                                    JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID 
                                    JOIN courses c ON ta.CourseCode = c.CourseCode
                                    WHERE pc.PersonnelID = ? 
                                    AND ta.Status != 'Completed'
                                    ORDER BY t.DueDate ASC";
                        $taskStmt = $conn->prepare($taskQuery);
                        $taskStmt->bind_param("i", $personnelId);
                        $taskStmt->execute();
                        $taskResult = $taskStmt->get_result();
                        ?>

                        <?php if ($taskResult->num_rows > 0): ?>
                            <div class="space-y-3 max-h-[120px] overflow-y-auto pr-2">
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
                                        <div class="bg-gray-100 border border-gray-200 rounded-lg p-3 hover:bg-gray-200 transition-all duration-300 ease-in-out cursor-pointer transform hover:-translate-y-1 hover:shadow-md" style="border-bottom: 4px solid <?php echo $borderColor; ?>;">
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
                            <div class="h-[calc(100%-3rem)] flex items-center justify-center">
                                <span class="text-lg text-gray-500">No active tasks</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Faculty -->
                    <div class="w-[300px] bg-white p-[30px] pb-[20px] rounded-lg shadow-md font-overpass h-[210px]">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold">Faculty</h2>
                            <a href="../faculty/faculty_frame.php" class="text-xs text-blue-600 hover:underline">
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
                <!-- Pinboard on the far right -->
                <?php include 'pinboard.php'; ?>
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
    });

    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
    }
    </script>
</body>
</html>
<?php

?>
