<?php
/**
 * ====================================
 * صفحه مدیریت ساکنین
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// دریافت لیست ساکنین با اطلاعات اتاق
$residents = dbQuery(
  "SELECT r.*, rm.room_number, rm.floor 
     FROM residents r
     INNER JOIN rooms rm ON r.room_id = rm.id
     ORDER BY r.name ASC"
);

// دریافت لیست اتاق‌ها برای فرم
$rooms = dbQuery("SELECT * FROM rooms ORDER BY room_number ASC");
?>

<div class="page-header">
  <h1 class="page-title">
    <i class="fas fa-users"></i>
    مدیریت ساکنین
  </h1>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">لیست ساکنین</h2>
      <p style="font-size: 14px; color: var(--text-color); margin-top: 5px;">
        تعداد کل: <?php echo convertToPersianNumbers(count($residents)); ?> نفر
      </p>
    </div>
    <button class="btn btn-primary" data-modal="addResidentModal">
      <i class="fas fa-user-plus"></i>
      افزودن ساکن جدید
    </button>
  </div>

  <!-- جستجو -->
  <div style="padding: 20px; background: var(--light-color); margin-bottom: 20px;">
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 250px;">
        <input type="text" id="searchInput" class="form-control"
          placeholder="جستجو بر اساس نام، شماره دانشجویی یا اتاق...">
      </div>
      <div style="min-width: 200px;">
        <select id="statusFilter" class="form-control">
          <option value="">همه وضعیت‌ها</option>
          <option value="inside">حاضر در خوابگاه</option>
          <option value="outside">خارج از خوابگاه</option>
        </select>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table id="residentsTable">
      <thead>
        <tr>
          <th>عکس</th>
          <th>نام و نام خانوادگی</th>
          <th>شماره دانشجویی</th>
          <th>اتاق</th>
          <th>شماره تلفن</th>
          <th>تلفن والدین</th>
          <th>وضعیت</th>
          <th>عملیات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($residents)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 40px;">
              <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; display: block; margin-bottom: 10px;"></i>
              هیچ ساکنی ثبت نشده است
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($residents as $resident): ?>
            <tr>
              <td>
                <?php if ($resident['photo']): ?>
                  <img src="<?php echo UPLOAD_URL . $resident['photo']; ?>" alt="<?php echo $resident['name']; ?>"
                    style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                  <div
                    style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <?php echo mb_substr($resident['name'], 0, 1); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><strong><?php echo $resident['name']; ?></strong></td>
              <td><?php echo convertToPersianNumbers($resident['student_id']); ?></td>
              <td>
                <span class="badge badge-info">
                  اتاق <?php echo convertToPersianNumbers($resident['room_number']); ?>
                </span>
              </td>
              <td style="direction: ltr; text-align: right;"><?php echo convertToPersianNumbers($resident['phone']); ?></td>
              <td style="direction: ltr; text-align: right;">
                <?php echo convertToPersianNumbers($resident['parent_phone']); ?></td>
              <td>
                <span class="badge badge-<?php echo $resident['status'] === 'inside' ? 'success' : 'danger'; ?>">
                  <?php echo $resident['status'] === 'inside' ? 'حاضر' : 'غایب'; ?>
                </span>
              </td>
              <td>
                <div style="display: flex; gap: 5px;">
                  <button class="btn btn-sm btn-icon btn-primary edit-resident" data-id="<?php echo $resident['id']; ?>"
                    title="ویرایش">
                    <i class="fas fa-edit"></i>
                  </button>
                  <a href="actions/delete_resident.php?id=<?php echo $resident['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                    class="btn btn-sm btn-icon btn-danger"
                    onclick="return confirmDelete('آیا از حذف این ساکن اطمینان دارید؟')" title="حذف">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- مودال افزودن/ویرایش ساکن -->
<div class="modal" id="addResidentModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitle">افزودن ساکن جدید</h3>
      <button class="modal-close" data-close-modal>
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form id="residentForm" action="actions/save_resident.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
      <input type="hidden" name="resident_id" id="residentId">

      <div class="form-group">
        <label for="name">
          <i class="fas fa-user"></i>
          نام و نام خانوادگی *
        </label>
        <input type="text" id="name" name="name" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="student_id">
          <i class="fas fa-id-card"></i>
          شماره دانشجویی *
        </label>
        <input type="text" id="student_id" name="student_id" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="room_id">
          <i class="fas fa-door-open"></i>
          اتاق *
        </label>
        <select id="room_id" name="room_id" class="form-control" required>
          <option value="">انتخاب کنید</option>
          <?php foreach ($rooms as $room): ?>
            <option value="<?php echo $room['id']; ?>">
              اتاق <?php echo $room['room_number']; ?> - طبقه <?php echo $room['floor']; ?>
              (<?php echo $room['occupied']; ?>/<?php echo $room['capacity']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="phone">
          <i class="fas fa-phone"></i>
          شماره تلفن *
        </label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="09121234567" maxlength="11"
          required>
      </div>

      <div class="form-group">
        <label for="parent_phone">
          <i class="fas fa-phone-alt"></i>
          شماره تلفن والدین *
        </label>
        <input type="tel" id="parent_phone" name="parent_phone" class="form-control" placeholder="09121234567"
          maxlength="11" required>
      </div>

      <div class="form-group">
        <label for="photo">
          <i class="fas fa-image"></i>
          عکس
        </label>
        <input type="file" id="photo" name="photo" class="form-control" accept="image/*" data-preview="photoPreview">
        <img id="photoPreview" style="display: none; margin-top: 10px; max-width: 150px; border-radius: 10px;">
      </div>

      <div style="display: flex; gap: 10px; margin-top: 25px;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">
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

<script>
  // جستجو در جدول
  document.getElementById('searchInput')?.addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#residentsTable tbody tr');

    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? '' : 'none';
    });
  });

  // فیلتر وضعیت
  document.getElementById('statusFilter')?.addEventListener('change', function () {
    const filter = this.value;
    const rows = document.querySelectorAll('#residentsTable tbody tr');

    rows.forEach(row => {
      if (!filter) {
        row.style.display = '';
        return;
      }

      const badge = row.querySelector('.badge');
      if (!badge) return;

      const text = badge.textContent.trim();
      const match = (filter === 'inside' && text === 'حاضر') || (filter === 'outside' && text === 'غایب');
      row.style.display = match ? '' : 'none';
    });
  });

  // ویرایش ساکن
  document.querySelectorAll('.edit-resident').forEach(btn => {
    btn.addEventListener('click', async function () {
      const id = this.getAttribute('data-id');

      try {
        const response = await fetch(`actions/get_resident.php?id=${id}`);
        const resident = await response.json();

        if (resident.success) {
          document.getElementById('modalTitle').textContent = 'ویرایش ساکن';
          document.getElementById('residentId').value = resident.data.id;
          document.getElementById('name').value = resident.data.name;
          document.getElementById('student_id').value = resident.data.student_id;
          document.getElementById('room_id').value = resident.data.room_id;
          document.getElementById('phone').value = resident.data.phone;
          document.getElementById('parent_phone').value = resident.data.parent_phone;

          if (resident.data.photo) {
            const preview = document.getElementById('photoPreview');
            preview.src = '<?php echo UPLOAD_URL; ?>' + resident.data.photo;
            preview.style.display = 'block';
          }

          openModal('addResidentModal');
        }
      } catch (error) {
        console.error('خطا در بارگذاری اطلاعات:', error);
      }
    });
  });

  // ریست فرم هنگام بستن مودال
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('residentForm').reset();
      document.getElementById('residentId').value = '';
      document.getElementById('modalTitle').textContent = 'افزودن ساکن جدید';
      document.getElementById('photoPreview').style.display = 'none';
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>