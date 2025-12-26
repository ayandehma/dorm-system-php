<?php
/**
 * هدر مشترک تمام صفحات
 */

// اگر از صفحه لاگین نیستیم، نیاز به احراز هویت داریم
if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
  requireLogin();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$admin = getLoggedInAdmin();
$systemName = getSetting('system_name', 'سیستم مدیریت خوابگاه');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $systemName; ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

  <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <!-- نوار بالایی -->
    <header class="top-header">
      <div class="container">
        <div class="header-content">
          <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
          </button>

          <h1 class="system-title"><?php echo $systemName; ?></h1>

          <div class="header-left">
            <span class="admin-name">
              <i class="fas fa-user"></i>
              <?php echo $admin['full_name']; ?>
            </span>
            <a href="logout.php" class="btn-logout" title="خروج از سیستم">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- سایدبار -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <i class="fas fa-building"></i>
        <span>منوی اصلی</span>
      </div>

      <nav class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
          <i class="fas fa-home"></i>
          <span>داشبورد</span>
        </a>

        <a href="residents.php" class="menu-item <?php echo $currentPage === 'residents' ? 'active' : ''; ?>">
          <i class="fas fa-users"></i>
          <span>مدیریت ساکنین</span>
        </a>

        <a href="rooms.php" class="menu-item <?php echo $currentPage === 'rooms' ? 'active' : ''; ?>">
          <i class="fas fa-door-open"></i>
          <span>مدیریت اتاق‌ها</span>
        </a>

        <a href="checkin.php" class="menu-item <?php echo $currentPage === 'checkin' ? 'active' : ''; ?>">
          <i class="fas fa-clock"></i>
          <span>ثبت ورود/خروج</span>
        </a>

        <a href="attendance.php" class="menu-item <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
          <i class="fas fa-clipboard-list"></i>
          <span>گزارش حضور و غیاب</span>
        </a>

        <a href="settings.php" class="menu-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
          <i class="fas fa-cog"></i>
          <span>تنظیمات</span>
        </a>
      </nav>
    </aside>

    <!-- محتوای اصلی -->
    <main class="main-content" id="mainContent">
      <div class="container">

        <?php
        // نمایش پیام‌های موفقیت
        $successMsg = getSuccessMessage();
        if ($successMsg):
          ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $successMsg; ?>
          </div>
        <?php endif; ?>

        <?php
        // نمایش پیام‌های خطا
        $errorMsg = getErrorMessage();
        if ($errorMsg):
          ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $errorMsg; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>