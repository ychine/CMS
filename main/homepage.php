<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Greeting setup
$salutation = $_SESSION['Salutation'] ?? '';
$lastName = $_SESSION['LastName'] ?? '';
$greeting = "Good day";
if (!empty($salutation) && !empty($lastName)) {
    $greeting .= ", {$salutation} {$lastName}!";
} else {
    $greeting .= "!";
}

$accountID = $_SESSION['AccountID'];

// Get user's role and faculty status
$query = "SELECT Role, FacultyID FROM personnel WHERE AccountID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

$dashboardPage = "";
$showFacultyPopup = false;

if ($row = $result->fetch_assoc()) {
    if (empty($row['FacultyID'])) {
        $showFacultyPopup = true;
    }

    if (!empty($row['Role'])) {
        if ($row['Role'] === 'DN') {
            $dashboardPage = "dashboard/dn-dash.php";
        } elseif ($row['Role'] === 'PH') {
            $dashboardPage = "dashboard/ph-dash.php";
        }
    }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="../src/tailwind/output.css" rel="stylesheet" />
    <link href="../src/styles.css" rel="stylesheet" />
    <title>Home | Coursedock</title>
    <link href="../img/cdicon.svg" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@200;300;400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }

        /* Sidebar */

        #sidebar {
            transition: width 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

       
        .collapsed #logo, 
        .collapsed #logo-text {
            visibility: hidden;  
        }

      
        .collapsed .link-text {
            display: none;      
        }

       
        .collapsed {
            width: 80px;
            align-items: center;
        }

        #toggleSidebar {
            transition: all 0.3s ease;
        }

        .collapsed #toggleSidebar {
            position: absolute;
            left: 3.5%; 
            width: ;
            background-color: #51D55A;
            color: white; 
        }

        .collapsed .menu-item {
            width: 50px; 
            height: 50px;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            padding: 0; 
            border-radius: 20%; 
         
        }


        .collapsed .menu-item img {
            width: 24px;
            height: 24px;
            margin: 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .menu-item:hover {
            background-color: #13275B;
        }

    
        .link-text {
            font-size: 16px;
            color: #E3E3E3;
            font-family: 'Onest', sans-serif;
            font-weight: 400;
            transition: opacity 0.3s ease;
        }



    </style>
</head>

<body class="w-full h-screen bg-[#020A27] px-10 pt-3 flex items-start justify-center">

    <!-- Wrapper -->
    <div class="w-full h-full flex flex-row rounded-t-[15px] overflow-hidden bg-white shadow-lg">

        <!-- Sidebar -->
        <div id="sidebar" class="w-[290px] bg-[#1D387B] text-white p-3 pt-5 flex flex-col transition-all duration-300 ease-in-out">
            <div class="text-left leading-tight mb-4 ml-2 font-onest flex items-center justify-between">
                <div class="flex flex-col items-start">
                    <img src="../img/COURSEDOCK.svg" class="w-[180px] transition-all duration-300" id="logo" />
                    <p class="text-[10px] font-light transition-all duration-300" id="logo-text">Courseware Monitoring System</p>
                </div>

                <button id="toggleSidebar" class="ml-3 bg-[#1D387B] border-2 border-[#2A4484] w-[30px] h-[30px] rounded-full flex items-center justify-center shadow-md transition-transform">
                    <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="p-2 flex flex-col gap-[8px]">
                <div class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/dashboard.png" alt="Dashboard" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Dashboard</span>
                </div>
                <div class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/materials-icon.png" alt="Tasks" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Tasks</span>
                </div>
                <div class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/notification-icon.png" alt="Inbox" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Inbox</span>
                </div>
                <div class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/faculty-icon.png" alt="Faculty" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Faculty</span>
                </div>
                <div class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/materials-icon.png" alt="Curriculum Materials" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Curriculum Materials</span>
                </div>
            </div>


            <button class="mt-auto bg-green-600 hover:bg-green-800 text-white px-4 py-3 rounded-md text-lg font-bold transition">
                + Create
            </button>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-full">
            <!-- Topbar -->
            <div class="bg-white px-[50px] py-[20px] h-[67px] flex justify-between items-center w-full box-border" style="box-shadow: 0 3px 4px 0 rgba(0, 0, 0, 0.3);">
                <div class="font-onest text-[24px] font-normal" style="letter-spacing: -0.03em;">
                    <?php echo htmlspecialchars($greeting); ?>
                </div>
                <div class="font-poppins text-[24px] font-semibold">Profile</div>
            </div>

            <!-- Dynamic Content -->
            <iframe src="<?php echo htmlspecialchars($dashboardPage); ?>" class="w-full flex-1" frameborder="0"></iframe>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const chevronIcon = document.getElementById('chevronIcon');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            chevronIcon.classList.toggle('rotate-180');
        });
    </script>
</body>
</html>
