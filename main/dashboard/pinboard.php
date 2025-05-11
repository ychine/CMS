<?php
// Pinboard functionality
$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle pinboard post submission
if (isset($_POST['post_announcement']) && ($userData['Role'] === 'DN' || $userData['Role'] === 'PH' || $userData['Role'] === 'COR')) {
    $title = $_POST['announcement_title'];
    $message = $_POST['announcement_message'];
    $createdBy = $personnelId;
    $facultyId = $userData['FacultyID'];

    $insertSql = "INSERT INTO pinboard (Title, Message, CreatedBy, FacultyID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssii", $title, $message, $createdBy, $facultyId);
    
    if ($stmt->execute()) {
        $message = "Announcement posted successfully!";
    } else {
        $message = "Error posting announcement: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch pinboard posts
$pinboardSql = "SELECT p.*, CONCAT(per.FirstName, ' ', per.LastName) as AuthorName, per.Role as AuthorRole 
                FROM pinboard p 
                JOIN personnel per ON p.CreatedBy = per.PersonnelID 
                WHERE p.FacultyID = ? 
                ORDER BY p.CreatedAt DESC 
                LIMIT 5";
$pinboardStmt = $conn->prepare($pinboardSql);
$pinboardStmt->bind_param("i", $facultyId);
$pinboardStmt->execute();
$pinboardResult = $pinboardStmt->get_result();
$pinboardPosts = [];
while ($row = $pinboardResult->fetch_assoc()) {
    $pinboardPosts[] = $row;
}
$pinboardStmt->close();
$conn->close();
?>

<!-- Pinboard Section -->
<div class="w-[300px] max-w-full min-w-[250px]">
    <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md h-full">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-bold">Pinboard</h2>
            <?php if ($userData['Role'] === 'DN' || $userData['Role'] === 'PH' || $userData['Role'] === 'COR'): ?>
            <button onclick="openPinboardModal()" class="text-xs text-blue-600 hover:underline">Post Announcement</button>
            <?php endif; ?>
        </div>
        <div class="space-y-4 overflow-y-auto max-h-[calc(100vh-400px)]">
            <?php if (empty($pinboardPosts)): ?>
                <div class="text-sm text-gray-600 text-center py-4">
                    No announcements yet.
                </div>
            <?php else: ?>
                <?php foreach ($pinboardPosts as $post): ?>
                    <div class="relative bg-gray-100 rounded-xl p-6 shadow-sm flex flex-col">
                        <div class="flex flex-row items-start">
                            <div class="w-1.5 h-full bg-green-400 rounded-full mr-4"></div>
                            <div class="flex-1 flex flex-col">
                                <h3 class="font-bold italic text-lg tracking-tight mb-1 break-words" style="color: #0D5191;"><?php echo htmlspecialchars($post['Title']); ?></h3>
                                <div class="flex items-center gap-1 mb-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-xs text-gray-400 font-medium"><?php echo date('M j, Y', strtotime($post['CreatedAt'])); ?></span>
                                </div>
                                <p class="text-base leading-relaxed text-gray-700 mb-4 whitespace-pre-line break-words"><?php echo nl2br(htmlspecialchars($post['Message'])); ?></p>
                                <div class="flex items-center text-xs text-gray-500 font-medium mt-2">
            
                                     <h3 class="font-bold"><?php echo htmlspecialchars($post['AuthorName']); ?> </h3>
                                    <span class="mx-1">•</span>
                                    <span><?php echo htmlspecialchars($post['AuthorRole']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Custom Post Announcement Modal -->
<div id="pinboardModal" class="fixed inset-0 z-50 hidden  items-center justify-center">
  <div class="bg-white p-8 rounded-2xl shadow-2xl w-[500px] max-w-full mx-auto border-2 border-gray-400 font-onest modal-animate max-h-[90vh] flex flex-col relative">
    <button onclick="closePinboardModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-3xl font-bold" title="Close">&times;</button>
    <div class="flex-none">
      <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800 flex items-center gap-2">
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Post Announcement
      </h2>
      <hr class="border-gray-400 mb-6">
    </div>
    <form id="pinboardForm" method="POST" action="" class="flex-1 overflow-y-auto pr-2 flex flex-col gap-6">
      <div class="space-y-2">
        <label class="block text-lg font-semibold text-gray-700">Title:</label>
        <input type="text" id="announcement_title" name="announcement_title" required class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="Enter announcement title">
      </div>
      <div class="space-y-2">
        <label class="block text-lg font-semibold text-gray-700">Message:</label>
        <textarea id="announcement_message" name="announcement_message" required rows="4" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="Type your message..."></textarea>
      </div>
      <div class="flex-none flex justify-end gap-4 pt-4 mt-4 border-t border-gray-200">
        <button type="button" onclick="closePinboardModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
        <button type="submit" name="post_announcement" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">Post Announcement</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPinboardModal() {
  document.getElementById('pinboardModal').classList.remove('hidden');
  document.getElementById('pinboardModal').classList.add('flex');
}
function closePinboardModal() {
  document.getElementById('pinboardModal').classList.add('hidden');
  document.getElementById('pinboardModal').classList.remove('flex');
}
</script> 