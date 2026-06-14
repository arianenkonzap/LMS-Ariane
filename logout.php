<?php
session_start();
session_unset();
session_destroy();
header('Location: /LMS-ARIANE/auth/login.php');
exit;
