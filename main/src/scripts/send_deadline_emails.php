<?php
require_once __DIR__ . '/../../../src/scripts/database.php';
require_once __DIR__ . '/../../../src/scripts/mailer.php';

date_default_timezone_set('Asia/Manila'); // Set your timezone if needed

// Query for overdue tasks
$query = "SELECT t.TaskID, t.Title, t.DueDate, t.Status as TaskStatus,
                 ta.TaskAssignmentID, ta.Status as AssignmentStatus,
                 ta.CourseCode, ta.ProgramID, p.Email, p.AccountID,
                 CONCAT(per.FirstName, ' ', per.LastName) as ProfessorName,
                 c.Title as CourseTitle
          FROM tasks t
          JOIN task_assignments ta ON t.TaskID = ta.TaskID
          JOIN program_courses pc ON ta.CourseCode = pc.CourseCode 
              AND ta.ProgramID = pc.ProgramID
          JOIN personnel per ON pc.PersonnelID = per.PersonnelID
          JOIN accounts p ON per.AccountID = p.AccountID
          JOIN courses c ON ta.CourseCode = c.CourseCode
          WHERE t.DueDate < NOW()
          AND ta.Status != 'Completed'";

$stmt = $mysqli->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$mailer = getMailer();

while ($row = $result->fetch_assoc()) {
    $subject = "URGENT: Task Past Deadline - " . $row['Title'];
    $message = "<html><body>"
        . "<h2>Task Past Deadline Notice</h2>"
        . "<p>Dear " . htmlspecialchars($row['ProfessorName']) . ",</p>"
        . "<p>This is to inform you that the following task is past its deadline:</p>"
        . "<ul>"
        . "<li><strong>Task:</strong> " . htmlspecialchars($row['Title']) . "</li>"
        . "<li><strong>Course:</strong> " . htmlspecialchars($row['CourseTitle']) . " (" . htmlspecialchars($row['CourseCode']) . ")</li>"
        . "<li><strong>Due Date:</strong> " . htmlspecialchars($row['DueDate']) . "</li>"
        . "<li><strong>Current Status:</strong> " . htmlspecialchars($row['AssignmentStatus']) . "</li>"
        . "</ul>"
        . "<p>Please submit your task as soon as possible.</p>"
        . "<p><em>This is an automated message from the Courseware Management System.</em></p>"
        . "</body></html>";

    $emailStatus = "Not Sent";
    try {
        $mailer->clearAddresses();
        $mailer->addAddress($row['Email'], $row['ProfessorName']);
        $mailer->Subject = $subject;
        $mailer->Body = $message;
        
        if ($mailer->send()) {
            $emailStatus = "Sent";
            // Create notification in database
            $notificationQuery = "INSERT INTO notifications (AccountID, Title, Message, TaskID) VALUES (?, ?, ?, ?)";
            $notificationStmt = $mysqli->prepare($notificationQuery);
            $notificationTitle = "Task Past Deadline: " . $row['Title'];
            $notificationMessage = "The task '" . $row['Title'] . "' for " . $row['CourseTitle'] . " is past its deadline.";
            $notificationStmt->bind_param("sssi", $row['AccountID'], $notificationTitle, $notificationMessage, $row['TaskID']);
            $notificationStmt->execute();
            $notificationStmt->close();
        }
    } catch (Exception $e) {
        $emailStatus = "Failed: " . $mailer->ErrorInfo;
    }
    echo date('Y-m-d H:i:s') . " | TaskID: {$row['TaskID']} | Email to: {$row['Email']} | Status: $emailStatus\n";
}
$stmt->close();
$mysqli->close(); 