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
            transition: all 0s ease; 
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

    <!-- for new users lang na walang faculty-->
  <?php if ($showFacultyPopup): ?>
        <div class="fixed inset-0 bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm">
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
            <form action="src/scripts/create_faculty.php" method="POST" class="space-y-4">

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

                <input type="text" name="faculty_code" placeholder="Enter Faculty Code" required
                class="w-full px-3 py-2 rounded-md shadow-inner bg-[#13275B] text-white border border-[#304374] font-onest" />

                <button type="submit" class="btnlogin text-[14px] mt-4">
                Join Faculty
                </button>
            </form>
            </div>

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

            function showCreateForm() {
            document.getElementById('popup-menu').classList.add('hidden');
            document.getElementById('welcome-section').classList.add('hidden');
            document.getElementById('create-title').classList.remove('hidden');
            document.getElementById('join-title').classList.add('hidden');
            document.getElementById('create-form').classList.remove('hidden');
            document.getElementById('join-form').classList.add('hidden');
            }

            function showJoinForm() {
            document.getElementById('popup-menu').classList.add('hidden');
            document.getElementById('welcome-section').classList.add('hidden');
            document.getElementById('join-title').classList.remove('hidden');
            document.getElementById('create-title').classList.add('hidden');
            document.getElementById('join-form').classList.remove('hidden');
            document.getElementById('create-form').classList.add('hidden');
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
</body>
</html>
