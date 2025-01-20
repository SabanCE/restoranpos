<?php
// Veritabanı bağlantısı
$serverName = "";// Escape the backslash
$database = "Restorann";
$uid = "sa";
$pass = "1";
$connectionOptions = array(
    "Database" => $database,
    "Uid" => $uid,
    "PWD" => $pass,
    "CharacterSet" => "UTF-8"
);

$db = sqlsrv_connect($serverName, $connectionOptions);

// Veritabanı bağlantısı kontrolü
if (!$db) {
    die(print_r(sqlsrv_errors(), true));
}

// Masa ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $icerik = $_POST['icerik'];
    $adi = $_POST['adi'];
    $fiyati = $_POST['fiyati'];

    // Fotoğraf yükleme işlemi
    $foto = $_FILES['foto'];
    $fotoYolu = "uploads/" . basename($foto['name']);
    move_uploaded_file($foto['tmp_name'], $fotoYolu);

    // SQL Server için insert sorgusu
    //$query = "INSERT INTO masa (icerik, adi, fiyati, fotograf) VALUES (?, ?, ?, ?)";
    $query = "EXEC sp_masa_ekle ?,?,?,?";
    $params = array($icerik, $adi, $fiyati, $fotoYolu);
    $stmt = sqlsrv_query($db, $query, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Başarılı işlem sonrası yönlendirme
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Masa silme işlemi
if (isset($_GET['id'])) {
    $masaId = $_GET['id'];

    $query = "EXEC sp_masa_sil ?";
    $params = array($masaId);
    $stmt = sqlsrv_query($db, $query, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
}

// Veritabanındaki masaları al
$query = "SELECT * FROM masa";
$result = sqlsrv_query($db, $query);

$masalar = [];
if ($result) {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $masalar[] = $row;
    }
} else {
    echo "Veritabanında masa bulunamadı.";
}

sqlsrv_close($db);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Paneli - Lezzet Şöleni</title>
    <style>
        * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
    }

    /* Sol Menü */
    .sidebar {
      background-color: #004d40;
      color: white;
      width: 250px;
      height: 100vh;
      position: fixed;
      padding-top: 20px;
    }

    .sidebar h2 {
      text-align: center;
      color: #fff;
      margin-bottom: 30px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar ul li {
      margin: 20px 0;
      text-align: center;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      font-size: 1.2rem;
      padding: 10px 20px;
      display: block;
      border-radius: 5px;
      transition: background-color 0.3s;
    }

    .sidebar ul li a:hover {
      background-color: #00796b;
    }

    /* Sağ içerik */
    .content {
      margin-left: 250px;
      padding: 20px;
      width: 100%;
    }

    .header {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      background-color: #004d40;
      padding: 10px 20px;
      color: white;
    }

    .header span {
      margin-right: 20px;
      font-size: 1.2rem;
    }

    .logout-btn {
      background-color: #00796b;
      color: white;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 5px;
    }

    .logout-btn:hover {
      background-color: #004d40;
    }

    .section {
      margin-top: 20px;
    }

    .section h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    .section p {
      font-size: 1.1rem;
      color: #333;
    }

    /* Masa Ekleme ve Görünüm */
    .masa-form {
      display: flex;
      flex-direction: column;
      width: 300px;
      margin-bottom: 20px;
    }

    .masa-form input {
      padding: 10px;
      margin: 5px 0;
      font-size: 1rem;
      border-radius: 5px;
    }

    .masa-form button {
      padding: 10px;
      background-color: #00796b;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 10px;
    }

    .masa-form button:hover {
      background-color: #004d40;
    }

    .masa-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .masa-card {
      background-color: #fff;
      padding: 10px;
      border-radius: 5px;
      width: 150px;
      height: 250px;
      /* Yükseklik belirledik, kareyi oluşturuyor */
      text-align: center;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
      /* Taşmayı engeller */
    }

    .masa-card p {
      word-wrap: break-word;
      /* Uzun kelimelerin satır sonunda kırılmasını sağlar */
      overflow: hidden;
      /* Taşmayı engeller */
      text-overflow: ellipsis;
      /* Fazla içerik için üç nokta ekler */
      font-size: 0.9rem;
      margin: 5px 0;
      color: #333;
    }

    .masa-card img {
      width: 100%;
      height: 100px;
      /* Resmin yüksekliğini sabitledik */
      object-fit: cover;
      /* Resmin kutuya sığmasını sağlar */
      border-radius: 5px;
    }

    .masa-card button {
      margin-top: 10px;
      padding: 5px;
      background-color: #f44336;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .masa-card button:hover {
      background-color: #d32f2f;
    }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin Paneli</h2>
        <ul>
            <li><a href="kullanicihesaplari.php">Kullanıcı Hesapları</a></li>
            <li><a href="menüler.php">Menüler</a></li>
            <li><a href="pos.php">POS</a></li>
            <li><a href="ozet.php">Özet</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header">
            <span>Hoş geldiniz, Admin</span>
            <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        </div>

        <div class="section">
            <h3>Masalar</h3>
            <form class="masa-form" id="masaForm" method="POST" enctype="multipart/form-data">
                <input type="text" id="icerikAdi" name="icerik" placeholder="İçerik Adı" required />
                <input type="text" id="masaAdi" name="adi" placeholder="Masa Adı" required />
                <input type="number" id="masaFiyat" name="fiyati" placeholder="Fiyat" required />
                <input type="file" id="masaFoto" name="foto" required />
                <button type="submit">Masa Ekle</button>
            </form>

            <div class="masa-container" id="masaContainer">
                <?php foreach ($masalar as $masa): ?>
                    <div class="masa-card" data-id="<?php echo $masa['id']; ?>">
                        <img src="<?php echo $masa['fotograf']; ?>" alt="<?php echo $masa['adi']; ?>">
                        <p><strong><?php echo $masa['adi']; ?></strong></p>
                        <p>İçerik: <?php echo $masa['icerik']; ?></p>
                        <p>Fiyat: <?php echo $masa['fiyati']; ?> TL</p>
                        <a href="?id=<?php echo $masa['id']; ?>" onclick="return confirm('Masa silinsin mi?')">
                            <button>Sil</button>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>
