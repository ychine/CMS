<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

$accountID = $_SESSION['AccountID'];

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


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

// Prepare and execute query filtered by FacultyID
$programs = [];

$sql = "
    SELECT 
        p.ProgramID, p.ProgramCode,
        c.id AS CurriculumID, c.name AS CurriculumName,
        co.CourseCode, co.Title,
        c.FacultyID
    FROM programs p
    LEFT JOIN curricula c ON p.ProgramID = c.ProgramID
    LEFT JOIN program_courses pc ON c.id = pc.CurriculumID
    LEFT JOIN courses co ON pc.CourseCode = co.CourseCode
    WHERE c.FacultyID = ?
    ORDER BY p.ProgramCode, c.name, co.Title
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $facultyID); 
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $programId = $row['ProgramID'];
    $program = $row['ProgramCode'];
    $curriculum = $row['CurriculumName'];
    $course = $row['Title'];
    
    if (!isset($programs[$programId])) {
        $programs[$programId] = [
            'code' => $program,
            'curricula' => []
        ];
    }

    if ($curriculum && !isset($programs[$programId]['curricula'][$curriculum])) {
        $programs[$programId]['curricula'][$curriculum] = [];
    }

    if ($course) {
        $programs[$programId]['curricula'][$curriculum][] = $course;
    }
}

$existingPrograms = [];
$programQuery = "
    SELECT DISTINCT p.ProgramID, p.ProgramCode, p.ProgramName
    FROM programs p
    INNER JOIN curricula c ON p.ProgramID = c.ProgramID
    INNER JOIN program_courses pc ON c.id = pc.CurriculumID
    WHERE pc.FacultyID = ?
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
        
        function renderProgramTree($programs) {
            foreach ($programs as $programId => $programData) {
                $programName = $programData['code'];
                $curricula = $programData['curricula'];
                
                $progId = 'prog_' . md5($programName);
                echo "<div class='mt-4'>";
                echo "<div class='flex items-center justify-between'>";
                echo "<button onclick=\"toggleCollapse('$progId')\" class=\"w-full text-left px-4 py-2 bg-blue-100 text-blue-800 rounded font-bold text-lg shadow hover:bg-blue-200 transition-all duration-200\">▶ $programName</button>";
                echo "<button onclick=\"confirmDelete('$programId', '$programName')\" class=\"delete-btn ml-2\">Delete</button>";
                echo "</div>";
                
                echo "<div id=\"$progId\" class='ml-4 mt-2 hidden'>";
                
                foreach ($curricula as $year => $courses) {
                    $yearId = 'year_' . md5($programName . $year);
                    echo "<div class='mt-2'>";
                    echo "<button onclick=\"toggleCollapse('$yearId')\" class=\"w-full text-left px-4 py-1 bg-blue-50 text-blue-700 rounded font-semibold shadow-sm hover:bg-blue-100 transition-all duration-200\">▶ $year</button>";
                    echo "<div id=\"$yearId\" class='ml-4 mt-1 hidden'>";
                    
                
                    echo "<div class='overflow-x-auto'>";
                    echo "<table class='min-w-full text-sm text-left text-gray-700 border border-gray-300'>";
                    echo "<thead class='bg-gray-100 text-gray-900'>";
                    echo "<tr><th class='px-4 py-2 border-b'>📚 Course</th></tr>";
                    echo "</thead><tbody>";
        
                    foreach ($courses as $course) {
                        echo "<tr class='hover:bg-gray-50'>";
                        echo "<td class='px-4 py-2 border-b'>" . htmlspecialchars($course) . "</td>";
                        echo "</tr>";
                    }
        
                    echo "</tbody></table></div>"; // Close table and scroll container
                    echo "</div></div>"; // Close year div
                }
        
                echo "</div></div>"; // Close program div
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
    <div id="task-dropdown" class="font-onest task-dropdown fixed bottom-24 right-10 w-48 space-y-2 z-50">
        <button onclick="openProgramModal()"
            class="w-screen text-xl text-center text-white py-3 px-4 rounded-full bg-[#51D55A] hover:bg-green-800 active:bg-blue-900 transition-all duration-300 slide-in delay-150">
            Add Program or Curriculum
        </button>
        <button onclick="openCourseModal()"
            class="w-screen text-xl text-center text-white py-3 px-4 rounded-full bg-[#51D55A] hover:bg-green-800 active:bg-green-900 transition-all duration-600 slide-in delay-0">
            Add Course
        </button>
    </div>

    <!-- Add Program Modal -->
    <div id="programModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-[500px] border border-blue-500">
            <h2 class="text-2xl font-overpass font-bold mb-4">Create Curriculum</h2>
            <form method="POST" action="../curriculum/create_program.php">
                
                <label class="block mb-1 font-semibold">Select Program:</label>
                <select id="existing_program" name="existing_program" class="w-full mb-3 p-2 border rounded" onchange="toggleProgramFields(); updateCurriculumPreview();">
                    <option value="">-- Select Program --</option>
                    <?php foreach ($existingPrograms as $program): ?>
                        <option value="<?= $program['code'] ?>" data-name="<?= $program['name'] ?>">
                            <?= $program['code'] ?> - <?= $program['name'] ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="other">Other (Add New Program)</option>
                </select>

                <div id="new_program_fields" class="hidden">
                    <label class="block mb-1 font-semibold">Program Code (e.g., BSIS):</label>
                    <input type="text" id="program_code_input" name="program_code" class="w-full mb-3 p-2 border rounded" oninput="updateCurriculumPreview()" />

                    <label class="block mb-1 font-semibold">Program Name:</label>
                    <input type="text" id="program_name_input" name="program_name" class="w-full mb-3 p-2 border rounded" />
                </div>

                <label class="block mb-1 font-semibold">Curriculum Year:</label>
                <input type="number" id="curriculum_year_input" name="curriculum_year" value="<?= date('Y') ?>" required class="w-full mb-3 p-2 border rounded" oninput="updateCurriculumPreview()" />

                <p class="text-sm text-gray-600 mt-1 mb-3">
                    <strong>Generated Curriculum Name:</strong>
                    <span id="curriculum_preview" class="text-blue-700 font-semibold">—</span>
                </p>

                <input type="hidden" id="curriculum_name_input" name="curriculum_name" />

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="closeProgramModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
    <div class="modal-content">
        <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
        <p id="deleteMessage" class="mb-4">Are you sure you want to delete this program?</p>
        <div class="flex justify-end gap-2">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
            <button id="confirmDeleteBtn" class= "delete-btn ml-2 px-4 py-2"> Delete </button>
        </div>
    </div>
</div>

</div>

<script>
    // Program ID for deletion
    let programToDelete = null;

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
    // Show loading state
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
        const selectedOption = dropdown.options[dropdown.selectedIndex];

        if (dropdown.value === "other") {
            otherFields.classList.remove("hidden");
            document.getElementById("program_code_input").required = true;
            document.getElementById("program_name_input").required = true;
        } else {
            otherFields.classList.add("hidden");
            document.getElementById("program_code_input").required = false;
            document.getElementById("program_name_input").required = false;
        }

        updateCurriculumPreview();
    }

    function updateCurriculumPreview() {
        const dropdown = document.getElementById("existing_program");
        const codeInput = document.getElementById("program_code_input");
        const yearInput = document.getElementById("curriculum_year_input");
        const preview = document.getElementById("curriculum_preview");
        const hiddenInput = document.getElementById("curriculum_name_input");

        let code = "";

        if (dropdown.value === "other") {
            code = codeInput.value.trim();
        } else if (dropdown.value !== "") {
            code = dropdown.value;
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
        if (!dropdown.contains(event.target) && !button.contains(event.target)) {
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
        // This function seems to be missing but is referenced in your HTML
        alert("Course modal functionality not yet implemented");
    }

    window.addEventListener('keydown', function (e) {
        if (e.key === "Escape") {
            closeTaskModal();
            closeProgramModal();
            closeDeleteModal();
        }
    });

    function closeTaskModal() {
        // This function is called in the event listener but doesn't seem to exist
        // Adding an empty implementation to prevent errors
    }

    function toggleCollapse(id) {
        const el = document.getElementById(id);
        el.classList.toggle("hidden");
        const btn = document.querySelector(`button[onclick="toggleCollapse('${id}')"]`);
        if (btn && btn.textContent.trim().startsWith("▶")) {
            btn.textContent = btn.textContent.replace("▶", "▼");
        } else if (btn && btn.textContent.trim().startsWith("▼")) {
            btn.textContent = btn.textContent.replace("▼", "▶");
        }
    }
</script>
</body>
</html>