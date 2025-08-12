<?php
// get_events.php
session_start();
header('Content-Type: application/json');
require_once 'Database.php';

$db = new Database();
$conn = $db->getConnection();
$events = [];

// Identify user status
$is_admin = isset($_SESSION['loggedin_admin']) && $_SESSION['loggedin_admin'] === true;
$is_customer = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$customer_id = $_SESSION['user_id'] ?? 0;
$current_id = $_GET['current_id'] ?? 0; 

// This query fetches:
// 1. 'bestaetigt' and 'eingereicht' camps.
// 2. OR 'in_planung' AND 'abgelehnt' camps that belong to the logged-in user.
$query_lager = "
    SELECT f.*, u.vereinsname, u.vorname, u.nachname, u.email, u.mobiltelefon 
    FROM fluglager f 
    JOIN users u ON f.user_id = u.id 
    WHERE 
        f.status IN ('bestaetigt', 'eingereicht') 
        OR (f.status IN ('in_planung', 'abgelehnt') AND f.user_id = ?)
";
$stmt = $conn->prepare($query_lager);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result_lager = $stmt->get_result();

while ($row = $result_lager->fetch_assoc()) {
    $event_data = [
        'start' => $row['startdatum'],
        'end'   => date('Y-m-d', strtotime($row['enddatum'] . ' +1 day')),
        'extendedProps' => [
            'vereinsname' => $row['vereinsname'],
            'kontakt' => $row['vorname'] . ' ' . $row['nachname'],
            'email' => $row['email'],
            'telefon' => $row['mobiltelefon'],
            'is_exclusive' => (bool)$row['exklusiv'],
            'status' => $row['status']
        ]
    ];

    // Highlight the event that is currently being edited
    if ($row['id'] == $current_id) {
        $event_data['title'] = 'Dieses Fluglager (in Bearbeitung)';
        $event_data['className'] = 'event-current';
        $event_data['color'] = '#ffc107'; 
    } else {
        // Logic for all other events
        if ($is_admin) {
            $title = 'Lager: ' . $row['vereinsname'] . ' (' . $row['status'] . ')';
            if ($row['exklusiv']) $title .= ' [EXKLUSIV]';
            $event_data['title'] = $title;
            if ($row['status'] == 'bestaetigt') $event_data['color'] = '#28a745';
            elseif ($row['status'] == 'eingereicht') $event_data['color'] = '#ffc107';
            else $event_data['color'] = '#adb5bd';
        } elseif ($is_customer) {
            if ($row['user_id'] == $customer_id) { 
                // User's own bookings
                if ($row['status'] == 'in_planung' || $row['status'] == 'abgelehnt') {
                    $event_data['title'] = 'Mein Fluglager (' . str_replace('_', ' ', $row['status']) . ')';
                    $event_data['color'] = '#6c757d'; // Gray for both "work-in-progress" statuses
                } else {
                    $event_data['title'] = 'Mein Fluglager (' . $row['status'] . ')';
                    $event_data['color'] = $row['status'] == 'bestaetigt' ? '#007bff' : '#F68A1E';
                }
            } else { 
                // Bookings from other users
                if ($row['status'] == 'bestaetigt') {
                    $event_data['title'] = 'Belegt durch: ' . $row['vereinsname'];
                    $event_data['color'] = '#6c757d';
                } else { continue; }
            }
        } else {
            // Public visitors
            if ($row['status'] == 'bestaetigt') {
                $event_data['title'] = 'Belegt';
                $event_data['color'] = '#6c757d';
                $event_data['extendedProps'] = [];
            } else { continue; }
        }
    }
    
    $events[] = $event_data;
}
$stmt->close();

// Load manually blocked dates
if ($is_admin || $is_customer) {
    $query_block = "SELECT * FROM kalender_block";
    $result_block = $conn->query($query_block);
    while ($row = $result_block->fetch_assoc()) {
        $events[] = [
            'title' => 'Gesperrt: ' . $row['grund'],
            'start' => $row['startdatum'],
            'end'   => date('Y-m-d', strtotime($row['enddatum'] . ' +1 day')),
            'color' => '#343a40',
            'extendedProps' => ['is_admin_block' => true]
        ];
    }
}

echo json_encode($events);
?>