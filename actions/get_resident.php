<?php
/**
 * ====================================
 * دریافت اطلاعات ساکن برای ویرایش (AJAX)
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// بررسی لاگین
requireLogin();

// بررسی متد GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  jsonResponse(['success' => false, 'message' => 'متد نامعتبر']);
}

$residentId = (int) ($_GET['id'] ?? 0);

if (empty($residentId)) {
  jsonResponse(['success' => false, 'message' => 'شناسه نامعتبر']);
}

// دریافت اطلاعات ساکن
$resident = dbQueryOne(
  "SELECT * FROM residents WHERE id = ?",
  [$residentId]
);

if (!$resident) {
  jsonResponse(['success' => false, 'message' => 'ساکن یافت نشد']);
}

jsonResponse([
  'success' => true,
  'data' => $resident
]);
?>