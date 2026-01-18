<?php
session_start();

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
        header("location: ../login.php");
        exit();
    }
}else{
    header("location: ../login.php");
    exit();
}

include("../connection.php");
date_default_timezone_set('Asia/Jakarta');

// Get month and year from GET parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get month name
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$monthName = $monthNames[$month];

// Get all attendance data for the month
$firstDay = sprintf('%04d-%02d-01', $year, $month);
$lastDay = sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year)));

// Get all specialties for mapping
$specialtiesQuery = "SELECT id, sname FROM specialties";
$specialtiesResult = $database->query($specialtiesQuery);
$specialtiesMap = [];
while ($spec = $specialtiesResult->fetch_assoc()) {
    $specialtiesMap[$spec['id']] = $spec['sname'];
}

$query = "
    SELECT 
        s.scheduleid,
        s.scheduledate,
        s.scheduletime,
        s.nop as max_capacity,
        d.docname,
        d.docemail,
        d.specialties,
        d.doctel,
        s.title as session_title,
        (SELECT COUNT(*) FROM appointment WHERE scheduleid = s.scheduleid) as total_patients,
        (SELECT COUNT(*) FROM appointment WHERE scheduleid = s.scheduleid AND status = 'success') as completed_count,
        (SELECT COUNT(*) FROM appointment WHERE scheduleid = s.scheduleid AND status = 'cancelled') as cancelled_count
    FROM schedule s
    INNER JOIN doctor d ON s.docid = d.docid
    WHERE s.scheduledate BETWEEN '$firstDay' AND '$lastDay'
    ORDER BY s.scheduledate ASC, s.scheduletime ASC, d.docname ASC
";

$result = $database->query($query);

// Calculate statistics
$totalSessions = 0;
$completedSessions = 0;
$pendingSessions = 0;
$failedSessions = 0;

$dataRows = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dataRows[] = $row;
        $totalSessions++;
        
        // Determine status based on appointments
        if ($row['completed_count'] > 0) {
            $completedSessions++;
        } elseif ($row['cancelled_count'] == $row['total_patients'] && $row['total_patients'] > 0) {
            $failedSessions++;
        } else {
            $pendingSessions++;
        }
    }
}

// Set headers for CSV download with proper Excel formatting
$filename = "Laporan_Kehadiran_" . $monthName . "_" . $year . "_" . date('YmdHis') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add report header (merged cells effect with tabs)
fputcsv($output, ['LAPORAN KEHADIRAN DOKTER'], ';');
fputcsv($output, ['Periode', $monthName . ' ' . $year], ';');
fputcsv($output, ['Tanggal Cetak', date('d/m/Y H:i:s')], ';');
fputcsv($output, [], ';'); // Empty row

// Add summary statistics
fputcsv($output, ['RINGKASAN KEHADIRAN'], ';');
fputcsv($output, ['Total Sesi', $totalSessions], ';');
fputcsv($output, ['Sesi Selesai', $completedSessions], ';');
fputcsv($output, ['Sesi Sedang Berjalan', $pendingSessions], ';');
fputcsv($output, ['Sesi Dibatalkan', $failedSessions], ';');
fputcsv($output, [], ';'); // Empty row

// Add column headers
fputcsv($output, [
    'No',
    'Tanggal',
    'Waktu',
    'Nama Dokter',
    'No. Telepon',
    'Email',
    'Spesialisasi',
    'Judul Sesi',
    'Kapasitas Maks',
    'Jumlah Pasien',
    'Status Sesi'
], ';');

// Add data rows
if (count($dataRows) > 0) {
    $no = 1;
    foreach ($dataRows as $row) {
        // Format date (with quotes to prevent Excel date conversion)
        $date = date('d/m/Y', strtotime($row['scheduledate']));
        
        // Format time (HH:MM only)
        $time = substr($row['scheduletime'], 0, 5);
        
        // Convert specialty IDs to names
        $specialtyIds = $row['specialties'];
        $specialtyNames = [];
        if (!empty($specialtyIds)) {
            $ids = array_map('trim', explode(',', $specialtyIds));
            foreach ($ids as $id) {
                if (is_numeric($id) && isset($specialtiesMap[$id])) {
                    $specialtyNames[] = $specialtiesMap[$id];
                }
            }
        }
        $specialtiesDisplay = !empty($specialtyNames) ? implode('; ', $specialtyNames) : '-';
        
        // Determine status
        $status = 'Sedang Berjalan';
        if ($row['completed_count'] > 0) {
            $status = 'Selesai';
        } elseif ($row['cancelled_count'] == $row['total_patients'] && $row['total_patients'] > 0) {
            $status = 'Dibatalkan';
        }
        
        // Clean phone number (remove spaces and format)
        $phone = preg_replace('/\s+/', '', $row['doctel']);
        
        fputcsv($output, [
            $no++,
            $date,
            $time,
            trim($row['docname']),
            $phone,
            trim($row['docemail']),
            $specialtiesDisplay,
            trim($row['session_title']),
            $row['max_capacity'],
            $row['total_patients'],
            $status
        ], ';');
    }
} else {
    fputcsv($output, ['Tidak ada data kehadiran untuk periode ini'], ';');
}

fputcsv($output, [], ';'); // Empty row
fputcsv($output, ['--- Akhir Laporan ---'], ';');

fclose($output);
exit();
?>
