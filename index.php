<?php
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /LMS-ARIANE/' . $_SESSION['user_role'] . '/dashboard.php');
} else {
    header('Location: /LMS-ARIANE/auth/login.php');
}
exit;
