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
    <title>Home | CourseDock</title>
    <link href="../img/cdicon.svg" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@200;300;400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }

        /* Sidebar */

        #sidebar {
            transition: width 0.6s ease-in-out;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

       
        .collapsed #logo, 
        .collapsed #logo-text {
            visibility: hidden; 
            transition: all 0s ease-in-out; 
        }

      
        .collapsed .link-text {
            display: none;      
        }

       
        .collapsed {
            width: 80px;
            align-items: center;
        }

        #toggleSidebar {
            transition: all 0.5s ease-in-out;
        }

        .collapsed #toggleSidebar {
            position: absolute;
        
            width: 35px;
            height: 35px;
            background-color: #324f96;
            color: white;
            display: flex;            
            align-items: center;      
            justify-content: center; 
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
            user-select: none;
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

        .user-info {
        text-align: right;
        padding: 6px 8px;
        border-radius: 8px;
        transition: background-color 0.2s;
        }

        .user-info:hover {
        background-color: #f3f4f6;
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
            background: #181a1b;
        }
        .dark .header-bar {
            background: #2d3036 !important;
            color: #fff;
        }
        .dark .sidebar {
            background: #23252b !important;
            color: #e3e3e3;
        }
        .dark .main-content {
            background: #1a1c20 !important;
            color: #fff;
        }
        .dark .notif-btn svg {
            color: #fff !important;
        }
        .dark .notif-btn:hover {
            background: #35373c !important;
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
        .dark .user-info:hover {
            background: #23252b !important;
        }
        .dark .text-gray-600, .dark .text-gray-500, .dark .text-[#333], .dark .text-[#808080] {
            color: #e3e3e3 !important;
        }

        .dark .link-text, .dark .font-onest, .dark .font-overpass {
            color: #e3e3e3 !important;
        }
    </style>
</head>

<body id="mainBody" class="w-full h-screen bg-[#020A27] px-10 pt-3 flex items-start justify-center">

    <!-- Wrapper -->
    <div class="w-full h-full flex flex-row rounded-t-[15px] overflow-hidden bg-gray-200 shadow-lg">

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
                <a href="homepage.php" class="bg-[#13275B] menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/dashboard.png" alt="Dashboard" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Dashboard</span>
                </a>

                <a href="task/tasks.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/materials-icon.png" alt="Tasks" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Tasks</span>
                </a>


                <a href="faculty/faculty.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/faculty-icon.png" alt="Faculty" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Faculty</span>
                </a>

                <a href="curriculum/curriculum.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/materials-icon.png" alt="Curriculum Materials" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Curricula</span>
                </a>
                
                <?php if ($row['Role'] === 'DN'): ?>
                <a href="auditlog/audit_log.php" class="menu-item flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
                    <img src="../img/Audit.png" alt="Audit Log" class="w-[22px] mr-[22px]" />
                    <span class="link-text">Audit Log</span>
                </a>
                <?php endif; ?>
            </div>


            <button id="createButton" class=" mt-auto rounded-[10px] text-white px-4 font-onest py-3 rounded-md text-lg font-regular transition-colors duration-300 flex items-center justify-between w-full">
              
            </button>



            <div class="sidebar-footer relative rounded-md m-0 w-full text-center font-overpass font-light text-[10px]  px-2 text-gray-400 mt-2 my-0 py-2">
                <hr class="border-t border-[#314f9b] w-full mx-auto mb-2" />
                © 2025 CourseDock. All rights reserved.
                <span class="mt-1">
                    <br>
                    <a href="#" class="text-gray-400 hover:underline mx-1">About CourseDock</a>
                    <a href="#" class="text-gray-400 hover:underline mx-1">Contact our Support</a>
                </span>
            </div>

        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col h-full ">

            <div class="header-bar bg-white px-[50px] py-[20px] h-[67px] flex justify-between items-center w-full box-border" style="box-shadow: 0 3px 4px 0 rgba(0, 0, 0, 0.3);">
                <div class="font-onest text-[24px] font-semibold mt-1" style="letter-spacing: -0.03em;">
                    <?php echo htmlspecialchars($facultyName); ?>
                </div>
             
                <div class="flex items-center gap-4">
                    <!-- Notification Icon -->
                    <div class="relative">
                        <button class="p-2 rounded-full notif-btn transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <!-- Notification Badge -->
                            <?php if ($notificationCount > 0): ?>
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </button>
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

        <script>
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const chevronIcon = document.getElementById('chevronIcon');

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                chevronIcon.classList.toggle('rotate-180');
            });

            
     
        </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  
  window.toggleUserMenu = function(event) {
    if (event) {
      event.stopPropagation();
    }
    
    const menu = document.getElementById('userMenu');
    const icon = document.getElementById('dropdown-icon');
    
    if (!menu) return; // Safety check
    
    menu.classList.toggle('hidden');
    
    // Rotate icon when menu is open
    if (icon) {
      if (menu.classList.contains('hidden')) {
        icon.classList.remove('rotate-180');
      } else {
        icon.classList.add('rotate-180');
      }
    }
  };

  // Close menu when clicking outside
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




</body>
</html>