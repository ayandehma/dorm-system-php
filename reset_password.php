<?php
/**
 * ====================================
 * بازنشانی رمز عبور ادمین
 * ====================================
 * این فایل را فقط یک بار اجرا کنید و سپس حذف کنید!
 */

require_once 'config/database.php';

// تنظیمات
$username = 'admin';
$newPassword = 'admin123';

// هش کردن رمز
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

echo "<h1>بازنشانی رمز عبور</h1>";
echo "<p>نام کاربری: <strong>$username</strong></p>";
echo "<p>رمز جدید: <strong>$newPassword</strong></p>";
echo "<p>رمز هش شده: <code>$hashedPassword</code></p>";
echo "<hr>";

try {
  $db = Database::getInstance();

  // بروزرسانی رمز
  $result = $db->execute(
    "UPDATE admins SET password = ? WHERE username = ?",
    [$hashedPassword, $username]
  );

  if ($result) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ رمز عبور با موفقیت بروزرسانی شد!</strong></p>";
    echo "<p>حالا می‌توانید با اطلاعات زیر وارد شوید:</p>";
    echo "<ul>";
    echo "<li>نام کاربری: <strong>admin</strong></li>";
    echo "<li>رمز عبور: <strong>admin123</strong></li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>رفتن به صفحه ورود</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ مهم: این فایل را حذف کنید!</strong></p>";
    echo "<p>برای امنیت، حتماً فایل <code>reset_password.php</code> را از سرور حذف کنید.</p>";
  } else {
    echo "<p style='color: red;'><strong>❌ خطا در بروزرسانی رمز!</strong></p>";
  }

} catch (Exception $e) {
  echo "<p style='color: red;'><strong>❌ خطا: " . $e->getMessage() . "</strong></p>";
}

// نمایش اطلاعات دیتابیس برای دیباگ
echo "<hr>";
echo "<h2>اطلاعات دیتابیس (برای دیباگ)</h2>";

try {
  $admin = $db->queryOne("SELECT * FROM admins WHERE username = ?", [$username]);

  if ($admin) {
    echo "<p>✅ کاربر پیدا شد:</p>";
    echo "<ul>";
    echo "<li>ID: " . $admin['id'] . "</li>";
    echo "<li>نام کاربری: " . $admin['username'] . "</li>";
    echo "<li>نام کامل: " . $admin['full_name'] . "</li>";
    echo "<li>رمز هش شده: <code>" . substr($admin['password'], 0, 50) . "...</code></li>";
    echo "</ul>";

    // تست رمز
    if (password_verify($newPassword, $admin['password'])) {
      echo "<p style='color: green;'><strong>✅ تست رمز موفق - رمز صحیح است!</strong></p>";
    } else {
      echo "<p style='color: red;'><strong>❌ تست رمز ناموفق - مشکلی وجود دارد!</strong></p>";
    }
  } else {
    echo "<p style='color: red;'>❌ کاربر پیدا نشد!</p>";
  }

} catch (Exception $e) {
  echo "<p style='color: red;'>خطا: " . $e->getMessage() . "</p>";
}
?>

<style>
  body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
  }

  code {
    background: #e0e0e0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
  }
</style>