<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$accountID = $_SESSION['AccountID'];
$facultyName = "Faculty";
$userRole = "";
$facultyID = null;
$message = "";

// Fetch the faculty name, faculty ID and role based on the logged-in user
$sql = "SELECT personnel.PersonnelID, personnel.FacultyID, personnel.Role, faculties.Faculty 
        FROM personnel 
        JOIN faculties ON personnel.FacultyID = faculties.FacultyID
        WHERE personnel.AccountID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $facultyID = $row['FacultyID'];
    $facultyName = $row['Faculty'];
    $personnelID = $row['PersonnelID'];
    $userRole = $row['Role'];
}
$stmt->close();

// Handle file upload and submission
if (isset($_POST['submit_file']) && isset($_POST['task_id']) && isset($_FILES['task_file'])) {
    $taskID = $_POST['task_id'];
    $courseCode = $_POST['course_code'];
    $programID = $_POST['program_id'];
    
    // Check if directory exists, if not create it
    $uploadDir = "../../uploads/tasks/{$taskID}/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = basename($_FILES["task_file"]["name"]);
    $targetFilePath = $uploadDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Upload file
    if(move_uploaded_file($_FILES["task_file"]["tmp_name"], $targetFilePath)) {
        $relativePath = "uploads/tasks/{$taskID}/" . $fileName;
        // Get school year and term from the task
        $taskInfoSql = "SELECT SchoolYear, Term FROM tasks WHERE TaskID = ?";
        $taskInfoStmt = $conn->prepare($taskInfoSql);
        $taskInfoStmt->bind_param("i", $taskID);
        $taskInfoStmt->execute();
        $taskInfoResult = $taskInfoStmt->get_result();
        $taskInfo = $taskInfoResult->fetch_assoc();
        $schoolYear = $taskInfo['SchoolYear'];
        $term = $taskInfo['Term'];
        $taskInfoStmt->close();
        // Insert a new submission record
        $insertSql = "INSERT INTO submissions (FacultyID, TaskID, CourseCode, ProgramID, SubmissionPath, SubmittedBy, SubmissionDate, SchoolYear, Term) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iisssiss", $facultyID, $taskID, $courseCode, $programID, $relativePath, $personnelID, $schoolYear, $term);
        if($insertStmt->execute()) {
            $message = "File uploaded successfully.";
        } else {
            $message = "Error inserting submission: " . $insertStmt->error;
        }
        $insertStmt->close();
        // Optionally, update task_assignments status for workflow
        $updateSql = "UPDATE task_assignments SET Status = 'Submitted', SubmissionDate = NOW(), SubmissionPath = ? WHERE TaskID = ? AND CourseCode = ? AND ProgramID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sisi", $relativePath, $taskID, $courseCode, $programID);
        $updateStmt->execute();
        $updateStmt->close();
        // Also update the parent task's status to 'Submitted'
        $updateTaskSql = "UPDATE tasks SET Status = 'Submitted' WHERE TaskID = ?";
        $updateTaskStmt = $conn->prepare($updateTaskSql);
        $updateTaskStmt->bind_param("i", $taskID);
        $updateTaskStmt->execute();
        $updateTaskStmt->close();
    } else {
        $message = "Error uploading file.";
    }
}

// Handle task approval (Dean only)
if (isset($_POST['approve_task']) && $userRole == 'Dean') {
    $taskAssignmentID = $_POST['task_assignment_id'];
    
    $approveSql = "UPDATE task_assignments 
                   SET Status = 'Completed', ApprovedBy = ?, ApprovalDate = NOW() 
                   WHERE TaskAssignmentID = ?";
    $approveStmt = $conn->prepare($approveSql);
    $approveStmt->bind_param("ii", $personnelID, $taskAssignmentID);
    
    if($approveStmt->execute()) {
        // Check if all assignments for this task are completed
        $checkAllCompletedSql = "SELECT TaskID FROM task_assignments 
                                WHERE TaskAssignmentID = ?";
        $checkStmt = $conn->prepare($checkAllCompletedSql);
        $checkStmt->bind_param("i", $taskAssignmentID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $taskData = $checkResult->fetch_assoc();
        $taskID = $taskData['TaskID'];
        $checkStmt->close();
        
        // Count total vs completed assignments for this task
        $countSql = "SELECT COUNT(*) as total, 
                    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed 
                    FROM task_assignments WHERE TaskID = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $taskID);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countData = $countResult->fetch_assoc();
        $countStmt->close();
        
        // If all assignments are completed, mark the task as completed
        if ($countData['total'] == $countData['completed']) {
            $updateTaskSql = "UPDATE tasks SET Status = 'Completed' WHERE TaskID = ?";
            $updateTaskStmt = $conn->prepare($updateTaskSql);
            $updateTaskStmt->bind_param("i", $taskID);
            $updateTaskStmt->execute();
            $updateTaskStmt->close();
        } else {
            // At least one assignment is completed, mark as In Progress
            $updateTaskSql = "UPDATE tasks SET Status = 'In Progress' WHERE TaskID = ?";
            $updateTaskStmt = $conn->prepare($updateTaskSql);
            $updateTaskStmt->bind_param("i", $taskID);
            $updateTaskStmt->execute();
            $updateTaskStmt->close();
        }
        
        $message = "Task approved successfully.";
    } else {
        $message = "Error approving task: " . $approveStmt->error;
    }
    $approveStmt->close();
}

// Fetch tasks based on user role
$tasks = [];

if ($userRole == 'Dean') {
    // Dean sees all tasks in the faculty
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, 
                t.SchoolYear, t.Term, COUNT(ta.TaskAssignmentID) as TotalAssignments,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedAssignments,
                p.FirstName as CreatorFirstName, p.LastName as CreatorLastName, p.Role as CreatorRole
                FROM tasks t
                LEFT JOIN task_assignments ta ON t.TaskID = ta.TaskID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                WHERE t.FacultyID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("i", $facultyID);
} else {
    // Faculty members and professors see tasks assigned to them
    $tasksSql = "SELECT t.TaskID, t.Title, t.Description, t.DueDate, t.Status, t.CreatedAt, 
                t.SchoolYear, t.Term, COUNT(ta.TaskAssignmentID) as TotalAssignments,
                SUM(CASE WHEN ta.Status = 'Completed' THEN 1 ELSE 0 END) as CompletedAssignments,
                p.FirstName as CreatorFirstName, p.LastName as CreatorLastName, p.Role as CreatorRole,
                ta.RevisionReason
                FROM tasks t
                JOIN task_assignments ta ON t.TaskID = ta.TaskID
                JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
                WHERE pc.PersonnelID = ? AND t.FacultyID = ?
                GROUP BY t.TaskID
                ORDER BY t.CreatedAt DESC";
    $tasksStmt = $conn->prepare($tasksSql);
    $tasksStmt->bind_param("ii", $personnelID, $facultyID);
}

$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

while ($taskRow = $tasksResult->fetch_assoc()) {
    // For each task, get the courses and their assignment details
    $assignmentsSql = "SELECT ta.TaskAssignmentID, ta.CourseCode, ta.ProgramID, ta.Status as AssignmentStatus, 
                    ta.SubmissionPath, ta.SubmissionDate, ta.ApprovalDate,
                    c.Title as CourseTitle, p.ProgramName, p.ProgramCode,
                    CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
                    CONCAT(apr.FirstName, ' ', apr.LastName) as ApprovedBy
                    FROM task_assignments ta
                    JOIN courses c ON ta.CourseCode = c.CourseCode
                    JOIN programs p ON ta.ProgramID = p.ProgramID
                    LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
                    LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
                    LEFT JOIN personnel apr ON ta.ApprovedBy = apr.PersonnelID
                    WHERE ta.TaskID = ?";
    
    if ($userRole != 'DN') {
        // Non-deans only see their own assignments
        $assignmentsSql .= " AND pc.PersonnelID = ?";
    }
    
    $assignmentsSql .= " ORDER BY p.ProgramName, ta.CourseCode";
    
    $assignmentsStmt = $conn->prepare($assignmentsSql);
    
    if ($userRole != 'DN') {
        $assignmentsStmt->bind_param("ii", $taskRow['TaskID'], $personnelID);
    } else {
        $assignmentsStmt->bind_param("i", $taskRow['TaskID']);
    }
    
    $assignmentsStmt->execute();
    $assignmentsResult = $assignmentsStmt->get_result();
    
    $assignments = [];
    while ($assignmentRow = $assignmentsResult->fetch_assoc()) {
        $assignments[] = $assignmentRow;
    }
    $assignmentsStmt->close();
    
    $taskRow['Assignments'] = $assignments;
    $tasks[] = $taskRow;
}
$tasksStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COURSEDOCK - Submissions</title>
  <link href="../../src/tailwind/output.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-overpass { font-family: 'Overpass', sans-serif; }
    .font-onest { font-family: 'Onest', sans-serif; }
    
    .content {
      display: flex;
      min-height: calc(100vh - 60px);
    }
    
    .tasks-section {
      flex: 3;
      padding: 20px;
      border-right: 1px solid #e5e7eb;
    }
    
    .files-section {
      flex: 1;
      padding: 20px;
      background-color: #f9fafb;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .task-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 16px;
      margin-bottom: 20px;
    }
    
    .task-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }
    
    .faculty-info {
      display: flex;
      align-items: center;
    }
    
    .faculty-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #e5e7eb;
      margin-right: 12px;
    }
    
    .faculty-name {
      font-weight: 600;
      margin: 0;
    }
    
    .faculty-role {
      color: #6b7280;
      font-size: 0.85rem;
      margin: 0;
    }
    
    .deadline {
      color: #ef4444;
      font-size: 0.85rem;
    }
    
    .approval-section {
      margin-top: 30px;
    }
    
    .approval-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .complete-label {
      color: #6b7280;
      font-size: 0.9rem;
    }
    
    .course-card {
      display: flex;
      justify-content: space-between;
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 10px;
      border-left: 4px solid transparent;
    }
    
    .course-card.pending {
      background-color: #fef3c7;
      border-left-color: #f59e0b;
    }
    
    .course-card.submitted {
      background-color: #e0f2fe;
      border-left-color: #3b82f6;
    }
    
    .course-card.completed {
      background-color: #dcfce7;
      border-left-color: #10b981;
    }
    
    .course-name {
      font-weight: 500;
      margin: 0 0 4px 0;
    }
    
    .course-badges {
      display: flex;
      gap: 6px;
    }
    
    .badge {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #9ca3af;
    }
    
    .status {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }
    
    .status-label {
      font-weight: 500;
      font-size: 0.85rem;
    }
    
    .status-label.pending {
      color: #f59e0b;
    }
    
    .status-label.submitted {
      color: #3b82f6;
    }
    
    .status-label.completed {
      color: #10b981;
    }
    
    .signed-by {
      font-size: 0.75rem;
      color: #4b5563;
      margin: 2px 0 0 0;
    }
    
    .file-placeholder {
      height: 80px;
      background-color: #e5e7eb;
      border-radius: 6px;
      margin-bottom: 12px;
    }
    
    .submit-btn {
      width: 100%;
      background-color: #2563eb;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-weight: 500;
      cursor: pointer;
      margin-top: 10px;
    }
    
    .submit-btn:hover {
      background-color: #1d4ed8;
    }
    
    .task-floating-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
    }
    
    .task-floating-button button {
      background-color: #2563eb;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 8px 16px;
      font-weight: 500;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .task-list {
      margin-bottom: 30px;
    }
    
    .upload-form {
      margin-top: 15px;
    }
    
    .file-input-container {
      position: relative;
      margin-bottom: 15px;
    }
    
    .file-input-label {
      display: block;
      background-color: #f3f4f6;
      border: 1px dashed #d1d5db;
      border-radius: 6px;
      padding: 30px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .file-input-label:hover {
      background-color: #e5e7eb;
    }
    
    .file-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .selected-file {
      margin-top: 10px;
      padding: 8px 12px;
      background-color: #e0f2fe;
      border-radius: 4px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .file-name {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 80%;
    }
    
    .remove-file {
      color: #ef4444;
      cursor: pointer;
    }
    
    .approval-btn {
      background-color: #10b981;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 4px 8px;
      font-size: 0.75rem;
      cursor: pointer;
      margin-top: 5px;
    }
    
    .approval-btn:hover {
      background-color: #059669;
    }
    
    .no-tasks {
      text-align: center;
      color: #6b7280;
      padding: 40px 0;
    }
    
    /* File preview */
    .file-preview {
      margin-top: 10px;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
    }
    
    .pdf-preview {
      width: 100%;
      height: 300px;
    }
    
    .image-preview {
      max-width: 100%;
      max-height: 300px;
      margin: 0 auto;
      display: block;
    }
    
    .doc-preview {
      padding: 15px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="content">
    <div class="tasks-section">
      <?php if (!empty($message)): ?>
      <div class="bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded mb-4">
          <?php echo $message; ?>
      </div>
      <?php endif; ?>
      
      <?php if (empty($tasks)): ?>
      <div class="no-tasks">
        <p>No tasks available. <?php echo ($userRole == 'Dean') ? 'Create your first task!' : 'Tasks assigned to you will appear here.'; ?></p>
      </div>
      <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <div class="task-list">
          <div class="section-header">
            <h3><?php echo htmlspecialchars($task['Title']); ?></h3>
            <span class="course-code">
              <?php echo htmlspecialchars($task['SchoolYear'] . ' ' . $task['Term']); ?>
            </span>
          </div>
          
          <div class="task-card">
            <div class="task-header">
              <div class="faculty-info">
                <div class="faculty-avatar"></div>
                <div class="faculty-details">
                  <p class="faculty-name"><?php echo htmlspecialchars($task['CreatorFirstName'] . ' ' . $task['CreatorLastName']); ?></p>
                  <p class="faculty-role"><?php echo htmlspecialchars($task['CreatorRole']); ?></p>
                </div>
              </div>
              <div class="deadline">
                <p>Deadline: <?php echo date("F j, g:i a", strtotime($task['DueDate'])); ?></p>
              </div>
            </div>
            <div class="task-content">
              <p>"<?php echo htmlspecialchars($task['Description']); ?>"</p>
              <?php if (!empty($task['RevisionReason'])): ?>
                <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h4 class="font-medium text-yellow-800 mb-2">Revision Requested</h4>
                    <p class="text-yellow-700"><?php echo nl2br(htmlspecialchars($task['RevisionReason'])); ?></p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="approval-section">
            <div class="approval-header">
              <h3>Approval Status</h3>
              <div class="complete-label">Complete: <?php echo $task['CompletedAssignments']; ?>/<?php echo $task['TotalAssignments']; ?></div>
            </div>
            
            <?php if (!empty($task['Assignments'])): ?>
              <?php foreach ($task['Assignments'] as $assignment): ?>
                <div class="course-card <?php echo $assignment['AssignmentStatus'] == 'Completed' ? 'completed' : ($assignment['AssignmentStatus'] == 'Submitted' ? 'submitted' : 'pending'); ?>">
                  <div class="course-info">
                    <p class="course-name"><?php echo htmlspecialchars($assignment['CourseCode'] . ' ' . $assignment['CourseTitle']); ?></p>
                    <div class="course-badges">
                      <span class="badge"></span>
                      <span class="badge"></span>
                    </div>
                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($assignment['ProgramName']); ?></p>
                    <p class="text-xs text-gray-600">Assigned to: <?php echo !empty($assignment['AssignedTo']) ? htmlspecialchars($assignment['AssignedTo']) : 'No assigned professor'; ?></p>
                  </div>
                  <div class="status">
                    <span class="status-label <?php echo strtolower($assignment['AssignmentStatus']); ?>">
                      <?php echo $assignment['AssignmentStatus']; ?>
                    </span>
                    
                    <?php if ($assignment['AssignmentStatus'] == 'Completed'): ?>
                      <p class="signed-by">Signed by: <?php echo htmlspecialchars($assignment['ApprovedBy']); ?></p>
                      <p class="text-xs text-gray-500"><?php echo date("M j, Y", strtotime($assignment['ApprovalDate'])); ?></p>
                    <?php elseif ($assignment['AssignmentStatus'] == 'Submitted' && $userRole == 'Dean'): ?>
                      <form method="POST" action="" class="mt-1">
                        <input type="hidden" name="task_assignment_id" value="<?php echo $assignment['TaskAssignmentID']; ?>">
                        <button type="submit" name="approve_task" class="approval-btn">
                          Approve
                        </button>
                      </form>
                    <?php elseif ($assignment['AssignmentStatus'] == 'Submitted'): ?>
                      <p class="text-xs text-gray-500">Submitted: <?php echo date("M j, Y", strtotime($assignment['SubmissionDate'])); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-center text-gray-500 my-4">No courses assigned to this task.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <div class="files-section">
      <h3>Your files</h3>
      
      <?php if (!empty($tasks) && $userRole != 'Dean'): ?>
        <?php 
        $uploadableTasks = [];
        foreach ($tasks as $task) {
            foreach ($task['Assignments'] as $assignment) {
                if ($assignment['AssignmentStatus'] != 'Completed') {
                    $uploadableTasks[] = [
                        'taskID' => $task['TaskID'],
                        'taskTitle' => $task['Title'],
                        'courseCode' => $assignment['CourseCode'],
                        'courseTitle' => $assignment['CourseTitle'],
                        'programID' => $assignment['ProgramID'],
                        'status' => $assignment['AssignmentStatus'],
                        'submissionPath' => $assignment['SubmissionPath']
                    ];
                }
            }
        }
        ?>
        
        <?php if (!empty($uploadableTasks)): ?>
          <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
            <div class="mb-3">
              <label class="block mb-1 font-medium">Select Task:</label>
              <select name="task_selector" id="taskSelector" class="w-full p-2 border rounded" onchange="updateTaskSelection()">
                <option value="">-- Select Task --</option>
                <?php foreach ($uploadableTasks as $index => $task): ?>
                  <option value="<?php echo $index; ?>">
                    <?php echo htmlspecialchars($task['taskTitle'] . ' - ' . $task['courseCode']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div id="uploadFormFields" class="hidden">
              <input type="hidden" name="task_id" id="taskID">
              <input type="hidden" name="course_code" id="courseCode">
              <input type="hidden" name="program_id" id="programID">
              
              <div class="file-input-container">
                <label for="taskFile" class="file-input-label">
                  <i class="fas fa-cloud-upload-alt text-2xl text-blue-500 mb-2"></i>
                  <p>Drag and drop your file here or click to browse</p>
                </label>
                <input type="file" name="task_file" id="taskFile" class="file-input" onchange="displayFileName()">
              </div>
              
              <div id="selectedFile" class="selected-file hidden">
                <span id="fileName" class="file-name"></span>
                <span class="remove-file" onclick="removeFile()">
                  <i class="fas fa-times"></i>
                </span>
              </div>
              
              <div id="filePreview" class="file-preview hidden"></div>
              
              <button type="submit" name="submit_file" class="submit-btn" id="submitBtn" disabled>
                Submit and Sign
              </button>
            </div>
          </form>
          
          <script>
  function updateTaskSelection() {
    const selector = document.getElementById('taskSelector');
    const uploadForm = document.getElementById('uploadFormFields');
    const submitBtn = document.getElementById('submitBtn');
    
    if (selector.value === '') {
      uploadForm.classList.add('hidden');
      return;
    }
    
    // Get the selected task data
    const taskData = <?php echo json_encode($uploadableTasks); ?>[selector.value];
    
    // Set hidden fields
    document.getElementById('taskID').value = taskData.taskID;
    document.getElementById('courseCode').value = taskData.courseCode;
    document.getElementById('programID').value = taskData.programID;
    
    // If there's already a submission, show it
    const filePreview = document.getElementById('filePreview');
    if (taskData.submissionPath) {
      // Extract file name from path
      const pathParts = taskData.submissionPath.split('/');
      const fileName = pathParts[pathParts.length - 1];
      
      document.getElementById('fileName').textContent = fileName;
      document.getElementById('selectedFile').classList.remove('hidden');
      
      // Show file preview based on extension
      const extension = fileName.split('.').pop().toLowerCase();
      if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        filePreview.innerHTML = `<img src="${taskData.submissionPath}" class="image-preview">`;
        filePreview.classList.remove('hidden');
      } else if (extension === 'pdf') {
        filePreview.innerHTML = `<embed src="${taskData.submissionPath}" type="application/pdf" class="pdf-preview">`;
        filePreview.classList.remove('hidden');
      } else {
        filePreview.innerHTML = `<div class="doc-preview">
          <i class="fas fa-file-alt text-4xl text-blue-500"></i>
          <p class="mt-2">Preview not available for this file type</p>
        </div>`;
        filePreview.classList.remove('hidden');
      }
    } else {
      document.getElementById('selectedFile').classList.add('hidden');
      filePreview.classList.add('hidden');
    }
    
    // Show upload form
    uploadForm.classList.remove('hidden');
  }
  
  function displayFileName() {
    const fileInput = document.getElementById('taskFile');
    const fileName = document.getElementById('fileName');
    const selectedFile = document.getElementById('selectedFile');
    const submitBtn = document.getElementById('submitBtn');
    
    if (fileInput.files.length > 0) {
      fileName.textContent = fileInput.files[0].name;
      selectedFile.classList.remove('hidden');
      submitBtn.disabled = false;
      
      // Preview file if possible
      previewFile(fileInput.files[0]);
    } else {
      selectedFile.classList.add('hidden');
      submitBtn.disabled = true;
    }
  }
  
  function removeFile() {
    const fileInput = document.getElementById('taskFile');
    const selectedFile = document.getElementById('selectedFile');
    const submitBtn = document.getElementById('submitBtn');
    
    selectedFile.classList.add('hidden');
    fileInput.value = '';
    document.getElementById('filePreview').classList.add('hidden');
    submitBtn.disabled = true;
  }
  
  function previewFile(file) {
    const filePreview = document.getElementById('filePreview');
    const fileType = file.type;
    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    
    if (validImageTypes.includes(fileType)) {
      const reader = new FileReader();
      reader.onload = function(e) {
        filePreview.innerHTML = `<img src="${e.target.result}" class="image-preview">`;
        filePreview.classList.remove('hidden');
      }
      reader.readAsDataURL(file);
    } else if (fileType === 'application/pdf') {
      const objectUrl = URL.createObjectURL(file);
      filePreview.innerHTML = `<embed src="${objectUrl}" type="application/pdf" class="pdf-preview">`;
      filePreview.classList.remove('hidden');
    } else {
      filePreview.innerHTML = `<div class="doc-preview">
        <i class="fas fa-file-alt text-4xl text-blue-500"></i>
        <p class="mt-2">Preview not available for this file type</p>
      </div>`;
      filePreview.classList.remove('hidden');
    }
  }

  // Add event listener for task selector change
  document.addEventListener('DOMContentLoaded', function() {
    // Check if there are tasks
    const taskSelector = document.getElementById('taskSelector');
    if (taskSelector) {
      taskSelector.addEventListener('change', updateTaskSelection);
    }
  });
</script>
        <?php else: ?>
          <div class="text-center py-8">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
            <p>All your tasks are completed!</p>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-center py-8">
          <i class="fas fa-info-circle text-4xl text-blue-500 mb-3"></i>
          <?php if ($userRole == 'Dean'): ?>
            <p>As Dean, you can create tasks and approve them when submitted.</p>
            <p class="mt-3 text-sm text-gray-600">Use the create task button to add new tasks.</p>
          <?php else: ?>
            <p>No tasks available yet.</p>
            <p class="mt-3 text-sm text-gray-600">Tasks assigned to you will appear here.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <?php if ($userRole == 'Dean'): ?>
  <div class="task-floating-button">
    <button onclick="location.href='create_task.php'">
      <i class="fas fa-plus"></i> Create Task
    </button>
  </div>
  <?php endif; ?>

  <script>
    // Add event listener for task selector change
    document.addEventListener('DOMContentLoaded', function() {
      // Check if there are tasks
      const taskSelector = document.getElementById('taskSelector');
      if (taskSelector) {
        taskSelector.addEventListener('change', updateTaskSelection);
      }
      
      // Real-time status updates for tasks (for demonstration - in production, you'd use WebSockets or AJAX)
      setInterval(function() {
        // This would normally check for updates from the server
        console.log('Checking for task updates...');
      }, 30000); // Every 30 seconds
    });

    // Function to toggle task details
    function toggleTaskDetails(taskId) {
      const detailsSection = document.getElementById('task-details-' + taskId);
      if (detailsSection.classList.contains('hidden')) {
        detailsSection.classList.remove('hidden');
      } else {
        detailsSection.classList.add('hidden');
      }
    }

    // Function to handle task notifications (for future implementation)
    function notifyTaskUpdate(taskId, status) {
      // This would show a notification when a task is updated
      const notification = document.createElement('div');
      notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded';
      notification.innerText = `Task ${taskId} has been ${status}`;
      document.body.appendChild(notification);
      
      setTimeout(function() {
        notification.remove();
      }, 3000);
    }
  </script>
</body>
</html>