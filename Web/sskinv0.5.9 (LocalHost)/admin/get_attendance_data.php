<?php
ob_start();
session_start();

// Check authentication
if (!isset($_SESSION["user"]) || $_SESSION['usertype'] != 'a') {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Database connection
$server = "localhost";
$username = "root";
$password = "";
$dbname = "edoc";

$database = new mysqli($server, $username, $password, $dbname);

if ($database->connect_error) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

if (!isset($_GET['date'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Date parameter required']));
}

$date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid date format']));
}

try {
    // Get all doctors with schedules on this date
    $query = "
        SELECT 
            s.scheduleid,
            s.docid,
            s.scheduledate,
            s.scheduletime,
            s.title,
            s.nop,
            d.docname,
            d.specialties
        FROM schedule s
        INNER JOIN doctor d ON s.docid = d.docid
        WHERE s.scheduledate = ?
        ORDER BY s.scheduletime ASC, d.docname ASC
    ";

    $stmt = $database->prepare($query);
    if (!$stmt) {
        throw new Exception('Query prepare error: ' . $database->error);
    }

    $stmt->bind_param('s', $date);
    if (!$stmt->execute()) {
        throw new Exception('Query execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        // Get specialty names
        $specialty_names = [];
        if (!empty($row['specialties']) && trim($row['specialties']) != '') {
            $spec_ids = array_filter(array_map('trim', explode(',', $row['specialties'])));
            foreach ($spec_ids as $spec_id) {
                if (is_numeric($spec_id)) {
                    $spec_res = $database->query("SELECT sname FROM specialties WHERE id='" . intval($spec_id) . "'");
                    if ($spec_res && $spec_res->num_rows > 0) {
                        $spec_data = $spec_res->fetch_assoc();
                        $specialty_names[] = $spec_data['sname'];
                    }
                }
            }
        }
        $specialty_display = !empty($specialty_names) ? implode(', ', $specialty_names) : 'Layanan Umum';
        
        // Count confirmed patients
        $patient_count = 0;
        $patient_query = $database->query("SELECT COUNT(*) as count FROM appointment WHERE scheduleid = " . intval($row['scheduleid']) . " AND IFNULL(status,'') = 'confirmed'");
        if ($patient_query && $patient_query->num_rows > 0) {
            $patient_data = $patient_query->fetch_assoc();
            $patient_count = intval($patient_data['count']);
        }
        
        // Determine session status based on appointment status
        $session_status = 'pending'; // Default
        
        // Check if any appointment in this session has status 'success' (completed)
        $completed_check = $database->query("SELECT COUNT(*) as count FROM appointment WHERE scheduleid = " . intval($row['scheduleid']) . " AND status = 'success'");
        if ($completed_check && $completed_check->num_rows > 0) {
            $completed_data = $completed_check->fetch_assoc();
            if ($completed_data['count'] > 0) {
                $session_status = 'completed';
            }
        }
        
        // Check if session was cancelled/failed
        $cancelled_check = $database->query("SELECT COUNT(*) as count FROM appointment WHERE scheduleid = " . intval($row['scheduleid']) . " AND status = 'cancelled'");
        if ($cancelled_check && $cancelled_check->num_rows > 0) {
            $cancelled_data = $cancelled_check->fetch_assoc();
            if ($cancelled_data['count'] > 0) {
                $session_status = 'failed';
            }
        }
        
        $doctors[] = [
            'scheduleid' => intval($row['scheduleid']),
            'docid' => intval($row['docid']),
            'docname' => $row['docname'],
            'specialties' => $specialty_display,
            'scheduledate' => $row['scheduledate'],
            'scheduletime' => $row['scheduletime'],
            'title' => $row['title'],
            'nop' => intval($row['nop']),
            'confirmed_patients' => $patient_count,
            'session_status' => $session_status
        ];
    }

    $database->close();
    
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'date' => $date,
        'doctors' => $doctors
    ]);
    
} catch (Exception $e) {
    $database->close();
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}