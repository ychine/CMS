<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide raw errors in output to prevent breaking JSON
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['Username']) || !isset($_SESSION['AccountID'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$jsonInput = file_get_contents("php://input");
if (!$jsonInput) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No input received']);
    exit();
}

// Decode JSON
$data = json_decode($jsonInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'received' => substr($jsonInput, 0, 100)
    ]);
    exit();
}

// Validate required fields
if (!isset($data['accountId'], $data['newRole'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$accountId = $data['accountId'];
$newRole = $data['newRole'];
$currentAccountId = $_SESSION['AccountID'];

$response = ['success' => false, 'message' => ''];

try {
    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get current user's role, faculty, personnel ID and name
    $stmt = $conn->prepare("SELECT PersonnelID, Role, FacultyID, CONCAT(FirstName, ' ', LastName) AS FullName FROM personnel WHERE AccountID = ?");
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

    // Get target user's info including personnel ID
    $stmt = $conn->prepare("SELECT PersonnelID, FirstName, LastName, FacultyID FROM personnel WHERE AccountID = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $targetResult = $stmt->get_result();
    $stmt->close();

    if ($targetResult->num_rows === 0) {
        throw new Exception("Target user not found");
    }

    $targetUser = $targetResult->fetch_assoc();
    $targetName = $targetUser['FirstName'] . ' ' . $targetUser['LastName'];
    $targetPersonnelID = $targetUser['PersonnelID'];
    $targetFacultyID = $targetUser['FacultyID'];

    // If dean wants to transfer role to another faculty member
    if ($currentUser['Role'] === 'DN' && $newRole === 'DN') {
        if ($targetUser['FacultyID'] != $currentUser['FacultyID']) {
            throw new Exception("Cannot transfer deanship outside your faculty");
        }

        $conn->begin_transaction();

        try {
            // Demote current dean
            $stmt = $conn->prepare("UPDATE personnel SET Role = 'FM' WHERE AccountID = ?");
            $stmt->bind_param("i", $currentAccountId);
            $stmt->execute();
            $stmt->close();

            // Promote target to dean
            $stmt = $conn->prepare("UPDATE personnel SET Role = 'DN' WHERE AccountID = ?");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $stmt->close();

            // Audit log with FacultyID and PersonnelID
            $desc = "Transferred deanship to {$targetName}";
            $stmt = $conn->prepare("INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description, LogDateTime) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $targetFacultyID, $currentPersonnelID, $currentUserFullName, $desc);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $response = [
                'success' => true,
                'message' => 'Deanship transferred successfully!',
                'roleSwapped' => true
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Transaction failed: " . $e->getMessage());
        }

    } else {
        // Just update the role
        $stmt = $conn->prepare("UPDATE personnel SET Role = ? WHERE AccountID = ?");
        $stmt->bind_param("si", $newRole, $accountId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            
            $desc = "Assigned new role '{$newRole}' to {$targetName}";
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description, LogDateTime) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $targetFacultyID, $currentPersonnelID, $currentUserFullName, $desc);
            $stmt->execute();
            $stmt->close();

            $response = ['success' => true, 'message' => 'Role updated successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'No changes made or update failed'];
            $stmt->close();
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit();
?>