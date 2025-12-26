<?php
/**
 * ====================================
 * سیستم ارسال پیامک با کاوه نگار
 * ====================================
 * این فایل مسئول ارسال پیامک از طریق سرویس کاوه نگار است
 * 
 * مستندات API کاوه نگار:
 * https://kavenegar.com/rest.html
 */

/**
 * کلاس مدیریت پیامک
 */
class SMS
{

  /**
   * @var string کلید API کاوه نگار
   */
  private $apiKey;

  /**
   * @var string شماره ارسال کننده
   */
  private $sender;

  /**
   * @var string آدرس API کاوه نگار
   */
  private $apiUrl = 'https://api.kavenegar.com/v1/';

  /**
   * @var bool وضعیت فعال/غیرفعال بودن ارسال پیامک
   */
  private $enabled;

  /**
   * سازنده کلاس
   * دریافت تنظیمات از دیتابیس
   */
  public function __construct()
  {
    $this->apiKey = getSetting('kavenegar_api_key', '');
    $this->sender = getSetting('sms_sender', '2000660110');
    $this->enabled = (bool) getSetting('sms_enabled', 1);
  }

  /**
   * ارسال پیامک ساده
   * 
   * @param string|array $receptor شماره گیرنده یا آرایه‌ای از شماره‌ها
   * @param string $message متن پیام
   * @return array نتیجه ارسال
   */
  public function send($receptor, $message)
  {
    // بررسی فعال بودن سیستم پیامک
    if (!$this->enabled) {
      return [
        'success' => false,
        'message' => 'سیستم پیامک غیرفعال است'
      ];
    }

    // بررسی وجود API Key
    if (empty($this->apiKey)) {
      return [
        'success' => false,
        'message' => 'API Key تنظیم نشده است'
      ];
    }

    // تبدیل آرایه به رشته با کاما
    if (is_array($receptor)) {
      $receptor = implode(',', $receptor);
    }

    // پارامترهای ارسال
    $params = [
      'receptor' => $receptor,
      'message' => $message,
      'sender' => $this->sender
    ];

    // ارسال درخواست به API
    $url = $this->apiUrl . $this->apiKey . '/sms/send.json';

    try {
      $response = $this->makeRequest($url, $params);

      // بررسی موفقیت
      if (isset($response['return']['status']) && $response['return']['status'] == 200) {
        return [
          'success' => true,
          'message' => 'پیامک با موفقیت ارسال شد',
          'data' => $response
        ];
      } else {
        return [
          'success' => false,
          'message' => $response['return']['message'] ?? 'خطا در ارسال پیامک',
          'data' => $response
        ];
      }

    } catch (Exception $e) {
      logError('SMS Error: ' . $e->getMessage(), 'sms.log');
      return [
        'success' => false,
        'message' => 'خطا در اتصال به سرویس پیامک: ' . $e->getMessage()
      ];
    }
  }

  /**
   * ارسال پیامک با استفاده از الگو (Template)
   * 
   * @param string $receptor شماره گیرنده
   * @param string $template نام الگو
   * @param array $tokens توکن‌های الگو
   * @return array نتیجه ارسال
   */
  public function sendWithTemplate($receptor, $template, $tokens)
  {
    if (!$this->enabled) {
      return [
        'success' => false,
        'message' => 'سیستم پیامک غیرفعال است'
      ];
    }

    if (empty($this->apiKey)) {
      return [
        'success' => false,
        'message' => 'API Key تنظیم نشده است'
      ];
    }

    // آماده‌سازی پارامترها
    $params = [
      'receptor' => $receptor,
      'template' => $template,
      'token' => implode(',', array_values($tokens))
    ];

    $url = $this->apiUrl . $this->apiKey . '/verify/lookup.json';

    try {
      $response = $this->makeRequest($url, $params);

      if (isset($response['return']['status']) && $response['return']['status'] == 200) {
        return [
          'success' => true,
          'message' => 'پیامک با موفقیت ارسال شد',
          'data' => $response
        ];
      } else {
        return [
          'success' => false,
          'message' => $response['return']['message'] ?? 'خطا در ارسال پیامک',
          'data' => $response
        ];
      }

    } catch (Exception $e) {
      logError('SMS Template Error: ' . $e->getMessage(), 'sms.log');
      return [
        'success' => false,
        'message' => 'خطا در اتصال به سرویس پیامک'
      ];
    }
  }

  /**
   * ارسال درخواست HTTP به API
   * 
   * @param string $url آدرس API
   * @param array $params پارامترها
   * @return array پاسخ JSON
   * @throws Exception
   */
  private function makeRequest($url, $params)
  {
    // استفاده از cURL
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($params),
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYPEER => false, // در سرور واقعی این را true کنید
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
      ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    // بررسی خطا
    if ($error) {
      throw new Exception("cURL Error: $error");
    }

    if ($httpCode !== 200) {
      throw new Exception("HTTP Error: $httpCode");
    }

    // تبدیل JSON به آرایه
    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception("JSON Parse Error: " . json_last_error_msg());
    }

    return $result;
  }

  /**
   * بررسی اعتبار API Key
   * 
   * @return array
   */
  public function checkAccount()
  {
    if (empty($this->apiKey)) {
      return [
        'success' => false,
        'message' => 'API Key تنظیم نشده است'
      ];
    }

    $url = $this->apiUrl . $this->apiKey . '/account/info.json';

    try {
      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
      ]);

      $response = curl_exec($ch);
      curl_close($ch);

      $result = json_decode($response, true);

      if (isset($result['return']['status']) && $result['return']['status'] == 200) {
        return [
          'success' => true,
          'message' => 'اتصال موفقیت‌آمیز',
          'data' => $result['entries']
        ];
      }

      return [
        'success' => false,
        'message' => 'خطا در اتصال به حساب کاربری'
      ];

    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'خطا در بررسی اعتبار: ' . $e->getMessage()
      ];
    }
  }
}

/**
 * ====================================
 * توابع کمکی ارسال پیامک
 * ====================================
 */

/**
 * ارسال پیامک ورود ساکن به والدین
 * 
 * @param array $resident اطلاعات ساکن
 * @return array نتیجه ارسال
 */
function sendEntryNotification($resident)
{
  // بررسی تنظیمات ارسال فقط اولین بار در روز
  $firstDailyOnly = (bool) getSetting('sms_first_daily_only', 1);

  if ($firstDailyOnly) {
    // بررسی آیا امروز قبلاً ورود ثبت شده؟
    $today = date('Y-m-d');
    $alreadySent = dbQueryOne(
      "SELECT id FROM attendance_log 
             WHERE resident_id = ? 
             AND action = 'ورود' 
             AND action_date = ? 
             AND sms_sent = 1",
      [$resident['id'], $today]
    );

    if ($alreadySent) {
      return [
        'success' => false,
        'message' => 'پیامک ورود امروز قبلاً ارسال شده است',
        'skip' => true
      ];
    }
  }

  // دریافت تاریخ و زمان فارسی
  $persianDateTime = getCurrentPersianDateTime();

  // متن پیام
  $message = sprintf(
    "سلام، فرزند گرامی شما %s در تاریخ %s ساعت %s ورود به خوابگاه را ثبت نمود.",
    $resident['name'],
    $persianDateTime['date'],
    convertToPersianNumbers(date('H:i'))
  );

  // ارسال پیامک
  $sms = new SMS();
  return $sms->send($resident['parent_phone'], $message);
}

/**
 * ارسال پیامک خروج ساکن به والدین
 * 
 * @param array $resident اطلاعات ساکن
 * @return array نتیجه ارسال
 */
function sendExitNotification($resident)
{
  // بررسی تنظیمات ارسال فقط اولین بار در روز
  $firstDailyOnly = (bool) getSetting('sms_first_daily_only', 1);

  if ($firstDailyOnly) {
    $today = date('Y-m-d');
    $alreadySent = dbQueryOne(
      "SELECT id FROM attendance_log 
             WHERE resident_id = ? 
             AND action = 'خروج' 
             AND action_date = ? 
             AND sms_sent = 1",
      [$resident['id'], $today]
    );

    if ($alreadySent) {
      return [
        'success' => false,
        'message' => 'پیامک خروج امروز قبلاً ارسال شده است',
        'skip' => true
      ];
    }
  }

  // دریافت تاریخ و زمان فارسی
  $persianDateTime = getCurrentPersianDateTime();

  // متن پیام
  $message = sprintf(
    "سلام، فرزند گرامی شما %s در تاریخ %s ساعت %s خروج از خوابگاه را ثبت نمود.",
    $resident['name'],
    $persianDateTime['date'],
    convertToPersianNumbers(date('H:i'))
  );

  // ارسال پیامک
  $sms = new SMS();
  return $sms->send($resident['parent_phone'], $message);
}

/**
 * تست ارسال پیامک
 * 
 * @param string $phone شماره تست
 * @return array
 */
function testSMS($phone)
{
  $persianDateTime = getCurrentPersianDateTime();
  $message = "این یک پیام تست از سیستم مدیریت خوابگاه است. تاریخ: " . $persianDateTime['date'] . " - ساعت: " . convertToPersianNumbers(date('H:i'));

  $sms = new SMS();
  return $sms->send($phone, $message);
}
?>