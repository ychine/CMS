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
    
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] overflow-y-auto">
     <h1 class="py-[10px] text-[35px] font-overpass font-bold" style="letter-spacing: -0.03em;">Dashboard dean</h1>

        <div class="grid grid-cols-2 gap-5">
        <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">Submissions</h2>
    <div class="text-sm text-blue-600">On-Going Task: 2425 ANDYR COURSE SYLLABUS</div>
  </div>
  
  
  <div class="flex space-x-4 mb-5">
   
    <a href="submissionspage.php?type=pending" class="flex-1">
      <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
        <div class="flex items-center">
          <div class="text-2xl font-bold mr-3">7</div>
          <div class="text-sm">Pending Review</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
        <div class="w-full bg-gray-300 h-1 mt-2">
          <div class="bg-yellow-500 h-1" style="width: 50%"></div>
        </div>
      </div>
    </a>
    
   
    <a href="submissionspage.php?type=unaccomplished" class="flex-1">
      <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
        <div class="flex items-center">
          <div class="text-2xl font-bold mr-3">3</div>
          <div class="text-sm">Unaccomplished</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
        <div class="w-full bg-gray-300 h-1 mt-2">
          <div class="bg-red-500 h-1" style="width: 30%"></div>
        </div>
      </div>
    </a>
    
    
    <a href="submissionspage.php?type=complete" class="flex-1">
      <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
        <div class="flex items-center">
          <div class="text-2xl font-bold mr-3">10</div>
          <div class="text-sm">Complete</div>
          <div class="ml-auto">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
        <div class="w-full bg-gray-300 h-1 mt-2">
          <div class="bg-green-500 h-1" style="width: 100%"></div>
        </div>
      </div>
    </a>
  </div>
  
  
  <div class="flex items-center">
    <div class="text-xs mr-2 font-medium">50%</div>
    <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
      <div class="bg-green-500 h-2" style="width: 50%"></div>
    </div>
    <div class="ml-2">
      <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>
    </div>
  </div>
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