<?php
// curriculum_frame.php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$accountID = $_SESSION['AccountID'];

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userRole = "";
$userRoleQuery = "SELECT Role FROM personnel WHERE AccountID = ?";
$roleStmt = $conn->prepare($userRoleQuery);
$roleStmt->bind_param("i", $accountID);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $userRole = $roleResult->fetch_assoc()['Role'];
}
$roleStmt->close();

$facultyID = null;
$stmt = $conn->prepare("SELECT FacultyID FROM personnel WHERE AccountID = ?");
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $facultyID = $row['FacultyID'];
}
$stmt->close();

if (!$facultyID) {
    echo "<p class='text-red-600'>Faculty not found.</p>";
    $conn->close();
    return;
}

$programs = [];
$curriculaMap = [];

$sqlCurricula = "
    SELECT p.ProgramID, p.ProgramCode, c.id AS CurriculumID, c.name AS CurriculumName
    FROM programs p
    LEFT JOIN curricula c ON p.ProgramID = c.ProgramID
    WHERE c.FacultyID = ?
    ORDER BY p.ProgramCode, c.name
";
$stmt = $conn->prepare($sqlCurricula);
$stmt->bind_param('i', $facultyID);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $programId = $row['ProgramID'];
    $program = $row['ProgramCode'];
    $curriculum = $row['CurriculumName'];
    $curriculumId = $row['CurriculumID'];
    if (!isset($programs[$programId])) {
        $programs[$programId] = [
            'code' => $program,
            'curricula' => []
        ];
    }
    if ($curriculum && !isset($programs[$programId]['curricula'][$curriculum])) {
        $programs[$programId]['curricula'][$curriculum] = [];
        $curriculaMap[$curriculumId] = [
            'programId' => $programId,
            'curriculum' => $curriculum
        ];
    }
}
$stmt->close();

// 2. Fetch all courses for those curricula and nest them
if (!empty($curriculaMap)) {
    $curriculumIds = implode(',', array_map('intval', array_keys($curriculaMap)));
    $sqlCourses = "
        SELECT 
            c.id AS CurriculumID,
            co.CourseCode, co.Title,
            pc.YearID, pc.SemesterID,
            ay.YearName, ay.YearOrder,
            s.SemesterName, s.SemesterOrder,
            pc.PersonnelID, per.FirstName, per.LastName
        FROM program_courses pc
        LEFT JOIN curricula c ON pc.CurriculumID = c.id
        LEFT JOIN courses co ON pc.CourseCode = co.CourseCode
        LEFT JOIN academic_years ay ON pc.YearID = ay.YearID
        LEFT JOIN semesters s ON pc.SemesterID = s.SemesterID
        LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
        WHERE pc.CurriculumID IN ($curriculumIds)
        ORDER BY c.id, ay.YearOrder, s.SemesterOrder, co.CourseCode
    ";
    $res = $conn->query($sqlCourses);
    while ($row = $res->fetch_assoc()) {
        $curriculumId = $row['CurriculumID'];
        $programId = $curriculaMap[$curriculumId]['programId'];
        $curriculum = $curriculaMap[$curriculumId]['curriculum'];
        $courseTitle = $row['Title'];
        $courseCode = $row['CourseCode'];
        $yearName = $row['YearName'];
        $semesterName = $row['SemesterName'];
        $assignedPersonnel = ($row['FirstName'] && $row['LastName']) 
            ? $row['FirstName'] . ' ' . $row['LastName'] 
            : null;
        if ($courseTitle && $yearName) {
            if (!isset($programs[$programId]['curricula'][$curriculum][$yearName])) {
                $programs[$programId]['curricula'][$curriculum][$yearName] = [];
            }
            if (!isset($programs[$programId]['curricula'][$curriculum][$yearName][$semesterName])) {
                $programs[$programId]['curricula'][$curriculum][$yearName][$semesterName] = [];
            }
            $programs[$programId]['curricula'][$curriculum][$yearName][$semesterName][] = [
                'title' => $courseTitle,
                'code' => $courseCode,
                'assigned_to' => $assignedPersonnel
            ];
        }
    }
}

// Helper arrays for year/semester names (for sorting)
$yearNames = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$semesterNames = ['1st Semester', '2nd Semester', 'Summer'];

// Sort courses inside each semester
foreach ($programs as $programId => &$programData) {
    foreach ($programData['curricula'] as $curriculum => &$years) {
        // Sort years by their order
        uksort($years, function($a, $b) use ($yearNames) {
            $yearOrderA = array_search($a, $yearNames) !== false ? array_search($a, $yearNames) : PHP_INT_MAX;
            $yearOrderB = array_search($b, $yearNames) !== false ? array_search($b, $yearNames) : PHP_INT_MAX;
            return $yearOrderA - $yearOrderB;
        });
        
        foreach ($years as $yearName => &$semesters) {
            // Sort semesters by their order
            uksort($semesters, function($a, $b) use ($semesterNames) {
                $semOrderA = array_search($a, $semesterNames) !== false ? array_search($a, $semesterNames) : PHP_INT_MAX;
                $semOrderB = array_search($b, $semesterNames) !== false ? array_search($b, $semesterNames) : PHP_INT_MAX;
                return $semOrderA - $semOrderB;
            });
            
            foreach ($semesters as $semesterName => &$courses) {
                usort($courses, function($a, $b) {
                    return strcmp($a['code'], $b['code']);
                });
            }
        }
    }
}
unset($programData, $years, $semesters, $courses);

$personnelList = [];
$personnelQuery = "SELECT PersonnelID, FirstName, LastName FROM personnel WHERE FacultyID = ?";
$stmt = $conn->prepare($personnelQuery);
$stmt->bind_param("i", $facultyID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $personnelList[] = [
        'id' => $row['PersonnelID'],
        'name' => $row['FirstName'] . ' ' . $row['LastName']
    ];
}
$stmt->close();

$existingPrograms = [];
$programQuery = "
    SELECT DISTINCT p.ProgramID, p.ProgramCode, p.ProgramName
    FROM programs p
    INNER JOIN curricula c ON p.ProgramID = c.ProgramID
    WHERE c.FacultyID = ?
    ORDER BY p.ProgramCode
";

$stmt = $conn->prepare($programQuery);
$stmt->bind_param("i", $facultyID);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $existingPrograms[] = [
        'id' => $row['ProgramID'],
        'code' => $row['ProgramCode'],
        'name' => $row['ProgramName']
    ];
}

$stmt->close();


$currentSchoolYear = '';
$currentTerm = '';
$taskSql = "SELECT SchoolYear, Term FROM tasks WHERE FacultyID = ? ORDER BY CreatedAt DESC LIMIT 1";
$taskStmt = $conn->prepare($taskSql);
$taskStmt->bind_param("i", $facultyID);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();
if ($row = $taskResult->fetch_assoc()) {
    $currentSchoolYear = $row['SchoolYear'];
    $currentTerm = $row['Term'];
}
$taskStmt->close();

// Fetch academic years
$yearOptions = [];
$yearQuery = $conn->query("SELECT YearID, YearName FROM academic_years ORDER BY YearOrder");
while ($row = $yearQuery->fetch_assoc()) {
    $yearOptions[] = $row;
}

// Fetch semesters
$semesterOptions = [];
$semesterQuery = $conn->query("SELECT SemesterID, SemesterName FROM semesters ORDER BY SemesterOrder");
while ($row = $semesterQuery->fetch_assoc()) {
    $semesterOptions[] = $row;
}

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

        /* Modal animations */
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

        /* Add arrow rotation styles */
        .collapse-arrow {
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .collapsed .collapse-arrow {
            transform: rotate(90deg);
        }

        .slide-in {
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        .show .slide-in {
            opacity: 1;
            transform: translateX(0);
        }

        .show .slide-in.delay-150 {
            transition-delay: 0.30s;
        }
        
        .delete-btn {
            padding: 0.25rem 0.5rem;
            background-color: #e53e3e;
            color: white;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        
        .delete-btn:hover {
            background-color: #c53030;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .x-delete-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10%;
            background-color: #f3f4f6;
            color: #dc2626;
            border: 1px solid #e5e7eb;
            font-size: 25px;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-left: 8px;
        }
        
        .x-delete-btn:hover {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .x-delete-btn-sm {
            width: 24px;
            height: 24px;
            font-size: 16px;
            margin-left: 4px;
            background-color: transparent;
            border: none;
            color: #9ca3af;
        }
        
        .x-delete-btn-sm:hover {
            background-color: transparent;
            border: none;
            color: #dc2626;
        }

        /* Personnel dropdown styling similar to faculty_frame.php */
        .assign-personnel-dropdown {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            font-weight: 500;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1em;
            padding-right: 2.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #4b5563;
        }
        
        .assign-personnel-dropdown:hover {
            background-color: #e5e7eb; 
            border-color: #4a84f1;
        }

        .assign-personnel-dropdown:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
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
        .dark .assign-personnel-dropdown {
            background-color: #23232a !important;
            color: #f3f4f6 !important;
            border-color: #374151 !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a1a1aa'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        }
        .dark .assign-personnel-dropdown:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
        }
        .dark .x-delete-btn,
        .dark .x-delete-btn-sm {
            background-color: #23232a !important;
            color: #f87171 !important;
            border-color: #374151 !important;
        }
        .dark .x-delete-btn:hover,
        .dark .x-delete-btn-sm:hover {
            background-color: #7f1d1d !important;
            color: #ef4444 !important;
            border-color: #ef4444 !important;
        }
        .dark .bg-white,
        .dark .bg-gray-100,
        .dark .bg-blue-100,
        .dark .bg-blue-50 {
            background: #23232a !important;
            color: #f3f4f6 !important;
        }
        .dark .hover\:bg-blue-200:hover,
        .dark .hover\:bg-gray-50:hover,
        .dark .hover\:bg-gray-300:hover,
        .dark .hover\:bg-gray-100:hover {
            background: #374151 !important;
        }
        .dark .text-gray-700,
        .dark .text-gray-900,
        .dark .text-blue-800,
        .dark .text-blue-700,
        .dark .text-gray-400,
        .dark .text-gray-500 {
            color: #a1a1aa !important;
        }
        .dark .border-gray-300,
        .dark .border {
            border-color: #374151 !important;
        }
        .dark .shadow-lg, .dark .shadow-2xl {
            box-shadow: 0 4px 24px rgba(0,0,0,0.32) !important;
        }
    </style>
</head>
<body>


<div class="flex-1 flex flex-col px-[50px] pt-[15px] pb-[50px] overflow-y-auto">
    <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Curricula</h1>
    <hr class="border-gray-400">
    <p class="text-gray-500 mt-3 mb-5 font-onest">
        Manage academic programs and curricula. Create programs, add courses, and assign faculty members. Track curriculum changes over time.
    </p>

    <div class="w-[75%] space-y-2 font-onest">
        <?php
        
        function renderProgramTree($programs, $userRole, $facultyID, $currentSchoolYear, $currentTerm) {
            $conn = new mysqli("localhost", "root", "", "cms");
            foreach ($programs as $programId => $programData) {
                $programName = $programData['code'];
                $curricula = $programData['curricula'];
                $progId = 'prog_' . md5($programName);
                
                // Program level - visible by default
                echo "<div class='mt-4'>";
                echo "<div class='flex items-center justify-between'>";
                echo "<button onclick=\"toggleCollapse('$progId')\" class=\"w-full text-left px-4 py-2 bg-blue-100 text-blue-800 rounded font-bold text-lg shadow hover:bg-blue-200 transition-all duration-200\">\n        <span class='collapse-arrow'>▶</span> $programName\n      </button>";
                if ($userRole === 'DN') {
                    echo "<button onclick=\"confirmDelete('$programId', '$programName')\" class=\"x-delete-btn\" title=\"Delete program\">×</button>";
                }
                echo "</div>";
                
                // Curricula level - hidden by default
                echo "<div id=\"$progId\" class='ml-4 mt-2 hidden'>";
                foreach ($curricula as $curriculum => $years) {
                    // Always render the curriculum, even if $years is empty
                    $currId = 'curr_' . md5($programName . $curriculum);
                    echo "<div class='mt-2'>";
                    echo "<div class='flex items-center justify-between'>";
                    echo "<button onclick=\"toggleCollapse('$currId')\" class=\"w-full text-left px-4 py-1 bg-green-100 text-green-900 rounded font-semibold shadow-sm hover:bg-green-200 transition-all duration-200\">\n        <span class='collapse-arrow'>▶</span> $curriculum\n      </button>";
                    if ($userRole === 'DN') {
                        echo "<button onclick=\"confirmDeleteCurriculum('$programId', '$curriculum')\" class=\"x-delete-btn x-delete-btn-sm\" title=\"Delete curriculum\">×</button>";
                    }
                    echo "</div>";
                    echo "<div id=\"$currId\" class='ml-4 mt-1 hidden'>";
                    if (empty($years)) {
                        echo "<div class='text-gray-400 italic px-4 py-2'>No courses yet.</div>";
                    } else {
                        foreach ($years as $yearName => $semesters) {
                            if (empty($semesters)) continue;
                            $yearNodeId = 'year_' . md5($programName . $curriculum . $yearName);
                            echo "<div class='mt-2'>";
                            echo "<button onclick=\"toggleCollapse('$yearNodeId')\" style=\"background:#ffe4b5;color:#7c4700;\" class=\"w-full text-left px-4 py-1 rounded font-semibold shadow-sm transition-all duration-200\">\n        <span class='collapse-arrow'>▶</span> $yearName\n      </button>";
                            echo "<div id=\"$yearNodeId\" class='ml-4 mt-1 hidden'>";
                            foreach ($semesters as $semesterName => $courses) {
                                if (empty($courses)) continue;
                                $semNodeId = 'sem_' . md5($programName . $curriculum . $yearName . $semesterName);
                                echo "<div class='mt-2'>";
                                echo "<button onclick=\"toggleCollapse('$semNodeId')\" style=\"background:#e6e6fa;color:#4b3869;\" class=\"w-full text-left px-4 py-1 rounded font-semibold shadow-sm transition-all duration-200\">\n        <span class='collapse-arrow'>▶</span> $semesterName\n      </button>";
                                echo "<div id=\"$semNodeId\" class='ml-4 mt-1 hidden'>";
                                echo "<div class='overflow-x-auto'>";
                                echo "<table class='min-w-full text-sm text-left text-gray-700 border border-gray-300'>";
                                echo "<thead class='bg-gray-100 text-gray-900'>";
                                echo "<tr>";
                                echo "<th class='text-right px-4 py-2 border-b w-[5%]'>Code</th>";
                                echo "<th class='px-4 py-2 border-b w-[60%]'>Course</th>";
                                echo "<th class='px-4 py-2 border-b text-left w-[35%]'>Assigned Prof.</th>";
                                echo "</tr>";
                                echo "</thead><tbody>";
                                foreach ($courses as $idx => $courseData) {
                                    $courseTitle = $courseData['title'];
                                    $assignedTo = $courseData['assigned_to'] ?? '';
                                    $courseCode = $courseData['code'] ?? $courseTitle;
                                    echo "<tr class='hover:bg-gray-50'>";
                                    echo "<td class='text-right px-4 py-2 border-b w-[15%]'>" . htmlspecialchars($courseCode) . "</td>";
                                    echo "<td class='px-4 py-2 border-b w-[55%]'>" . htmlspecialchars($courseTitle) . "</td>";
                                    echo "<td class='px-4 py-2 border-b w-[30%]'>";
                                    echo "<div class='flex items-center justify-between'>";
                                    echo "<div class='flex-1'>";
                                    if ($userRole === 'DN') {
                                        echo "<select class='w-full assign-personnel-dropdown' 
                                            data-course-code='" . htmlspecialchars($courseTitle) . "' 
                                            data-curriculum='" . htmlspecialchars($curriculum) . "' 
                                            data-program='" . htmlspecialchars($programId) . "'>";
                                        echo "<option value=''>-- Assign Personnel --</option>";
                                        foreach ($GLOBALS['personnelList'] as $person) {
                                            $selected = ($person['name'] === $assignedTo) ? 'selected' : '';
                                            echo "<option value='" . $person['id'] . "' $selected>" . htmlspecialchars($person['name']) . "</option>";
                                        }
                                        echo "</select>";
                                    } else {
                                        echo htmlspecialchars($assignedTo ?: 'Not assigned');
                                    }
                                    echo "</div>";
                                    if ($userRole === 'DN') {
                                        echo "<button onclick=\"confirmDeleteCourse('$programId', '$curriculum', '$courseCode', '$courseTitle')\" 
                                            class='ml-2 p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors duration-200' 
                                            title='Remove course from curriculum'>
                                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' />
                                            </svg>
                                        </button>";
                                    }
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table>";
                                echo "</div></div>";
                                echo "</div>";
                            }
                            echo "</div></div>";
                        }
                    }
                    echo "</div></div>";
                }
                echo "</div></div>";
            }
            $conn->close();
        }

        renderProgramTree($programs, $userRole, $facultyID, $currentSchoolYear, $currentTerm);
        ?>
    </div>

    <!-- Floating Add Button -->
    <?php if ($userRole === 'DN' || $userRole === 'PH' || $userRole === 'COR'): ?>
    <a href="javascript:void(0)" onclick="toggleTaskDropdown()" 
        class="task-button fixed bottom-8 right-10 w-13 h-13 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-50"
        title="Add Task">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 transition-transform duration-500 ease-in-out" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>

    <!-- Dropdown -->
    <div id="task-dropdown" class="font-onest task-dropdown fixed bottom-24 right-10 w-80 space-y-2 z-50 flex flex-col items-end">
        <button onclick="openProgramModal()"
            class="text-xl text-center text-white py-3 px-4 rounded-full bg-[#51D55A] hover:bg-green-800 active:bg-blue-900 transition-all duration-300 slide-in delay-150 whitespace-nowrap">
            Add Program or Curriculum
        </button>
        <button onclick="openCourseModal()"
            class="text-xl text-center text-white py-3 px-4 rounded-full bg-[#51D55A] hover:bg-green-800 active:bg-green-900 transition-all duration-600 slide-in delay-0">
            Add Course
        </button>
    </div>
    <?php endif; ?>

    <div id="courseModal" class="hidden fixed inset-0 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-[600px] max-h-[90vh] border-2 border-gray-400 font-onest modal-animate overflow-hidden flex flex-col">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">Add New Course</h2>
            <hr class="border-gray-400 mb-6">
            <form id="addCourseForm" method="POST" action="../curriculum/add_course.php" class="space-y-4 overflow-y-auto flex-1">
                
                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Program:</label>
                    <select id="course_program" name="program_id" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required onchange="loadCurricula(this.value)">
                        <option value="">-- Select Program --</option>
                        <?php foreach ($existingPrograms as $program): ?>
                            <option value="<?= $program['id'] ?>">
                                <?= $program['code'] ?> - <?= $program['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Curriculum:</label>
                    <select id="course_curriculum" name="curriculum_id" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required>
                        <option value="">-- Select Program First --</option>
                    </select>
                </div>

                <!-- New Course Selection Section -->
                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Year:</label>
                    <select id="course_year" name="year_id" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required>
                        <option value="">-- Select Year --</option>
                        <?php
                        foreach ($yearOptions as $year): ?>
                            <option value="<?= $year['YearID'] ?>"><?= htmlspecialchars($year['YearName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Semester:</label>
                    <select id="course_semester" name="semester_id" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required>
                        <option value="">-- Select Semester --</option>
                        <?php
                        foreach ($semesterOptions as $sem): ?>
                            <option value="<?= $sem['SemesterID'] ?>"><?= htmlspecialchars($sem['SemesterName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Existing Courses or Create New:</label>
                    <div class="relative">
                        <input type="text" id="courseSearch" placeholder="Search existing courses..." 
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500">
                        <div id="existingCoursesList" class="absolute z-10 w-full mt-1 bg-white border-2 border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                            <!-- Courses will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Selected Courses List -->
                <div id="selectedCoursesList" class="space-y-2 max-h-40 overflow-y-auto border-2 border-gray-200 rounded-lg p-2">
                    <!-- Selected courses will be shown here -->
                </div>

                <!-- New Course Creation Fields -->
                <div id="newCourseFields" class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-lg font-semibold text-gray-700">Course Code:</label>
                        <input type="text" id="course_code" name="course_code" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="e.g., COMP101" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-lg font-semibold text-gray-700">Course Title:</label>
                        <input type="text" id="course_title" name="course_title" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="e.g., Introduction to Programming" />
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4 sticky bottom-0 bg-white">
                    <button type="button" onclick="closeCourseModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">Add Courses</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div id="programModal" class="hidden fixed inset-0 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-[600px] border-2 border-gray-400 font-onest modal-animate">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">Create Curriculum</h2>
            <hr class="border-gray-400 mb-6">
            <form id="createProgramForm" method="POST" action="../curriculum/create_program.php" class="space-y-4">
                <input type="hidden" name="is_new_program" id="is_new_program" value="0">
                
                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Select Program:</label>
                    <select id="existing_program" name="existing_program" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" onchange="toggleProgramFields(); updateCurriculumPreview();">
                        <option value="">-- Select Program --</option>
                        <?php foreach ($existingPrograms as $program): ?>
                            <option value="<?= $program['id'] ?>" data-code="<?= $program['code'] ?>" data-name="<?= $program['name'] ?>">
                                <?= $program['code'] ?> - <?= $program['name'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="other">Other (Add New Program)</option>
                    </select>
                </div>

                <div id="new_program_fields" class="hidden space-y-4">
                    <div class="space-y-2">
                        <label class="block text-lg font-semibold text-gray-700">Program Code:</label>
                        <input type="text" id="program_code_input" name="program_code" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="e.g., BSIS" oninput="updateCurriculumPreview()" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-lg font-semibold text-gray-700">Program Name:</label>
                        <input type="text" id="program_name_input" name="program_name" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="e.g., Bachelor of Science in Information Systems" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Curriculum Year:</label>
                    <input type="number" id="curriculum_year_input" name="curriculum_year" value="<?= date('Y') ?>" required class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" oninput="updateCurriculumPreview()" />
                </div>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <p class="text-gray-700">
                        <span class="font-semibold">Generated Curriculum Name:</span>
                        <span id="curriculum_preview" class="text-blue-700 font-bold ml-2">—</span>
                    </p>
                </div>

                <input type="hidden" id="curriculum_name_input" name="curriculum_name" />

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeProgramModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">Create Curriculum</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-[600px] border-2 border-gray-400 font-onest modal-animate">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">Confirm Deletion</h2>
            <hr class="border-gray-400 mb-6">
            <p id="deleteMessage" class="text-lg text-gray-700 mb-6">Are you sure you want to delete this program?</p>
            <div class="flex justify-end gap-4 pt-4">
                <button onclick="closeDeleteModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                <button id="confirmDeleteBtn" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 font-semibold">Delete</button>
            </div>
        </div>
    </div>

    <!-- Delete Curriculum Confirmation Modal -->
    <div id="deleteCurriculumModal" class="modal">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-[600px] border-2 border-gray-400 font-onest modal-animate">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">Confirm Deletion</h2>
            <hr class="border-gray-400 mb-6">
            <p id="deleteCurriculumMessage" class="text-lg text-gray-700 mb-6">Are you sure you want to delete this curriculum?</p>
            <div class="flex justify-end gap-4 pt-4">
                <button onclick="closeDeleteCurriculumModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                <button id="confirmDeleteCurriculumBtn" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 font-semibold">Delete</button>
            </div>
        </div>
    </div>

</div>

<div id="filePreviewModal" class="hidden fixed inset-0 bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 pr-12 rounded-lg shadow-lg w-[90vw] max-w-[1200px] max-h-[95vh] flex flex-col relative">
    <button onclick="closeFilePreviewModal()" class="absolute top-4 right-4 text-gray-700 hover:text-red-600 text-4xl font-bold z-50" title="Close">&times;</button>
    <div class="flex justify-center items-center mb-4" style="position:relative;">
      <h2 class="text-2xl font-bold w-full text-center font-overpass">File Preview</h2>
    </div>
    <div class="flex-1 overflow-hidden" id="filePreviewContent"></div>
  </div>
</div>

<script>
   
    const userRole = "<?php echo $userRole; ?>";
    let programToDelete = null;
    let curriculumToDelete = null;
    let curriculumProgramId = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.assign-personnel-dropdown').forEach(dropdown => {
           
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            dropdown.addEventListener('change', function(e) {
                e.stopPropagation();
                const personnelId = this.value;
                const courseTitle = this.dataset.courseCode;
                const curriculumName = this.dataset.curriculum;
                const programId = this.dataset.program;

                if (!personnelId) return;

                fetch('assign_personnel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        personnel_id: personnelId,
                        course_title: courseTitle,
                        curriculum: curriculumName,
                        program_id: programId
                    })
                })
                .then(async res => {
                    const text = await res.text();
                    console.log("Raw response from assign_personnel.php:", text);
                    try {
                        const json = JSON.parse(text);
                        console.log("Parsed JSON:", json);
                    } catch (e) {
                        console.error("Error parsing JSON:", e);
                        alert("Raw error: " + text); 
                    }
                })
                .catch(err => {
                    console.error("Network error:", err);
                    alert("Network error");
                });
            });
        });
    });


    function confirmDelete(programId, programName) {
        programToDelete = programId;
        document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the program "${programName}"? This will delete all associated curricula and courses.`;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (programToDelete) {
            deleteProgram(programToDelete);
        }
    });

    function deleteProgram(programId) {
  
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Deleting...';
        confirmBtn.disabled = true;
        
    
        closeDeleteModal();

        const formData = new FormData();
        formData.append('program_id', programId);
        formData.append('ajax', 'true');

        
        fetch('../curriculum/remove_program.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success === true) {
        
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Program deleted successfully',
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    // Reload the page after the user clicks OK
                    location.reload();
                });
            } else {
                // Show error message with SweetAlert2
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to delete program',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
                // Reset button
                confirmBtn.textContent = originalText;
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while deleting the program',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        
            confirmBtn.textContent = originalText;
            confirmBtn.disabled = false;
        });
    }

    function toggleProgramFields() {
        const dropdown = document.getElementById("existing_program");
        const otherFields = document.getElementById("new_program_fields");
        const isNewProgramInput = document.getElementById("is_new_program");
        const selectedOption = dropdown.options[dropdown.selectedIndex];

        if (dropdown.value === "other") {
            otherFields.classList.remove("hidden");
            document.getElementById("program_code_input").required = true;
            document.getElementById("program_name_input").required = true;
            isNewProgramInput.value = "1";
        } else {
            otherFields.classList.add("hidden");
            document.getElementById("program_code_input").required = false;
            document.getElementById("program_name_input").required = false;
            isNewProgramInput.value = "0";
        }

        updateCurriculumPreview();
    }

    function updateCurriculumPreview() {
        const dropdown = document.getElementById("existing_program");
        const codeInput = document.getElementById("program_code_input");
        const yearInput = document.getElementById("curriculum_year_input");
        const preview = document.getElementById("curriculum_preview");
        const hiddenInput = document.getElementById("curriculum_name_input");
        const selectedOption = dropdown.options[dropdown.selectedIndex];

        let code = "";
        let name = "";

        if (dropdown.value === "other") {
            code = codeInput.value.trim();
            name = document.getElementById("program_name_input").value.trim();
        } else if (dropdown.value !== "") {
            code = selectedOption.dataset.code;
            name = selectedOption.dataset.name;
        }

        const year = yearInput.value.trim();

        if (code && year) {
            const generated = `${code} Curriculum ${year}`;
            preview.textContent = generated;
            hiddenInput.value = generated;
        } else {
            preview.textContent = "—";
            hiddenInput.value = "";
        }
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
        if (dropdown && button && !dropdown.contains(event.target) && !button.contains(event.target)) {
            dropdown.classList.remove('show');
            button.classList.remove('open');
        }
    });

    function openProgramModal() {
        document.getElementById('programModal').classList.remove('hidden');
        document.getElementById('task-dropdown').classList.remove('show');
    }

    function closeProgramModal() {
        document.getElementById('programModal').classList.add('hidden');
    }

    function openCourseModal() {
        document.getElementById('courseModal').classList.remove('hidden');
        document.getElementById('task-dropdown').classList.remove('show');
        
        document.getElementById('course_program').value = '';
        document.getElementById('course_curriculum').innerHTML = '<option value="">-- Select Program First --</option>';
        document.getElementById('course_code').value = '';
        document.getElementById('course_title').value = '';
        document.getElementById('courseSearch').value = '';
        document.getElementById('existingCoursesList').classList.add('hidden');

        loadExistingCourses();
    }

    window.addEventListener('keydown', function (e) {
        if (e.key === "Escape") {
            closeTaskModal();
            closeProgramModal();
            closeDeleteModal();
        }
    });


    function closeCourseModal() {
        document.getElementById('courseModal').classList.add('hidden');
    }

    function loadCurricula(programId) {
        if (!programId) {
            document.getElementById('course_curriculum').innerHTML = '<option value="">-- Select Program First --</option>';
            return;
        }
        
        document.getElementById('course_curriculum').innerHTML = '<option value="">Loading curricula...</option>';
        
        fetch(`../curriculum/get_curricula.php?program_id=${programId}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('course_curriculum');
                
                if (data.success) {
                    if (data.curricula.length > 0) {
                        select.innerHTML = '<option value="">-- Select Curriculum --</option>';
                        data.curricula.forEach(curriculum => {
                            select.innerHTML += `<option value="${curriculum.id}">${curriculum.name}</option>`;
                        });
                    } else {
                        select.innerHTML = '<option value="">No curricula available</option>';
                    }
                } else {
                    select.innerHTML = '<option value="">Error loading curricula</option>';
                    console.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('course_curriculum').innerHTML = '<option value="">Error loading curricula</option>';
            });
    }

    function closeTaskModal() {
       
    }

    function toggleCollapse(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.toggle('hidden');
          
            const button = document.querySelector(`button[onclick="toggleCollapse('${id}')"]`);
            if (button) {
                button.classList.toggle('collapsed');
            }
        }
    }

    function openFilePreviewModal(fileUrl) {
        const modal = document.getElementById('filePreviewModal');
        const content = document.getElementById('filePreviewContent');
        // Determine file type
        const ext = fileUrl.split('.').pop().toLowerCase();
        if (["pdf"].includes(ext)) {
            content.innerHTML = `<embed src="${fileUrl}" type="application/pdf" style="width:100%;height:85vh;">`;
        } else if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
            content.innerHTML = `<img src="${fileUrl}" style="max-width:100%;max-height:85vh;display:block;margin:auto;">`;
        } else {
            content.innerHTML = `<div class='text-center text-gray-500'>Preview not available for this file type.</div>`;
        }
        modal.classList.remove('hidden');
    }

    function closeFilePreviewModal() {
        document.getElementById('filePreviewModal').classList.add('hidden');
        document.getElementById('filePreviewContent').innerHTML = '';
    }

    document.getElementById('filePreviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeFilePreviewModal();
        }
    });

    window.addEventListener('keydown', function(e) {
        if (e.key === "Escape") closeFilePreviewModal();
    });

    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark');
    }

    function confirmDeleteCurriculum(programId, curriculumYear) {
        curriculumToDelete = curriculumYear;
        curriculumProgramId = programId;
        document.getElementById('deleteCurriculumMessage').textContent = `Are you sure you want to delete the curriculum "${curriculumYear}"? This will delete all associated courses.`;
        document.getElementById('deleteCurriculumModal').style.display = 'flex';
    }

    function closeDeleteCurriculumModal() {
        document.getElementById('deleteCurriculumModal').style.display = 'none';
    }

    document.getElementById('confirmDeleteCurriculumBtn').addEventListener('click', function() {
        if (curriculumToDelete && curriculumProgramId) {
            deleteCurriculum(curriculumProgramId, curriculumToDelete);
        }
    });

    function deleteCurriculum(programId, curriculumYear) {
      
        const confirmBtn = document.getElementById('confirmDeleteCurriculumBtn');
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Deleting...';
        confirmBtn.disabled = true;
        
        closeDeleteCurriculumModal();
        
        const formData = new FormData();
        formData.append('program_id', programId);
        formData.append('curriculum_year', curriculumYear);
        formData.append('ajax', 'true');
        
        console.log('Deleting curriculum:', { programId, curriculumYear }); 
        
        fetch('../curriculum/remove_curriculum.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status); 
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); 
            if (data.success === true) {
               
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Curriculum deleted successfully',
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                 
                    location.reload();
                });
            } else {
            
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to delete curriculum',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
                
                confirmBtn.textContent = originalText;
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
           
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while deleting the curriculum. Please check the console for details.',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
          
            confirmBtn.textContent = originalText;
            confirmBtn.disabled = false;
        });
    }

    //corusee searchingg
    document.addEventListener('DOMContentLoaded', function() {
        const searchInputs = document.querySelectorAll('.search-course-input');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const curriculumId = this.dataset.curriculumId;
                const curriculumSection = document.getElementById(curriculumId);
                const courseRows = curriculumSection.querySelectorAll('tbody tr:not([id^="files_"])');
                
                courseRows.forEach(row => {
                    const courseCode = row.querySelector('td:first-child').textContent.toLowerCase();
                    const courseTitle = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    
                    if (courseCode.includes(searchTerm) || courseTitle.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });

    function confirmDeleteCourse(programId, curriculumYear, courseCode, courseTitle) {
        event.stopPropagation(); 
        Swal.fire({
            title: 'Remove Course?',
            text: `Are you sure you want to remove "${courseCode} - ${courseTitle}" from the curriculum?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteCourse(programId, curriculumYear, courseCode);
            }
        });
    }

    function deleteCourse(programId, curriculumYear, courseCode) {
        const formData = new FormData();
        formData.append('program_id', programId);
        formData.append('curriculum_year', curriculumYear);
        formData.append('course_code', courseCode);
        formData.append('ajax', 'true');

        fetch('../curriculum/remove_course.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Removed!',
                    text: 'Course has been removed from the curriculum.',
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to remove course',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while removing the course',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        });
    }

    
    function loadExistingCourses() {
        const searchInput = document.getElementById('courseSearch');
        const coursesList = document.getElementById('existingCoursesList');
        const selectedCoursesList = document.getElementById('selectedCoursesList');
        let selectedCourses = new Set();
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            if (searchTerm.length < 2) {
                coursesList.classList.add('hidden');
                return;
            }
            
         
            fetch(`../curriculum/search_courses.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.courses.length > 0) {
                        coursesList.innerHTML = '';
                        data.courses.forEach(course => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200 last:border-b-0';
                            div.innerHTML = `
                                <div class="flex items-center">
                                    <input type="checkbox" id="course_${course.CourseCode}" 
                                        class="course-checkbox mr-2" 
                                        data-code="${course.CourseCode}"
                                        data-title="${course.Title}">
                                    <label for="course_${course.CourseCode}" class="flex-1">
                                        <div class="font-semibold">${course.CourseCode}</div>
                                        <div class="text-sm text-gray-600">${course.Title}</div>
                                    </label>
                                </div>
                            `;
                            coursesList.appendChild(div);
                        });
                        coursesList.classList.remove('hidden');
                    } else {
                        coursesList.innerHTML = '<div class="p-3 text-gray-500">No courses found</div>';
                        coursesList.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    coursesList.innerHTML = '<div class="p-3 text-red-500">Error loading courses</div>';
                    coursesList.classList.remove('hidden');
                });
        });
        
       
        coursesList.addEventListener('change', function(e) {
            if (e.target.classList.contains('course-checkbox')) {
                const courseCode = e.target.dataset.code;
                const courseTitle = e.target.dataset.title;
                
                if (e.target.checked) {
                    selectedCourses.add(JSON.stringify({ code: courseCode, title: courseTitle }));
                } else {
                    selectedCourses.delete(JSON.stringify({ code: courseCode, title: courseTitle }));
                }
                
                updateSelectedCoursesList();
            }
        });
        
        function updateSelectedCoursesList() {
            selectedCoursesList.innerHTML = '';
            selectedCourses.forEach(courseStr => {
                const course = JSON.parse(courseStr);
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-2 bg-gray-50 rounded mb-2';
                div.innerHTML = `
                    <div>
                        <div class="font-semibold">${course.code}</div>
                        <div class="text-sm text-gray-600">${course.title}</div>
                    </div>
                    <button type="button" onclick="removeSelectedCourse('${course.code}')" 
                        class="text-red-500 hover:text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                `;
                selectedCoursesList.appendChild(div);
            });
        }
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !coursesList.contains(e.target)) {
                coursesList.classList.add('hidden');
            }
        });
    }

  
    function removeSelectedCourse(courseCode) {
        const checkbox = document.querySelector(`input[data-code="${courseCode}"]`);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change'));
        }
    }

    document.getElementById('addCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // Collect selected courses from the UI
        const selectedCourses = Array.from(document.querySelectorAll('#selectedCoursesList > div')).map(div => ({
            code: div.querySelector('.font-semibold').textContent,
            title: div.querySelector('.text-sm').textContent
        }));
        // Check if new course fields are filled
        const newCourseCode = document.getElementById('course_code').value.trim();
        const newCourseTitle = document.getElementById('course_title').value.trim();
        if (newCourseCode && newCourseTitle) {
            // Only add if not already in selectedCourses
            if (!selectedCourses.some(c => c.code === newCourseCode)) {
                selectedCourses.push({ code: newCourseCode, title: newCourseTitle });
            }
        }
        formData.append('selected_courses', JSON.stringify(selectedCourses));
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    closeCourseModal();
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while adding the courses',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        });
    });
</script>

<?php if (isset($_SESSION['success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo $_SESSION['success']; ?>',
        showConfirmButton: false,
        timer: 1500
    });
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '<?php echo $_SESSION['error']; ?>',
        showConfirmButton: false,
        timer: 1500
    });
</script>
<?php unset($_SESSION['error']); endif; ?>
</body>
</html>