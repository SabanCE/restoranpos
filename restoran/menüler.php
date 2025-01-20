<?php
session_start();

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
$conn = sqlsrv_connect($serverName, $connection);

// Bağlantıyı kontrol et
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Menü ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $adi = $_POST['adi'] ?? '';
    $fiyati = $_POST['fiyati'] ?? 0;
    $kategori = $_POST['kategori'] ?? '';

    // Fotoğraf yükleme işlemi
    if (isset($_FILES['fotograf']) && $_FILES['fotograf']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Klasör oluştur
        }

        $file_name = basename($_FILES['fotograf']['name']);
        $target_file = $target_dir . uniqid() . "_" . $file_name;
        $file_type = pathinfo($target_file, PATHINFO_EXTENSION);

        // Sadece belirli türde dosyalara izin ver
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($file_type), $allowed_types)) {
            // Yükleme hedef dosyası
            $target_file = $target_dir . basename($_FILES['fotograf']['name']);

            if (move_uploaded_file($_FILES['fotograf']['tmp_name'], $target_file)) {
                // Dosya başarıyla yüklendi
                $sql = "INSERT INTO menü (adi, fiyati, kategorisi, fotograf) VALUES (?, ?, ?, ?)";
                $params = array($adi, $fiyati, $kategori, $target_file);

                // Sorguyu hazırla
                $stmt = sqlsrv_prepare($conn, $sql, $params);

                if ($stmt) {
                    // Sorguyu çalıştır
                    if (sqlsrv_execute($stmt)) {
                        // İşlem başarılı olduğunda yönlendirme
                        header("Location: menüler.php");
                        exit();
                    } else {
                        echo "<p style='color: red;'>Hata: " . print_r(sqlsrv_errors(), true) . "</p>";
                    }
                    sqlsrv_free_stmt($stmt);
                } else {
                    echo "<p style='color: red;'>Sorgu hazırlanırken bir hata oluştu: " . print_r(sqlsrv_errors(), true) . "</p>";
                }
            } else {
                echo "<p style='color: red;'>Dosya yüklenirken bir hata oluştu.</p>";
            }
        } else {
            echo "<p style='color: red;'>Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilir.</p>";
        }
    } else {
        echo "<p style='color: red;'>Lütfen bir fotoğraf seçin.</p>";
    }
}

// Menü silme işlemi
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM menü WHERE id = ?";
    $params = array($delete_id);
    $stmt = sqlsrv_prepare($conn, $delete_sql, $params);

    if ($stmt) {
        if (sqlsrv_execute($stmt)) {
            header("Location: menüler.php");
            exit();
        } else {
            echo "<p style='color: red;'>Silme işlemi başarısız oldu. Hata: " . print_r(sqlsrv_errors(), true) . "</p>";
        }
        sqlsrv_free_stmt($stmt);
    } else {
        echo "<p style='color: red;'>Sorgu hazırlanırken bir hata oluştu: " . print_r(sqlsrv_errors(), true) . "</p>";
    }
}

// Menüleri listeleme
$sql = "SELECT * FROM dbo.GetAllMenus()";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Menüleri listeleme sorgusu başarısız: " . print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Paneli - Lezzet Şöleni</title>
  <style>
    /* Genel Stiller */
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
      margin-bottom: 30px;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin: 20px 0;
      text-align: center;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      padding: 10px 20px;
      display: block;
      border-radius: 5px;
      transition: background-color 0.3s;
    }

    .sidebar ul li a:hover {
      background-color: #00796b;
    }

    /* Sağ İçerik */
    .content {
      margin-left: 250px;
      padding: 20px;
      width: 100%;
    }

    /* Header */
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
      font-size: 1rem;
      text-decoration: none;
      display: inline-block;
    }

    .logout-btn:hover {
      background-color: #004d40;
    }

    /* Bölüm Başlıkları */
    .section {
      margin-top: 20px;
    }

    .section h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    /* Tablo Stilleri */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table,
    th,
    td {
      border: 1px solid #ddd;
    }

    th {
      background-color: #004d40;
      color: white;
    }

    td,
    th {
      padding: 10px;
      text-align: left;
    }

    .delete-btn {
      background-color: red;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
    }

    .delete-btn:hover {
      background-color: #f44336;
    }

    /* Ekleme Formu Stilleri */
    .form-container {
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .form-container label {
      font-size: 1rem;
      margin-bottom: 5px;
      display: block;
    }

    .form-container input,
    .form-container select,
    .form-container button {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .form-container input:focus,
    .form-container select:focus {
      border-color: #004d40;
      outline: none;
    }

    .form-container button {
      background-color: #00796b;
      color: white;
      font-size: 1rem;
      cursor: pointer;
      border: none;
    }

    .form-container button:hover {
      background-color: #004d40;
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
    <h3>Menüler</h3>

    <!-- Menü Ekleme Formu -->
    <div class="form-container">
        <form method="POST" action="menüler.php" enctype="multipart/form-data">
            <label for="adi">Adı:</label>
            <input type="text" name="adi" id="adi" placeholder="Yemek veya içecek adı" required>

            <label for="adi">Stok Adedi:</label>
            <input type="text" name="stok" id="stok" step="0.01" placeholder="Stok Girin" required>

            <label for="fiyati">Fiyatı:</label>
            <input type="number" name="fiyati" id="fiyati" step="0.01" placeholder="Fiyatı" required>

            <label for="kategori">Kategori:</label>
            <select name="kategori" id="kategori" required>
                <option value="yemek">Yemek</option>
                <option value="tatli">Tatlı</option>
                <option value="sicakicecek">Sıcak İçecek</option>
                <option value="sogukicecek">Soğuk İçecek</option>
                

            </select>

            <label for="fotograf">Fotoğraf:</label>
            <input type="file" name="fotograf" id="fotograf" accept="image/*" required>

            <button type="submit" name="submit">Ekle</button>
        </form>
    </div>

    <!-- Menü Tablosu -->
    <table>
        <thead>
            <tr>
                <th>Adı</th>
                <th>Fiyatı</th>
                <th>Kategori</th>
                <th>Fotoğraf</th>
                <th>İşlemler</th>
                <th>Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Veritabanı bağlantısı
            $serverName = "SABANAKCEHRE\SABAN";
            $database = "Restorann";
            $uid = "sa";
            $pass = "1";
            $connection = [
                "Database" => $database,
                "Uid" => $uid,
                "PWD" => $pass,
                "CharacterSet" => "UTF-8"
            ];
            $conn = sqlsrv_connect($serverName, $connection);

            if (!$conn) {
                die("Veritabanı bağlantısı başarısız: " . print_r(sqlsrv_errors(), true));
            }

            // Menü listeleme
            //$sql = "SELECT * FROM menü";
            $sql = "SELECT *from menuvestokadedibirlestirme ";
            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                die("Sorgu başarısız: " . print_r(sqlsrv_errors(), true));
            }
            

            $hasData = false;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $hasData = true;
                if ($row['stok'] === null) {
                  $row['stok'] = '';
              }
                echo "<tr>
                    <td>" . htmlspecialchars($row['adi']) . "</td>
                    <td>" . htmlspecialchars($row['fiyati']) . "</td>
                    <td>" . htmlspecialchars($row['kategorisi']) . "</td>
                    <td><img src='" . htmlspecialchars($row['fotograf']) . "' alt='Fotoğraf' width='50'></td>
                    <td>
                        <a href='menüler.php?delete_id={$row['id']}' class='delete-btn' onclick='return confirm(\"Bu menüyü silmek istediğinize emin misiniz?\")'>Sil</a>
                    </td>
                    <td>" . htmlspecialchars($row['stok']) . "</td>
                    
                </tr>";
            }

            if (!$hasData) {
                echo "<tr><td colspan='5'>Hiç menü bulunmamaktadır.</td></tr>";
            }

            // Menü silme işlemi
            if (isset($_GET['delete_id'])) {
                $delete_id = $_GET['delete_id'];
                $delete_sql = "DELETE FROM menü WHERE id = ?";
                $params = [$delete_id];

                $delete_stmt = sqlsrv_prepare($conn, $delete_sql, $params);

                if ($delete_stmt && sqlsrv_execute($delete_stmt)) {
                    echo "<p style='color: green;'>Menü başarıyla silindi.</p>";
                    header("Refresh:0; url=menüler.php");
                } else {
                    echo "<p style='color: red;'>Silme işlemi başarısız: " . print_r(sqlsrv_errors(), true) . "</p>";
                }

                sqlsrv_free_stmt($delete_stmt);
            }

            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
            ?>
        </tbody>
    </table>
</div>

  </div>
</body>

</html>