<?php
/**
 * ====================================
 * تنظیمات اتصال به دیتابیس
 * ====================================
 * این فایل تنظیمات اتصال به دیتابیس را مدیریت می‌کند
 * از PDO برای اتصال امن استفاده می‌شود
 */

// تنظیمات دیتابیس - این مقادیر را با اطلاعات سرور خود جایگزین کنید
define('DB_HOST', 'localhost');        // آدرس سرور MySQL
define('DB_NAME', 'dorm_system');      // نام دیتابیس
define('DB_USER', 'root');             // نام کاربری MySQL
define('DB_PASS', '');                 // رمز عبور MySQL
define('DB_CHARSET', 'utf8mb4');       // کاراکترست (برای پشتیبانی کامل از فارسی)

// تنظیمات اپلیکیشن
define('BASE_URL', 'http://localhost/dorm-system/');  // آدرس اصلی سایت
define('UPLOAD_DIR', __DIR__ . '/../uploads/');       // مسیر آپلود فایل‌ها
define('UPLOAD_URL', BASE_URL . 'uploads/');          // URL آپلود فایل‌ها

// تنظیمات امنیتی
define('SESSION_LIFETIME', 3600 * 8);  // مدت زمان Session (8 ساعت)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // حداکثر حجم فایل آپلود (5MB)

/**
 * کلاس Database - مدیریت اتصال به دیتابیس
 * 
 * این کلاس با استفاده از Singleton Pattern
 * فقط یک نمونه از اتصال دیتابیس ایجاد می‌کند
 */
class Database
{

  /**
   * @var PDO نمونه اتصال دیتابیس
   */
  private static $instance = null;

  /**
   * @var PDO شی PDO
   */
  private $pdo;

  /**
   * سازنده خصوصی برای جلوگیری از ساخت نمونه جدید
   * 
   * @throws PDOException در صورت خطا در اتصال
   */
  private function __construct()
  {
    try {
      // ایجاد رشته اتصال DSN
      $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

      // تنظیمات PDO
      $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // نمایش خطاها به صورت Exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // برگشت نتایج به صورت آرایه انجمنی
        PDO::ATTR_EMULATE_PREPARES => false,                     // غیرفعال کردن emulate برای امنیت بیشتر
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET  // تنظیم کاراکترست
      ];

      // ایجاد اتصال
      $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    } catch (PDOException $e) {
      // در صورت خطا، نمایش پیام خطا و توقف اجرا
      die("خطا در اتصال به دیتابیس: " . $e->getMessage());
    }
  }

  /**
   * دریافت نمونه واحد از کلاس (Singleton Pattern)
   * 
   * @return Database نمونه کلاس
   */
  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * دریافت شی PDO برای اجرای کوئری‌ها
   * 
   * @return PDO
   */
  public function getConnection()
  {
    return $this->pdo;
  }

  /**
   * اجرای کوئری SELECT و برگشت تمام نتایج
   * 
   * @param string $query کوئری SQL
   * @param array $params پارامترهای کوئری (برای Prepared Statement)
   * @return array آرایه نتایج
   */
  public function query($query, $params = [])
  {
    try {
      $stmt = $this->pdo->prepare($query);
      $stmt->execute($params);
      return $stmt->fetchAll();
    } catch (PDOException $e) {
      error_log("Database Query Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * اجرای کوئری SELECT و برگشت یک سطر
   * 
   * @param string $query کوئری SQL
   * @param array $params پارامترهای کوئری
   * @return array|false یک سطر یا false
   */
  public function queryOne($query, $params = [])
  {
    try {
      $stmt = $this->pdo->prepare($query);
      $stmt->execute($params);
      return $stmt->fetch();
    } catch (PDOException $e) {
      error_log("Database Query Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * اجرای کوئری INSERT, UPDATE, DELETE
   * 
   * @param string $query کوئری SQL
   * @param array $params پارامترهای کوئری
   * @return bool|int موفقیت یا ID آخرین رکورد درج شده
   */
  public function execute($query, $params = [])
  {
    try {
      $stmt = $this->pdo->prepare($query);
      $result = $stmt->execute($params);

      // اگر کوئری INSERT بود، ID آخرین رکورد را برمی‌گرداند
      if (stripos($query, 'INSERT') === 0) {
        return $this->pdo->lastInsertId();
      }

      return $result;
    } catch (PDOException $e) {
      error_log("Database Execute Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * شروع Transaction
   */
  public function beginTransaction()
  {
    return $this->pdo->beginTransaction();
  }

  /**
   * تایید Transaction
   */
  public function commit()
  {
    return $this->pdo->commit();
  }

  /**
   * لغو Transaction
   */
  public function rollback()
  {
    return $this->pdo->rollBack();
  }

  /**
   * جلوگیری از کلون کردن
   */
  private function __clone()
  {
  }

  /**
   * جلوگیری از unserialize
   */
  public function __wakeup()
  {
    throw new Exception("Cannot unserialize singleton");
  }
}

/**
 * تابع کمکی برای دریافت اتصال دیتابیس
 * 
 * @return PDO
 */
function getDB()
{
  return Database::getInstance()->getConnection();
}

/**
 * تابع کمکی برای اجرای کوئری
 * 
 * @param string $query
 * @param array $params
 * @return array|false
 */
function dbQuery($query, $params = [])
{
  return Database::getInstance()->query($query, $params);
}

/**
 * تابع کمکی برای اجرای کوئری و دریافت یک سطر
 * 
 * @param string $query
 * @param array $params
 * @return array|false
 */
function dbQueryOne($query, $params = [])
{
  return Database::getInstance()->queryOne($query, $params);
}

/**
 * تابع کمکی برای اجرای کوئری‌های تغییردهنده
 * 
 * @param string $query
 * @param array $params
 * @return bool|int
 */
function dbExecute($query, $params = [])
{
  return Database::getInstance()->execute($query, $params);
}
?>