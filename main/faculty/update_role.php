<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['Username'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $accountId = $data['accountId'];
    $newRole = $data['newRole'];
    $response = ['success' => false, 'message' => ''];

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    $currentAccountId = $_SESSION['AccountID'];
    $currentUserQuery = "SELECT Role, FacultyID FROM personnel WHERE AccountID = ?";
    $stmt = $conn->prepare($currentUserQuery);
    $stmt->bind_param("i", $currentAccountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();
    $stmt->close();

    // Fetch full name of the user whose role is being changed
    $targetName = "";
    $stmt = $conn->prepare("SELECT FirstName, MiddleName, LastName FROM personnel WHERE AccountID = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $targetName = $row['FirstName'] . ' ' . ($row['MiddleName'] ? $row['MiddleName'] . ' ' : '') . $row['LastName'];
    }
    $stmt->close();

    if ($currentUser['Role'] === 'DN' && $newRole === 'DN') {
        $conn->begin_transaction();
        try {
            $targetUserQuery = "SELECT FacultyID FROM personnel WHERE AccountID = ?";
            $stmt = $conn->prepare($targetUserQuery);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $targetResult = $stmt->get_result();
            $targetUser = $targetResult->fetch_assoc();
            $stmt->close();

            if ($targetUser['FacultyID'] != $currentUser['FacultyID']) {
                throw new Exception("Cannot swap roles with user from different faculty");
            }

            $updateCurrentUser = "UPDATE personnel SET Role = 'FM' WHERE AccountID = ?";
            $stmt = $conn->prepare($updateCurrentUser);
            $stmt->bind_param("i", $currentAccountId);
            $stmt->execute();
            if ($stmt->affected_rows <= 0) {
                throw new Exception("Failed to update current user role");
            }
            $stmt->close();

            $updateNewDean = "UPDATE personnel SET Role = 'DN' WHERE AccountID = ?";
            $stmt = $conn->prepare($updateNewDean);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            if ($stmt->affected_rows <= 0) {
                throw new Exception("Failed to update new dean role");
            }
            $stmt->close();

            // Insert audit log for deanship transfer
            $desc = "Assigned new role 'DN' to {$targetName}";
            $stmt = $conn->prepare("INSERT INTO auditlog (FullName, Description, LogDateTime) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $targetName, $desc);
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
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    } else {
        $updateQuery = "UPDATE personnel SET Role = ? WHERE AccountID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newRole, $accountId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response = ['success' => true, 'message' => 'Role updated successfully!'];

            // Insert audit log for standard role update
            $desc = "Assigned new role '{$newRole}' to {$targetName}";
            $stmt = $conn->prepare("INSERT INTO auditlog (FullName, Description, LogDateTime) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $targetName, $desc);
            $stmt->execute();
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'No changes made or update failed'];
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    $conn->close();
}
?>
