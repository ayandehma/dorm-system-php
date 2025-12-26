<?php
/**
 * ====================================
 * صفحه مدیریت اتاق‌ها
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// دریافت لیست اتاق‌ها
$rooms = dbQuery("SELECT * FROM rooms ORDER BY CAST(room_number AS UNSIGNED), room_number ASC");
?>

<div class="page-header">
  <h1 class="page-title">
    <i class="fas fa-door-open"></i>
    مدیریت اتاق‌ها
  </h1>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">لیست اتاق‌ها</h2>
      <p style="font-size: 14px; color: var(--text-color); margin-top: 5px;">
        تعداد کل: <?php echo convertToPersianNumbers(count($rooms)); ?> اتاق
      </p>
    </div>
    <button class="btn btn-success" data-modal="addRoomModal">
      <i class="fas fa-plus"></i>
      افزودن اتاق جدید
    </button>
  </div>

  <!-- جستجو -->
  <div style="padding: 20px; background: var(--light-color); margin-bottom: 20px;">
    <input type="text" id="searchInput" class="form-control" placeholder="جستجو بر اساس شماره اتاق یا طبقه..."
      style="max-width: 400px;">
  </div>

  <div class="rooms-grid-view">
    <?php if (empty($rooms)): ?>
      <div class="empty-state" style="grid-column: 1/-1;">
        <i class="fas fa-bed" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
        <p>هیچ اتاقی ثبت نشده است</p>
      </div>
    <?php else: ?>
      <?php foreach ($rooms as $room): ?>
        <?php
        $percentage = $room['capacity'] > 0 ? ($room['occupied'] / $room['capacity']) * 100 : 0;
        $statusClass = $percentage >= 100 ? 'full' : ($percentage > 0 ? 'partial' : 'empty');
        ?>
        <div class="room-card-detail <?php echo $statusClass; ?>"
          data-room-info="<?php echo htmlspecialchars(json_encode($room)); ?>">
          <div class="room-card-header">
            <div>
              <h3 class="room-number">اتاق <?php echo convertToPersianNumbers($room['room_number']); ?></h3>
              <span class="room-floor">
                <i class="fas fa-layer-group"></i>
                طبقه <?php echo convertToPersianNumbers($room['floor']); ?>
              </span>
            </div>
            <div class="room-actions">
              <button class="btn btn-sm btn-icon btn-primary edit-room" title="ویرایش">
                <i class="fas fa-edit"></i>
              </button>
              <a href="actions/delete_room.php?id=<?php echo $room['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                class="btn btn-sm btn-icon btn-danger" onclick="return confirmDelete('آیا از حذف این اتاق اطمینان دارید؟')"
                title="حذف">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </div>

          <div class="room-capacity-info">
            <div class="capacity-stats">
              <div class="stat">
                <i class="fas fa-users"></i>
                <span>ظرفیت: <?php echo convertToPersianNumbers($room['capacity']); ?></span>
              </div>
              <div class="stat">
                <i class="fas fa-user-check"></i>
                <span>اشغال: <?php echo convertToPersianNumbers($room['occupied']); ?></span>
              </div>
              <div class="stat">
                <i class="fas fa-user-times"></i>
                <span>خالی: <?php echo convertToPersianNumbers($room['capacity'] - $room['occupied']); ?></span>
              </div>
            </div>

            <div class="capacity-progress">
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
              </div>
              <span class="progress-text"><?php echo convertToPersianNumbers(round($percentage)); ?>%</span>
            </div>
          </div>

          <div class="room-status-badge <?php echo $statusClass; ?>">
            <?php
            if ($percentage >= 100) {
              echo '<i class="fas fa-door-closed"></i> پر';
            } elseif ($percentage > 0) {
              echo '<i class="fas fa-door-open"></i> نیمه‌پر';
            } else {
              echo '<i class="fas fa-door-open"></i> خالی';
            }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- مودال افزودن/ویرایش اتاق -->
<div class="modal" id="addRoomModal">
  <div class="modal-content" style="max-width: 500px;">
    <div class="modal-header">
      <h3 id="modalTitle">افزودن اتاق جدید</h3>
      <button class="modal-close" data-close-modal>
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form id="roomForm" action="actions/save_room.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
      <input type="hidden" name="room_id" id="roomId">

      <div class="form-group">
        <label for="room_number">
          <i class="fas fa-hashtag"></i>
          شماره اتاق *
        </label>
        <input type="text" id="room_number" name="room_number" class="form-control" placeholder="مثال: 101" required>
      </div>

      <div class="form-group">
        <label for="floor">
          <i class="fas fa-layer-group"></i>
          طبقه *
        </label>
        <input type="number" id="floor" name="floor" class="form-control" min="1" placeholder="مثال: 1" required>
      </div>

      <div class="form-group">
        <label for="capacity">
          <i class="fas fa-users"></i>
          ظرفیت *
        </label>
        <input type="number" id="capacity" name="capacity" class="form-control" min="1" placeholder="مثال: 2" required>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 25px;">
        <button type="submit" class="btn btn-success" style="flex: 1;">
          <i class="fas fa-save"></i>
          ذخیره
        </button>
        <button type="button" class="btn btn-secondary" data-close-modal style="flex: 1;">
          <i class="fas fa-times"></i>
          انصراف
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  .rooms-grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
  }

  .room-card-detail {
    background: white;
    border: 2px solid var(--border-color);
    border-radius: 15px;
    padding: 20px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
  }

  .room-card-detail::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 5px;
    height: 100%;
    background: var(--primary-color);
  }

  .room-card-detail.full::before {
    background: var(--danger-color);
  }

  .room-card-detail.empty::before {
    background: var(--secondary-color);
  }

  .room-card-detail:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
  }

  .room-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
  }

  .room-number {
    font-size: 24px;
    font-weight: bold;
    color: var(--dark-color);
    margin-bottom: 5px;
  }

  .room-floor {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: var(--text-color);
    background: var(--light-color);
    padding: 3px 10px;
    border-radius: 20px;
  }

  .room-actions {
    display: flex;
    gap: 5px;
  }

  .room-capacity-info {
    margin-bottom: 15px;
  }

  .capacity-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
  }

  .capacity-stats .stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 10px;
    background: var(--light-color);
    border-radius: 8px;
    font-size: 13px;
  }

  .capacity-stats .stat i {
    font-size: 18px;
    color: var(--primary-color);
  }

  .capacity-progress {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .progress-bar {
    flex: 1;
    height: 12px;
    background: var(--light-color);
    border-radius: 10px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    transition: width 0.3s ease;
  }

  .room-card-detail.full .progress-fill {
    background: linear-gradient(90deg, var(--danger-color), #dc2626);
  }

  .room-card-detail.empty .progress-fill {
    background: linear-gradient(90deg, var(--secondary-color), #059669);
  }

  .progress-text {
    font-size: 14px;
    font-weight: bold;
    color: var(--dark-color);
    min-width: 45px;
    text-align: left;
  }

  .room-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
  }

  .room-status-badge.full {
    background: #fee2e2;
    color: #991b1b;
  }

  .room-status-badge.partial {
    background: #fef3c7;
    color: #92400e;
  }

  .room-status-badge.empty {
    background: #d1fae5;
    color: #065f46;
  }
</style>

<script>
  // جستجو
  document.getElementById('searchInput')?.addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const cards = document.querySelectorAll('.room-card-detail');

    cards.forEach(card => {
      const text = card.textContent.toLowerCase();
      card.style.display = text.includes(filter) ? '' : 'none';
    });
  });

  // ویرایش اتاق
  document.querySelectorAll('.edit-room').forEach(btn => {
    btn.addEventListener('click', function () {
      const card = this.closest('.room-card-detail');
      const roomData = JSON.parse(card.getAttribute('data-room-info'));

      document.getElementById('modalTitle').textContent = 'ویرایش اتاق';
      document.getElementById('roomId').value = roomData.id;
      document.getElementById('room_number').value = roomData.room_number;
      document.getElementById('floor').value = roomData.floor;
      document.getElementById('capacity').value = roomData.capacity;

      openModal('addRoomModal');
    });
  });

  // ریست فرم
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('roomForm').reset();
      document.getElementById('roomId').value = '';
      document.getElementById('modalTitle').textContent = 'افزودن اتاق جدید';
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>