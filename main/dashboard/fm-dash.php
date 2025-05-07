<?php

session_start();

if (!isset($_SESSION['Username'])) {
    // Redirect to login if not logged in
    header('Location: ../index.php');
    exit;
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the account ID from the session
$accountID = $_SESSION['AccountID'];

// Get the personnel information and faculty for the logged-in user
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
    // Handle error - user not found or not associated with any faculty
    $personnelId = 0;
    $facultyId = 0;
    $facultyName = "Unknown Faculty";
}

// Get counts of faculty members grouped by role
$roleCounts = [];
$roleLabels = [
    'DN' => 'Dean',
    'COR' => 'Coordinator',
    'PH' => 'Program Head',
    'FM' => 'Faculty Member'
];

$roleQuery = "SELECT Role, COUNT(*) as count 
              FROM personnel 
              WHERE FacultyID = ? 
              GROUP BY Role";

$roleStmt = $conn->prepare($roleQuery);
$roleStmt->bind_param("i", $facultyId);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();

$roleCounts = [
    'DN' => 0,
    'COR' => 0,
    'PH' => 0,
    'FM' => 0
];

while ($row = $roleResult->fetch_assoc()) {
    $roleCounts[$row['Role']] = $row['count'];
}

// Get total faculty count
$totalQuery = "SELECT COUNT(*) as total FROM personnel WHERE FacultyID = ?";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param("i", $facultyId);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalFaculty = $totalRow['total'];

// Define colors for each role for the visual presentation
$roleColors = [
    'DN' => '#4F46E5',  // Blue
    'COR' => '#10B981', // Green
    'PH' => '#F59E0B',  // Amber
    'FM' => '#EF4444'   // Red
];

// Format the data for JavaScript
$formattedRoleData = [];
foreach ($roleLabels as $code => $label) {
    $formattedRoleData[] = [
        'name' => $label,
        'value' => $roleCounts[$code] ?? 0,
        'color' => $roleColors[$code]
    ];
}
$roleDataJSON = json_encode($formattedRoleData);

// Close the database connections
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

        <div class="grid grid-cols-2 gap-5">
        <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">Submissions</h2>
    <div class="text-sm text-blue-600">On-Going Task: 2425 ANDYR COURSE SYLLABUS</div>
  </div>
  
  
  <div class="flex space-x-4 mb-5">
   
    <a href="submissionspage.php?type=pending" class="flex-1">
      <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
        <div class="flex items-center">
          <div class="text-2xl font-bold mr-3">7</div>
          <div class="text-sm">Pending Review</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
          <div class="text-2xl font-bold mr-3">3</div>
          <div class="text-sm">Unaccomplished</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
          <div class="text-2xl font-bold mr-3">10</div>
          <div class="text-sm">Complete</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
    <div class="text-xs mr-2 font-medium">50%</div>
    <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
      <div class="bg-green-500 h-2" style="width: 50%"></div>
    </div>
    <div class="ml-2">
      <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>
    </div>
  </div>
</div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-bold font-overpass">Faculty</h2>
              <a href="../faculty/faculty_frame.php" class="text-sm text-blue-600 hover:underline">
                  Total: <?php echo $totalFaculty; ?> members
              </a>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="faculty-chart-container" style="position: relative; height: 200px;">
                <canvas id="facultyDonutChart"></canvas>
              </div>
              
              <div class="grid grid-cols-2 gap-2">
                <?php foreach($roleLabels as $code => $label): ?>
                <div class="bg-gray-100 p-3 rounded-lg">
                  <div class="flex items-center mb-1">
                    <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo $roleColors[$code]; ?>"></div>
                    <div class="text-sm font-medium"><?php echo $label; ?></div>
                  </div>
                  <div class="text-2xl font-bold"><?php echo $roleCounts[$code] ?? 0; ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">Pending Reviews</div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">Pinboard</div>
        </div>
      </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Parse the PHP data
      const roleData = <?php echo $roleDataJSON; ?>;
      
      // Setup the chart colors and labels
      const colors = roleData.map(item => item.color);
      const labels = roleData.map(item => item.name);
      const values = roleData.map(item => item.value);
      
      // Create the chart
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
            legend: {
              display: false
            },
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