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

// Fetch the faculty name and faculty ID based on the logged-in user
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
    </style>
</head>
<body>

        <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
            <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Tasks</h1> 
            <hr class="border-gray-400">
            <p class="text-gray-500 mt-3 mb-5 font-onest">Here you can view tasks, assign responsibilities, update statuses, and ensure your faculty members stay on track with their deliverables.</p>
            
        
        
        <!-- yung plus bttton -->
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

        <!-- Task Modal -->
        <div id="taskModal" class="hidden fixed inset-0 bg-transparent bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg w-[500px] border border-blue">
                <h2 class="text-2xl font-overpass font-bold mb-4">Create Task</h2>
                <form method="POST" action="">
                    <input type="text" name="title" placeholder="Task Title" required class="w-full mb-3 p-2 border rounded" />
                    <textarea name="description" placeholder="Task Description" class="w-full mb-3 p-2 border rounded"></textarea>
                    <input type="date" name="due_date" class="w-full mb-3 p-2 border rounded" required />

                    <!-- School Year and Term -->
                    <div class="flex gap-2 mb-3">
                        <input type="text" name="school_year" placeholder="School Year (e.g. 2024-2025)" class="w-1/2 p-2 border rounded" />
                        <select name="term" class="w-1/2 p-2 border rounded">
                            <option value="">Select Term</option>
                            <option value="1st">1st</option>
                            <option value="2nd">2nd</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>

                    <!-- Hidden CreatedBy -->
                    <input type="hidden" name="created_by" value="<?php echo $_SESSION['personnel_id']; ?>" />

                    <!-- Assign to multiple faculties and courses -->
                    <label class="block mb-1">Assign to (Faculty + Course):</label>
                    <div class="mb-3 max-h-40 overflow-y-auto border rounded p-2">
                        <?php foreach ($facultyCoursePairs as $pair): ?>
                            <div>
                                <label>
                                    <input type="checkbox" name="assigned[]" value="<?= $pair['FacultyID'] . '|' . $pair['CourseCode'] ?>" />
                                    <?= $pair['FacultyName'] ?> - <?= $pair['CourseCode'] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="closeTaskModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                        <button type="submit" name="create_task" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create</button>
                    </div>
                </form>
            </div>
        </div>



    <script>
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
            const dropdown = document.getElementById('task-dropdown');
            dropdown.classList.remove('show'); 
        }

        function closeTaskModal() {
            document.getElementById('taskModal').classList.add('hidden');
        }

        window.addEventListener('keydown', function (e) {
            if (e.key === "Escape") closeTaskModal();
        });
    </script>
</body>
</html>
