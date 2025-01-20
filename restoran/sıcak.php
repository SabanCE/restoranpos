<?php
// Veritabanı bağlantı bilgileri
$serverName = ""; // Escape the backslash
$database = "Restorann";
$uid = "sa";
$pass = "1";
$connectionOptions = array(
    "Database" => $database,
    "Uid" => $uid,
    "PWD" => $pass,
    "CharacterSet" => "UTF-8"
    
);

// Veritabanı bağlantısını kur
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Bağlantıyı kontrol et
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Kategori parametresini al ve temizle
//$kategori = isset($_GET['kategorisi']) ? strtolower(trim($_GET['kategorisi'])) : 'sicakicecek'; // Burada sicak_icecek kategorisini alıyoruz
$kategori = isset($_GET['kategorisi']) ? filter_var(trim($_GET['kategorisi']), FILTER_SANITIZE_STRING) : 'sicakicecek';
// Kategori parametresi boşsa hata mesajı ver
if (empty($kategori)) {
    echo "<p>Kategori parametresi eksik veya geçersiz.</p>";
    exit;
}

// Kategoriye göre içecekleri sorgula
$sql = "SELECT * FROM dbo.GetMenuByCategory(?)";
$params = array($kategori);
$stmt = sqlsrv_query($conn, $sql, $params);

// Sorgu hatası kontrolü
if ($stmt === false) {
    die('Sorgu hatası: ' . print_r(sqlsrv_errors(), true));
}

// Sıcak içecekler için veri var mı kontrol et
$sicakIceceklerMevcut = false;
if ($stmt) {
    // Verileri kontrol et
    if (sqlsrv_has_rows($stmt)) {
        $sicakIceceklerMevcut = true;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo ucfirst($kategori); ?> Menüsü - Lezzet Şöleni</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .header {
      background-color: #00796b;
      color: white;
      text-align: center;
      padding: 20px;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 10;
    }

    .header h1 {
      font-size: 2rem;
    }

    .content {
      flex-grow: 1;
      padding: 80px 20px 20px;
      /* Üst kısımdaki sabit bar için boşluk bırakıyoruz */
      text-align: center;
    }

    .menu-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }

    .menu-item {
      text-align: center;
      max-width: 250px;
      margin-top: 30px;
      /* Fotoğrafların üst kısmında boşluk bırakmak için margin-top ekledik */
    }

    .menu-item img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-top: 70px;
    }

    .menu-item h3 {
      font-size: 1.2rem;
      margin-top: 10px;
      color: #333;
    }

    .back-button {
      display: inline-block;
      padding: 12px 30px;
      background-color: #000000;
      color: white;
      font-size: 1.2rem;
      font-weight: bold;
      border-radius: 50px;
      text-decoration: none;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
      margin-top: 30px;
    }

    .back-button:hover {
      background-color: #004d40;
      transform: translateY(-3px);
      box-shadow: 0 6px 8px rgba(0, 0, 0, 0.3);
    }

    .footer {
      text-align: center;
      background-color: #00796b;
      color: white;
      padding: 10px;
      font-size: 0.9rem;
      margin-top: auto;
    }

    @media (max-width: 768px) {
      .header h1 {
        font-size: 1.5rem;
      }

      .menu-item img {
        height: 150px;
      }

      .back-button {
        font-size: 1rem;
        padding: 10px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="header">
    <h1><?php echo ucfirst($kategori); ?> Menüsü</h1>
  </div>

  <div class="content">
    <?php if ($sicakIceceklerMevcut): ?>
      <div class="menu-grid">
        <?php
        // Veritabanından sonuçları çekmek ve ekrana yazdırmak
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)):
        ?>
          <div class="menu-item">
            <?php
            // Fotoğraf yolunu kontrol et ve varsa göster, yoksa varsayılan bir fotoğraf kullan
            $fotograf = !empty($row['fotograf']) ? $row['fotograf'] : 'images/default.jpg'; // varsayılan fotoğraf
            ?>
            <img src="<?php echo $fotograf; ?>" alt="Fotoğraf">
            <h3><?php echo $row['adi']; ?></h3>
            <p><?php echo $row['fiyati']; ?> TL</p>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p>Bu kategoriye ait menü bulunmamaktadır.</p>
    <?php endif; ?>

    <a href="index.html" class="back-button">Ana Sayfaya Dön</a>
  </div>

  <div class="footer">
    <p>© 2024 Lezzet Şöleni. Tüm hakları saklıdır.</p>
  </div>
</body>

</html>

<?php
// Bağlantıyı kapat
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
