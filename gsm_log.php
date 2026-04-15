<?php
require_once 'tomato_db.php';

$messages = [];
$result = $conn->query("SELECT * FROM gsm_messages ORDER BY sent_at DESC LIMIT 50");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSM Message Log - Tomato Farm</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        body { background: var(--bg); padding: 20px; }
        .log-card { max-width: 900px; margin: 0 auto; }
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th, .log-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        .log-table th { background: var(--bg-alt); font-weight: 600; }
        .log-table tr:hover { background: var(--bg-alt); }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
        .status-sent { background: var(--yellow-pale); color: #996900; }
        .status-delivered { background: var(--green-pale); color: #1a7a3e; }
        .status-failed { background: var(--red-pale); color: #a62929; }
    </style>
</head>
<body>
    <div class="card log-card">
        <div class="card-head">
            <div class="card-head-label"><i class="fas fa-sms"></i> GSM Message Log</div>
            <a href="index.php" class="btn btn-outline" style="font-size: 0.8rem;">← Back to Dashboard</a>
        </div>
        <div class="card-body">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Message</th>
                        <th>Recipients</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No messages sent yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                        <tr>
                            <td>#<?= $m['id'] ?></td>
                            <td><?= htmlspecialchars($m['message']) ?></td>
                            <td><?= htmlspecialchars($m['recipients']) ?></td>
                            <td><span class="status-badge status-<?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                            <td><?= date('M d, Y h:i A', strtotime($m['sent_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>