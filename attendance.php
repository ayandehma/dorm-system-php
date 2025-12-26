<?php
/**
 * ====================================
 * صفحه گزارش حضور و غیاب
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// فیلترها
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$residentId = $_GET['resident_id'] ?? '';
$action = $_GET['action'] ?? '';

// ساخت کوئری با فیلتر
$whereConditions = ['1=1'];
$params = [];

if (!empty($dateFrom)) {
  $whereConditions[] = "al.action_date >= ?";
  $params[] = $dateFrom;
}

if (!empty($dateTo)) {
  $whereConditions[] = "al.action_date <= ?";
  $params[] = $dateTo;
}

if (!empty($residentId)) {
  $whereConditions[] = "al.resident_id = ?";
  $params[] = $residentId;
}

if (!empty($action)) {
  $whereConditions[] = "al.action = ?";
  $params[] = $action;
}

$whereClause = implode(' AND ', $whereConditions);

// دریافت گزارشات
$attendanceLogs = dbQuery(
  "SELECT al.*, r.name as resident_name, r.student_id, rm.room_number
     FROM attendance_log al
     INNER JOIN residents r ON al.resident_id = r.id
     INNER JOIN rooms rm ON r.room_id = rm.id
     WHERE $whereClause
     ORDER BY al.action_date DESC, al.action_time DESC
     LIMIT 500",
  $params
);

// لیست ساکنین برای فیلتر
$residents = dbQuery("SELECT id, name, student_id FROM residents ORDER BY name ASC");

// آمار
$totalEntries = dbQueryOne(
  "SELECT COUNT(*) as total FROM attendance_log WHERE action = 'ورود' AND $whereClause",
  $params
)['total'] ?? 0;

$totalExits = dbQueryOne(
  "SELECT COUNT(*) as total FROM attendance_log WHERE action = 'خروج' AND $whereClause",
  $params
)['total'] ?? 0;

$totalSMS = dbQueryOne(
  "SELECT COUNT(*) as total FROM attendance_log WHERE sms_sent = 1 AND $whereClause",
  $params
)['total'] ?? 0;
?>

<div class="page-header">
  <h1 class="page-title">
    <i class="fas fa-clipboard-list"></i>
    گزارش حضور و غیاب
  </h1>
</div>

<!-- آمار -->
<div class="stats-grid" style="margin-bottom: 25px;">
  <div class="stat-card success">
    <div class="stat-icon">
      <i class="fas fa-sign-in-alt"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($totalEntries); ?></h3>
      <p>تعداد ورودها</p>
    </div>
  </div>

  <div class="stat-card danger">
    <div class="stat-icon">
      <i class="fas fa-sign-out-alt"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($totalExits); ?></h3>
      <p>تعداد خروج‌ها</p>
    </div>
  </div>

  <div class="stat-card primary">
    <div class="stat-icon">
      <i class="fas fa-sms"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($totalSMS); ?></h3>
      <p>پیامک‌های ارسالی</p>
    </div>
  </div>

  <div class="stat-card warning">
    <div class="stat-icon">
      <i class="fas fa-list"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers(count($attendanceLogs)); ?></h3>
      <p>کل رکوردها</p>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">فیلتر گزارشات</h2>
  </div>

  <!-- فرم فیلتر -->
  <form method="GET" action="" style="padding: 20px; background: var(--light-color);">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label for="date_from">از تاریخ</label>
        <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="date_to">تا تاریخ</label>
        <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="resident_id">ساکن</label>
        <select id="resident_id" name="resident_id" class="form-control">
          <option value="">همه ساکنین</option>
          <?php foreach ($residents as $resident): ?>
            <option value="<?php echo $resident['id']; ?>" <?php echo $residentId == $resident['id'] ? 'selected' : ''; ?>>
              <?php echo $resident['name']; ?> (<?php echo $resident['student_id']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 0;">
        <label for="action">نوع عملیات</label>
        <select id="action" name="action" class="form-control">
          <option value="">همه</option>
          <option value="ورود" <?php echo $action === 'ورود' ? 'selected' : ''; ?>>ورود</option>
          <option value="خروج" <?php echo $action === 'خروج' ? 'selected' : ''; ?>>خروج</option>
        </select>
      </div>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 15px;">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-filter"></i>
        اعمال فیلتر
      </button>
      <a href="attendance.php" class="btn btn-secondary">
        <i class="fas fa-times"></i>
        پاک کردن فیلتر
      </a>
      <button type="button" class="btn btn-success" onclick="exportToExcel()">
        <i class="fas fa-file-excel"></i>
        خروجی Excel
      </button>
    </div>
  </form>

  <!-- جدول گزارشات -->
  <div class="table-responsive">
    <table id="attendanceTable">
      <thead>
        <tr>
          <th>ردیف</th>
          <th>نام ساکن</th>
          <th>شماره دانشجویی</th>
          <th>اتاق</th>
          <th>عملیات</th>
          <th>تاریخ</th>
          <th>ساعت</th>
          <th>وضعیت پیامک</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($attendanceLogs)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px;">
              <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; display: block; margin-bottom: 10px;"></i>
              هیچ رکوردی یافت نشد
            </td>
          </tr>
        <?php else: ?>
          <?php $counter = 1; ?>
          <?php foreach ($attendanceLogs as $log): ?>
            <tr>
              <td><?php echo convertToPersianNumbers($counter++); ?></td>
              <td><strong><?php echo $log['resident_name']; ?></strong></td>
              <td><?php echo convertToPersianNumbers($log['student_id']); ?></td>
              <td>
                <span class="badge badge-info">
                  <?php echo convertToPersianNumbers($log['room_number']); ?>
                </span>
              </td>
              <td>
                <span class="badge badge-<?php echo $log['action'] === 'ورود' ? 'success' : 'danger'; ?>">
                  <i class="fas fa-<?php echo $log['action'] === 'ورود' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
                  <?php echo $log['action']; ?>
                </span>
              </td>
              <td><?php echo gregorianToJalali($log['action_date']); ?></td>
              <td style="direction: ltr; text-align: right;">
                <?php echo formatPersianTime(substr($log['action_time'], 0, 5)); ?></td>
              <td>
                <?php if ($log['sms_sent']): ?>
                  <span class="badge badge-success">
                    <i class="fas fa-check-circle"></i>
                    ارسال شد
                  </span>
                <?php else: ?>
                  <span class="badge badge-danger" title="<?php echo $log['sms_status']; ?>">
                    <i class="fas fa-times-circle"></i>
                    ارسال نشد
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  // خروجی Excel (ساده با استفاده از HTML table)
  function exportToExcel() {
    const table = document.getElementById('attendanceTable');
    const html = table.outerHTML;

    const blob = new Blob(['\ufeff' + html], {
      type: 'application/vnd.ms-excel;charset=utf-8'
    });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'attendance_report_' + new Date().toISOString().split('T')[0] + '.xls';
    link.click();

    showNotification('فایل Excel در حال دانلود است', 'success');
  }

  // تنظیم تاریخ پیش‌فرض (هفته اخیر)
  window.addEventListener('DOMContentLoaded', function () {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    if (!dateFrom.value) {
      const weekAgo = new Date();
      weekAgo.setDate(weekAgo.getDate() - 7);
      dateFrom.value = weekAgo.toISOString().split('T')[0];
    }

    if (!dateTo.value) {
      dateTo.value = new Date().toISOString().split('T')[0];
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>