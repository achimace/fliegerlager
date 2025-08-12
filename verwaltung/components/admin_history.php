<?php // verwaltung/components/admin_history.php ?>
<div class="section">
    <h3>Status-Verlauf</h3>
    <table class="styled-table">
        <thead><tr><th>Zeitpunkt</th><th>Status</th><th>Nachricht</th></tr></thead>
        <tbody>
            <?php foreach ($historie as $eintrag): ?>
            <tr>
               <td><?php echo date('d.m.Y H:i', strtotime($eintrag['geaendert_am'])); ?></td>
                <td><?php echo getStatusBadge($eintrag['status']); ?></td>
                <td><?php echo htmlspecialchars($eintrag['nachricht']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>