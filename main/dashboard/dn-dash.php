<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }
    </style>
</head>
<body>
    <!-- Dashboard -->
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
        <h1 class="py-[10px] text-[35px] font-overpass font-bold">Dashboard Dean!!</h1>
        <div class="grid grid-cols-2 gap-5">
          <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md">Submissions


          </div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">
            <div>Faculty</div>
            <div class="faculty-grid mt-2"></div>
          </div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">Pending Reviews</div>
          <div class="bg-white p-[30px] rounded-lg shadow-md">Pinboard</div>
        </div>
      </div>
</body>
</html>