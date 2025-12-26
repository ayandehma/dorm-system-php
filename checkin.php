<?php
/**
 * ====================================
 * صفحه ثبت ورود و خروج
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sms.php';
require_once 'includes/header.php';

// دریافت لیست ساکنین
$residents = dbQuery(
  "SELECT r.*, rm.room_number, rm.floor 
     FROM residents r
     INNER JOIN rooms rm ON r.room_id = rm.id
     ORDER BY r.name ASC"
);
?>

<div class="page-header">
  <h1 class="page-title">
    <i class="fas fa-clock"></i>
    ثبت ورود و خروج
  </h1>
  <p class="page-subtitle">ثبت سریع ورود و خروج ساکنین از خوابگاه</p>
</div>

<!-- جستجو سریع -->
<div class="card" style="margin-bottom: 25px;">
  <div class="card-header">
    <h2 class="card-title">
      <i class="fas fa-search"></i>
      جستجوی سریع
    </h2>
  </div>
  <div style="padding: 20px;">
    <input type="text" id="quickSearch" class="form-control" placeholder="جستجو بر اساس نام، شماره دانشجویی یا اتاق..."
      style="font-size: 16px; padding: 15px;" autofocus>
  </div>
</div>

<!-- لیست ساکنین -->
<div class="residents-checkin-grid">
  <?php if (empty($residents)): ?>
    <div class="empty-state" style="grid-column: 1/-1;">
      <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 10px;"></i>
      <p>هیچ ساکنی ثبت نشده است</p>
      <a href="residents.php" class="btn btn-primary" style="margin-top: 15px;">
        <i class="fas fa-user-plus"></i>
        افزودن ساکن اول
      </a>
    </div>
  <?php else: ?>
    <?php foreach ($residents as $resident): ?>
      <div class="checkin-card"
        data-search="<?php echo strtolower($resident['name'] . ' ' . $resident['student_id'] . ' ' . $resident['room_number']); ?>">
        <div class="checkin-card-header">
          <div class="resident-avatar">
            <?php if ($resident['photo']): ?>
              <img src="<?php echo UPLOAD_URL . $resident['photo']; ?>" alt="<?php echo $resident['name']; ?>">
            <?php else: ?>
              <div class="avatar-placeholder">
                <?php echo mb_substr($resident['name'], 0, 1); ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="resident-info">
            <h3><?php echo $resident['name']; ?></h3>
            <div class="resident-meta">
              <span><i class="fas fa-id-card"></i> <?php echo convertToPersianNumbers($resident['student_id']); ?></span>
              <span><i class="fas fa-door-open"></i> اتاق
                <?php echo convertToPersianNumbers($resident['room_number']); ?></span>
            </div>
          </div>
          <div class="status-indicator <?php echo $resident['status']; ?>">
            <?php if ($resident['status'] === 'inside'): ?>
              <i class="fas fa-check-circle"></i>
              <span>حاضر</span>
            <?php else: ?>
              <i class="fas fa-times-circle"></i>
              <span>غایب</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="checkin-actions">
          <button class="btn-checkin entry <?php echo $resident['status'] === 'inside' ? 'disabled' : ''; ?>"
            data-id="<?php echo $resident['id']; ?>" data-action="entry" <?php echo $resident['status'] === 'inside' ? 'disabled' : ''; ?>>
            <i class="fas fa-sign-in-alt"></i>
            ثبت ورود
          </button>

          <button class="btn-checkin exit <?php echo $resident['status'] === 'outside' ? 'disabled' : ''; ?>"
            data-id="<?php echo $resident['id']; ?>" data-action="exit" <?php echo $resident['status'] === 'outside' ? 'disabled' : ''; ?>>
            <i class="fas fa-sign-out-alt"></i>
            ثبت خروج
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- مودال تایید -->
<div class="modal" id="confirmModal">
  <div class="modal-content" style="max-width: 450px;">
    <div class="modal-header">
      <h3 id="confirmTitle"></h3>
      <button class="modal-close" onclick="closeModal('confirmModal')">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div id="confirmMessage" style="padding: 20px; text-align: center; font-size: 16px;"></div>

    <div style="display: flex; gap: 10px; padding: 0 20px 20px;">
      <button id="confirmBtn" class="btn btn-primary" style="flex: 1;">
        <i class="fas fa-check"></i>
        تایید
      </button>
      <button class="btn btn-secondary" onclick="closeModal('confirmModal')" style="flex: 1;">
        <i class="fas fa-times"></i>
        انصراف
      </button>
    </div>
  </div>
</div>

<style>
  .residents-checkin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
  }

  .checkin-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: var(--transition);
  }

  .checkin-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
  }

  .checkin-card-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
  }

  .resident-avatar {
    width: 60px;
    height: 60px;
    flex-shrink: 0;
  }

  .resident-avatar img,
  .avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
  }

  .avatar-placeholder {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
  }

  .resident-info {
    flex: 1;
  }

  .resident-info h3 {
    font-size: 18px;
    color: var(--dark-color);
    margin-bottom: 8px;
  }

  .resident-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 13px;
    color: var(--text-color);
  }

  .resident-meta i {
    margin-left: 5px;
    width: 15px;
  }

  .status-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
  }

  .status-indicator i {
    font-size: 20px;
  }

  .status-indicator.inside {
    background: #d1fae5;
    color: #065f46;
  }

  .status-indicator.outside {
    background: #fee2e2;
    color: #991b1b;
  }

  .checkin-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  .btn-checkin {
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-family: 'Vazir', Arial;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .btn-checkin:not(.disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }

  .btn-checkin.entry {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
  }

  .btn-checkin.exit {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
  }

  .btn-checkin.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  @media (max-width: 768px) {
    .residents-checkin-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
  // جستجوی سریع
  document.getElementById('quickSearch')?.addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const cards = document.querySelectorAll('.checkin-card');

    cards.forEach(card => {
      const searchText = card.getAttribute('data-search');
      card.style.display = searchText.includes(filter) ? '' : 'none';
    });
  });

  // ثبت ورود/خروج
  document.querySelectorAll('.btn-checkin:not(.disabled)').forEach(btn => {
    btn.addEventListener('click', function () {
      const residentId = this.getAttribute('data-id');
      const action = this.getAttribute('data-action');
      const card = this.closest('.checkin-card');
      const residentName = card.querySelector('.resident-info h3').textContent;

      // نمایش مودال تایید
      const actionText = action === 'entry' ? 'ورود' : 'خروج';
      document.getElementById('confirmTitle').textContent = `تایید ${actionText}`;
      document.getElementById('confirmMessage').innerHTML = `
            آیا از ثبت <strong>${actionText}</strong> برای <strong>${residentName}</strong> اطمینان دارید؟
            <br><br>
            <small style="color: var(--text-color);">پیامک به والدین ارسال خواهد شد.</small>
        `;

      // تنظیم دکمه تایید
      const confirmBtn = document.getElementById('confirmBtn');
      confirmBtn.onclick = () => recordAttendance(residentId, action);

      openModal('confirmModal');
    });
  });

  // ثبت حضور و غیاب
  async function recordAttendance(residentId, action) {
    const confirmBtn = document.getElementById('confirmBtn');
    const originalText = confirmBtn.innerHTML;

    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ثبت...';

    try {
      const formData = new FormData();
      formData.append('resident_id', residentId);
      formData.append('action', action);
      formData.append('csrf_token', CSRF_TOKEN);

      const response = await fetch('actions/record_attendance.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        showNotification(result.message, 'success');
        closeModal('confirmModal');

        // رفرش صفحه بعد از 1 ثانیه
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        showNotification(result.message, 'error');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
      }

    } catch (error) {
      console.error('خطا:', error);
      showNotification('خطا در ثبت اطلاعات', 'error');
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = originalText;
    }
  }
</script>

<?php require_once 'includes/footer.php'; ?>