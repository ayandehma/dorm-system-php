<?php
/**
 * ====================================
 * حذف اتاق
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// بررسی لاگین
requireLogin();

// بررسی CSRF Token
if (!validateCSRFToken($_GET['csrf_token'] ?? '')) {
  setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
  redirect('../rooms.php');
}

$roomId = (int) ($_GET['id'] ?? 0);

if (empty($roomId)) {
  setErrorMessage('شناسه نامعتبر است');
  redirect('../rooms.php');
}

try {
  // دریافت اطلاعات اتاق
  $room = dbQueryOne("SELECT * FROM rooms WHERE id = ?", [$roomId]);

  if (!$room) {
    throw new Exception('اتاق یافت نشد');
  }

  // بررسی وجود ساکن در اتاق
  if ($room['occupied'] > 0) {
    throw new Exception('این اتاق دارای ساکن است و نمی‌توان آن را حذف کرد. ابتدا ساکنین را منتقل کنید.');
  }

  // حذف اتاق
  $result = dbExecute("DELETE FROM rooms WHERE id = ?", [$roomId]);

  if (!$result) {
    throw new Exception('خطا در حذف اتاق');
  }

  setSuccessMessage('اتاق با موفقیت حذف شد');

} catch (Exception $e) {
  setErrorMessage('خطا: ' . $e->getMessage());
  logError("Delete Room Error: " . $e->getMessage());
}

redirect('../rooms.php');
?>