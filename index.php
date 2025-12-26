<?php
/**
 * ====================================
 * داشبورد اصلی سیستم
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// دریافت آمار
$totalResidents = getTotalResidents();
$residentsInside = getResidentsInside();
$residentsOutside = getResidentsOutside();
$totalRooms = getTotalRooms();

// دریافت آخرین فعالیت‌ها
$recentActivities = dbQuery(
  "SELECT al.*, r.name as resident_name, r.student_id, rm.room_number
     FROM attendance_log al
     INNER JOIN residents r ON al.resident_id = r.id
     INNER JOIN rooms rm ON r.room_id = rm.id
     ORDER BY al.created_at DESC
     LIMIT 10"
);

// دریافت اتاق‌ها برای نمایش
$rooms = dbQuery(
  "SELECT * FROM rooms ORDER BY room_number ASC LIMIT 6"
);
?>

<div class="page-header">
  <h1 class="page-title">
    <i class="fas fa-home"></i>
    داشبورد
  </h1>
  <p class="page-subtitle">خوش آمدید، <?php echo $admin['full_name']; ?></p>
</div>

<!-- آمارها -->
<div class="stats-grid">
  <div class="stat-card primary">
    <div class="stat-icon">
      <i class="fas fa-users"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($totalResidents); ?></h3>
      <p>تعداد کل ساکنین</p>
    </div>
  </div>

  <div class="stat-card success">
    <div class="stat-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($residentsInside); ?></h3>
      <p>حاضر در خوابگاه</p>
    </div>
  </div>

  <div class="stat-card warning">
    <div class="stat-icon">
      <i class="fas fa-door-open"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($residentsOutside); ?></h3>
      <p>خارج از خوابگاه</p>
    </div>
  </div>

  <div class="stat-card danger">
    <div class="stat-icon">
      <i class="fas fa-bed"></i>
    </div>
    <div class="stat-content">
      <h3><?php echo convertToPersianNumbers($totalRooms); ?></h3>
      <p>تعداد اتاق‌ها</p>
    </div>
  </div>
</div>

<div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px;">

  <!-- آخرین فعالیت‌ها -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <i class="fas fa-history"></i>
        آخرین فعالیت‌ها
      </h2>
      <a href="attendance.php" class="btn btn-sm btn-primary">
        <i class="fas fa-list"></i>
        مشاهده همه
      </a>
    </div>

    <div class="activity-list">
      <?php if (empty($recentActivities)): ?>
        <div class="empty-state">
          <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
          <p>هیچ فعالیتی ثبت نشده است</p>
        </div>
      <?php else: ?>
        <?php foreach ($recentActivities as $activity): ?>
          <div class="activity-item">
            <div class="activity-icon <?php echo $activity['action'] === 'ورود' ? 'success' : 'danger'; ?>">
              <i class="fas fa-<?php echo $activity['action'] === 'ورود' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
            </div>
            <div class="activity-content">
              <div class="activity-title">
                <strong><?php echo $activity['resident_name']; ?></strong>
                <span class="badge badge-<?php echo $activity['action'] === 'ورود' ? 'success' : 'danger'; ?>">
                  <?php echo $activity['action']; ?>
                </span>
              </div>
              <div class="activity-meta">
                <span><i class="fas fa-calendar"></i> <?php echo gregorianToJalali($activity['action_date']); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo formatPersianTime($activity['action_time']); ?></span>
                <span><i class="fas fa-door-open"></i> اتاق <?php echo $activity['room_number']; ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- اتاق‌ها -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <i class="fas fa-bed"></i>
        وضعیت اتاق‌ها
      </h2>
      <a href="rooms.php" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i>
        مدیریت اتاق‌ها
      </a>
    </div>

    <div class="rooms-grid">
      <?php if (empty($rooms)): ?>
        <div class="empty-state">
          <i class="fas fa-bed" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
          <p>هیچ اتاقی ثبت نشده است</p>
        </div>
      <?php else: ?>
        <?php foreach ($rooms as $room): ?>
          <div class="room-card">
            <div class="room-header">
              <h3>اتاق <?php echo $room['room_number']; ?></h3>
              <span class="badge badge-info">طبقه <?php echo convertToPersianNumbers($room['floor']); ?></span>
            </div>
            <div class="room-capacity">
              <div class="capacity-info">
                <span>ظرفیت: <?php echo convertToPersianNumbers($room['capacity']); ?> نفر</span>
                <span>اشغال: <?php echo convertToPersianNumbers($room['occupied']); ?> نفر</span>
              </div>
              <div class="capacity-bar">
                <div class="capacity-fill"
                  style="width: <?php echo ($room['capacity'] > 0) ? ($room['occupied'] / $room['capacity'] * 100) : 0; ?>%">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<style>
  /* استایل‌های اضافی برای داشبورد */
  .page-header {
    margin-bottom: 30px;
  }

  .page-title {
    font-size: 28px;
    color: var(--dark-color);
    margin-bottom: 5px;
  }

  .page-subtitle {
    color: var(--text-color);
    font-size: 15px;
  }

  .activity-list {
    max-height: 500px;
    overflow-y: auto;
  }

  .activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
  }

  .activity-item:last-child {
    border-bottom: none;
  }

  .activity-item:hover {
    background: var(--light-color);
  }

  .activity-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
  }

  .activity-icon.success {
    background: linear-gradient(135deg, #10b981, #059669);
  }

  .activity-icon.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
  }

  .activity-content {
    flex: 1;
  }

  .activity-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
  }

  .activity-meta {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: var(--text-color);
  }

  .activity-meta i {
    margin-left: 5px;
  }

  .rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
  }

  .room-card {
    background: var(--light-color);
    border-radius: 10px;
    padding: 15px;
    border-right: 4px solid var(--primary-color);
    transition: var(--transition);
  }

  .room-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }

  .room-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .room-header h3 {
    font-size: 18px;
    color: var(--dark-color);
  }

  .room-capacity {
    margin-top: 10px;
  }

  .capacity-info {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--text-color);
    margin-bottom: 8px;
  }

  .capacity-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
  }

  .capacity-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    transition: width 0.3s ease;
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-color);
  }

  .row {
    margin-bottom: 25px;
  }
</style>

<?php require_once 'includes/footer.php'; ?>