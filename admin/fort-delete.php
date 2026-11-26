<?php
/**
 * FORT EXPLORER - Admin Delete Fort Handler
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
                $fStmt = $db->prepare("SELECT name, destination_id FROM forts WHERE id = ?");
                $fStmt->execute([$id]);
                $fort = $fStmt->fetch();

                if ($fort) {
                    $del = $db->prepare("DELETE FROM forts WHERE id = ?");
                    $del->execute([$id]);

                    if (!empty($fort['destination_id'])) {
                        $delDest = $db->prepare("DELETE FROM destinations WHERE id = ?");
                        $delDest->execute([$fort['destination_id']]);
                    }

                    set_flash('success', "Fort '" . htmlspecialchars($fort['name']) . "' removed successfully.");
                } else {
                    set_flash('warning', 'Fort record not found.');
                }
            } catch (Exception $e) {
                set_flash('danger', 'Error deleting fort: ' . $e->getMessage());
            }
        }
    } else {
        set_flash('danger', 'Invalid security token.');
    }
}

header("Location: forts.php");
exit;
