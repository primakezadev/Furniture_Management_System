<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireRole('admin');

$pageTitle = 'Change password';
$dashboardUrl = 'dashboard.php';
$accountType = 'administrator';

require __DIR__ . '/../includes/change_password_page.php';
