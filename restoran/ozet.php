<?php
// Veritabanı bağlantısı
$serverName = "";
$database = "Restorann"; 
$uid = "sa"; 
$pass = "1"; 
$connection = [
    "Database" => $database, 
    "Uid" => $uid, 
    "PWD" => $pass,
    "CharacterSet" => "UTF-8"
];
$db = sqlsrv_connect($serverName, $connection);

// Bağlantıyı kontrol et
if (!$db) {
    die(print_r(sqlsrv_errors(), true));
}

// Ödeme işlemi
if (isset($_POST['odeme'])) {
    if (isset($_POST['masa_ids']) && is_array($_POST['masa_ids']) && count($_POST['masa_ids']) > 0) {
        $masaIds = $_POST['masa_ids'];  // Seçilen masa ID'leri
        foreach ($masaIds as $masaId) {
            // Masa silme işlemi
            $deleteQuery = "DELETE FROM masa WHERE id = ?";
            $stmt = sqlsrv_prepare($db, $deleteQuery, array(&$masaId));
            if (sqlsrv_execute($stmt) === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // Son ödendi mesajını al
        $logQuery = "SELECT TOP 1 mesaj FROM MasaOdemeLog ORDER BY log_id DESC";
        $logResult = sqlsrv_query($db, $logQuery);

        if ($logResult !== false) {
            $logRow = sqlsrv_fetch_array($logResult, SQLSRV_FETCH_ASSOC);
            $odemeMesaji = $logRow['mesaj'];
            echo "<script>alert('$odemeMesaji');</script>";
        }
    }
}

// Masaları getir
$query = "SELECT * FROM masa";
$queryResult = sqlsrv_query($db, $query);

$masalar = [];
if ($queryResult !== false) {
    while ($row = sqlsrv_fetch_array($queryResult, SQLSRV_FETCH_ASSOC)) {
        $masalar[] = $row;
    }
} else {
    echo "Veritabanında masa bulunamadı.";
}

// Bağlantıyı kapat
sqlsrv_free_stmt($queryResult);
sqlsrv_close($db);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Lezzet Şöleni</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; }
        .sidebar { background-color: #004d40; color: white; width: 250px; height: 100vh; padding-top: 20px; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a { color: white; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar ul li a:hover { background-color: #00796b; }
        .content { margin-left: 250px; padding: 20px; width: 100%; }
        .header { background-color: #004d40; color: white; padding: 10px; text-align: right; }
        .masa-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .masa-card { background-color: white; padding: 10px; border: 1px solid #ddd; border-radius: 5px; text-align: center; width: 150px; }
        .masa-card img { width: 100%; height: 100px; object-fit: cover; border-radius: 5px; }
        .kasa { margin-top: 20px; text-align: center; }
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

    /* Masa Kartı */
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
      text-align: center;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
    }

    .masa-card img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      border-radius: 5px;
    }

    .masa-card p {
      font-size: 0.9rem;
      margin: 5px 0;
      color: #333;
    }

    /* Kasa ve Ödeme */
    .kasa {
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
      text-align: center;
      width: 200px;
    }

    .kasa h3 {
      margin-bottom: 15px;
    }

    .kasa p {
      font-size: 1.2rem;
      font-weight: bold;
      color: #333;
    }

    .kasa button {
      background-color: #00796b;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .kasa button:hover {
      background-color: #004d40;
    }
    </style>
</head>
<body>
    <div class="sidebar">
        <ul>
            <li><a href="kullanicihesaplari.php">Kullanıcı Hesapları</a></li>
            <li><a href="menüler.php">Menüler</a></li>
            <li><a href="pos.php">POS</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="header">Hoş geldiniz, Admin</div>
        <h3>Masalar</h3>
        <form method="POST">
            <div class="masa-container">
                <?php foreach ($masalar as $masa): ?>
                    <div class="masa-card">
                        <img src="<?php echo $masa['fotograf']; ?>" alt="<?php echo $masa['adi']; ?>">
                        <p><strong><?php echo $masa['adi']; ?></strong></p>
                        <p><?php echo $masa['fiyati']; ?> TL</p>
                        <input type="checkbox" name="masa_ids[]" value="<?php echo $masa['id']; ?>"> Öde
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="kasa">
                <button type="submit" name="odeme">Öde ve Sil</button>
            </div>
        </form>
    </div>
</body>
</html>
