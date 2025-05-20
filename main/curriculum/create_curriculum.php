<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$type = $_POST['type'];
$parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$name = trim($_POST['name']);

if (!$type || !$name) {
    die("Missing type or name.");
}

if ($type === 'course') {
    
    $stmt = $conn->prepare("INSERT INTO courses (course_title) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        $newCourseId = $conn->insert_id;
       
        $link = $conn->prepare("INSERT INTO program_courses (curriculum_id, course_id) VALUES (?, ?)");
        $link->bind_param("ii", $parentId, $newCourseId);
        $link->execute();
    }
} else {
    // Insert new node into curricula
    $stmt = $conn->prepare("INSERT INTO curricula (curricula_name, type, parent_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $type, $parentId);
    $stmt->execute();
}

header("Location: curriculum.php");
exit();
?>