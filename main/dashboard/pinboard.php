<?php

if (!isset($userData) || !is_array($userData)) {
    $userData = [];
}
$role = isset($userData['Role']) ? $userData['Role'] : '';
$facultyId = isset($userData['FacultyID']) ? $userData['FacultyID'] : null;

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['post_announcement']) && in_array($role, ['DN', 'PH', 'COR'])) {
    $title = $_POST['announcement_title'];
    $message = $_POST['announcement_message'];
    $createdBy = $personnelId;

    $insertSql = "INSERT INTO pinboard (Title, Message, CreatedBy, FacultyID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssii", $title, $message, $createdBy, $facultyId);
    
    if ($stmt->execute()) {
      
        $personnelQuery = "SELECT AccountID FROM personnel WHERE FacultyID = ?";
        $personnelStmt = $conn->prepare($personnelQuery);
        $personnelStmt->bind_param("i", $facultyId);
        $personnelStmt->execute();
        $personnelResult = $personnelStmt->get_result();
        
     
        while ($row = $personnelResult->fetch_assoc()) {
            $accountID = $row['AccountID'];
            $notifTitle = "New Announcement: " . $title;
            $notifMessage = $message;
            
            $notifSql = "INSERT INTO notifications (AccountID, Title, Message) VALUES (?, ?, ?)";
            $notifStmt = $conn->prepare($notifSql);
            $notifStmt->bind_param("iss", $accountID, $notifTitle, $notifMessage);
            $notifStmt->execute();
            $notifStmt->close();
        }
        
        $personnelStmt->close();
        $message = "Announcement posted successfully!";
    } else {
        $message = "Error posting announcement: " . $stmt->error;
    }
    $stmt->close();
}


if (isset($_POST['delete_announcement']) && in_array($role, ['DN', 'PH', 'COR'])) {
    $pinId = $_POST['pin_id'];
    $deleteSql = "DELETE FROM pinboard WHERE PinID = ? AND FacultyID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("ii", $pinId, $facultyId);
    
    if ($deleteStmt->execute()) {
        $message = "Announcement deleted successfully!";
    } else {
        $message = "Error deleting announcement: " . $deleteStmt->error;
    }
    $deleteStmt->close();
}


if (isset($_POST['upload_syllabus']) && in_array($role, ['DN', 'PH', 'COR'])) {
    $target_dir = "uploads/syllabus_formats/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES['syllabus_file'];
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    

    if ($file_type == "pdf" || $file_type == "doc" || $file_type == "docx") {
        // First, delete any existing syllabus format
        $deleteSql = "DELETE FROM syllabus_formats WHERE FacultyID = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $facultyId);
        $deleteStmt->execute();
        $deleteStmt->close();

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $title = $_POST['syllabus_title'];
            $uploadDate = date('Y-m-d H:i:s');
            
            $insertSql = "INSERT INTO syllabus_formats (Title, FilePath, UploadDate, FacultyID) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("sssi", $title, $file_name, $uploadDate, $facultyId);
            
            if ($stmt->execute()) {
                $message = "Syllabus format uploaded successfully!";
            } else {
                $message = "Error uploading syllabus format: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error uploading file.";
        }
    } else {
        $message = "Only PDF, DOC, and DOCX files are allowed.";
    }
}

// Handle syllabus format deletion
if (isset($_POST['delete_syllabus']) && in_array($role, ['DN', 'PH', 'COR'])) {
    $formatId = $_POST['format_id'];
    $deleteSql = "DELETE FROM syllabus_formats WHERE FormatID = ? AND FacultyID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("ii", $formatId, $facultyId);
    
    if ($deleteStmt->execute()) {
        $message = "Syllabus format deleted successfully!";
    } else {
        $message = "Error deleting syllabus format: " . $deleteStmt->error;
    }
    $deleteStmt->close();
}

if (!empty($facultyId)) {
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

    $syllabusSql = "SELECT sf.*, CONCAT(per.FirstName, ' ', per.LastName) as UploaderName, per.Role as UploaderRole 
                    FROM syllabus_formats sf 
                    JOIN personnel per ON sf.FacultyID = per.FacultyID 
                    WHERE sf.FacultyID = ? 
                    ORDER BY sf.UploadDate DESC 
                    LIMIT 1";
    $syllabusStmt = $conn->prepare($syllabusSql);
    $syllabusStmt->bind_param("i", $facultyId);
    $syllabusStmt->execute();
    $syllabusResult = $syllabusStmt->get_result();
    $syllabusFormats = [];
    while ($row = $syllabusResult->fetch_assoc()) {
        $syllabusFormats[] = $row;
    }
    $syllabusStmt->close();
} else {
    $pinboardPosts = [];
    $syllabusFormats = [];
}
$conn->close();
?>

<!-- Pinboard Section -->
<div class="w-[300px] max-w-full min-w-[250px]">
    <div class="bg-white p-[30px] pr-[20px] font-overpass rounded-sm shadow-md h-[600px]">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-bold">Pinboard 📌</h2>
            <?php if (in_array($role, ['DN', 'PH', 'COR'])): ?>
            <button onclick="openPinboardModal()" class="text-xs text-right pr-[10px] font-onest text-blue-600 hover:underline">Post Announcement</button>
            <?php endif; ?>
        </div>
        <div class="space-y-4 overflow-y-auto h-[calc(100%-100px)] pr-4 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-400 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-gray-500">
            <?php if (empty($pinboardPosts)): ?>
                <div class="text-sm text-gray-600 text-center py-4">
                    No announcements yet.
                </div>
            <?php else: ?>
                <?php foreach ($pinboardPosts as $post): ?>
                    <div class="relative bg-gray-100 rounded-xl pl-1 pr-3 py-8 shadow-sm flex flex-col">
                        <?php if (in_array($role, ['DN', 'PH', 'COR'])): ?>
                        <form method="POST" class="absolute top-2 right-2">
                            <input type="hidden" name="pin_id" value="<?php echo $post['PinID']; ?>">
                            <button type="submit" name="delete_announcement" class="text-gray-400 hover:text-red-500 transition-colors duration-200" title="Delete announcement">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </form>
                        <?php endif; ?>
                        <div class="flex flex-row items-start">
                            <div class="w-1.5 h-full bg-green-400 rounded-full mr-4"></div>
                            <div class="flex-1 flex flex-col">
                                <h3 class="font-bold text-lg tracking-tight mb-1 break-words" style="color: #0D5191;"><?php echo htmlspecialchars($post['Title']); ?></h3>
                                <div class="flex items-center gap-1 mb-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-xs text-gray-400 font-medium"><?php echo date('M j, Y', strtotime($post['CreatedAt'])); ?></span>
                                </div>
                                <p class="text-base leading-relaxed font-onest text-gray-700 mb-4 whitespace-pre-line break-words"><?php echo nl2br(htmlspecialchars($post['Message'])); ?></p>
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

            <!-- Syllabus Formats Section -->
            <div class="mt-6 pt-[20px] border-t border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg pt-4 font-bold">Syllabus Format</h2>
                    <?php if (in_array($role, ['DN', 'PH', 'COR'])): ?>
                    <button onclick="openSyllabusModal()" class="text-xs text-blue-600 pt-3 font-onest hover:underline"><?php echo empty($syllabusFormats) ? 'Upload' : 'Update'; ?></button>
                    <?php endif; ?>
                </div>
                <div class="space-y-4">
                    <?php if (empty($syllabusFormats)): ?>
                        <div class="text-sm text-gray-600 pl-3 pr-3 text-center py-4">
                            No syllabus format uploaded yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($syllabusFormats as $format): ?>
                            <div class="relative bg-gray-100 rounded-xl py-8  flex flex-col">
                                <?php if (in_array($role, ['DN', 'PH', 'COR'])): ?>
                                <form method="POST" class="absolute top-2 right-2">
                                    <input type="hidden" name="format_id" value="<?php echo $format['FormatID']; ?>">
                                    <button type="submit" name="delete_syllabus" class="text-gray-400 hover:text-red-500 transition-colors duration-200" title="Delete syllabus format">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <div class="flex flex-row items-start">
                                    <div class="w-1.5 h-full bg-blue-400 rounded-full"></div>
                                    <div class="flex-1 flex flex-col px-4">
                                        <h3 class="font-bold text-lg tracking-tight mb-1 break-words" style="color: #0D5191;"><?php echo htmlspecialchars($format['Title']); ?></h3>
                                        <div class="flex items-center gap-2">
                                            <a href="uploads/syllabus_formats/<?php echo htmlspecialchars($format['FilePath']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Download
                                            </a>
                                            <span class="text-xs text-gray-400">•</span>
                                            <span class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($format['UploadDate'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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

<!-- Syllabus Upload Modal -->
<div id="syllabusModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-[500px] max-w-full mx-auto border-2 border-gray-400 font-onest modal-animate max-h-[90vh] flex flex-col relative">
        <button onclick="closeSyllabusModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-3xl font-bold" title="Close">&times;</button>
        <div class="flex-none">
            <h2 class="text-3xl font-overpass font-bold mb-2 text-blue-800 flex items-center gap-2">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Upload Syllabus Format
            </h2>
            <hr class="border-gray-400 mb-6">
        </div>
        <form id="syllabusForm" method="POST" action="" enctype="multipart/form-data" class="flex-1 overflow-y-auto pr-2 flex flex-col gap-6">
            <div class="space-y-2">
                <label class="block text-lg font-semibold text-gray-700">Title:</label>
                <input type="text" id="syllabus_title" name="syllabus_title" required class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500" placeholder="Enter syllabus format title">
            </div>
            <div class="space-y-2">
                <label class="block text-lg font-semibold text-gray-700">File (PDF/DOC/DOCX):</label>
                <input type="file" id="syllabus_file" name="syllabus_file" required accept=".pdf,.doc,.docx" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-500">
            </div>
            <div class="flex-none flex justify-end gap-4 pt-4 mt-4 border-t border-gray-200">
                <button type="button" onclick="closeSyllabusModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-200 font-semibold">Cancel</button>
                <button type="submit" name="upload_syllabus" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-semibold">Upload Format</button>
            </div>
        </form>
    </div>
</div>

<!-- Notification Dropdown -->
<div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-[9999] transform transition-all duration-300 ease-in-out opacity-0 scale-95" style="box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 -2px 4px -1px rgba(0, 0, 0, 0.06), 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">

<script>
function openPinboardModal() {
  document.getElementById('pinboardModal').classList.remove('hidden');
  document.getElementById('pinboardModal').classList.add('flex');
}
function closePinboardModal() {
  document.getElementById('pinboardModal').classList.add('hidden');
  document.getElementById('pinboardModal').classList.remove('flex');
}

function openSyllabusModal() {
    document.getElementById('syllabusModal').classList.remove('hidden');
    document.getElementById('syllabusModal').classList.add('flex');
}

function closeSyllabusModal() {
    document.getElementById('syllabusModal').classList.add('hidden');
    document.getElementById('syllabusModal').classList.remove('flex');
}
</script> 