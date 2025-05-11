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
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($post['Title']); ?></h3>
                            <span class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($post['CreatedAt'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2"><?php echo nl2br(htmlspecialchars($post['Message'])); ?></p>
                        <div class="flex items-center text-xs text-gray-500">
                            <span class="font-medium"><?php echo htmlspecialchars($post['AuthorName']); ?></span>
                            <span class="mx-1">•</span>
                            <span><?php echo htmlspecialchars($post['AuthorRole']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pinboard Modal -->
<div id="pinboardModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-[500px] max-w-[90%]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Post Announcement</h3>
            <button onclick="closePinboardModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <div class="mb-4">
                <label for="announcement_title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="announcement_title" name="announcement_title" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label for="announcement_message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="announcement_message" name="announcement_message" required rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePinboardModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" name="post_announcement"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Post Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Pinboard Modal Functions
function openPinboardModal() {
    document.getElementById('pinboardModal').classList.remove('hidden');
    document.getElementById('pinboardModal').classList.add('flex');
}

function closePinboardModal() {
    document.getElementById('pinboardModal').classList.add('hidden');
    document.getElementById('pinboardModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('pinboardModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePinboardModal();
    }
});
</script> 