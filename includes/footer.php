<?php
/**
 * فوتر مشترک تمام صفحات
 */
?>

<?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
  </div> <!-- پایان container -->
  </main> <!-- پایان main-content -->

  <footer class="footer">
    <div class="container">
      <p>© <?php echo date('Y'); ?> - <?php echo getSetting('system_name'); ?></p>
    </div>
  </footer>
<?php endif; ?>

<!-- اسکریپت‌های جاوااسکریپت -->
<script src="assets/js/main.js"></script>

<?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
  <script>
    // تنظیمات عمومی
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
  </script>
<?php endif; ?>

</body>

</html>