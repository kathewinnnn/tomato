<?php
require_once 'tomato_db.php';

echo "=== Schedules Status Migration ===\n\n";

// 1. Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'schedules'");
if ($table_check->num_rows == 0) {
    echo "❌ 'schedules' table not found. Creating...\n";
    $create_sql = "CREATE TABLE schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_date DATE NOT NULL,
        task_type ENUM('irrigation','fertilization','harvest','maintenance') NOT NULL DEFAULT 'irrigation',
        task_name VARCHAR(255) NOT NULL,
        task_time TIME NOT NULL DEFAULT '06:00:00',
        task_zone VARCHAR(100) DEFAULT 'Farm',
        task_notes TEXT,
        status ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if ($conn->query($create_sql)) {
        echo "✅ Table 'schedules' created.\n";
    } else {
        echo "❌ Create failed: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "✅ 'schedules' table exists.\n";
}

// 2. Check/Add status column
$col_check = $conn->query("SHOW COLUMNS FROM schedules LIKE 'status'");
if ($col_check->num_rows == 0) {
    echo "Adding status column...\n";
    $alter_sql = "ALTER TABLE schedules ADD COLUMN status ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled' AFTER task_notes";
    if ($conn->query($alter_sql)) {
        echo "✅ Status column added.\n";
    } else {
        echo "❌ Add column failed: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "✅ Status column exists.\n";
}

// 3. Mark recent past schedules as completed (last 3 days)
$past_cutoff = date('Y-m-d', strtotime('-3 days'));
$conn->query("UPDATE schedules SET status = 'completed' WHERE schedule_date >= '$past_cutoff' AND schedule_date < CURDATE()");
$completed_count = $conn->affected_rows;
echo "✅ Marked $completed_count past schedules (3 days) as 'completed'.\n";

// 4. Current stats
$total_schedules = $conn->query("SELECT COUNT(*) as cnt FROM schedules")->fetch_assoc()['cnt'];
$completed = $conn->query("SELECT COUNT(*) as cnt FROM schedules WHERE status='completed' AND schedule_date >= '$past_cutoff'")->fetch_assoc()['cnt'];
echo "\n=== Stats ===\n";
echo "Total schedules: $total_schedules\n";
echo "Recent completed (3 days): $completed\n";
echo "Sample recent: ";
$sample_result = $conn->query("SELECT schedule_date, task_name, task_type, status FROM schedules WHERE schedule_date >= '$past_cutoff' ORDER BY schedule_date DESC LIMIT 3");
while ($row = $sample_result->fetch_assoc()) {
    echo "[" . $row['schedule_date'] . " " . $row['task_name'] . " (" . $row['status'] . ")] ";
}
echo "\n\n✅ Migration COMPLETE. Delete this file after verification.\n";
$conn->close();
?>

