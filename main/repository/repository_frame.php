<?php
session_start();

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
    </style>
</head>
<body>

<div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
    <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Curricula</h1>
    <hr class="border-gray-400">
    <p class="text-gray-500 mt-3 mb-5 font-onest">
        Here you can view tasks, assign responsibilities, update statuses, and ensure your faculty members stay on track with their deliverables.
    </p>

    <div class="w-[70%] space-y-2 font-onest">
        <?php
        $conn = new mysqli("localhost", "root", "", "cms");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $programs = [];

        $sql = "
            SELECT 
                p.ProgramID, p.ProgramCode,
                c.id, c.name,
                co.CourseCode, co.Title
            FROM programs p
            LEFT JOIN curricula c ON p.ProgramID = c.ProgramID
            LEFT JOIN program_courses pc ON c.id = pc.CurriculumID
            LEFT JOIN courses co ON pc.CourseCode = co.CourseCode
            ORDER BY p.ProgramCode, c.name, co.Title
        ";

        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $program = $row['ProgramCode'];
            $year = $row['name'];
            $course = $row['Title'];

            if (!isset($programs[$program])) {
                $programs[$program] = [];
            }

            if ($year && !isset($programs[$program][$year])) {
                $programs[$program][$year] = [];
            }

            if ($course) {
                $programs[$program][$year][] = $course;
            }
        }

        $conn->close();

        function renderProgramTree($programs) {
            foreach ($programs as $programName => $curricula) {
                $progId = 'prog_' . md5($programName);
                echo "<div class='mt-4'>";
                echo "<button onclick=\"toggleCollapse('$progId')\" class=\"w-full text-left px-4 py-2 bg-blue-100 text-blue-800 rounded font-bold text-lg shadow hover:bg-blue-200 transition-all duration-200\">▶ $programName</button>";
                echo "<div id=\"$progId\" class='ml-4 mt-2 hidden'>";
        
                foreach ($curricula as $year => $courses) {
                    $yearId = 'year_' . md5($programName . $year);
                    echo "<div class='mt-2'>";
                    echo "<button onclick=\"toggleCollapse('$yearId')\" class=\"w-full text-left px-4 py-1 bg-blue-50 text-blue-700 rounded font-semibold shadow-sm hover:bg-blue-100 transition-all duration-200\">▶ $year</button>";
                    echo "<div id=\"$yearId\" class='ml-4 mt-1 hidden'>";
                    echo "<ul class='list-disc pl-5 text-sm text-gray-700 space-y-1'>";
                    foreach ($courses as $course) {
                        echo "<li class='flex items-center gap-2'><span>📚</span><span>" . htmlspecialchars($course) . "</span></li>";
                    }
                    echo "</ul></div></div>";
                }
        
                echo "</div></div>";
            }
        }
        

        renderProgramTree($programs);

        ?>
    </div>

    <!-- Floating Add Button -->
    <a href="javascript:void(0)" onclick="toggleTaskDropdown()" 
        class="task-button fixed bottom-8 right-10 w-13 h-13 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-50"
        title="Add Task">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 transition-transform duration-500 ease-in-out" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>

    <!-- Dropdown -->
    <div id="task-dropdown" class="font-onest task-dropdown fixed bottom-24 right-10 w-45 bg-[#51D55A] shadow-lg rounded-full hover:bg-green-800 transition-all duration-300">
        <button onclick="openTaskModal()" 
            class="w-full text-xl text-center text-white py-3 px-4 active:bg-green-900 transition-colors duration-150"> 
            Add Curriculum
        </button>
    </div>

    <!-- Modal -->
    <div id="taskModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-[500px] border border-blue-500">
            <h2 class="text-2xl font-overpass font-bold mb-4">Create Curriculum</h2>
            <form method="POST" action="create_curriculum.php">
                <label class="block mb-1 font-semibold">Program (e.g., BSIT):</label>
                <input type="text" name="program" required class="w-full mb-3 p-2 border rounded" />

                <label class="block mb-1 font-semibold">Curriculum Year (e.g., 2022):</label>
                <input type="number" name="curriculum_year" required class="w-full mb-3 p-2 border rounded" />

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="closeTaskModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create</button>
                </div>
            </form>
        </div>
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
        document.getElementById('task-dropdown').classList.remove('show');
    }

    function closeTaskModal() {
        document.getElementById('taskModal').classList.add('hidden');
    }

    window.addEventListener('keydown', function (e) {
        if (e.key === "Escape") closeTaskModal();
    });

    function toggleCollapse(id) {
        const el = document.getElementById(id);
        el.classList.toggle("hidden");
        const btn = el.previousElementSibling;
        if (btn && btn.textContent.trim().startsWith("▶")) {
            btn.textContent = btn.textContent.replace("▶", "▼");
        } else if (btn && btn.textContent.trim().startsWith("▼")) {
            btn.textContent = btn.textContent.replace("▼", "▶");
        }
    }

    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
    }
</script>
</body>
</html>
