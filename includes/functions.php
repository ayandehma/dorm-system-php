<?php
/**
 * ====================================
 * توابع کمکی سیستم
 * ====================================
 * این فایل شامل توابع کمکی مورد استفاده در سراسر سیستم است
 */

// شروع Session در صورت عدم شروع
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// بارگذاری کتابخانه تاریخ جلالی
require_once __DIR__ . '/jdf.php';

// تنظیم timezone به تهران
date_default_timezone_set('Asia/Tehran');

/**
 * ====================================
 * توابع احراز هویت
 * ====================================
 */

/**
 * بررسی لاگین بودن کاربر
 * 
 * @return bool
 */
function isLoggedIn()
{
  return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * اجبار به لاگین - ریدایرکت به صفحه لاگین در صورت عدم احراز هویت
 */
function requireLogin()
{
  if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
  }
}

/**
 * دریافت اطلاعات ادمین لاگین شده
 * 
 * @return array|null
 */
function getLoggedInAdmin()
{
  if (!isLoggedIn()) {
    return null;
  }

  $admin = dbQueryOne(
    "SELECT id, username, full_name FROM admins WHERE id = ?",
    [$_SESSION['admin_id']]
  );

  return $admin;
}

/**
 * خروج از سیستم
 */
function logout()
{
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

/**
 * ====================================
 * توابع امنیتی
 * ====================================
 */

/**
 * پاکسازی ورودی کاربر (XSS Prevention)
 * 
 * @param string $data
 * @return string
 */
function clean($data)
{
  if (is_array($data)) {
    return array_map('clean', $data);
  }
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

/**
 * تولید توکن CSRF
 * 
 * @return string
 */
function generateCSRFToken()
{
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

/**
 * بررسی اعتبار توکن CSRF
 * 
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token)
{
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * هش کردن رمز عبور
 * 
 * @param string $password
 * @return string
 */
function hashPassword($password)
{
  return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * بررسی صحت رمز عبور
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash)
{
  return password_verify($password, $hash);
}

/**
 * ====================================
 * توابع اعتبارسنجی
 * ====================================
 */

/**
 * اعتبارسنجی شماره موبایل ایران
 * 
 * @param string $phone
 * @return bool
 */
function validatePhone($phone)
{
  // فرمت: 09xxxxxxxxx (11 رقم)
  return preg_match('/^09[0-9]{9}$/', $phone);
}

/**
 * اعتبارسنجی شماره دانشجویی
 * 
 * @param string $studentId
 * @return bool
 */
function validateStudentId($studentId)
{
  // حداقل 5 کاراکتر و فقط عدد و حروف
  return preg_match('/^[a-zA-Z0-9]{5,20}$/', $studentId);
}

/**
 * اعتبارسنجی فایل آپلود شده
 * 
 * @param array $file فایل از $_FILES
 * @param array $allowedTypes انواع مجاز
 * @param int $maxSize حداکثر حجم (بایت)
 * @return array ['success' => bool, 'message' => string]
 */
function validateUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = MAX_FILE_SIZE)
{
  // بررسی خطای آپلود
  if ($file['error'] !== UPLOAD_ERR_OK) {
    return ['success' => false, 'message' => 'خطا در آپلود فایل'];
  }

  // بررسی حجم فایل
  if ($file['size'] > $maxSize) {
    return ['success' => false, 'message' => 'حجم فایل بیش از حد مجاز است'];
  }

  // بررسی نوع فایل
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  if (!in_array($mimeType, $allowedTypes)) {
    return ['success' => false, 'message' => 'فرمت فایل مجاز نیست'];
  }

  return ['success' => true, 'message' => 'فایل معتبر است'];
}

/**
 * ====================================
 * توابع مدیریت فایل
 * ====================================
 */

/**
 * آپلود فایل
 * 
 * @param array $file فایل از $_FILES
 * @param string $directory مسیر ذخیره
 * @return array ['success' => bool, 'filename' => string, 'message' => string]
 */
function uploadFile($file, $directory = 'photos')
{
  // اعتبارسنجی فایل
  $validation = validateUpload($file);
  if (!$validation['success']) {
    return $validation;
  }

  // ایجاد مسیر در صورت عدم وجود
  $uploadPath = UPLOAD_DIR . $directory . '/';
  if (!file_exists($uploadPath)) {
    mkdir($uploadPath, 0777, true);
  }

  // تولید نام یکتا برای فایل
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = uniqid() . '_' . time() . '.' . $extension;
  $destination = $uploadPath . $filename;

  // انتقال فایل
  if (move_uploaded_file($file['tmp_name'], $destination)) {
    return [
      'success' => true,
      'filename' => $directory . '/' . $filename,
      'message' => 'فایل با موفقیت آپلود شد'
    ];
  }

  return ['success' => false, 'message' => 'خطا در ذخیره فایل'];
}

/**
 * حذف فایل
 * 
 * @param string $filename نام فایل نسبت به UPLOAD_DIR
 * @return bool
 */
function deleteFile($filename)
{
  if (empty($filename)) {
    return false;
  }

  $filePath = UPLOAD_DIR . $filename;
  if (file_exists($filePath)) {
    return unlink($filePath);
  }

  return false;
}

/**
 * ====================================
 * توابع تاریخ و زمان
 * ====================================
 */

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $gregorianDate تاریخ میلادی (Y-m-d)
 * @return string تاریخ شمسی
 */
function gregorianToJalali($gregorianDate)
{
  if (empty($gregorianDate)) {
    return '';
  }

  $timestamp = strtotime($gregorianDate);
  return jdate('Y/m/d', $timestamp);
}

/**
 * تبدیل زمان به فرمت فارسی
 * 
 * @param string $time زمان (H:i:s)
 * @return string زمان فارسی
 */
function formatPersianTime($time)
{
  if (empty($time)) {
    return '';
  }

  return convertToPersianNumbers($time);
}

/**
 * دریافت تاریخ و زمان فعلی به فارسی
 * 
 * @return array ['date' => تاریخ, 'time' => زمان]
 */
function getCurrentPersianDateTime()
{
  return [
    'date' => jdate('Y/m/d'),
    'time' => jdate('H:i:s'),
    'datetime' => jdate('Y/m/d H:i:s'),
    'full' => jdate('l، j F Y - ساعت H:i')
  ];
}

/**
 * تبدیل اعداد انگلیسی به فارسی
 * 
 * @param string $string
 * @return string
 */
function convertToPersianNumbers($string)
{
  $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

  return str_replace($english, $persian, $string);
}

/**
 * ====================================
 * توابع تنظیمات
 * ====================================
 */

/**
 * دریافت مقدار تنظیمات
 * 
 * @param string $key کلید تنظیمات
 * @param mixed $default مقدار پیش‌فرض
 * @return mixed
 */
function getSetting($key, $default = null)
{
  $setting = dbQueryOne(
    "SELECT setting_value FROM settings WHERE setting_key = ?",
    [$key]
  );

  return $setting ? $setting['setting_value'] : $default;
}

/**
 * بروزرسانی تنظیمات
 * 
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function updateSetting($key, $value)
{
  return dbExecute(
    "UPDATE settings SET setting_value = ? WHERE setting_key = ?",
    [$value, $key]
  );
}

/**
 * ====================================
 * توابع پیام‌ها و نوتیفیکیشن
 * ====================================
 */

/**
 * تنظیم پیام موفقیت
 * 
 * @param string $message
 */
function setSuccessMessage($message)
{
  $_SESSION['success_message'] = $message;
}

/**
 * تنظیم پیام خطا
 * 
 * @param string $message
 */
function setErrorMessage($message)
{
  $_SESSION['error_message'] = $message;
}

/**
 * دریافت و حذف پیام موفقیت
 * 
 * @return string|null
 */
function getSuccessMessage()
{
  if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
    return $message;
  }
  return null;
}

/**
 * دریافت و حذف پیام خطا
 * 
 * @return string|null
 */
function getErrorMessage()
{
  if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
    return $message;
  }
  return null;
}

/**
 * ====================================
 * توابع مدیریت ساکنین و اتاق‌ها
 * ====================================
 */

/**
 * دریافت تعداد کل ساکنین
 * 
 * @return int
 */
function getTotalResidents()
{
  $result = dbQueryOne("SELECT COUNT(*) as total FROM residents");
  return $result ? (int) $result['total'] : 0;
}

/**
 * دریافت تعداد ساکنین داخل خوابگاه
 * 
 * @return int
 */
function getResidentsInside()
{
  $result = dbQueryOne("SELECT COUNT(*) as total FROM residents WHERE status = 'inside'");
  return $result ? (int) $result['total'] : 0;
}

/**
 * دریافت تعداد ساکنین خارج از خوابگاه
 * 
 * @return int
 */
function getResidentsOutside()
{
  $result = dbQueryOne("SELECT COUNT(*) as total FROM residents WHERE status = 'outside'");
  return $result ? (int) $result['total'] : 0;
}

/**
 * دریافت تعداد کل اتاق‌ها
 * 
 * @return int
 */
function getTotalRooms()
{
  $result = dbQueryOne("SELECT COUNT(*) as total FROM rooms");
  return $result ? (int) $result['total'] : 0;
}

/**
 * بررسی ظرفیت اتاق قبل از اضافه کردن ساکن
 * 
 * @param int $roomId
 * @return bool
 */
function checkRoomCapacity($roomId)
{
  $room = dbQueryOne(
    "SELECT capacity, occupied FROM rooms WHERE id = ?",
    [$roomId]
  );

  if (!$room) {
    return false;
  }

  return $room['occupied'] < $room['capacity'];
}

/**
 * ====================================
 * توابع کمکی عمومی
 * ====================================
 */

/**
 * ریدایرکت به صفحه دیگر
 * 
 * @param string $url
 */
function redirect($url)
{
  header("Location: $url");
  exit;
}

/**
 * نمایش JSON و توقف اجرا
 * 
 * @param mixed $data
 */
function jsonResponse($data)
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * لاگ کردن خطاها
 * 
 * @param string $message
 * @param string $file
 */
function logError($message, $file = 'error.log')
{
  $logFile = __DIR__ . '/../logs/' . $file;
  $logDir = dirname($logFile);

  if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
  }

  $timestamp = date('Y-m-d H:i:s');
  $logMessage = "[$timestamp] $message" . PHP_EOL;

  file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * نمایش داده به صورت debug (فقط در محیط توسعه)
 * 
 * @param mixed $data
 */
function dd($data)
{
  echo '<pre dir="ltr" style="background: #222; color: #0f0; padding: 20px; border-radius: 5px;">';
  var_dump($data);
  echo '</pre>';
  die();
}
?>