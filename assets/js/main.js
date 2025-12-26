/**
 * ====================================
 * اسکریپت‌های اصلی سیستم
 * ====================================
 */

// بارگذاری صفحه
document.addEventListener('DOMContentLoaded', function () {
  initializeSidebar();
  initializeAlerts();
  initializeModals();
  initializeForms();
});

/**
 * ====================================
 * مدیریت سایدبار
 * ====================================
 */
function initializeSidebar() {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const footer = document.querySelector('.footer');

  if (menuToggle && sidebar) {
    // بررسی وضعیت ذخیره شده
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
      sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('expanded');
      if (footer) footer.classList.add('expanded');
    }

    // کلیک روی دکمه منو
    menuToggle.addEventListener('click', function () {
      sidebar.classList.toggle('collapsed');
      if (mainContent) mainContent.classList.toggle('expanded');
      if (footer) footer.classList.toggle('expanded');

      // ذخیره وضعیت
      const isCollapsed = sidebar.classList.contains('collapsed');
      localStorage.setItem(
        'sidebarState',
        isCollapsed ? 'collapsed' : 'expanded'
      );
    });

    // برای موبایل
    if (window.innerWidth <= 768) {
      menuToggle.addEventListener('click', function () {
        sidebar.classList.toggle('active');
      });

      // بستن سایدبار با کلیک بیرون از آن
      document.addEventListener('click', function (e) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
          sidebar.classList.remove('active');
        }
      });
    }
  }
}

/**
 * ====================================
 * مدیریت الرت‌ها
 * ====================================
 */
function initializeAlerts() {
  const alerts = document.querySelectorAll('.alert');

  alerts.forEach(function (alert) {
    // بستن خودکار بعد از 5 ثانیه
    setTimeout(function () {
      alert.style.opacity = '0';
      setTimeout(function () {
        alert.remove();
      }, 300);
    }, 5000);

    // امکان بستن دستی
    alert.style.cursor = 'pointer';
    alert.addEventListener('click', function () {
      this.style.opacity = '0';
      const self = this;
      setTimeout(function () {
        self.remove();
      }, 300);
    });
  });
}

/**
 * ====================================
 * مدیریت مودال‌ها
 * ====================================
 */
function initializeModals() {
  // دکمه‌های باز کردن مودال
  const modalTriggers = document.querySelectorAll('[data-modal]');

  modalTriggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const modalId = this.getAttribute('data-modal');
      openModal(modalId);
    });
  });

  // دکمه‌های بستن مودال
  const closeButtons = document.querySelectorAll(
    '.modal-close, [data-close-modal]'
  );

  closeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const modal = this.closest('.modal');
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  // بستن با کلیک روی پس‌زمینه
  const modals = document.querySelectorAll('.modal');

  modals.forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === this) {
        closeModal(this.id);
      }
    });
  });

  // بستن با ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const activeModal = document.querySelector('.modal.active');
      if (activeModal) {
        closeModal(activeModal.id);
      }
    }
  });
}

/**
 * باز کردن مودال
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

/**
 * بستن مودال
 */
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';

    // پاک کردن فرم در صورت وجود
    const form = modal.querySelector('form');
    if (form) {
      form.reset();
    }
  }
}

/**
 * ====================================
 * مدیریت فرم‌ها
 * ====================================
 */
function initializeForms() {
  // اعتبارسنجی شماره تلفن
  const phoneInputs = document.querySelectorAll('input[type="tel"]');

  phoneInputs.forEach(function (input) {
    input.addEventListener('input', function () {
      // فقط اعداد
      this.value = this.value.replace(/[^0-9]/g, '');

      // حداکثر 11 رقم
      if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
      }

      // اعتبارسنجی فرمت
      if (this.value.length === 11) {
        if (!this.value.startsWith('09')) {
          this.setCustomValidity('شماره تلفن باید با 09 شروع شود');
        } else {
          this.setCustomValidity('');
        }
      }
    });
  });

  // پیش‌نمایش تصویر
  const imageInputs = document.querySelectorAll(
    'input[type="file"][accept*="image"]'
  );

  imageInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      const file = this.files[0];
      const previewId = this.getAttribute('data-preview');

      if (file && previewId) {
        const reader = new FileReader();

        reader.onload = function (e) {
          const preview = document.getElementById(previewId);
          if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
          }
        };

        reader.readAsDataURL(file);
      }
    });
  });
}

/**
 * ====================================
 * حذف با تایید
 * ====================================
 */
function confirmDelete(message) {
  if (!message) {
    message = 'آیا از حذف این مورد اطمینان دارید؟';
  }
  return confirm(message);
}

/**
 * ====================================
 * جستجو در جدول
 * ====================================
 */
function searchTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);

  if (!input || !table) return;

  input.addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(function (row) {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? '' : 'none';
    });
  });
}

/**
 * ====================================
 * فیلتر جدول
 * ====================================
 */
function filterTable(selectId, tableId, columnIndex) {
  const select = document.getElementById(selectId);
  const table = document.getElementById(tableId);

  if (!select || !table) return;

  select.addEventListener('change', function () {
    const filter = this.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(function (row) {
      const cell = row.cells[columnIndex];
      if (!cell) return;

      const text = cell.textContent.toLowerCase();

      if (filter === '' || text.includes(filter)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
}

/**
 * ====================================
 * نمایش نوتیفیکیشن
 * ====================================
 */
function showNotification(message, type) {
  if (!type) type = 'success';

  const notification = document.createElement('div');
  notification.className = 'alert alert-' + type;

  const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
  notification.innerHTML = '<i class="fas fa-' + icon + '"></i>' + message;

  const container = document.querySelector('.container');
  if (container) {
    container.insertBefore(notification, container.firstChild);

    // حذف خودکار
    setTimeout(function () {
      notification.style.opacity = '0';
      setTimeout(function () {
        notification.remove();
      }, 300);
    }, 3000);
  }
}

/**
 * ====================================
 * تبدیل اعداد به فارسی
 * ====================================
 */
function convertToPersianNumbers(str) {
  const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

  for (let i = 0; i < 10; i++) {
    str = str.replace(new RegExp(english[i], 'g'), persian[i]);
  }

  return str;
}

/**
 * ====================================
 * کپی متن به کلیپبورد
 * ====================================
 */
function copyToClipboard(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function () {
      showNotification('متن کپی شد', 'success');
    });
  } else {
    // روش قدیمی
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showNotification('متن کپی شد', 'success');
  }
}

/**
 * ====================================
 * فرمت شماره تلفن
 * ====================================
 */
function formatPhoneNumber(phone) {
  if (!phone || phone.length !== 11) return phone;

  return phone.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
}

/**
 * ====================================
 * بررسی آنلاین بودن
 * ====================================
 */
window.addEventListener('online', function () {
  showNotification('اتصال اینترنت برقرار شد', 'success');
});

window.addEventListener('offline', function () {
  showNotification('اتصال اینترنت قطع شد', 'error');
});
