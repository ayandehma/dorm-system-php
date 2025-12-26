<?php
/**
 * ====================================
 * صفحه تنظیمات سیستم
 * ====================================
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sms.php';
require_once 'includes/header.php';

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
    } else {
        $systemName = clean($_POST['system_name'] ?? '');
        $apiKey = clean($_POST['kavenegar_api_key'] ?? '');
        $smsSender = clean($_POST['sms_sender'] ?? '');
        $smsEnabled = isset($_POST['sms_enabled']) ? '1' : '0';
        $smsFirstDaily = isset($_POST['sms_first_daily_only']) ? '1' : '0';
        
        // بروزرسانی تنظیمات
        $success = true;
        $success = $success && updateSetting('system_name', $systemName);
        $success = $success && updateSetting('kavenegar_api_key', $apiKey);
        $success = $success && updateSetting('sms_sender', $smsSender);
        $success = $success && updateSetting('sms_enabled', $smsEnabled);
        $success = $success && updateSetting('sms_first_daily_only', $smsFirstDaily);
        
        if ($success) {
            setSuccessMessage('تنظیمات با موفقیت ذخیره شد');
        } else {
            setErrorMessage('خطا در ذخیره تنظیمات');
        }
        
        redirect('settings.php');
    }
}

// تست پیامک
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_sms'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
    } else {
        $testPhone = clean($_POST['test_phone'] ?? '');
        
        if (validatePhone($testPhone)) {
            $result = testSMS($testPhone);
            
            if ($result['success']) {
                setSuccessMessage('پیامک تست با موفقیت ارسال شد');
            } else {
                setErrorMessage('خطا در ارسال پیامک: ' . $result['message']);
            }
        } else {
            setErrorMessage('شماره تلفن معتبر نیست');
        }
    }
    
    redirect('settings.php');
}

// تغییر رمز عبور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setErrorMessage('خطای امنیتی. لطفاً دوباره تلاش کنید.');
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $admin = getLoggedInAdmin();
        $adminFull = dbQueryOne("SELECT * FROM admins WHERE id = ?", [$admin['id']]);
        
        if (!verifyPassword($currentPassword, $adminFull['password'])) {
            setErrorMessage('رمز عبور فعلی اشتباه است');
        } elseif (strlen($newPassword) < 6) {
            setErrorMessage('رمز عبور جدید باید حداقل 6 کاراکتر باشد');
        } elseif ($newPassword !== $confirmPassword) {
            setErrorMessage('رمز عبور جدید و تکرار آن مطابقت ندارند');
        } else {
            $hashedPassword = hashPassword($newPassword);
            $result = dbExecute(
                "UPDATE admins SET password = ? WHERE id = ?",
                [$hashedPassword, $admin['id']]
            );
            
            if ($result) {
                setSuccessMessage('رمز عبور با موفقیت تغییر یافت');
                logError("Password changed: {$admin['username']}", 'security.log');
            } else {
                setErrorMessage('خطا در تغییر رمز عبور');
            }
        }
    }
    
    redirect('settings.php');
}

// دریافت تنظیمات فعلی
$settings = [
    'system_name' => getSetting('system_name'),
    'kavenegar_api_key' => getSetting('kavenegar_api_key'),
    'sms_sender' => getSetting('sms_sender'),
    'sms_enabled' => getSetting('sms_enabled'),
    'sms_first_daily_only' => getSetting('sms_first_daily_only')
];
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-cog"></i>
        تنظیمات سیستم
    </h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 25px;">
    
    <!-- تنظیمات عمومی -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-sliders-h"></i>
                تنظیمات عمومی
            </h2>
        </div>
        
        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="system_name">
                    <i class="fas fa-building"></i>
                    نام سیستم
                </label>
                <input 
                    type="text" 
                    id="system_name" 
                    name="system_name" 
                    class="form-control" 
                    value="<?php echo $settings['system_name']; ?>"
                    required
                >
            </div>
            
            <div style="border-top: 2px solid var(--border-color); margin: 20px 0; padding-top: 20px;">
                <h3 style="margin-bottom: 15px; font-size: 16px;">
                    <i class="fas fa-sms"></i>
                    تنظیمات پیامک (کاوه نگار)
                </h3>
            </div>
            
            <div class="form-group">
                <label for="kavenegar_api_key">
                    <i class="fas fa-key"></i>
                    API Key کاوه نگار
                </label>
                <input 
                    type="text" 
                    id="kavenegar_api_key" 
                    name="kavenegar_api_key" 
                    class="form-control" 
                    value="<?php echo $settings['kavenegar_api_key']; ?>"
                    placeholder="مثال: 7262542B756B6D454B6B6D..."
                    required
                >
                <small style="color: var(--text-color); display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i>
                    از پنل کاوه نگار دریافت کنید: 
                    <a href="https://console.kavenegar.com/home" target="_blank">console.kavenegar.com</a>
                </small>
            </div>
            
            <div class="form-group">
                <label for="sms_sender">
                    <i class="fas fa-phone"></i>
                    شماره ارسال کننده
                </label>
                <input 
                    type="text" 
                    id="sms_sender" 
                    name="sms_sender" 
                    class="form-control" 
                    value="<?php echo $settings['sms_sender']; ?>"
                    placeholder="مثال: 10008663"
                    required
                >
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="sms_enabled" 
                        <?php echo $settings['sms_enabled'] ? 'checked' : ''; ?>
                        style="width: 20px; height: 20px;"
                    >
                    <span>فعال‌سازی ارسال پیامک</span>
                </label>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="sms_first_daily_only" 
                        <?php echo $settings['sms_first_daily_only'] ? 'checked' : ''; ?>
                        style="width: 20px; height: 20px;"
                    >
                    <span>ارسال فقط اولین ورود/خروج روزانه</span>
                </label>
                <small style="color: var(--text-color); display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i>
                    در صورت فعال بودن، برای هر ساکن فقط یک بار در روز پیامک ارسال می‌شود
                </small>
            </div>
            
            <button type="submit" name="save_settings" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-save"></i>
                ذخیره تنظیمات
            </button>
        </form>
    </div>
    
    <!-- تست پیامک -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-vial"></i>
                تست پیامک
            </h2>
        </div>
        
        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="test_phone">
                    <i class="fas fa-mobile-alt"></i>
                    شماره تلفن تست
                </label>
                <input 
                    type="tel" 
                    id="test_phone" 
                    name="test_phone" 
                    class="form-control" 
                    placeholder="09121234567"
                    maxlength="11"
                    required
                >
                <small style="color: var(--text-color); display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i>
                    یک پیامک تست به این شماره ارسال می‌شود
                </small>
            </div>
            
            <button type="submit" name="test_sms" class="btn btn-success" style="width: 100%;">
                <i class="fas fa-paper-plane"></i>
                ارسال پیامک تست
            </button>
        </form>
        
        <div style="padding: 20px; border-top: 2px solid var(--border-color);">
            <h3 style="margin-bottom: 15px; font-size: 16px;">
                <i class="fas fa-info-circle"></i>
                راهنمای تنظیمات پیامک
            </h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 10px; background: var(--light-color); border-radius: 8px; margin-bottom: 10px;">
                    <strong>۱.</strong> ابتدا در سایت کاوه نگار ثبت‌نام کنید
                </li>
                <li style="padding: 10px; background: var(--light-color); border-radius: 8px; margin-bottom: 10px;">
                    <strong>۲.</strong> API Key خود را از پنل دریافت کنید
                </li>
                <li style="padding: 10px; background: var(--light-color); border-radius: 8px; margin-bottom: 10px;">
                    <strong>۳.</strong> شماره ارسال کننده را وارد کنید
                </li>
                <li style="padding: 10px; background: var(--light-color); border-radius: 8px;">
                    <strong>۴.</strong> با دکمه "ارسال پیامک تست" اتصال را بررسی کنید
                </li>
            </ul>
        </div>
    </div>
    
    <!-- تغییر رمز عبور -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-lock"></i>
                تغییر رمز عبور
            </h2>
        </div>
        
        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="current_password">
                    <i class="fas fa-key"></i>
                    رمز عبور فعلی
                </label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    class="form-control"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="new_password">
                    <i class="fas fa-lock"></i>
                    رمز عبور جدید
                </label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-control"
                    minlength="6"
                    required
                >
                <small style="color: var(--text-color); display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i>
                    حداقل 6 کاراکتر
                </small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i>
                    تکرار رمز عبور جدید
                </label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control"
                    minlength="6"
                    required
                >
            </div>
            
            <button type="submit" name="change_password" class="btn btn-warning" style="width: 100%;">
                <i class="fas fa-sync-alt"></i>
                تغییر رمز عبور
            </button>
        </form>
    </div>
    
    <!-- اطلاعات سیستم -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-info-circle"></i>
                اطلاعات سیستم
            </h2>
        </div>
        
        <div style="padding: 20px;">
            <div class="info-row">
                <span class="info-label">نسخه PHP:</span>
                <span class="info-value"><?php echo phpversion(); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">نسخه سیستم:</span>
                <span class="info-value">1.0.0 MVP</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">کاربر فعال:</span>
                <span class="info-value"><?php echo $admin['full_name']; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">تعداد ساکنین:</span>
                <span class="info-value"><?php echo convertToPersianNumbers(getTotalResidents()); ?> نفر</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">تعداد اتاق‌ها:</span>
                <span class="info-value"><?php echo convertToPersianNumbers(getTotalRooms()); ?> اتاق</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">وضعیت پیامک:</span>
                <span class="info-value">
                    <?php if ($settings['sms_enabled']): ?>
                        <span class="badge badge-success">فعال</span>
                    <?php else: ?>
                        <span class="badge badge-danger">غیرفعال</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: bold;
    color: var(--dark-color);
}

.info-value {
    color: var(--text-color);
}
</style>

<?php require_once 'includes/footer.php'; ?>