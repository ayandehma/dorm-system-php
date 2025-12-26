<?php
/**
 * ====================================
 * ثبت ورود و خروج ساکن
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/sms.php';

// بررسی لاگین
requireLogin();

// بررسی متد POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jsonResponse(['success' => false, 'message' => 'متد نامعتبر']);
}

// بررسی CSRF Token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
  jsonResponse(['success' => false, 'message' => 'خطای امنیتی']);
}

$residentId = (int) ($_POST['resident_id'] ?? 0);
$action = clean($_POST['action'] ?? '');

// اعتبارسنجی
if (empty($residentId)) {
  jsonResponse(['success' => false, 'message' => 'شناسه ساکن نامعتبر است']);
}

if (!in_array($action, ['entry', 'exit'])) {
  jsonResponse(['success' => false, 'message' => 'نوع عملیات نامعتبر است']);
}

// تبدیل به فارسی
$actionFa = $action === 'entry' ? 'ورود' : 'خروج';
$newStatus = $action === 'entry' ? 'inside' : 'outside';

try {
  $db = Database::getInstance();
  $db->beginTransaction();

  // دریافت اطلاعات ساکن
  $resident = dbQueryOne(
    "SELECT r.*, rm.room_number 
         FROM residents r
         INNER JOIN rooms rm ON r.room_id = rm.id
         WHERE r.id = ?",
    [$residentId]
  );

  if (!$resident) {
    throw new Exception('ساکن یافت نشد');
  }

  // بررسی وضعیت فعلی
  if ($action === 'entry' && $resident['status'] === 'inside') {
    throw new Exception('این ساکن در حال حاضر داخل خوابگاه است');
  }

  if ($action === 'exit' && $resident['status'] === 'outside') {
    throw new Exception('این ساکن در حال حاضر خارج از خوابگاه است');
  }

  // تاریخ و زمان فعلی
  $currentDate = date('Y-m-d');
  $currentTime = date('H:i:s');

  // ارسال پیامک
  $smsResult = null;
  $smsSent = false;
  $smsStatus = '';

  if ($action === 'entry') {
    $smsResult = sendEntryNotification($resident);
  } else {
    $smsResult = sendExitNotification($resident);
  }

  // بررسی نتیجه پیامک
  if ($smsResult && isset($smsResult['skip']) && $smsResult['skip']) {
    // پیامک قبلاً امروز ارسال شده
    $smsSent = false;
    $smsStatus = $smsResult['message'];
  } elseif ($smsResult && $smsResult['success']) {
    $smsSent = true;
    $smsStatus = 'ارسال موفق';
  } else {
    $smsSent = false;
    $smsStatus = $smsResult['message'] ?? 'خطای ناشناخته';
  }

  // ثبت در لاگ حضور و غیاب
  $logId = dbExecute(
    "INSERT INTO attendance_log (resident_id, action, action_date, action_time, sms_sent, sms_status) 
         VALUES (?, ?, ?, ?, ?, ?)",
    [$residentId, $actionFa, $currentDate, $currentTime, $smsSent ? 1 : 0, $smsStatus]
  );

  if (!$logId) {
    throw new Exception('خطا در ثبت لاگ');
  }

  // بروزرسانی وضعیت ساکن
  $result = dbExecute(
    "UPDATE residents SET status = ? WHERE id = ?",
    [$newStatus, $residentId]
  );

  if (!$result) {
    throw new Exception('خطا در بروزرسانی وضعیت');
  }

  $db->commit();

  // پیام موفقیت
  $message = "{$actionFa} {$resident['name']} با موفقیت ثبت شد.";

  if ($smsSent) {
    $message .= ' پیامک به والدین ارسال شد.';
  } else {
    $message .= ' اما پیامک ارسال نشد: ' . $smsStatus;
  }

  jsonResponse([
    'success' => true,
    'message' => $message,
    'data' => [
      'resident_id' => $residentId,
      'action' => $actionFa,
      'status' => $newStatus,
      'sms_sent' => $smsSent
    ]
  ]);

} catch (Exception $e) {
  $db->rollback();
  logError("Record Attendance Error: " . $e->getMessage());
  jsonResponse([
    'success' => false,
    'message' => 'خطا: ' . $e->getMessage()
  ]);
}
?>