<?php
session_start();

// Authentication check
if(!isset($_SESSION["user"]) || $_SESSION['usertype']!='p'){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include("../connection.php");

$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if($service_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// Get doctors with this specialty/service
// Uses FIND_IN_SET to support comma-separated specialties
$query = "
    SELECT DISTINCT 
        d.docid, 
        d.docname, 
        d.docemail,
        d.specialties,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM schedule s 
                WHERE s.docid = d.docid 
                AND s.scheduledate = ? 
                AND s.attendance_status = 'present'
            ) THEN 1 
            ELSE 0 
        END as available
    FROM doctor d
    WHERE FIND_IN_SET(?, d.specialties) > 0
    ORDER BY available DESC, d.docname ASC
";

$stmt = $database->prepare($query);
$stmt->bind_param("si", $today, $service_id);
$stmt->execute();
$result = $stmt->get_result();

$doctors = [];
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $doctors[] = [
            'docid' => $row['docid'],
            'docname' => $row['docname'],
            'docemail' => $row['docemail'],
            'available' => ($row['available'] == 1)
        ];
    }
}

echo json_encode([
    'success' => true,
    'doctors' => $doctors,
    'count' => count($doctors)
]);
?>
