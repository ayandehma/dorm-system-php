<?php
/**
 * ====================================
 * صفحه خروج از سیستم
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// ثبت لاگ قبل از خروج
if (isLoggedIn()) {
  $admin = getLoggedInAdmin();
  logError("Logout: {$admin['username']}", 'login.log');
}

// خروج از سیستم
logout();
?>