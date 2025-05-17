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

// Fetch user role
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

$sql = "
    SELECT 
        p.ProgramID, p.ProgramCode,
        c.id AS CurriculumID, c.name AS CurriculumName,
        co.CourseCode, co.Title,
        pc.PersonnelID,
        per.FirstName, per.LastName
    FROM programs p
    LEFT JOIN curricula c ON p.ProgramID = c.ProgramID
    LEFT JOIN program_courses pc ON c.id = pc.CurriculumID
    LEFT JOIN courses co ON pc.CourseCode = co.CourseCode
    LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
    WHERE c.FacultyID = ?
    ORDER BY p.ProgramCode, c.name, co.CourseCode, co.Title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $facultyID); 
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $programId = $row['ProgramID'];
    $program = $row['ProgramCode'];
    $curriculum = $row['CurriculumName'];
    $courseTitle = $row['Title'];

    // Combine personnel name
    $assignedPersonnel = ($row['FirstName'] && $row['LastName']) 
        ? $row['FirstName'] . ' ' . $row['LastName'] 
        : null;

    // Init program entry if not set
    if (!isset($programs[$programId])) {
        $programs[$programId] = [
            'code' => $program,
            'curricula' => []
        ];
    }

    // Init curriculum entry if not set
    if ($curriculum && !isset($programs[$programId]['curricula'][$curriculum])) {
        $programs[$programId]['curricula'][$curriculum] = [];
    }

    // Add course with assigned personnel and course code
    if ($courseTitle) {
        $programs[$programId]['curricula'][$curriculum][] = [
            'title' => $courseTitle,
            'code' => $row['CourseCode'],
            'assigned_to' => $assignedPersonnel
        ];
    }
}

// Sort courses within each curriculum by course code
foreach ($programs as $programId => $programData) {
    foreach ($programData['curricula'] as $curriculum => $courses) {
        usort($programs[$programId]['curricula'][$curriculum], function($a, $b) {
            return strcmp($a['code'], $b['code']);
        });
    }
}

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

// 1. Get current school year and term from the latest task in this faculty
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
                echo "<div class='mt-4'>";
                echo "<div class='flex items-center justify-between'>";
                echo "<button onclick=\"toggleCollapse('$progId')\" class=\"w-full text-left px-4 py-2 bg-blue-100 text-blue-800 rounded font-bold text-lg shadow hover:bg-blue-200 transition-all duration-200\"><span class='collapse-arrow'>▶</span> $programName</button>";
                
                
                if ($userRole === 'DN') {
                    echo "<button onclick=\"confirmDelete('$programId', '$programName')\" class=\"x-delete-btn\" title=\"Delete program\">×</button>";
                }
                
                echo "</div>";
                
                echo "<div id=\"$progId\" class='ml-4 mt-2 hidden'>";
                
                foreach ($curricula as $year => $courses) {
                    $yearId = 'year_' . md5($programName . $year);
                    echo "<div class='mt-2'>";
                    echo "<div class='flex items-center justify-between'>";
                    echo "<button onclick=\"toggleCollapse('$yearId')\" class=\"w-full text-left px-4 py-1 bg-blue-50 text-blue-700 rounded font-semibold shadow-sm hover:bg-blue-100 transition-all duration-200\"><span class='collapse-arrow'>▶</span> $year</button>";
                    if ($userRole === 'DN') {
                        echo "<button onclick=\"confirmDeleteCurriculum('$programId', '$year')\" class=\"x-delete-btn x-delete-btn-sm\" title=\"Delete curriculum\">×</button>";
                    }
                    echo "</div>";
                    echo "<div id=\"$yearId\" class='ml-4 mt-1 hidden'>";
                    
                    echo "<div class='overflow-x-auto'>";
                    echo "<table class='min-w-full text-sm text-left text-gray-700 border border-gray-300'>";
                    echo "<thead class='bg-gray-100 text-gray-900'>";
                    echo "<tr>";
                    echo "<th class='text-right px-4 py-2 border-b w-[5%]'> Code</th>";
                    echo "<th class='px-4 py-2 border-b w-[60%]'>";
                    echo "<div class='flex items-center justify-between'>";
                    echo "<span>📚 Course</span>";
                    echo "<input type='text' 
                        class='w-48 p-2 min-h-[32px] text-sm border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all duration-200 text-gray-500 search-course-input' 
                        placeholder='Search courses...' 
                        data-curriculum-id='$yearId'>";
                    echo "</div>";
                    echo "</th>";
                    echo "<th class='px-4 py-2 border-b text-left w-[35%]'>Assigned Prof.</th>";
                    echo "</tr>";
                    echo "</thead><tbody>";
        
                    foreach ($courses as $idx => $courseData) {
                        $courseTitle = $courseData['title'];
                        $assignedTo = $courseData['assigned_to'] ?? '';
                        $courseCode = $courseData['code'] ?? $courseTitle;
                        $rowId = 'files_' . md5($programName . $year . $courseTitle . $idx);
                        echo "<tr class='hover:bg-gray-50 cursor-pointer' onclick=\"toggleCollapse('$rowId')\">";
                        echo "<td class='text-right px-4 py-2 border-b w-[15%]'>" . htmlspecialchars($courseCode) . "</td>";
                        echo "<td class='px-4 py-2 border-b w-[55%]'>" . htmlspecialchars($courseTitle) . "</td>";
                    
                        echo "<td class='px-4 py-2 border-b w-[30%]'>";
                        
                        
                        if ($userRole === 'DN') {
                            echo "<select class='w-full assign-personnel-dropdown' 
                                data-course-code='" . htmlspecialchars($courseTitle) . "' 
                                data-curriculum='" . htmlspecialchars($year) . "' 
                                data-program='" . htmlspecialchars($programId) . "'>";
                            echo "<option value=''>-- Assign Personnel --</option>";
                        
                            // Sort personnel list alphabetically by name
                            usort($GLOBALS['personnelList'], function($a, $b) {
                                return strcasecmp($a['name'], $b['name']);
                            });
                            
                            foreach ($GLOBALS['personnelList'] as $person) {
                                $selected = ($person['name'] === $assignedTo) ? 'selected' : '';
                                echo "<option value='" . $person['id'] . "' $selected>" . htmlspecialchars($person['name']) . "</option>";
                            }
                        
                            echo "</select>";
                        } else {
                            
                            echo htmlspecialchars($assignedTo ?: 'Not assigned');
                        }
                        
                        echo "</td>";
                        // Collapsible row for approved files
                        echo "<tr id='$rowId' class='hidden'>";
                        echo "<td colspan='3' class='bg-gray-50 px-6 py-3 border-b'>";
                        // Fetch all submissions for this course, current year/term
                        $fileSql = "SELECT s.SubmissionPath, s.SubmissionDate, per.FirstName, per.LastName
                                    FROM submissions s
                                    LEFT JOIN personnel per ON s.SubmittedBy = per.PersonnelID
                                    JOIN task_assignments ta ON s.TaskID = ta.TaskID AND s.CourseCode = ta.CourseCode AND s.ProgramID = ta.ProgramID
                                    WHERE s.FacultyID = ?
                                    AND s.SubmissionPath IS NOT NULL
                                    AND s.SubmissionPath != ''
                                    AND s.CourseCode = ?
                                    AND ta.ReviewStatus = 'Approved'
                                    ORDER BY s.SubmissionDate DESC";
                        $fileStmt = $conn->prepare($fileSql);
                        $fileStmt->bind_param("is", $facultyID, $courseCode);
                        $fileStmt->execute();
                        $fileResult = $fileStmt->get_result();
                        if ($fileResult->num_rows > 0) {
                            echo "<ul class='list-disc pl-4'>";
                            while ($fileRow = $fileResult->fetch_assoc()) {
                                $filePath = $fileRow['SubmissionPath'];
                                $submitter = trim($fileRow['FirstName'] . ' ' . $fileRow['LastName']);
                                $date = $fileRow['SubmissionDate'] ? date('M j, Y g:i A', strtotime($fileRow['SubmissionDate'])) : '';
                                $fileName = basename($filePath);
                                $fileUrl = '../../' . htmlspecialchars($filePath);
                                echo "<li class='mb-1'>";
                                echo "<a href='javascript:void(0);' onclick=\"openFilePreviewModal('$fileUrl')\" class='text-blue-600 hover:underline'>Preview</a> | ";
                                echo "<a href='$fileUrl' download class='text-green-600 hover:underline'>Download</a> ";
                                echo "<span class='text-gray-700 ml-2'>($fileName)</span>";
                                if ($submitter) echo "<span class='text-gray-500 ml-2'>by $submitter</span>";
                                if ($date) echo "<span class='text-gray-400 ml-2'>$date</span>";
                                echo "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<div class=' text-gray-400 italic'>No approved files</div>";
                        }
                        $fileStmt->close();
                        echo "</td></tr>";
                    }
                    
        
                    echo "</tbody></table></div>"; 
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
        <div class="bg-white p-8 rounded-xl shadow-2xl w-[600px] border-2 border-gray-400 font-onest modal-animate">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800">Add New Course</h2>
            <hr class="border-gray-400 mb-6">
            <form id="addCourseForm" method="POST" action="../curriculum/add_course.php" class="space-y-4">
                
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

                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Course Code:</label>
                    <input type="text" id="course_code" name="course_code" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required placeholder="e.g., COMP101" />
                </div>

                <div class="space-y-2">
                    <label class="block text-lg font-semibold text-gray-700">Course Title:</label>
                    <input type="text" id="course_title" name="course_title" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" required placeholder="e.g., Introduction to Programming" />
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeCourseModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">Add Course</button>
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
    // Store user role in JavaScript for use in functions
    const userRole = "<?php echo $userRole; ?>";
    let programToDelete = null;
    let curriculumToDelete = null;
    let curriculumProgramId = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.assign-personnel-dropdown').forEach(dropdown => {
            // Add click event to stop propagation
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
                        alert("Raw error: " + text); // Show raw response
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
    
    // Close the modal
    closeDeleteModal();
    
    // Create form data
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
            // Show success message with SweetAlert2
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
        // Show error message with SweetAlert2
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while deleting the program',
            icon: 'error',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        // Reset button
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
    
    // Reset form fields
    document.getElementById('course_program').value = '';
    document.getElementById('course_curriculum').innerHTML = '<option value="">-- Select Program First --</option>';
    document.getElementById('course_code').value = '';
    document.getElementById('course_title').value = '';
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
    
    // Show loading state
    document.getElementById('course_curriculum').innerHTML = '<option value="">Loading curricula...</option>';
    
    // Fetch curricula for the selected program
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

document.addEventListener('DOMContentLoaded', function() {
    const addCourseForm = document.getElementById('addCourseForm');
    if (addCourseForm) {
        addCourseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Submit form via AJAX
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close modal and reload page
                        closeCourseModal();
                        location.reload();
                    });
                } else {
                    // Show error message
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
                    text: 'An error occurred while adding the course',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            });
        });
    }
});

    function closeTaskModal() {
        // This function is called in the event listener but doesn't seem to exist
        // Adding an empty implementation to prevent errors
    }

    function toggleCollapse(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.toggle('hidden');
            // Find the button that triggered this collapse
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

    // Add click outside functionality
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
        // Show loading state
        const confirmBtn = document.getElementById('confirmDeleteCurriculumBtn');
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Deleting...';
        confirmBtn.disabled = true;
        
        // Close the modal
        closeDeleteCurriculumModal();
        
        // Create form data
        const formData = new FormData();
        formData.append('program_id', programId);
        formData.append('curriculum_year', curriculumYear);
        formData.append('ajax', 'true');
        
        console.log('Deleting curriculum:', { programId, curriculumYear }); // Debug log
        
        fetch('../curriculum/remove_curriculum.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success === true) {
                // Show success message with SweetAlert2
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Curriculum deleted successfully',
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
                    text: data.message || 'Failed to delete curriculum',
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
            console.error('Error:', error); // Debug log
            // Show error message with SweetAlert2
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while deleting the curriculum. Please check the console for details.',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            // Reset button
            confirmBtn.textContent = originalText;
            confirmBtn.disabled = false;
        });
    }

    // Course search functionality
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