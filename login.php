<?php
/**
 * ====================================
 * صفحه ورود به سیستم
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// اگر قبلاً لاگین کرده، به داشبورد برود
if (isLoggedIn()) {
  redirect('index.php');
}

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = clean($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  // اعتبارسنجی
  if (empty($username) || empty($password)) {
    setErrorMessage('لطفاً تمام فیلدها را پر کنید');
  } else {
    // جستجوی کاربر
    $admin = dbQueryOne(
      "SELECT * FROM admins WHERE username = ?",
      [$username]
    );

    if ($admin && verifyPassword($password, $admin['password'])) {
      // لاگین موفق
      $_SESSION['admin_id'] = $admin['id'];
      $_SESSION['admin_username'] = $admin['username'];
      $_SESSION['admin_name'] = $admin['full_name'];

      // ثبت لاگ
      logError("Login successful: {$admin['username']}", 'login.log');

      redirect('index.php');
    } else {
      // لاگین ناموفق
      setErrorMessage('نام کاربری یا رمز عبور اشتباه است');
      logError("Login failed: {$username}", 'login.log');
    }
  }
}

require_once 'includes/header.php';
?>

<div class="login-container">
  <div class="login-box">
    <div class="login-logo">
      <i class="fas fa-building"></i>
      <h2><?php echo getSetting('system_name'); ?></h2>
      <p style="color: #6b7280; margin-top: 10px;">ورود به سیستم مدیریت</p>
    </div>

    <?php
    $errorMsg = getErrorMessage();
    if ($errorMsg):
      ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $errorMsg; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">
          <i class="fas fa-user"></i>
          نام کاربری
        </label>
        <input type="text" id="username" name="username" class="form-control" placeholder="نام کاربری خود را وارد کنید"
          required autofocus>
      </div>

      <div class="form-group">
        <label for="password">
          <i class="fas fa-lock"></i>
          رمز عبور
        </label>
        <input type="password" id="password" name="password" class="form-control"
          placeholder="رمز عبور خود را وارد کنید" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
        <i class="fas fa-sign-in-alt"></i>
        ورود به سیستم
      </button>
    </form>

    <div
      style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 13px;">
      <p><i class="fas fa-info-circle"></i> نام کاربری و رمز پیش‌فرض: admin / admin123</p>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>