<?php
session_start();

if (!isset($_SESSION['AccountID'])) {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$facultyName = $_POST['faculty_name'];
$facultyCode = $_POST['faculty_code'];
$accountID = $_SESSION['AccountID'];

$stmt = $conn->prepare("INSERT INTO faculties (Faculty, JoinCode) VALUES (?, ?)");

$stmt->bind_param("ss", $facultyName, $facultyCode);

if ($stmt->execute()) {
 
    $newFacultyID = $conn->insert_id;

    $updatePersonnel = "UPDATE personnel SET FacultyID = ?, Role = 'DN' WHERE AccountID = ?";
    $stmt2 = $conn->prepare($updatePersonnel);
    $stmt2->bind_param("ii", $newFacultyID, $accountID);
    $stmt2->execute();
    $stmt2->close();


    $_SESSION['Role'] = 'DN';

    // Close the connection
    $stmt->close();
    $conn->close();

    // Return success to hide the popup, load the Dean's dashboard, and show toast
    echo "<script>
    
            document.getElementById('showFacultyPopup').style.display = 'none';

            document.querySelector('iframe').src = 'dn-dash.php';

            var toast = document.createElement('div');
            toast.className = 'toast success fade-in';
            toast.innerHTML = 'Faculty created successfully!';
            document.body.appendChild(toast);

  
            setTimeout(function() {
                toast.classList.remove('fade-in');
                toast.classList.add('fade-out');
                setTimeout(function() {
                    toast.remove();
                }, 500); 
            }, 3000);
          </script>";
    exit();

} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
