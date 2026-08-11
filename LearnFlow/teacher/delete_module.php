<?php
require_once '../config/db.php';

$moduleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($moduleId > 0) {
    $stmt = $conn->prepare('DELETE FROM MODULE WHERE ModuleID = ?');
    if ($stmt) {
        $stmt->bind_param('i', $moduleId);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: modules.php');
exit;
