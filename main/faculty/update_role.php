<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $accountId = $data['accountId'];
    $newRole = $data['newRole'];

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $updateQuery = "UPDATE personnel SET Role = ? WHERE AccountID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $newRole, $accountId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        http_response_code(200); // Success
    } else {
        http_response_code(400); // Error
    }
    $stmt->close();
    $conn->close();
}
?>