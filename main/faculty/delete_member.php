<?php
session_start();

if (!isset($_SESSION['Username']) || !isset($_SESSION['AccountID'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['accountId'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $accountId = $data['accountId'];
    $currentAccountId = $_SESSION['AccountID'];

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
        exit();
    }
    
    try {
      
        $stmt = $conn->prepare("SELECT PersonnelID, CONCAT(FirstName, ' ', LastName) AS FullName 
                               FROM personnel WHERE AccountID = ?");
        $stmt->bind_param("i", $currentAccountId);
        $stmt->execute();
        $currentUserResult = $stmt->get_result();
        $stmt->close();
        
        if ($currentUserResult->num_rows === 0) {
            throw new Exception("Current user not found");
        }
        
        $currentUser = $currentUserResult->fetch_assoc();
        $currentPersonnelID = $currentUser['PersonnelID'];
        $currentUserFullName = $currentUser['FullName'];
        
        // Get target member's info before removal
        $stmt = $conn->prepare("SELECT PersonnelID, FacultyID, CONCAT(FirstName, ' ', LastName) AS FullName 
                               FROM personnel WHERE AccountID = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $targetResult = $stmt->get_result();
        $stmt->close();
        
        if ($targetResult->num_rows === 0) {
            throw new Exception("Target member not found");
        }
        
        $targetUser = $targetResult->fetch_assoc();
        $targetPersonnelID = $targetUser['PersonnelID'];
        $targetFacultyID = $targetUser['FacultyID'];
        $targetFullName = $targetUser['FullName'];
        
        // If target user doesn't belong to any faculty
        if ($targetFacultyID === NULL) {
            http_response_code(400); 
            echo json_encode(["success" => false, "message" => "Member is not assigned to any faculty"]);
            exit();
        }
        
        $conn->begin_transaction();
        
        try {
            // Update member to remove from faculty
            $updateQuery = "UPDATE personnel SET FacultyID = NULL WHERE AccountID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Add audit log entry
                $desc = "Removed {$targetFullName} from faculty";
                $stmt->close();
                
                $stmt = $conn->prepare("INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description, LogDateTime) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiss", $targetFacultyID, $currentPersonnelID, $currentUserFullName, $desc);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                
                http_response_code(200);
                echo json_encode([
                    "success" => true, 
                    "message" => "Member removed from faculty successfully"
                ]);
            } else {
                throw new Exception("Error removing member from faculty");
            }
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    
    $conn->close();
}
?>