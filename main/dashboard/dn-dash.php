<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header('Location: ../index.php');
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

// Dynamic submissions counts
// Count Pending Review
if ($userData['Role'] === 'DN') {
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
        WHERE t.FacultyID = ? AND ta.Status = 'Not Started'";
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
        WHERE t.FacultyID = ? AND ta.Status = 'Not Started' AND pc.PersonnelID = ?";
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
$conn->close();
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
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
        <h1 class="py-[10px] text-[35px] font-overpass font-bold" style="letter-spacing: -0.03em;">Dashboard</h1>

        <div class="relative w-full h-full">
            <div class="flex gap-5 justify-between h-full ">


            <!-- Left Column -->
            <div class="flex gap-5 w-full">
                <!-- Submissions -->
                <div class="flex-1 bg-white p-[30px] font-overpass rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold">Submissions</h2>
                        <div class="text-sm text-blue-600">On-Going Task: 2425 ANDYR COURSE SYLLABUS</div>
                    </div>

                    <div class="flex space-x-4 mb-5">
                        <a href="submissionspage.php?type=pending" class="flex-1">
                            <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="text-2xl font-bold mr-3"><?php echo $pendingCount; ?></div>
                                    <div class="text-sm">Pending Review</div>
                                    <div class="ml-auto">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-300 h-1 mt-2">
                                    <div class="bg-yellow-500 h-1" style="width: 50%"></div>
                                </div>
                            </div>
                        </a>

                        <a href="submissionspage.php?type=unaccomplished" class="flex-1">
                            <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="text-2xl font-bold mr-3"><?php echo $unaccomplishedCount; ?></div>
                                    <div class="text-sm">Unaccomplished</div>
                                    <div class="ml-auto">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-300 h-1 mt-2">
                                    <div class="bg-red-500 h-1" style="width: 30%"></div>
                                </div>
                            </div>
                        </a>

                        <a href="submissionspage.php?type=complete" class="flex-1">
                            <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                                <div class="flex items-center">
                                    <div class="text-2xl font-bold mr-3"><?php echo $completeCount; ?></div>
                                    <div class="text-sm">Complete</div>
                                    <div class="ml-auto">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-300 h-1 mt-2">
                                    <div class="bg-green-500 h-1" style="width: 100%"></div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="flex items-center">
                        <div class="text-xs mr-2 font-medium"><?php echo $progress; ?>%</div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-green-500 h-2" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="ml-2">
                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="w-[300px] bg-white p-[30px] rounded-lg shadow-md font-overpass">
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
                                    <!-- More prominent dot -->
                                    <div class="w-3 h-3 rounded-full mr-2 shrink-0" style="background-color: <?php echo $roleColors[$code]; ?>"></div>
                                    <div class="text-xs flex-1 truncate"><?php echo $label; ?></div>
                                    <div class="text-xs font-semibold"><?php echo $roleCounts[$code] ?? 0; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>


            <!-- Right Column -->
            <div class="w-[300px] flex flex-col gap-5">
                <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md flex-1">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-lg font-bold">Pinboard</h2>
                        <button class="text-xs text-blue-600 hover:underline">Manage</button>
                    </div>
                    <div class="text-sm text-gray-600">
                        No pinned items yet.
                    </div>
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
    });
    </script>
</body>
</html>
