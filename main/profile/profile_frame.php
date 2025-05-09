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
$username = $_SESSION['Username'];

// Fetch user details
$sql = "SELECT p.PersonnelID, p.FirstName, p.LastName, p.Gender, p.Role, 
        a.Username, a.Email, f.Faculty, f.FacultyID  
        FROM personnel p
        JOIN accounts a ON p.AccountID = a.AccountID
        LEFT JOIN faculties f ON p.FacultyID = f.FacultyID
        WHERE a.AccountID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $facultyID = $user['FacultyID'];
    $facultyName = $user['Faculty'] ?? "No Faculty Assigned";
    $fullName = $user['FirstName'] . ' ' . $user['LastName'];
    $email = $user['Email'];
    $role = $user['Role'];

    // Map role codes to descriptive names
    $roleNames = [
        'DN' => 'Dean',
        'PH' => 'Program Head',
        'COR' => 'Courseware Coordinator',
        'FM' => 'Faculty Member',
        'user' => 'User'
    ];
    
    $roleDisplay = isset($roleNames[$role]) ? $roleNames[$role] : $role;

    // Fetch the list of members within the same faculty
    if ($facultyID) {
        $memberQuery = "SELECT FirstName, LastName, Role 
                        FROM personnel 
                        WHERE FacultyID = ?";
        $memberStmt = $conn->prepare($memberQuery);
        $memberStmt->bind_param("i", $facultyID);
        $memberStmt->execute();
        $memberResult = $memberStmt->get_result();

        $members = [];
        while ($memberRow = $memberResult->fetch_assoc()) {
            $members[] = $memberRow;
        }

        $memberStmt->close();
    }
} else {
    // Handle error - user not found
    $fullName = "User Not Found";
    $email = $username;
    $facultyName = "No Faculty Assigned";
    $roleDisplay = "Unknown";
    $members = [];
}

// Process password change if form submitted
$passwordMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verify current password
    $checkPasswordQuery = "SELECT Password FROM accounts WHERE AccountID = ?";
    $checkStmt = $conn->prepare($checkPasswordQuery);
    $checkStmt->bind_param("i", $accountID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $userData = $checkResult->fetch_assoc();
    
    if (password_verify($currentPassword, $userData['Password'])) {
        if ($newPassword === $confirmPassword) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updateQuery = "UPDATE accounts SET Password = ? WHERE AccountID = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $hashedPassword, $accountID);
            
            if ($updateStmt->execute()) {
                $passwordMessage = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4" role="alert">
                                      <p>Password updated successfully!</p>
                                    </div>';
            } else {
                $passwordMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4" role="alert">
                                      <p>Error updating password. Please try again.</p>
                                    </div>';
            }
            $updateStmt->close();
        } else {
            $passwordMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4" role="alert">
                                  <p>New password and confirm password do not match.</p>
                                </div>';
        }
    } else {
        $passwordMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4" role="alert">
                              <p>Current password is incorrect.</p>
                            </div>';
    }
    $checkStmt->close();
}

// Process profile update
$profileMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    
    // Update profile information
    $updateProfileQuery = "UPDATE personnel p JOIN accounts a ON p.AccountID = a.AccountID 
                          SET p.FirstName = ?, p.LastName = ?, a.Email = ? 
                          WHERE a.AccountID = ?";
    $updateProfileStmt = $conn->prepare($updateProfileQuery);
    $updateProfileStmt->bind_param("sssi", $firstName, $lastName, $email, $accountID);
    
    if ($updateProfileStmt->execute()) {
        $profileMessage = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4" role="alert">
                            <p>Profile updated successfully!</p>
                          </div>';
        // Update session data
        $fullName = $firstName . ' ' . $lastName;
    } else {
        $profileMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4" role="alert">
                            <p>Error updating profile. Please try again.</p>
                          </div>';
    }
    $updateProfileStmt->close();
}

// Handle account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    // Delete account (you might want to add a confirmation dialog in JS)
    $deleteQuery = "DELETE FROM accounts WHERE AccountID = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $accountID);
    
    if ($deleteStmt->execute()) {
        // Logout and redirect
        session_destroy();
        header("Location: ../index.php?msg=account_deleted");
        exit();
    }
    $deleteStmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
        }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }
        .profile-avatar {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            border-radius: 50%;
        }
        .form-input {
            border: 1px solid #e2e8f0;
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            width: 100%;
            transition: border-color 0.2s ease-in-out;
        }
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .btn-edit {
            background-color: #4f46e5;
            color: white;
        }
        .btn-edit:hover {
            background-color: #4338ca;
        }
        .btn-delete {
            background-color: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background-color: #dc2626;
        }
        .section-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 1.5rem;

        }
        body.dark {
            background: #18181b !important;
            color: #f3f4f6 !important;
        }
        .dark .bg-white {
            background: #23232a !important;
            color: #f3f4f6 !important;
        }
        .dark .shadow-lg, .dark .shadow-2xl {
            box-shadow: 0 4px 24px rgba(0,0,0,0.32) !important;
        }
        .dark .text-gray-800, .dark .text-gray-700, .dark .text-gray-600 {
            color: #e5e7eb !important;
        }
        .dark .text-gray-500 {
            color: #a1a1aa !important;
        }
        .dark .border-gray-300, .dark .border {
            border-color: #374151 !important;
        }
        .dark .bg-blue-50, .dark .bg-blue-100 {
            background: #1e293b !important;
        }
        .dark .file-input-label {
            background: #23232a !important;
            border-color: #374151 !important;
            color: #e5e7eb !important;
        }
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] pb-[50px] overflow-y-auto">
        <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold">Edit Profile</h1> 
        <hr class="border-gray-400">
        <p class="text-gray-500 mt-3 mb-5 font-onest">Manage your personal information and account settings.</p>
        
        <!-- User Profile Header -->
        <div class="section-card">
            <div class="flex items-center">
                <div class="profile-avatar mr-6 w-8 h-8 rounded-full bg-[#1D387B] text-white flex items-center justify-center ml-2">
                    <?php echo strtoupper(substr($fullName, 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold font-overpass"><?php echo htmlspecialchars($fullName); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($roleDisplay); ?> - <?php echo htmlspecialchars($facultyName); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Personal Information Section -->
        <div class="section-card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold font-overpass">Personal Information</h2>
                <button id="toggleEditProfile" class="btn btn-edit flex items-center">
                    <i class="fas fa-pen-to-square mr-2"></i> Edit
                </button>
            </div>
            
            <?php echo $profileMessage; ?>
            
            <div id="profileDisplay" class="bg-gray-50 rounded-lg p-6">
                <div class="mb-4">
                    <p class="text-gray-600 mb-1">Name</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($fullName); ?></p>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600 mb-1">Username</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($username); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-1">Email</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>
            
            <form id="profileForm" action="" method="post" class="hidden">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-4">
                        <label for="firstName" class="block text-gray-600 mb-2">First Name</label>
                        <input type="text" id="firstName" name="firstName" class="form-input" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="lastName" class="block text-gray-600 mb-2">Last Name</label>
                        <input type="text" id="lastName" name="lastName" class="form-input" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-600 mb-2">Email</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="button" id="cancelProfileEdit" class="btn bg-gray-300 text-gray-700 mr-2 hover:bg-gray-400">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-edit">Save Changes</button>
                </div>
            </form>
        </div>
        
        <!-- Change Password Section -->
        <div class="section-card">
            <h2 class="text-xl font-bold font-overpass mb-6">Change Password</h2>
            
            <?php echo $passwordMessage; ?>
            
            <form action="" method="post">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-600 mb-2">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-600 mb-2">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-600 mb-2">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" name="change_password" class="btn btn-edit">Update Password</button>
                </div>
            </form>
        </div>
        
        <!-- Account Management Section -->
        <div class="section-card">
            <h2 class="text-xl font-bold font-overpass mb-6">Account Management</h2>
            <div class="bg-gray-50 rounded-lg p-6 flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h4 class="text-lg font-semibold text-red-600">Delete Account</h4>
                    <p class="text-gray-600">Once you delete your account, there is no going back. Please be certain.</p>
                </div>
                <form action="" method="post" onsubmit="return confirmDelete()">
                    <button type="submit" name="delete_account" class="btn btn-delete flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle profile edit form
        document.getElementById('toggleEditProfile').addEventListener('click', function() {
            document.getElementById('profileDisplay').classList.add('hidden');
            document.getElementById('profileForm').classList.remove('hidden');
        });
        
        document.getElementById('cancelProfileEdit').addEventListener('click', function() {
            document.getElementById('profileForm').classList.add('hidden');
            document.getElementById('profileDisplay').classList.remove('hidden');
        });
        
        
        document.getElementById('new_password').addEventListener('input', function() {
            
        });
        
        // Confirm delete account
        function confirmDelete() {
            return confirm("Are you sure you want to delete your account? This action cannot be undone.");
        }

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>