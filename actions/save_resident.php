<?php
/**
 * ====================================
 * ذخیره اطلاعات ساکن (افزودن/ویرایش)
 * ====================================
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// بررسی لاگین
requireLogin();

// بررسی متد POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  setErrorMessage('متد درخواست نامعتبر است');
  redirect('../residents.php');
}

// بررسی CSRF Token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
  setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
  redirect('../residents.php');
}

// دریافت و پاکسازی داده‌ها
$residentId = !empty($_POST['resident_id']) ? (int) $_POST['resident_id'] : null;
$name = clean($_POST['name'] ?? '');
$studentId = clean($_POST['student_id'] ?? '');
$roomId = (int) ($_POST['room_id'] ?? 0);
$phone = clean($_POST['phone'] ?? '');
$parentPhone = clean($_POST['parent_phone'] ?? '');

// اعتبارسنجی
$errors = [];

if (empty($name)) {
  $errors[] = 'نام و نام خانوادگی الزامی است';
}

if (empty($studentId) || !validateStudentId($studentId)) {
  $errors[] = 'شماره دانشجویی معتبر نیست';
}

if (empty($roomId)) {
  $errors[] = 'انتخاب اتاق الزامی است';
}

if (!validatePhone($phone)) {
  $errors[] = 'شماره تلفن معتبر نیست';
}

if (!validatePhone($parentPhone)) {
  $errors[] = 'شماره تلفن والدین معتبر نیست';
}

// بررسی تکراری نبودن شماره دانشجویی
$existingStudent = dbQueryOne(
  "SELECT id FROM residents WHERE student_id = ? AND id != ?",
  [$studentId, $residentId ?? 0]
);

if ($existingStudent) {
  $errors[] = 'این شماره دانشجویی قبلاً ثبت شده است';
}

// در صورت وجود خطا
if (!empty($errors)) {
  setErrorMessage(implode('<br>', $errors));
  redirect('../residents.php');
}

// پردازش آپلود عکس
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
  $uploadResult = uploadFile($_FILES['photo'], 'photos');

  if ($uploadResult['success']) {
    $photoPath = $uploadResult['filename'];
  } else {
    setErrorMessage($uploadResult['message']);
    redirect('../residents.php');
  }
}

try {
  $db = Database::getInstance();
  $db->beginTransaction();

  if ($residentId) {
    // ویرایش ساکن موجود

    // دریافت اطلاعات قبلی
    $oldResident = dbQueryOne("SELECT * FROM residents WHERE id = ?", [$residentId]);

    if (!$oldResident) {
      throw new Exception('ساکن یافت نشد');
    }

    // ساخت کوئری UPDATE
    $updateFields = [
      'name = ?',
      'student_id = ?',
      'room_id = ?',
      'phone = ?',
      'parent_phone = ?'
    ];

    $updateParams = [$name, $studentId, $roomId, $phone, $parentPhone];

    // اگر عکس جدید آپلود شده
    if ($photoPath) {
      $updateFields[] = 'photo = ?';
      $updateParams[] = $photoPath;

      // حذف عکس قدیمی
      if ($oldResident['photo']) {
        deleteFile($oldResident['photo']);
      }
    }

    $updateParams[] = $residentId;

    $result = dbExecute(
      "UPDATE residents SET " . implode(', ', $updateFields) . " WHERE id = ?",
      $updateParams
    );

    if (!$result) {
      throw new Exception('خطا در بروزرسانی اطلاعات');
    }

    // اگر اتاق تغییر کرده، به‌روزرسانی تعداد اشغال
    if ($oldResident['room_id'] != $roomId) {
      // کاهش از اتاق قبلی
      dbExecute(
        "UPDATE rooms SET occupied = occupied - 1 WHERE id = ?",
        [$oldResident['room_id']]
      );

      // افزایش به اتاق جدید
      dbExecute(
        "UPDATE rooms SET occupied = occupied + 1 WHERE id = ?",
        [$roomId]
      );
    }

    $db->commit();
    setSuccessMessage('اطلاعات ساکن با موفقیت بروزرسانی شد');

  } else {
    // افزودن ساکن جدید

    // بررسی ظرفیت اتاق
    if (!checkRoomCapacity($roomId)) {
      throw new Exception('ظرفیت این اتاق پر است');
    }

    $insertId = dbExecute(
      "INSERT INTO residents (name, student_id, room_id, phone, parent_phone, photo, status) 
             VALUES (?, ?, ?, ?, ?, ?, 'outside')",
      [$name, $studentId, $roomId, $phone, $parentPhone, $photoPath]
    );

    if (!$insertId) {
      throw new Exception('خطا در ثبت اطلاعات');
    }

    // افزایش تعداد اشغال اتاق (trigger خودکار انجام می‌دهد، اما برای اطمینان)
    dbExecute(
      "UPDATE rooms SET occupied = occupied + 1 WHERE id = ?",
      [$roomId]
    );

    $db->commit();
    setSuccessMessage('ساکن جدید با موفقیت ثبت شد');
  }

} catch (Exception $e) {
  $db->rollback();

  // حذف عکس آپلود شده در صورت خطا
  if ($photoPath) {
    deleteFile($photoPath);
  }

  setErrorMessage('خطا: ' . $e->getMessage());
  logError("Save Resident Error: " . $e->getMessage());
}

redirect('../residents.php');
?>