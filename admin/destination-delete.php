<?php
/**
 * FORT EXPLORER - Admin Delete Destination Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $db = getDB();
            try {
                $del = $db->prepare("DELETE FROM destinations WHERE id = ?");
                $del->execute([$id]);
                set_flash('success', 'Destination deleted successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Error deleting destination: ' . $e->getMessage());
            }
        }
    } else {
        set_flash('danger', 'Invalid security token.');
    }
}

header("Location: destinations.php");
exit;
