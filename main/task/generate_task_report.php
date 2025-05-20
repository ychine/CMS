<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Composer autoloader not found. Please run composer install.');
}
require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
if (!$taskId) die("Invalid Task ID");

// Fetch task info
$taskSql = "SELECT t.*, CONCAT(p.FirstName, ' ', p.LastName) as CreatorName, p.Role as CreatorRole
            FROM tasks t
            LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID
            WHERE t.TaskID = ?";
$stmt = $conn->prepare($taskSql);
$stmt->bind_param("i", $taskId);
$stmt->execute();
$taskResult = $stmt->get_result();
if (!$taskResult || $taskResult->num_rows == 0) die("Task not found");
$task = $taskResult->fetch_assoc();

// Fetch assignments
$assignments = [];
$assignSql = "SELECT ta.*, c.Title as CourseTitle, p.ProgramName, 
                     CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo, 
                     per.Role as ProfessorRole,
                     ta.SubmissionDate,
                     ta.RevisionReason
              FROM task_assignments ta
              JOIN courses c ON ta.CourseCode = c.CourseCode
              JOIN programs p ON ta.ProgramID = p.ProgramID
              LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
              LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
              WHERE ta.TaskID = ?";
$assignStmt = $conn->prepare($assignSql);
$assignStmt->bind_param("i", $taskId);
$assignStmt->execute();
$assignResult = $assignStmt->get_result();
while ($row = $assignResult->fetch_assoc()) $assignments[] = $row;

// After fetching $assignments, attach co-authors to each assignment
foreach ($assignments as &$a) {
    if (!empty($a['SubmissionPath'])) {
        $submissionPath = $a['SubmissionPath'];
        $subStmt = $conn->prepare("SELECT SubmissionID FROM submissions WHERE SubmissionPath = ? LIMIT 1");
        $subStmt->bind_param("s", $submissionPath);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        if ($subRow = $subRes->fetch_assoc()) {
            $submissionID = $subRow['SubmissionID'];
            $coStmt = $conn->prepare("SELECT p.FirstName, p.LastName FROM teammembers tm JOIN personnel p ON tm.MembersID = p.PersonnelID WHERE tm.SubmissionID = ?");
            $coStmt->bind_param("i", $submissionID);
            $coStmt->execute();
            $coRes = $coStmt->get_result();
            $coauthorsList = [];
            while ($coRow = $coRes->fetch_assoc()) {
                $coauthorsList[] = $coRow['FirstName'] . ' ' . $coRow['LastName'];
            }
            $a['CoAuthors'] = $coauthorsList;
            $coStmt->close();
        }
        $subStmt->close();
    } else {
        $a['CoAuthors'] = [];
    }
}

$total = count($assignments);
$completed = 0;
$pending = 0;
$submitted = 0;
foreach ($assignments as $a) {
    if ($a['Status'] === 'Completed') $completed++;
    elseif ($a['Status'] === 'Submitted') $submitted++;
    else $pending++;
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Task Report');


    $sheet->setCellValue('A1', 'Task Report: ' . $task['Title']);
    $sheet->setCellValue('A2', 'School Year: ' . $task['SchoolYear'] . ' | Term: ' . $task['Term']);
    

    $sheet->mergeCells('A1:F1');
    $sheet->mergeCells('A2:F2');
    
   
    $titleStyle = [
        'font' => [
            'bold' => true,
            'size' => 14,
            'color' => ['rgb' => '2563EB']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];
    $sheet->getStyle('A1:F1')->applyFromArray($titleStyle);
    
    $subtitleStyle = [
        'font' => [
            'size' => 12,
            'color' => ['rgb' => '475569']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];
    $sheet->getStyle('A2:F2')->applyFromArray($subtitleStyle);

   
    $headers = ['Course Code', 'Course Title', 'Program Name', 'Assigned Professor', 'Co-Authors', 'Status', 'Submission Date'];
    $sheet->fromArray($headers, NULL, 'A4');

 
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '334155'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F1F5F9']
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'E2E8F0']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);

   
    $row = 5;
    foreach ($assignments as $a) {
        $coauthors = !empty($a['CoAuthors']) ? implode(', ', $a['CoAuthors']) : '-';
        $sheet->setCellValue('A' . $row, $a['CourseCode']);
        $sheet->setCellValue('B' . $row, $a['CourseTitle']);
        $sheet->setCellValue('C' . $row, $a['ProgramName']);
        $sheet->setCellValue('D' . $row, $a['AssignedTo'] ?: 'Unassigned');
        $sheet->setCellValue('E' . $row, $coauthors);
        $sheet->setCellValue('F' . $row, $a['Status']);
        $sheet->setCellValue('G' . $row, $a['SubmissionDate'] ?: '-');
        $row++;
    }

 
    $dataStyle = [
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'F1F5F9']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];
    $sheet->getStyle('A5:G' . ($row-1))->applyFromArray($dataStyle);


    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

  
    $summarySheet = $spreadsheet->createSheet();
    $summarySheet->setTitle('Summary');
    
  
    $summarySheet->setCellValue('A1', 'Task Summary');
    $summarySheet->setCellValue('A3', 'Total Assignments');
    $summarySheet->setCellValue('B3', $total);
    $summarySheet->setCellValue('A4', 'Completed');
    $summarySheet->setCellValue('B4', $completed);
    $summarySheet->setCellValue('A5', 'Submitted');
    $summarySheet->setCellValue('B5', $submitted);
    $summarySheet->setCellValue('A6', 'Pending');
    $summarySheet->setCellValue('B6', $pending);

  
    $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $summarySheet->getStyle('A3:B6')->getFont()->setSize(12);
    $summarySheet->getColumnDimension('A')->setAutoSize(true);
    $summarySheet->getColumnDimension('B')->setAutoSize(true);

 
    $spreadsheet->setActiveSheetIndex(0);


    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="task_report_' . $taskId . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

function statusBadge($status) {
    $map = [
        'Completed' => ['#22c55e', '#dcfce7', '#166534', 'Completed'],
        'Submitted' => ['#3b82f6', '#e0f2fe', '#1e40af', 'Submitted'],
        'Pending'   => ['#f59e0b', '#fef3c7', '#78350f', 'Pending'],
    ];
    $s = $map[$status] ?? ['#64748b', '#f1f5f9', '#334155', $status];
    return "<span style='background:{$s[1]};color:{$s[2]};padding:4px 12px;border-radius:999px;font-size:0.95em;font-weight:600;display:inline-block;'>{$s[3]}</span>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Report: <?= htmlspecialchars($task['Title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #f6f8fa;
            margin: 0;
            padding: 0;
        }
        .report-container {
            background: #fff;
            max-width: 900px;
            margin: 32px auto;
            border-radius: 18px;
            box-shadow: 0 4px 32px 0 rgba(0,0,0,0.08);
            padding: 40px 32px 32px 32px;
        }
        .report-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #2563eb;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
        }
        .meta {
            margin-bottom: 1.5rem;
        }
        .meta-row {
            margin-bottom: 0.4rem;
            font-size: 1.05rem;
        }
        .meta-label {
            font-weight: 600;
            color: #475569;
        }
        .summary-box {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 18px 22px;
            margin: 1.5rem 0 2.5rem 0;
            font-size: 1.08rem;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }
        .summary-metric {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
            margin-right: 0.5rem;
        }
        .summary-label {
            color: #64748b;
            font-size: 1rem;
        }
        .assignment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.03);
        }
        .assignment-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .assignment-table td {
            padding: 13px 10px;
            font-size: 0.98rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .assignment-table tr:nth-child(even) td {
            background: #f9fafb;
        }
        .assignment-table tr:last-child td {
            border-bottom: none;
        }
        @media (max-width: 700px) {
            .report-container { padding: 18px 4vw; }
            .assignment-table th, .assignment-table td { font-size: 0.93rem; padding: 8px 4px; }
            .report-title { font-size: 1.3rem; }
            .section-title { font-size: 1.05rem; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div class="report-title">Task Report: <?= htmlspecialchars($task['Title']) ?></div>
        </div>
        <div class="meta" style="background:#f9fafb;border-radius:12px;padding:24px 28px 18px 28px;box-shadow:0 1px 4px 0 rgba(0,0,0,0.03);margin-bottom:2.2rem;max-width:520px;">
            <div style="display:grid;grid-template-columns:130px 1fr;row-gap:12px;column-gap:18px;align-items:center;">
                <div class="meta-label">Description:</div>
                <div><?= htmlspecialchars($task['Description']) ?></div>
                <div class="meta-label">Created By:</div>
                <div><?= htmlspecialchars($task['CreatorName']) ?> (<?= htmlspecialchars($task['CreatorRole']) ?>)</div>
                <div class="meta-label">Date Created:</div>
                <div><?= htmlspecialchars($task['CreatedAt']) ?></div>
                <div class="meta-label">Due Date:</div>
                <div><?= htmlspecialchars($task['DueDate']) ?></div>
                <div class="meta-label">School Year:</div>
                <div><?= htmlspecialchars($task['SchoolYear']) ?> <span class="meta-label">| Term:</span> <?= htmlspecialchars($task['Term']) ?></div>
                <div class="meta-label">Status:</div>
                <div><?= statusBadge($task['Status']) ?></div>
            </div>
        </div>
        <div class="summary-box">
            <div><span class="summary-metric"><?= $completed ?></span><span class="summary-label">Completed</span></div>
            <div><span class="summary-metric"><?= $submitted ?></span><span class="summary-label">Submitted</span></div>
            <div><span class="summary-metric"><?= $pending ?></span><span class="summary-label">Pending</span></div>
            <div style="margin-left:auto;"><span class="summary-label">Total:</span> <span class="summary-metric" style="color:#0ea5e9;"><?= $total ?></span></div>
        </div>
        <div class="section-title">Assignment Summary</div>
        <a href="generate_task_report.php?task_id=<?= $taskId ?>&export=excel" 
           style="display:inline-block;margin-bottom:18px;padding:8px 18px;background:#2563eb;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;"
           download>
           Download as Excel
        </a>
        <div style="overflow-x:auto;">
        <table class="assignment-table">
            <tr>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>Program Name</th>
                <th>Assigned Professor</th>
                <th>Co-Authors</th>
                <th>Status</th>
                <th>Submission Date</th>
            </tr>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['CourseCode']) ?></td>
                <td><?= htmlspecialchars($a['CourseTitle']) ?></td>
                <td><?= htmlspecialchars($a['ProgramName']) ?></td>
                <td><?= htmlspecialchars($a['AssignedTo']) ?: '<span style="color:red;">Unassigned</span>' ?></td>
                <td><?= !empty($a['CoAuthors']) ? htmlspecialchars(implode(', ', $a['CoAuthors'])) : '-' ?></td>
                <td><?= statusBadge($a['Status']) ?></td>
                <td><?= $a['SubmissionDate'] ? htmlspecialchars($a['SubmissionDate']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</body>
</html> 