<?php
require_once __DIR__ . '/../../../src/scripts/database.php';
require_once __DIR__ . '/../../../src/scripts/mailer.php';

// Test the deadline checking logic
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

echo "<h2>Current Tasks Past Deadline</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr>
        <th>Task ID</th>
        <th>Title</th>
        <th>Due Date</th>
        <th>Task Status</th>
        <th>Assignment Status</th>
        <th>Course</th>
        <th>Professor</th>
        <th>Email</th>
        <th>Email Status</th>
      </tr>";

// Get mailer instance
$mailer = getMailer();

while ($row = $result->fetch_assoc()) {
    // Prepare email content
    $subject = "URGENT: Task Past Deadline - " . htmlspecialchars($row['Title']);
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { padding: 20px; }
            .header { color: #d9534f; }
            .details { margin: 20px 0; }
            .footer { color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2 class='header'>Task Past Deadline Notice</h2>
            <div class='details'>
                <p>Dear " . htmlspecialchars($row['ProfessorName']) . ",</p>
                <p>This is to inform you that the following task is past its deadline:</p>
                <ul>
                    <li><strong>Task:</strong> " . htmlspecialchars($row['Title']) . "</li>
                    <li><strong>Course:</strong> " . htmlspecialchars($row['CourseTitle']) . " (" . htmlspecialchars($row['CourseCode']) . ")</li>
                    <li><strong>Due Date:</strong> " . htmlspecialchars($row['DueDate']) . "</li>
                    <li><strong>Current Status:</strong> " . htmlspecialchars($row['AssignmentStatus']) . "</li>
                </ul>
                <p>Please submit your task as soon as possible.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message from the Courseware Management System.</p>
            </div>
        </div>
    </body>
    </html>";

    // Send email
    $emailStatus = "Not Sent";
    try {
        $mailer->clearAddresses();
        $mailer->addAddress($row['Email'], $row['ProfessorName']);
        $mailer->Subject = $subject;
        $mailer->Body = $message;
        
        if ($mailer->send()) {
            $emailStatus = "Sent";
            
            // Create notification in database (fixed, no JOINs)
            $notificationQuery = "INSERT INTO notifications (AccountID, Title, Message, TaskID) VALUES (?, ?, ?, ?)";
            $notificationStmt = $mysqli->prepare($notificationQuery);
            $notificationTitle = "Task Past Deadline: " . $row['Title'];
            $notificationMessage = "The task '" . $row['Title'] . "' for " . $row['CourseTitle'] . " is past its deadline.";
            $notificationStmt->bind_param("sssi", $row['AccountID'], $notificationTitle, $notificationMessage, $row['TaskID']);
            $notificationStmt->execute();
        }
    } catch (Exception $e) {
        $emailStatus = "Failed: " . $mailer->ErrorInfo;
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['TaskID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['DueDate']) . "</td>";
    echo "<td>" . htmlspecialchars($row['TaskStatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['AssignmentStatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['CourseTitle']) . " (" . htmlspecialchars($row['CourseCode']) . ")</td>";
    echo "<td>" . htmlspecialchars($row['ProfessorName']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
    echo "<td>" . htmlspecialchars($emailStatus) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Also show upcoming deadlines
$query = "SELECT t.TaskID, t.Title, t.DueDate, t.Status as TaskStatus,
                 ta.TaskAssignmentID, ta.Status as AssignmentStatus,
                 ta.CourseCode, ta.ProgramID, p.Email,
                 CONCAT(per.FirstName, ' ', per.LastName) as ProfessorName,
                 c.Title as CourseTitle
          FROM tasks t
          JOIN task_assignments ta ON t.TaskID = ta.TaskID
          JOIN program_courses pc ON ta.CourseCode = pc.CourseCode 
              AND ta.ProgramID = pc.ProgramID
          JOIN personnel per ON pc.PersonnelID = per.PersonnelID
          JOIN accounts p ON per.AccountID = p.AccountID
          JOIN courses c ON ta.CourseCode = c.CourseCode
          WHERE t.DueDate > NOW()
          AND ta.Status != 'Completed'
          ORDER BY t.DueDate ASC
          LIMIT 5";

$stmt = $mysqli->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Upcoming Deadlines (Next 5)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr>
        <th>Task ID</th>
        <th>Title</th>
        <th>Due Date</th>
        <th>Task Status</th>
        <th>Assignment Status</th>
        <th>Course</th>
        <th>Professor</th>
        <th>Email</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['TaskID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['DueDate']) . "</td>";
    echo "<td>" . htmlspecialchars($row['TaskStatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['AssignmentStatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['CourseTitle']) . " (" . htmlspecialchars($row['CourseCode']) . ")</td>";
    echo "<td>" . htmlspecialchars($row['ProfessorName']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>