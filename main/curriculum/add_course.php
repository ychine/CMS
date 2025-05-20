<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get faculty ID
$accountID = $_SESSION['AccountID'];
$facultyQuery = "SELECT FacultyID FROM personnel WHERE AccountID = ?";
$facultyStmt = $conn->prepare($facultyQuery);
$facultyStmt->bind_param("i", $accountID);
$facultyStmt->execute();
$facultyResult = $facultyStmt->get_result();

if ($facultyRow = $facultyResult->fetch_assoc()) {
    $facultyID = $facultyRow['FacultyID'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Faculty not found']);
    exit();
}
$facultyStmt->close();

// Get form data
$programID = $_POST['program_id'];
$curriculumID = $_POST['curriculum_id'];
$selectedCourses = json_decode($_POST['selected_courses'], true);

if (empty($programID) || empty($curriculumID)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Program and curriculum are required']);
    exit();
}

if (empty($selectedCourses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No courses selected']);
    exit();
}

$success = true;
$addedCourses = [];
$errors = [];

$conn->begin_transaction();

try {
    foreach ($selectedCourses as $course) {
        $courseCode = $course['code'];
        $courseTitle = $course['title'];

        
        $checkCourse = "SELECT CourseCode FROM courses WHERE CourseCode = ?";                                      // check if course exist
        $stmt = $conn->prepare($checkCourse);
        $stmt->bind_param("s", $courseCode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
          
            $insertCourse = "INSERT INTO courses (CourseCode, Title) VALUES (?, ?)";                               // adding course
            $stmt = $conn->prepare($insertCourse);
            $stmt->bind_param("ss", $courseCode, $courseTitle);
            $stmt->execute();
        }

        
        $checkExisting = "SELECT CourseCode FROM program_courses WHERE CurriculumID = ? AND CourseCode = ?";        // validation if course ifs in curriculum
        $stmt = $conn->prepare($checkExisting);
        $stmt->bind_param("is", $curriculumID, $courseCode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
          
            $insertProgramCourse = "INSERT INTO program_courses (ProgramID, CurriculumID, CourseCode, FacultyID) VALUES (?, ?, ?, ?)";  
            $stmt = $conn->prepare($insertProgramCourse);
            $stmt->bind_param("iisi", $programID, $curriculumID, $courseCode, $facultyID);        //adding course into program course with progid
            $stmt->execute();
            $addedCourses[] = $courseCode;
        } else {
            $errors[] = "Course $courseCode is already in this curriculum";
        }
    }

    if (empty($addedCourses)) {
        throw new Exception("No new courses were added");
    }

    $conn->commit();
    
    $message = count($addedCourses) . " course(s) added successfully";
    if (!empty($errors)) {
        $message .= ". " . implode(", ", $errors);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>