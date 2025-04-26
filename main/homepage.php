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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="../src/tailwind/output.css" rel="stylesheet" />
  <title>Dashboard | Coursedock</title>
  <link href="../img/cdicon.svg" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
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

</body>
</html>
