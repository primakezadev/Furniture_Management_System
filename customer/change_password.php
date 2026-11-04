<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireRole('customer');

$pageTitle = 'Change password';
$dashboardUrl = 'dashboard.php';
$accountType = 'customer';

require __DIR__ . '/../includes/change_password_page.php';
