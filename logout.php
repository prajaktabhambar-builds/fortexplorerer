<?php
/**
 * FORT EXPLORER - Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();
set_flash('info', 'You have been successfully logged out. See you on the next trek!');
header("Location: login.php");
exit;
