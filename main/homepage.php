<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

$salutation = isset($_SESSION['Salutation']) ? $_SESSION['Salutation'] : '';
$lastName = isset($_SESSION['LastName']) ? $_SESSION['LastName'] : '';
$greeting = "Good day";
if (!empty($salutation) && !empty($lastName)) {
    $greeting .= ", {$salutation} {$lastName}!";
} else {
    $greeting .= "!";
}

$showFacultyPopup = false;
if (isset($_SESSION['ShowFacultyPopup']) && $_SESSION['ShowFacultyPopup'] === true) {
    $showFacultyPopup = true;
    unset($_SESSION['ShowFacultyPopup']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="../src/tailwind/output.css" rel="stylesheet" />
  <link href="../src/styles.css" rel="stylesheet" />
  <title>Dashboard | Coursedock</title>
  <link href="../img/cdicon.svg" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@200;300;400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-overpass { font-family: 'Overpass', sans-serif; }
    .font-onest { font-family: 'Onest', sans-serif; }
  </style>
</head>

<body class="w-full h-screen bg-[#020A27] px-10 pt-3 flex items-start justify-center">

  <!-- wrapper para sa lahat!-->
  <div class="w-full h-full flex flex-row rounded-t-[15px] overflow-hidden bg-white shadow-lg">

    <!-- sidebar -->
    <div class="w-[290px] bg-[#1D387B] text-white p-3 pt-5 flex flex-col">

      <div class="text-left leading-tight mb-8 ml-2 font-onest">
        <img src="../img/COURSEDOCK.svg" class="w-[180px]" />
        <p class="text-[10px] font-light">Courseware Monitoring System</p>
      </div>

      <div class="flex flex-col gap-[8px]">
        <div class="flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
          <img src="../img/dashboard.png" alt="Dashboard" class="w-[22px] mr-[22px]" />
          Dashboard
        </div>
        <div class="flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
          <img src="../img/materials-icon.png" alt="Curriculum" class="w-[22px] mr-[22px]" />
          Tasks
        </div>
        <div class="flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
          <img src="../img/notification-icon.png" alt="Notifications" class="w-[22px] mr-[22px]" />
          Inbox
        </div>
        <div class="flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
          <img src="../img/faculty-icon.png" alt="Faculty" class="w-[22px] mr-[22px]" />
          Faculty
        </div>
        <div class="flex items-center px-7 py-3 h-[53px] border-2 border-[#2A4484] text-[16px] font-onest text-[#E3E3E3] font-[400] rounded-[10px] hover:bg-[#13275B] active:border-[#51D55A] cursor-pointer transition">
          <img src="../img/materials-icon.png" alt="Curriculum" class="w-[22px] mr-[22px]" />
          Curriculum Materials
        </div>
      </div>

      <button class="mt-auto bg-green-600 hover:bg-green-800 text-white px-4 py-3 rounded-md text-lg font-bold transition">
        + Create
      </button>
    </div>

    <!-- Main Panel top bar and content inside -->
    <div class="flex-1 flex flex-col h-full">

      <!-- topbar -->
      <div class="bg-white px-[50px] py-[20px] h-[67px] flex justify-between items-center w-full box-border" style="box-shadow: 0 3px 4px 0 rgba(0, 0, 0, 0.3);">
        <div class="font-onest text-[24px] font-light" style="letter-spacing: -0.03em;">
          <?php echo htmlspecialchars($greeting); ?>
        </div>

        <div class="font-poppins text-[24px] font-semibold">Profile</div>
      </div>

      <iframe src="dashboard/ph-dash.php" class="w-full flex-1" frameborder="0"></iframe>

    </div>
  </div>

 
  <?php if ($showFacultyPopup): ?>
        <div class="fixed inset-0 bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="signupbox2 signinbox bg-white p-8 rounded-xl shadow-md w-full max-w-md text-center relative bg-opacity-90">

            <!-- Welcome Section -->
            <div id="welcome-section" class="flex flex-col items-center justify-center">
            <h2 class="text-xl text-amber-50 font-onest font-thin mb-1">Welcome to</h2>
            <img src="../img/COURSEDOCK.svg" class="w-[180px] mb-4" />
            </div>

            <!-- Create Faculty Title -->
            <h2 id="create-title" class="hidden text-2xl text-[#E3E3E3] font-overpass font-semibold justify-start tracking-wide mb-4">
            Creating a Faculty
            </h2>

            <!-- Join Faculty Title -->
            <h2 id="join-title" class="hidden text-2xl text-[#E3E3E3] font-overpass font-semibold justify-start tracking-wide mb-4">
            Join with a Code
            </h2>


            <!-- Popup main menu buttons -->
            <div id="popup-menu" class="flex flex-col space-y-4">
            <p class="text-white font-normal font-onest text-[14px] mb-4">You are not currently part of any faculty.</p>

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
            <form action="create_faculty.php" method="POST" class="space-y-4">

                <input type="text" name="faculty_name" placeholder="Faculty Name" required
                class="w-full px-3 py-2 rounded-md shadow-inner bg-[#13275B] text-white border border-[#304374] font-onest" />

                <div class="flex flex-col items-start text-left w-full space-y-2">
                <label class="text-white text-sm font-onest">Faculty Code:</label>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                    <input type="text" name="faculty_code" id="generatedCode" readonly
                        class="w-full px-3 py-2 pr-10 rounded-md shadow-inner bg-[#13275B] text-white border border-[#304374] font-onest" />
                    <!-- Copy Button inside input -->
                    <button type="button" onclick="copyCode()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs bg-green-600 hover:bg-green-800 text-white px-2 py-1 rounded">
                        Copy
                    </button>
                    </div>

                    <!-- Generate Button -->
                    <button type="button" onclick="generateCode()"
                    class="bg-blue-600 hover:bg-blue-800 text-white px-4 py-2 rounded-md text-sm">
                    Generate
                    </button>
                </div>
                </div>

                <button type="submit" class="btnlogin text-[14px] mt-4">
                Create Faculty
                </button>
            </form>
            </div>

            <!-- Join Faculty Form -->
            <div id="join-form" class="hidden flex flex-col space-y-4">
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

        <!-- SCRIPT -->
        <script>
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
        codeInput.setSelectionRange(0, 99999); // For mobile compatibility
        document.execCommand('copy');
        alert('Code copied!');
        }
        </script>
        <?php endif; ?>






</body>
</html>
