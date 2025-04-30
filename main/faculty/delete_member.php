<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $accountId = $data['accountId'];

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    
    $updateQuery = "UPDATE personnel SET FacultyID = NULL WHERE AccountID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        http_response_code(200); // Success
        echo json_encode(["message" => "Member removed from faculty successfully"]);
    } else {
        http_response_code(400); // Error
        echo json_encode(["message" => "Error removing member from faculty"]);
    }
    $stmt->close();
    $conn->close();
}
?>