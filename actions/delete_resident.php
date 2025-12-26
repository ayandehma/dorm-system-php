<?php
/**
 * ====================================
 * حذف ساکن
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// بررسی لاگین
requireLogin();

// بررسی CSRF Token
if (!validateCSRFToken($_GET['csrf_token'] ?? '')) {
  setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
  redirect('../residents.php');
}

$residentId = (int) ($_GET['id'] ?? 0);

if (empty($residentId)) {
  setErrorMessage('شناسه نامعتبر است');
  redirect('../residents.php');
}

try {
  $db = Database::getInstance();
  $db->beginTransaction();

  // دریافت اطلاعات ساکن
  $resident = dbQueryOne("SELECT * FROM residents WHERE id = ?", [$residentId]);

  if (!$resident) {
    throw new Exception('ساکن یافت نشد');
  }

  // حذف ساکن
  $result = dbExecute("DELETE FROM residents WHERE id = ?", [$residentId]);

  if (!$result) {
    throw new Exception('خطا در حذف ساکن');
  }

  // حذف عکس
  if ($resident['photo']) {
    deleteFile($resident['photo']);
  }

  // کاهش تعداد اشغال اتاق (trigger خودکار انجام می‌دهد، اما برای اطمینان)
  dbExecute(
    "UPDATE rooms SET occupied = GREATEST(0, occupied - 1) WHERE id = ?",
    [$resident['room_id']]
  );

  $db->commit();
  setSuccessMessage('ساکن با موفقیت حذف شد');

} catch (Exception $e) {
  $db->rollback();
  setErrorMessage('خطا: ' . $e->getMessage());
  logError("Delete Resident Error: " . $e->getMessage());
}

redirect('../residents.php');
?>