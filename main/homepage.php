<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$notificationQuery = "SELECT COUNT(*) as count FROM notifications WHERE AccountID = ? AND is_read = 0";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("i", $accountID);
$notificationStmt->execute();
$notificationResult = $notificationStmt->get_result();
$notificationCount = $notificationResult->fetch_assoc()['count'];
$notificationStmt->close();

if (isset($_SESSION['show_join_form'])) {
    $showFacultyPopup = true;
    $autoShowJoinForm = true;
    unset($_SESSION['show_join_form']);
} else {
    $autoShowJoinForm = false;
}

if (isset($_SESSION['joined_faculty_success'])) {
    unset($_SESSION['joined_faculty_success']);
}


$accountID = $_SESSION['AccountID'];

$userQuery = "SELECT FirstName, LastName FROM personnel WHERE AccountID = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $accountID);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userInfo = $userResult->fetch_assoc();
$userStmt->close();

$query = "SELECT Role, FacultyID FROM personnel WHERE AccountID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $accountID);
$stmt->execute();
$result = $stmt->get_result();

$dashboardPage = "";
$showFacultyPopup = false;
$facultyName = '';
$userRole = '';

if ($row = $result->fetch_assoc()) {
    if (empty($row['FacultyID'])) {
        $showFacultyPopup = true;
    } else {
       
        $facultyQuery = "SELECT Faculty FROM faculties WHERE FacultyID = ?";
        $facultyStmt = $conn->prepare($facultyQuery);
        $facultyStmt->bind_param("i", $row['FacultyID']);
        $facultyStmt->execute();
        $facultyResult = $facultyStmt->get_result();
        if ($facultyRow = $facultyResult->fetch_assoc()) {
            $facultyName = strtoupper($facultyRow['Faculty']);
        }
        $facultyStmt->close();
    }

    if (!empty($row['Role'])) {
        // Set the role text based on the role code
        if ($row['Role'] === 'DN') {
            $dashboardPage = "dashboard/dn-dash.php";
            $userRole = "College Dean";
        } elseif ($row['Role'] === 'PH') {
            $dashboardPage = "dashboard/ph-dash.php";
            $userRole = "Program Head";
        } elseif ($row['Role'] === 'FM') {
            $dashboardPage = "dashboard/fm-dash.php";
            $userRole = "Faculty Member";
        }
        elseif ($row['Role'] === 'COR') {
            $dashboardPage = "dashboard/ph-dash.php";
            $userRole = "Courseware Coordinator";
        }
        elseif ($row['Role'] === 'USER') {
            $dashboardPage = "dashboard/fm-dash.php";
            $userRole = "New User";
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
    <link href="../src/sidebar.css" rel="stylesheet" />
    <title>Home | CourseDock</title>
        
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@200;300;400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }

        .user-info {
        text-align: right;
        padding: 6px 10px 6px 20px;
        border-radius: 8px;
        transition: all 0.2s;
        }

        .user-info:hover {
            background-color: #f9fafb;
          
        }
        
        .profile-container {
          position: relative;
          user-select: none;
        
        }

        
        .profile-container:hover .profile-dropdown {
          display: block;
        }
        
        .profile-dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        font-size: 14px;
        color: #4b5563;
        transition: all 0.2s;
        text-decoration: none;
        }
        
        .profile-dropdown-item:hover {
          background-color: #f9fafb;
        }

        .profile-dropdown-item:first-child {
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        }

        .profile-dropdown-item:last-child {
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
        }


        #userMenu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            width: 200px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 50;
            transform-origin: top right;
            transition: opacity 0.2s, transform 0.2s;
        }

        #userMenu.hidden {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
        pointer-events: none;
        }

        #userMenu:not(.hidden) {
        opacity: 1;
        transform: translateY(0) scale(1);
        }

        .back-button {
        cursor: pointer;
        transition: all 0.2s ease;
        background: transparent;
        border: none;
        padding: 4px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        }

        .back-button:hover {
            transform: translateX(-2px);
            background: rgba(255, 255, 255, 0.1);
        }

        .back-button:active {
            transform: translateX(-2px) scale(0.95);
        }

        .back-button svg {
            transition: all 0.2s ease;
        }

        .back-button:hover svg {
            transform: scale(1.1);
        }

        .collapsed .create-text {
            display: none;
        }

    
        .collapsed #createButton {
            justify-content: center;
            gap: 0;
            padding: 14px; 
            border-radius: 30%;
            transition: all 0.5s ease-in-out;
            transition: all 1s ease-in-out;
        }

      
        .collapsed .sidebar-footer {
            display: none;
            transition: all 1s ease-in-out;
        }
                
        /* DARK MODE STYLES */
        body.dark {
            background-color: #324f96;
        }

        .dark .bg-white {
            background: #2d3036 !important;
            color: #fff;
        }
        .dark .hover\:bg-gray-100:hover {
            background: #35373c !important;
        }
        .dark .header-bar {
            background: #2d3036 !important;
            color: #fff;
        }
        .dark .profile-dropdown-item svg {
            color: #e3e3e3 !important;
        }   
        .dark .sidebar {
            background: #23252b !important;
            color: #e3e3e3;
        }
        .dark .main-content {
            background: #1a1c20 !important;
            color: #fff;
        }
        .dark .profile-dropdown-bg {
            background: #23252b !important;
            border-color: #35373c !important;
        }
        .dark .profile-dropdown-item {  
            color: #e3e3e3 !important;
        }
        .dark .profile-dropdown-item:hover {
            background: #35373c !important;
            color: #fff !important;
        }
        .dark .profile-dropdown-item.text-red-500 {
            color: #ff6b6b !important;
        }
        .dark .user-info:hover {
            background: #23252b !important;
        }
        .dark .text-gray-600, .dark .text-gray-500 {
            color: #e3e3e3 !important;
        }

        .dark .link-text, .dark .font-onest, .dark .font-overpass {
            color: #e3e3e3 !important;
        }

        .menu-item {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: none;
            border: 1px solid #2A4484;
        }

        .menu-item:hover {
            background: #1D387B !important;
            box-shadow: -5px -5px 15px rgba(81, 213, 90, 0.3),
                        5px 5px 15px rgba(0, 0, 0, 0.5);
            transform: translateY(-1px);
       
        }

        .menu-item:active {
            transform: translateY(0);
            box-shadow: -3px -3px 10px rgba(81, 213, 90, 0.2),
                        3px 3px 10px rgba(0, 0, 0, 0.3);
            border-color: #51D55A;
            border-width: 2px;
        }

        .menu-item.active {
            background: #1D387B !important;
            box-shadow: -5px -5px 15px rgba(81, 213, 90, 0.3),
                        5px 5px 15px rgba(0, 0, 0, 0.5);
            border-color: #51D55A;
            border-width: 2px;
        }

        .menu-item .link-text {
            position: relative;
            z-index: 2;
        }

        .menu-item img {
            position: relative;
            z-index: 2;
        }

        .notification-btn {
            width: 50%;
            border-radius: 30px;
            transition: all 0.3s ease;
            outline: none !important;
            -webkit-tap-highlight-color: transparent;
        }

        .notification-btn:hover {
            background-color: white;
            box-shadow: -3px -3px 10px rgba(81, 213, 90, 0.2),
                        3px 3px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-1px);
        }

        .notification-btn:active {
            transform: translateY(0);
            box-shadow: -3px -3px 10px rgba(81, 213, 90, 0.2),
                        3px 3px 10px rgba(0, 0, 0, 0.3);
            border-color: #51D55A;
            border-width: 2px;
            background-color: transparent;
        }

        .notification-btn:focus {
            outline: none !important;
            box-shadow: none;
        }

       
    </style>
</head>

<body id="mainBody" class="w-full h-screen bg-[#020A27] px-3 pt-3 flex items-start justify-center">

    <!-- Wrapper -->
    <div class="w-full h-full flex flex-row rounded-t-[15px] overflow-hidden bg-gray-200 shadow-lg" >

        <!-- Sidebar -->
        <div id="sidebar" class="sidebar w-[290px] bg-[#1D387B] text-white p-3 pt-5 flex flex-col transition-all duration-300 ease-in-out">
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
                <a href="homepage.php" class="bg-[#13275B] menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/dashboard.png" alt="Dashboard" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Dashboard</span>
                </a>

                <a href="faculty/faculty.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/faculty-icon.png" alt="Faculty" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Faculty</span>
                </a>

                <a href="curriculum/curriculum.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/materials-icon.png" alt="Curriculum Materials" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Curricula</span>
                </a>

                <a href="task/tasks.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/task.png" alt="Tasks" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Tasks</span>
                </a>
                
                <?php if ($row['Role'] === 'DN'): ?>
                <a href="auditlog/audit_log.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/Audit.png" alt="Audit Log" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Audit Log</span>
                </a>
                <?php endif; ?>
            </div>


            <button id="createButton" class=" mt-auto text-white px-4 font-onest py-3 rounded-md text-lg font-regular transition-colors duration-300 flex items-center justify-between w-full">
              
            </button>



            <div class="sidebar-footer relative rounded-md m-0 w-full text-center font-overpass font-light text-[10px]  px-2 text-gray-400 mt-2 my-0 py-2">
                <hr class="border-t border-[#314f9b] w-full mx-auto mb-2" />
                © 2025 CourseDock. All rights reserved.
                <span class="mt-1">
                    <br>
                    <a href="../src/about.php" class="text-gray-400 hover:underline mx-1">About CourseDock</a>
                    <a href="../src/support.php" class="text-gray-400 hover:underline mx-1">Contact our Support</a>
                </span>
            </div>

        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col h-full ">

            <div class="header-bar bg-white px-[50px] py-[20px] h-[67px] flex justify-between items-center w-full box-border" style="box-shadow: 0 3px 4px 0 rgba(0, 0, 0, 0.3); z-index: 100;">
                <div class="font-onest text-[24px] font-semibold mt-1" style="letter-spacing: -0.03em;">
                    <?php echo htmlspecialchars($facultyName); ?>
                </div>
                    
                <div class="flex items-center gap-0">   
                    <!-- Notification Icon -->
                    <div class="relative p-[2px] z-[102]">
                    <button id="notificationButton" class="p-2 hover:bg-gray-100 transition-all duration-300 ease-in-out focus:outline-none focus:border-[#51D55A] focus:rounded-lg focus:border-2 flex items-center justify-center active:scale-95 notification-btn" style="position: relative; width: 40px; height: 40px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-600 transition-all duration-300 hover:scale-75" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?php if ($notificationCount > 0): ?>
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full transition-all duration-300"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                            <span id="notifDot" class="absolute top-1 right-1 w-3 h-3 bg-red-600 rounded-full z-50 transition-all duration-300"></span>
                        </button> 
                        <div id="notificationDropdown" class="hidden border border-gray-200  absolute right-0 mt-2 w-90 bg-white rounded-lg shadow-lg z-[100] transform transition-all duration-300 ease-in-out opacity-0 scale-95" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                            <div class="p-4 shadow-md rounded-t-lg relative z-[101] bg-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-onest font-semibold text-gray-900">Notifications</h3>
                                    <button id="clearNotifications" class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200">
                                        Clear All
                                    </button>
                                </div>
                            </div>
                            <div id="notificationList" class="max-h-96 overflow-y-auto relative z-[100] bg-white">
                                <!-- D2 LALABAS UNG NOTIFS -->
                            </div>
                            <div class="p-4 border-t border-gray-200 text-center relative z-[101] bg-white">
                                <a href="../task/tasks.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All Tasks</a>
                            </div>
                        </div>
                    </div>
             

                    <div class="profile-container relative">
                        <div class="user-info flex items-center">
                            <div class="flex items-center cursor-pointer" onclick="toggleUserMenu(event)">
                                <!-- User Avatar -->
                                <div class="flex flex-col mr-2">
                                    <span class="font-onest text-[14px] font-medium text-[#333]">
                                        <?php echo htmlspecialchars($userInfo['FirstName'] . ' ' . $userInfo['LastName']); ?>
                                    </span>
                                    <span class="font-onest text-[12px] text-[#808080] -mt-[2px]">
                                        <?php echo htmlspecialchars($userRole); ?>
                                    </span>
                                </div>

                                <!-- User Avatar -->
                                <div class="w-8 h-8 rounded-full bg-[#1D387B] text-white flex items-center justify-center ml-2">
                                    <?php 
                                    $initials = substr($userInfo['FirstName'], 0, 1) . substr($userInfo['LastName'], 0, 1);
                                    echo htmlspecialchars(strtoupper($initials)); 
                                    ?>
                                </div>
                                
                                <!-- Dropdown Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 ml-2 transition-transform duration-300" id="dropdown-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Dropdown Menu (Hidden by Default) -->
                        <div id="userMenu" class="hidden">
                            <div class="py-1 border border-gray-200 rounded-md profile-dropdown-bg">
                                <a href="profile/profile.php" class="profile-dropdown-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    View Profile
                                </a>
                                <a href="settings/settings.php" class="profile-dropdown-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </a>
                                <hr class="my-1 border-gray-200" />
                                <a href="../index.php" class="profile-dropdown-item text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Content -->
            <iframe id="contentIframe" src="<?php echo htmlspecialchars($dashboardPage); ?>" class="w-full flex-1 fade-in" frameborder="0"></iframe>

        </div>
    </div>

    <!-- for new users lang na walang faculty-->
  <?php if ($showFacultyPopup): ?>
         <div class="fixed inset-0 bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm fade-in">
        <div class="signupbox2 signinbox bg-white p-8 rounded-xl shadow-md w-full max-w-md text-center relative bg-opacity-90">

            <!-- Welcome Section -->
            <div id="welcome-section" class="flex flex-col items-center justify-center">
            <h2 class="text-xl text-amber-50 font-onest font-thin mb-1">Welcome to</h2>
            <img src="../img/COURSEDOCK.svg" class="w-[180px] mb-4" />
            </div>

            <!-- Create Faculty Title -->
            <h3 id="create-title" class="hidden text-2xl text-[#E3E3E3] font-overpass font-semibold justify-start tracking-wide mb-4">
            ❇️ Creating a Faculty
            </h3>

            <!-- Join Faculty Title -->
            <h3 id="join-title" class="hidden text-2xl text-[#E3E3E3] font-overpass font-semibold justify-start tracking-wide mb-4">
            Join with a Code
            </h3>

            <!-- Popup main menu buttons -->
            <div id="popup-menu" class="flex flex-col space-y-4">
            <p class="text-white font-normal font-onest text-[12px] mb-4">You are not currently part of any faculty.</p>

            <button onclick="showCreateForm()" class="btnlogin text-[14px]">
                Create a New Faculty
            </button>

            <div class="flex items-center justify-center mt-0 mb-2">
                <hr class="flex-grow border-t-2 border-white mx-2">
                <p class="text-white font-normal font-onest text-[12px]">or</p>
                <hr class="flex-grow border-t-2 border-white mx-2">
            </div>

            <button onclick="showJoinForm()" class="btnlogin text-[14px]">
                Join Faculty
            </button>
            </div>

            <!-- Create Faculty Form -->
            <div id="create-form" class="hidden flex flex-col space-y-4">
                <hr>
            <form action="../src/scripts/create_faculty.php" method="POST" class="space-y-4">

                <p class="subtext">Create faculty name and generate faculty code.</p> 

                <input type="text" name="faculty_name" placeholder="Faculty Name" required
                class="w-full px-3 py-2 rounded-md shadow-inner text-[12px] bg-[#13275B] text-white border border-[#304374] font-onest text-center" />


                <div class="flex flex-col items-start text-left w-full space-y-2">
                <label class="text-white text-sm font-onest">Faculty Code:</label>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                    <input type="text" name="faculty_code" id="generatedCode" readonly
                        class="w-full px-3 py-2 pr-10 rounded-md text-[12px] shadow-inner bg-[#13275B] text-white border border-[#304374] font-onest" />

                    <button type="button" onclick="copyCode()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs bg-green-600 hover:bg-green-800 text-white px-2 py-1 rounded">
                        Copy
                    </button>
                    </div>  


                    <button type="button" onclick="generateCode()"
                    class="bg-blue-600 hover:bg-blue-800 text-white px-4 py-2 rounded-md text-sm">
                    Generate
                    </button>
                </div>
                     <p class="text-[10px] subtext ">Note: Faculty's code is permanent once created!</p> 
                </div>

                <button type="submit" class="btnlogin text-[14px] mt-2">
                Create Faculty
                </button>
            </form>
            </div>

            <!-- Join Faculty Form -->
            <div id="join-form" class="hidden flex flex-col space-y-4">
            <hr>
    
                <form action="join_faculty.php" method="POST" class="space-y-4">
                    <p class="subtext">Enter the your faculty's code to join.</p> 
                    <input type="text" name="faculty_code" placeholder="Enter Faculty Code" required
                    class="w-full px-3 py-2 rounded-md shadow-inner bg-[#13275B] text-white border border-[#304374] font-onest" 
                    value="<?php echo isset($_POST['faculty_code']) ? htmlspecialchars($_POST['faculty_code']) : ''; ?>"/>

                    <button type="submit" class="btnlogin text-[14px] mt-4">
                        Join Faculty
                    </button>
                </form>
            </div>

            <?php if (isset($_SESSION['joined_faculty_success']) || isset($_SESSION['joined_faculty_error'])): ?>
                <script>
                    window.onload = function() {
                        <?php if (isset($_SESSION['joined_faculty_success'])): ?>
                            showToast("<?php echo addslashes($_SESSION['joined_faculty_success']); ?>", "success");
                            <?php unset($_SESSION['joined_faculty_success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['joined_faculty_error'])): ?>
                            showToast("<?php echo addslashes($_SESSION['joined_faculty_error']); ?>", "error");
                            <?php unset($_SESSION['joined_faculty_error']); ?>
                        <?php endif; ?>
                    };
                </script>
            <?php endif; ?>

        </div>
        </div>


        <script>



            function showToast(message, type = 'error') {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.innerText = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3500);
            }

            document.addEventListener('DOMContentLoaded', function() {
                            <?php if ($autoShowJoinForm): ?>
                                showJoinForm();
                            <?php endif; ?>
                        });


            function showMainMenu() {
            document.getElementById('popup-menu').classList.remove('hidden');
            document.getElementById('welcome-section').classList.remove('hidden');
            document.getElementById('join-title').classList.add('hidden');
            document.getElementById('create-title').classList.add('hidden');
            document.getElementById('join-form').classList.add('hidden');
            document.getElementById('create-form').classList.add('hidden');

                           
            }
            
            function showCreateForm() {
            document.getElementById('popup-menu').classList.add('hidden');
            document.getElementById('welcome-section').classList.add('hidden');
            document.getElementById('create-title').classList.remove('hidden');
            document.getElementById('join-title').classList.add('hidden');
            document.getElementById('create-form').classList.remove('hidden');
            document.getElementById('join-form').classList.add('hidden');

                const createTitle = document.getElementById('create-title');
                if (!createTitle.querySelector('.back-button')) {
                    const backButton = document.createElement('button');
                    backButton.className = 'back-button text-white hover:text-gray-300 transition-colors duration-200 mr-2 cursor-pointer';
                    backButton.onclick = showMainMenu;
                    backButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                    </svg>`;
                    
                    createTitle.prepend(backButton);
                }
            }

            function showJoinForm() {
            document.getElementById('popup-menu').classList.add('hidden');
            document.getElementById('welcome-section').classList.add('hidden');
            document.getElementById('join-title').classList.remove('hidden');
            document.getElementById('create-title').classList.add('hidden');
            document.getElementById('join-form').classList.remove('hidden');
            document.getElementById('create-form').classList.add('hidden');

                const joinTitle = document.getElementById('join-title');
                if (!joinTitle.querySelector('.back-button')) {
                    const backButton = document.createElement('button');
                    backButton.className = 'back-button text-white hover:text-gray-300 transition-colors duration-200 mr-2 cursor-pointer';
                    backButton.onclick = showMainMenu;
                    backButton.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                    </svg>`;
                    
                    joinTitle.prepend(backButton);
                }
            }

            function generateCode() {
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 5; i++) {
                code += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            document.getElementById('generatedCode').value = code;
            }

            function copyCode() {
            const codeInput = document.getElementById('generatedCode');
            codeInput.select();
            codeInput.setSelectionRange(0, 99999); 
            document.execCommand('copy');
            alert('Code copied!');
            }

            
        </script>
   
        <?php endif; ?>

        <script src="../src/sidebar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  
  window.toggleUserMenu = function(event) {
    if (event) {
      event.stopPropagation();
    }
    
    const menu = document.getElementById('userMenu');
    const icon = document.getElementById('dropdown-icon');
    
    if (!menu) return;
    
    menu.classList.toggle('hidden');
    
  
    if (icon) {
      if (menu.classList.contains('hidden')) {
        icon.classList.remove('rotate-180');
      } else {
        icon.classList.add('rotate-180');
      }
    }
  };

  document.addEventListener('click', function(event) {
    const menu = document.getElementById('userMenu');
    const profileContainer = document.querySelector('.profile-container');
    
    if (menu && profileContainer && !profileContainer.contains(event.target) && !menu.classList.contains('hidden')) {
      menu.classList.add('hidden');
      const icon = document.getElementById('dropdown-icon');
      if (icon) {
        icon.classList.remove('rotate-180');
      }
    }
  });
});

if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark');
  }

  </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const clearNotificationsButton = document.getElementById('clearNotifications');

    loadNotifications();

    // Add click handler for clear notifications button
    clearNotificationsButton.addEventListener('click', function(e) {
        e.stopPropagation();
        if (confirm('Are you sure you want to clear all notifications?')) {
            clearAllNotifications();
        }
    });

    // Add click handler for the entire document
    document.addEventListener('click', function(e) {
        // Check if click is outside the notification dropdown and button
        if (!notificationDropdown.contains(e.target) && !notificationButton.contains(e.target)) {
            // Close the dropdown with animation
            notificationDropdown.classList.remove('opacity-100', 'scale-100');
            notificationDropdown.classList.add('opacity-0', 'scale-95');
            
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
            }, 300);
        }
    });

    // Add click handler for iframe
    const iframe = document.getElementById('contentIframe');
    if (iframe) {
        iframe.addEventListener('load', function() {
            try {
                iframe.contentWindow.document.addEventListener('click', function() {
                    // Close the dropdown with animation
                    notificationDropdown.classList.remove('opacity-100', 'scale-100');
                    notificationDropdown.classList.add('opacity-0', 'scale-95');
                    
                    setTimeout(() => {
                        notificationDropdown.classList.add('hidden');
                    }, 300);
                });
            } catch (e) {
                console.log('Could not add click listener to iframe content');
            }
        });
    }

    function clearAllNotifications() {
      
        fetch('../src/scripts/clear_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                
                notificationList.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                
                const badge = notificationButton.querySelector('span');
                if (badge) {
                    badge.remove();
                }
                
                const notifDot = document.getElementById('notifDot');
                if (notifDot) {
                    notifDot.style.display = 'none';
                }
                
                loadNotifications();
            } else {
              
                alert(data.message || 'Failed to clear notifications. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error clearing notifications:', error);
            alert('Failed to clear notifications. Please try again.');
        });
    }

    setInterval(loadNotifications, 30000);

    notificationButton.addEventListener('click', function(e) {
        e.stopPropagation();
        if (notificationDropdown.classList.contains('hidden')) {
            notificationDropdown.classList.remove('hidden');
            setTimeout(() => {
                notificationDropdown.classList.remove('opacity-0', 'scale-95');
                notificationDropdown.classList.add('opacity-100', 'scale-100');
            }, 10);
        } else {
            notificationDropdown.classList.remove('opacity-100', 'scale-100');
            notificationDropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
            }, 300);
        }
        if (!notificationDropdown.classList.contains('hidden')) {
            loadNotifications();
        }
    });

    function loadNotifications() {
        fetch('src/scripts/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                notificationList.innerHTML = '';
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                    updateNotifDot();
                    return;
                }

                data.notifications.forEach(notification => {
                    const notificationElement = document.createElement('div');
                    notificationElement.className = `p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer ${notification.is_read ? 'bg-white' : 'bg-blue-50'}`;
                    notificationElement.innerHTML = `
                        <div class="flex items-start z-[-1]">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                <p class="text-sm text-gray-500">${notification.message}</p>
                                <p class="text-xs text-gray-400 mt-1">${new Date(notification.created_at).toLocaleString()}</p>
                            </div>
                            ${!notification.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>' : ''}
                        </div>
                    `;

                    notificationElement.addEventListener('click', () => {
                        if (!notification.is_read) {
                            markAsRead(notification.id);
                        }
                        if (notification.task_id) {
                            const iframe = document.getElementById('contentIframe');
                            if (iframe) {
                                let fromParam = '';
                                if (userRole === 'Faculty Member') fromParam = 'fm-dash';
                                else if (userRole === 'Program Head') fromParam = 'ph-dash';
                                else if (userRole === 'Dean') fromParam = 'dn-dash';
                                else if (userRole === 'Coordinator') fromParam = 'ph-dash';
                                iframe.src = `dashboard/submissionspage.php?task_id=${notification.task_id}&from=${fromParam}`;
                                document.getElementById('notificationDropdown').classList.add('hidden');
                            }
                        }
                    });

                    notificationList.appendChild(notificationElement);
                });
                updateNotifDot();
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                notificationList.innerHTML = '<div class="p-4 text-center text-red-500">Error loading notifications</div>';
                updateNotifDot();
            });
    }

    function markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);

        fetch('src/scripts/mark_notification_read.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
               
                const badge = notificationButton.querySelector('span');
                if (badge) {
                    const currentCount = parseInt(badge.textContent);
                    if (currentCount > 1) {
                        badge.textContent = currentCount - 1;
                    } else {
                        badge.remove();
                    }
                }
                updateNotifDot();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    function updateNotifDot() {
        let notifDot = document.getElementById('notifDot');
        if (!notifDot) {
            notifDot = document.createElement('span');
            notifDot.id = 'notifDot';
            notifDot.className = 'absolute top-1 right-1 w-3 h-3 bg-red-600 rounded-full z-50';
            notificationButton.appendChild(notifDot);
        }
     
        const hasUnread = notificationList && notificationList.querySelector('.bg-blue-50');
        
        if (hasUnread) {
            notifDot.style.display = '';
        } else {
            notifDot.style.display = 'none';
        }
    }
});
</script>

<script>
const userRole = "<?php echo $userRole; ?>";
console.log('userRole:', userRole);
</script>

<script>
    // On page load, set red dot visibility based on PHP unread count
    document.addEventListener('DOMContentLoaded', function() {
        const notifDot = document.getElementById('notifDot');
        if (<?php echo $notificationCount; ?> == 0) {
            notifDot.style.display = 'none';
        } else {
            notifDot.style.display = '';
        }
    });
</script>

</body>
</html>