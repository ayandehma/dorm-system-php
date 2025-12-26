-- ====================================
-- دیتابیس سیستم مدیریت خوابگاه
-- نسخه: 1.0 MVP
-- تاریخ: 2025
-- ====================================

-- ایجاد دیتابیس
CREATE DATABASE IF NOT EXISTS dorm_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE dorm_system;

-- ====================================
-- جدول مدیران سیستم
-- ====================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL COMMENT 'نام کاربری',
    password VARCHAR(255) NOT NULL COMMENT 'رمز عبور (هش شده)',
    full_name VARCHAR(100) NOT NULL COMMENT 'نام و نام خانوادگی',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'تاریخ ایجاد'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول مدیران';

-- ====================================
-- جدول اتاق‌ها
-- ====================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL COMMENT 'شماره اتاق',
    floor INT NOT NULL COMMENT 'طبقه',
    capacity INT NOT NULL COMMENT 'ظرفیت کل',
    occupied INT DEFAULT 0 COMMENT 'تعداد اشغال شده',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'تاریخ ایجاد',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'تاریخ ویرایش',
    INDEX idx_floor (floor),
    INDEX idx_room_number (room_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول اتاق‌ها';

-- ====================================
-- جدول ساکنین
-- ====================================
CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'نام و نام خانوادگی',
    student_id VARCHAR(20) UNIQUE NOT NULL COMMENT 'شماره دانشجویی',
    room_id INT NOT NULL COMMENT 'شناسه اتاق',
    phone VARCHAR(11) NOT NULL COMMENT 'شماره تلفن ساکن',
    parent_phone VARCHAR(11) NOT NULL COMMENT 'شماره تلفن والدین',
    photo VARCHAR(255) DEFAULT NULL COMMENT 'مسیر عکس',
    status ENUM('inside', 'outside') DEFAULT 'outside' COMMENT 'وضعیت فعلی: داخل یا خارج خوابگاه',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'تاریخ ایجاد',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'تاریخ ویرایش',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_room_id (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول ساکنین';

-- ====================================
-- جدول گزارش حضور و غیاب
-- ====================================
CREATE TABLE IF NOT EXISTS attendance_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL COMMENT 'شناسه ساکن',
    action ENUM('ورود', 'خروج') NOT NULL COMMENT 'نوع عملیات',
    action_date DATE NOT NULL COMMENT 'تاریخ عملیات',
    action_time TIME NOT NULL COMMENT 'ساعت عملیات',
    sms_sent BOOLEAN DEFAULT FALSE COMMENT 'وضعیت ارسال پیامک',
    sms_status VARCHAR(255) DEFAULT NULL COMMENT 'پیام وضعیت ارسال',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'زمان ثبت',
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    INDEX idx_resident_id (resident_id),
    INDEX idx_action_date (action_date),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول حضور و غیاب';

-- ====================================
-- جدول تنظیمات سیستم
-- ====================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL COMMENT 'کلید تنظیمات',
    setting_value TEXT NOT NULL COMMENT 'مقدار تنظیمات',
    description VARCHAR(255) DEFAULT NULL COMMENT 'توضیحات',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'تاریخ ویرایش'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول تنظیمات';

-- ====================================
-- داده‌های اولیه
-- ====================================

-- ایجاد یک ادمین پیش‌فرض
-- نام کاربری: admin
-- رمز عبور: admin123
-- توجه: حتماً بعد از نصب رمز عبور را تغییر دهید!
INSERT INTO admins (username, password, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدیر سیستم');

-- تنظیمات پیش‌فرض
INSERT INTO settings (setting_key, setting_value, description) VALUES
('kavenegar_api_key', 'YOUR_API_KEY_HERE', 'کلید API کاوه نگار'),
('sms_sender', '10008663', 'شماره ارسال کننده پیامک'),
('sms_enabled', '1', 'فعال/غیرفعال بودن ارسال پیامک (1=فعال, 0=غیرفعال)'),
('sms_first_daily_only', '1', 'ارسال فقط اولین ورود/خروج روزانه (1=فعال, 0=غیرفعال)'),
('system_name', 'سیستم مدیریت خوابگاه دانشگاه مشهد', 'نام سیستم');

-- نمونه داده برای اتاق‌ها (اختیاری - برای تست)
INSERT INTO rooms (room_number, floor, capacity, occupied) VALUES
('101', 1, 2, 0),
('102', 1, 2, 0),
('103', 1, 3, 0),
('201', 2, 2, 0),
('202', 2, 3, 0),
('203', 2, 2, 0);

-- ====================================
-- ویوها (Views) برای گزارش‌گیری راحت‌تر
-- ====================================

-- ویو برای نمایش ساکنین با اطلاعات اتاق
CREATE OR REPLACE VIEW v_residents_with_rooms AS
SELECT 
    r.id,
    r.name,
    r.student_id,
    r.phone,
    r.parent_phone,
    r.photo,
    r.status,
    rm.room_number,
    rm.floor,
    r.created_at,
    r.updated_at
FROM residents r
INNER JOIN rooms rm ON r.room_id = rm.id
ORDER BY r.name;

-- ویو برای نمایش آخرین فعالیت‌های حضور و غیاب
CREATE OR REPLACE VIEW v_latest_attendance AS
SELECT 
    al.id,
    al.resident_id,
    r.name AS resident_name,
    r.student_id,
    rm.room_number,
    al.action,
    al.action_date,
    al.action_time,
    al.sms_sent,
    al.created_at
FROM attendance_log al
INNER JOIN residents r ON al.resident_id = r.id
INNER JOIN rooms rm ON r.room_id = rm.id
ORDER BY al.created_at DESC;

-- ====================================
-- Stored Procedures (اختیاری - برای عملیات پیچیده‌تر)
-- ====================================

DELIMITER //

-- پروسیجر برای به‌روزرسانی تعداد اشغال اتاق
CREATE PROCEDURE update_room_occupancy(IN p_room_id INT)
BEGIN
    UPDATE rooms 
    SET occupied = (
        SELECT COUNT(*) 
        FROM residents 
        WHERE room_id = p_room_id
    )
    WHERE id = p_room_id;
END //

-- پروسیجر برای بررسی آیا امروز قبلاً ورود/خروج ثبت شده
CREATE PROCEDURE check_daily_attendance(
    IN p_resident_id INT,
    IN p_action VARCHAR(10),
    OUT p_exists BOOLEAN
)
BEGIN
    SELECT EXISTS(
        SELECT 1 
        FROM attendance_log 
        WHERE resident_id = p_resident_id 
        AND action = p_action 
        AND action_date = CURDATE()
    ) INTO p_exists;
END //

DELIMITER ;

-- ====================================
-- Triggers برای حفظ یکپارچگی داده‌ها
-- ====================================

DELIMITER //

-- تریگر برای به‌روزرسانی خودکار تعداد اشغال هنگام افزودن ساکن
CREATE TRIGGER after_resident_insert
AFTER INSERT ON residents
FOR EACH ROW
BEGIN
    UPDATE rooms 
    SET occupied = occupied + 1 
    WHERE id = NEW.room_id;
END //

-- تریگر برای به‌روزرسانی خودکار تعداد اشغال هنگام حذف ساکن
CREATE TRIGGER after_resident_delete
AFTER DELETE ON residents
FOR EACH ROW
BEGIN
    UPDATE rooms 
    SET occupied = occupied - 1 
    WHERE id = OLD.room_id;
END //

-- تریگر برای به‌روزرسانی هنگام تغییر اتاق ساکن
CREATE TRIGGER after_resident_update
AFTER UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.room_id != OLD.room_id THEN
        -- کاهش از اتاق قبلی
        UPDATE rooms 
        SET occupied = occupied - 1 
        WHERE id = OLD.room_id;
        
        -- افزایش به اتاق جدید
        UPDATE rooms 
        SET occupied = occupied + 1 
        WHERE id = NEW.room_id;
    END IF;
END //

DELIMITER ;

-- ====================================
-- پایان اسکریپت
-- ====================================