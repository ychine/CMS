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

$sql = "SELECT personnel.FacultyID, faculties.Faculty 
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

    $memberQuery = "SELECT FirstName, LastName, Role FROM personnel WHERE FacultyID = ?";
    $memberStmt = $conn->prepare($memberQuery);
    $memberStmt->bind_param("i", $facultyID);
    $memberStmt->execute();
    $memberResult = $memberStmt->get_result();

    while ($memberRow = $memberResult->fetch_assoc()) {
        $members[] = $memberRow;
    }

    $memberStmt->close();
} else {
  
    $facultyName = "No Faculty Assigned";
}

$stmt->close();
$conn->close();
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
    </style>
</head>
<body>

<div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
    <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Tasks</h1> 
    <hr class="border-gray-400">
    <p class="text-gray-500 mt-3 mb-5 font-onest">Here you can view tasks, assign responsibilities, update statuses, and ensure your faculty members stay on track with their deliverables.</p>
    
    <div class="grid grid-cols-1 grid-rows-3 gap-5 w-[60%]">
        <?php if (!empty($members)): ?>
            <?php foreach ($members as $member): ?>
                <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold">
                            <?php 
                                echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']); 
                            ?>
                        </h2>
                        <div class="text-sm text-blue-600">
                            <?php echo htmlspecialchars($member['Role']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500">No members found in your faculty.</p>
        <?php endif; ?>
    </div>
</div>

    
        <a href="" 
        class="fixed bottom-8 right-10 w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-50"
        title="Add Task">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>

</body>
</html>
