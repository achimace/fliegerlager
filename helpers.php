<?php
// helpers.php
function ladeEinstellungen($conn) {
    $einstellungen = [];
    $sql = "SELECT einstellung_name, einstellung_wert FROM einstellungen";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $einstellungen[$row['einstellung_name']] = $row['einstellung_wert'];
        }
    }
    return $einstellungen;
}
// In helpers.php
function getStatusBadge($status) {
    $classMap = [
        'in_planung'          => 'status-in_planung',
        'eingereicht'         => 'status-eingereicht',
        'bestaetigt'          => 'status-bestaetigt',
        'abgelehnt'           => 'status-abgelehnt',
        'abrechnung_gesendet' => 'status-abrechnung_gesendet',
		'fertig_abgerechnet'  => 'status-fertig_abgerechnet'
    ];
    $className = $classMap[$status] ?? 'status-default';
    $text = ucfirst(str_replace('_', ' ', $status));
    return '<span class="status-badge ' . $className . '">' . htmlspecialchars($text) . '</span>';
}
?>