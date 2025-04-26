<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Role | CourseDock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">

    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md text-center">
        <h2 class="text-2xl font-semibold mb-4">Welcome to CourseDock</h2>
        <p class="text-gray-600 mb-6">You are not currently part of any faculty.</p>
        
        <div class="flex flex-col space-y-4">
           
            <form action="create_faculty.php" method="POST">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                    Create a New Faculty
                </button>
            </form>

  
            <form action="join_faculty.php" method="POST" class="space-y-2">
                <input type="text" name="faculty_code" placeholder="Enter Faculty Code" 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:border-blue-400" 
                    maxlength="5" required>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
                    Join Faculty
                </button>
            </form>
        </div>
    </div>

</body>
</html>
