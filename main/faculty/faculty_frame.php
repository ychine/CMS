<?php 
session_start(); 
if (!isset($_SESSION['Username'])) { 
    header("Location: ../index.php"); 
    exit(); 
} 

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$accountID = $_SESSION['AccountID']; 

$userRole = "";

$userRoleQuery = "SELECT Role FROM personnel WHERE AccountID = ?";
$roleStmt = $conn->prepare($userRoleQuery);
$roleStmt->bind_param("i", $accountID);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $userRole = $roleResult->fetch_assoc()['Role'];
}
$roleStmt->close();

$facultyName = "Faculty"; 
$members = [];




$sql = "SELECT personnel.FacultyID, faculties.Faculty, faculties.JoinCode 
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
    $joinCode = $row['JoinCode'] ?? '';

    
    
    $memberQuery = "SELECT FirstName, LastName, Role, AccountID 
                FROM personnel 
                WHERE FacultyID = ? 
                ORDER BY 
                    CASE Role 
                        WHEN 'DN' THEN 1 
                        WHEN 'PH' THEN 2 
                        WHEN 'FM' THEN 3 
                        ELSE 4 
                    END";
    $memberStmt = $conn->prepare($memberQuery);
    $memberStmt->bind_param("i", $facultyID);
    $memberStmt->execute();
    $memberResult = $memberStmt->get_result();

    while ($memberRow = $memberResult->fetch_assoc()) {
        $members[] = $memberRow;
    }
    $memberStmt->close();
} else {
    $facultyName = "No Faculty Assigned"; 
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }
        
        /* Custom style for dropdown */
        .role-select {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            font-weight: 500;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1em;
            padding-right: 2.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #4b5563;
        }
        .role-select:hover {
            background-color: #e5e7eb; 
            border-color: #4a84f1;
        }

        .role-select:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        
        .actions-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .delete-button {
            background-color: #f3f4f6;
            border-radius: 6px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #6b7280;
        }
        
        .delete-button:hover {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
    <h1 class="py-[5px] text-[35px] tracking-tight font-overpass font-bold flex items-center gap-4">Members of 
            <?php echo htmlspecialchars($facultyName); ?> 

            <?php if (!empty($joinCode) && $userRole === 'DN'): ?>
                    <div onclick="copyJoinCode()" class="flex items-center gap-2 bg-gray-100 text-gray-800 text-sm font-semibold px-3 py-1 rounded-md inline-flex cursor-pointer hover:bg-gray-400 transition">
                        <span id="join-code"><?php echo htmlspecialchars($joinCode); ?></span>
                        <!-- Copy icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 hover:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8l4 4v8a2 2 0 01-2 2h-2M8 16v4a2 2 0 002 2h6" />
                        </svg>
                    </div>
            <?php endif; ?>

        </h1>
        <hr class="border-gray-400">
        <?php if ($userRole === 'DN'): ?>
            <p class="text-gray-500 mt-3 mb-5 font-onest">Here you can view, delete, and change the roles of your faculty members.</p>
        <?php else: ?>
            <p class="text-gray-500 mt-3 mb-5 font-onest">This section displays your faculty members and their roles.</p>
        <?php endif; ?>
        <div class="grid grid-cols-1 grid-rows-3 gap-5 w-[60%]">
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                    <div class="bg-white p-[25px] font-overpass rounded-lg shadow-md flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-bold"><?php echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']); ?></h2>
                            <?php
                                $roleMap = [
                                    'FM' => 'Member',
                                    'DN' => 'Dean',
                                    'PH' => 'Program Head'
                                ];
                                $readableRole = $roleMap[$member['Role']] ?? $member['Role'];
                            ?>
                            <div class="text-sm text-gray-400"><?php echo htmlspecialchars($readableRole); ?></div>
                        </div>
                        <div class="actions-container">
    <?php if ($userRole === 'DN'): ?>
        <select class="role-select role-change-dropdown" data-account-id="<?php echo $member['AccountID']; ?>">
            <option value="" disabled selected>Select a role</option>
            <option value="DN" <?php echo $member['Role'] === 'DN' ? 'selected' : ''; ?>>DEAN</option>
            <option value="PH" <?php echo $member['Role'] === 'PH' ? 'selected' : ''; ?>>PROGRAM HEAD</option>
            <option value="FM" <?php echo $member['Role'] === 'FM' ? 'selected' : ''; ?>>MEMBER</option>
        </select>
        <button class="delete-button" data-account-id="<?php echo $member['AccountID']; ?>" title="Remove Member">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 hover:text-red-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    <?php else: ?>

    <?php endif; ?>
</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center">No members found in your faculty.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const loggedInUserRole = "<?php echo $userRole; ?>";

   document.querySelectorAll('.role-change-dropdown').forEach(select => {
    let previousValue = select.value;

    select.addEventListener('mousedown', () => {
        previousValue = select.value;
    });

    select.addEventListener('change', function () {
        const accountId = this.getAttribute('data-account-id');
        const newRole = this.value;
        const isDeanTransfer = (previousValue === 'DN' || newRole === 'DN');

        if (loggedInUserRole === 'DN' && isDeanTransfer && newRole === 'DN') {
            // Special confirmation for dean transfer
            Swal.fire({
                title: 'Transfer Deanship?',
                html: '<div class="text-left">' +
                      '<p class="mb-2">You are about to transfer your Dean role to another member.</p>' +
                      '<p class="font-semibold text-red-600">This action will:</p>' +
                      '<ul class="list-disc pl-5 mt-2 space-y-1">' +
                      '<li>Make you a regular faculty member</li>' +
                      '<li>Transfer all dean privileges to the selected member</li>' +
                      '</ul>' +
                      '<p class="mt-3">Are you sure you want to proceed?</p>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, transfer deanship',
                cancelButtonText: 'Cancel',
                focusCancel: true,
                customClass: {
                    popup: 'text-left',
                    htmlContainer: 'text-left'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    processRoleChange(accountId, newRole);
                } else {
                    select.value = previousValue;
                }
            });
        } else {
            // Regular role change confirmation
            Swal.fire({
                title: 'Change Role?',
                text: "Are you sure you want to update this member's role?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#51D55A',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    processRoleChange(accountId, newRole);
                } else {
                    select.value = previousValue;
                }
            });
        }
    });
});


document.querySelectorAll('.role-change-dropdown').forEach(select => {
    if (loggedInUserRole !== 'DN') {
        select.style.display = 'none'; // Hide the dropdown
    }
});

document.querySelectorAll('.delete-button').forEach(button => {
    if (loggedInUserRole !== 'DN') {
        button.style.display = 'none'; // Hide the delete button
    }
});

function processRoleChange(accountId, newRole) {
    fetch('update_role.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ accountId, newRole }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.roleSwapped) {
                Swal.fire({
                    title: 'Deanship Transferred!',
                    html: '<div class="text-left">' +
                          '<p>You are no longer the Dean of this faculty.</p>' +
                          '<p class="mt-2">Your role has been changed to Faculty Member.</p>' +
                          '</div>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Success', data.message, 'success').then(() => {
                    location.reload();
                });
            }
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Failed to update role: ' + error, 'error');
    });
}

    document.querySelectorAll('.delete-button').forEach(el => {
        el.addEventListener('click', function () {
            const accountId = this.getAttribute('data-account-id');

            Swal.fire({
                title: 'Remove Member?',
                text: 'Are you sure you want to remove this member from the faculty?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, remove',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ accountId }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire('Removed!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Failed to remove member: ' + error, 'error');
                    });
                }
            });
        });
    });
        function copyJoinCode() {
        const code = document.getElementById('join-code').innerText;
        navigator.clipboard.writeText(code).then(() => {
            // Optional feedback
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Join code has been copied to clipboard.',
                timer: 1500,
                showConfirmButton: false
            });
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
    </script>
</body>
</html>