<?php 
session_start(); 

if (!isset($_SESSION['AccountID'])) {     
    header("Location: ../../index.php");     
    exit(); 
}  

if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['program_id'])) {     
    $programID = intval($_POST['program_id']);     
    $accountID = $_SESSION['AccountID'];      
    
    $conn = new mysqli("localhost", "root", "", "cms");     
    if ($conn->connect_error) {         
        if(isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
            exit();
        } else {
            die("Connection failed: " . $conn->connect_error);
        }
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
    
    if ($facultyID) {
        // Begin transaction for data integrity
        $conn->begin_transaction();
        
        try {
            // First, delete related program_courses entries
            $stmt = $conn->prepare("
                DELETE pc FROM program_courses pc
                INNER JOIN curricula c ON pc.CurriculumID = c.id
                WHERE c.ProgramID = ? AND pc.FacultyID = ?
            ");
            $stmt->bind_param("ii", $programID, $facultyID);
            $stmt->execute();
            $stmt->close();
            
            // Then, delete curricula
            $stmt = $conn->prepare("DELETE FROM curricula WHERE ProgramID = ? AND FacultyID = ?");
            $stmt->bind_param("ii", $programID, $facultyID);
            $stmt->execute();
            $stmt->close();
            
            // Check if there are any remaining curricula for this program
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM curricula WHERE ProgramID = ?");
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            // Only delete the program if there are no more curricula associated with it
            if ($row['count'] == 0) {
                $stmt = $conn->prepare("DELETE FROM programs WHERE ProgramID = ?");
                $stmt->bind_param("i", $programID);
                $stmt->execute();
                $stmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            
            if(isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
                exit();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            if(isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit();
            }
        }
    } else {
        if(isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'message' => 'Faculty not found']);
            exit();
        }
    }      
    
    $conn->close(); 
}   

// If not an AJAX request, redirect back to curriculum page
header("Location: ../curriculum/curriculum_frame.php"); 
exit(); 
?>