<?php
/**
 * ====================================
 * ذخیره اطلاعات اتاق (افزودن/ویرایش)
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// بررسی لاگین
requireLogin();

// بررسی متد POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  setErrorMessage('متد درخواست نامعتبر است');
  redirect('../rooms.php');
}

// بررسی CSRF Token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
  setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
  redirect('../rooms.php');
}

// دریافت و پاکسازی داده‌ها
$roomId = !empty($_POST['room_id']) ? (int) $_POST['room_id'] : null;
$roomNumber = clean($_POST['room_number'] ?? '');
$floor = (int) ($_POST['floor'] ?? 0);
$capacity = (int) ($_POST['capacity'] ?? 0);

// اعتبارسنجی
$errors = [];

if (empty($roomNumber)) {
  $errors[] = 'شماره اتاق الزامی است';
}

if ($floor < 1) {
  $errors[] = 'طبقه باید عددی مثبت باشد';
}

if ($capacity < 1) {
  $errors[] = 'ظرفیت باید حداقل 1 نفر باشد';
}

// بررسی تکراری نبودن شماره اتاق
$existingRoom = dbQueryOne(
  "SELECT id FROM rooms WHERE room_number = ? AND id != ?",
  [$roomNumber, $roomId ?? 0]
);

if ($existingRoom) {
  $errors[] = 'این شماره اتاق قبلاً ثبت شده است';
}

// در صورت وجود خطا
if (!empty($errors)) {
  setErrorMessage(implode('<br>', $errors));
  redirect('../rooms.php');
}

try {
  $db = Database::getInstance();
  $db->beginTransaction();

  if ($roomId) {
    // ویرایش اتاق موجود

    // دریافت اطلاعات قبلی
    $oldRoom = dbQueryOne("SELECT * FROM rooms WHERE id = ?", [$roomId]);

    if (!$oldRoom) {
      throw new Exception('اتاق یافت نشد');
    }

    // بررسی کاهش ظرفیت
    if ($capacity < $oldRoom['occupied']) {
      throw new Exception("نمی‌توان ظرفیت را کمتر از تعداد ساکنین فعلی ({$oldRoom['occupied']} نفر) تنظیم کرد");
    }

    $result = dbExecute(
      "UPDATE rooms SET room_number = ?, floor = ?, capacity = ? WHERE id = ?",
      [$roomNumber, $floor, $capacity, $roomId]
    );

    if (!$result) {
      throw new Exception('خطا در بروزرسانی اطلاعات');
    }

    $db->commit();
    setSuccessMessage('اطلاعات اتاق با موفقیت بروزرسانی شد');

  } else {
    // افزودن اتاق جدید

    $insertId = dbExecute(
      "INSERT INTO rooms (room_number, floor, capacity, occupied) VALUES (?, ?, ?, 0)",
      [$roomNumber, $floor, $capacity]
    );

    if (!$insertId) {
      throw new Exception('خطا در ثبت اطلاعات');
    }

    $db->commit();
    setSuccessMessage('اتاق جدید با موفقیت ثبت شد');
  }

} catch (Exception $e) {
  $db->rollback();
  setErrorMessage('خطا: ' . $e->getMessage());
  logError("Save Room Error: " . $e->getMessage());
}

redirect('../rooms.php');
?>