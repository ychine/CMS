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
<body class="w-full h-screen flex flex-row">

  
  <div class="w-[250px] bg-[#1D387B] text-white p-5 h-full flex flex-col">
    <div class="text-left leading-tight mb-10 font-onest">
        <img src="../img/COURSEDOCK.svg" class="w-[180px] mb-1" />
        <p class="text-[10px] font-light">Courseware Monitoring System</p>
    </div>

    
   

    <div class="flex items-center px-3 py-4 text-lg font-onest font-medium rounded-md hover:bg-[#13275B] cursor-pointer transition">
      <img src="../img/dashboard.png" alt="Dashboard" class="w-[30px] mr-[30px]"> Dashboard
    </div>
    <div class="flex items-center px-3 py-4 text-lg font-onest font-medium rounded-md hover:bg-[#13275B] cursor-pointer transition">
      <img src="../img/faculty-icon.png" alt="Faculty" class="w-[30px] mr-[30px]"> Faculty
    </div>
    <div class="flex items-center px-3 py-4 text-lg font-onest font-medium rounded-md hover:bg-[#13275B] cursor-pointer transition">
      <img src="../img/notification-icon.png" alt="Notifications" class="w-[30px] mr-[30px]"> Notifications
    </div>
    <div class="flex items-center px-3 py-4 text-lg font-onest font-medium rounded-md hover:bg-[#13275B] cursor-pointer transition">
      <img src="../img/materials-icon.png" alt="Curriculum" class="w-[30px] mr-[30px]"> Curriculum Materials
    </div>

    <button class="mt-auto bg-green-600 hover:bg-green-800 text-white px-4 py-3 rounded-md text-lg font-bold transition">
      + Create
    </button>
  </div>

  <!-- Main Panel (Top Bar + Dashboard Content) -->
  <div class="flex-1 flex flex-col h-full">

    <!-- Top Bar -->
    <div class="bg-white px-[50px] py-[20px] h-[70px] flex justify-between items-center shadow-md w-full box-border">
      <div class="font-poppins text-[30px] font-light">Good day, Dean Tan!</div>
      <div class="font-poppins text-[24px] font-semibold">Profile</div>
    </div>

    <!-- Dashboard -->
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
      <h1 class="py-[10px] text-2xl font-bold">Dashboard</h1>
      <div class="grid grid-cols-2 gap-5">
        <div class="bg-white p-[30px] rounded-lg shadow-md">Submissions</div>
        <div class="bg-white p-[30px] rounded-lg shadow-md">
          <div>Faculty</div>
          <div class="faculty-grid mt-2"></div>
        </div>
        <div class="bg-white p-[30px] rounded-lg shadow-md">Pending Reviews</div>
        <div class="bg-white p-[30px] rounded-lg shadow-md">Pinboard</div>
      </div>
    </div>

  </div>

</body>
</html>
