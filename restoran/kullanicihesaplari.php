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
    "PWD" => $pass
];
$conn = sqlsrv_connect($serverName, $connection);

// Bağlantıyı kontrol et
if (!$conn) {
    die("Veritabanı bağlantısı başarısız: " . print_r(sqlsrv_errors(), true));
}

// Admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.html"); // Admin değilse login sayfasına yönlendir
    exit();
}

// Kullanıcı silme işlemi
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "EXEC sp_kullanici_sil ?";
    $params = array($delete_id);

    $stmt = sqlsrv_prepare($conn, $delete_sql, $params);
    if ($stmt) {
        if (sqlsrv_execute($stmt)) {
            // Silme işlemi başarılı, log kaydını kontrol et
            // Kullanıcı silme işlemi sonrası mesajı göstermek için
$sql = "SELECT TOP 1 mesaj FROM KullaniciSilmeLog ORDER BY log_id DESC";
$result_log = sqlsrv_query($conn, $sql);

if ($result_log === false) {
    die("Log sorgusu çalıştırma başarısız: " . print_r(sqlsrv_errors(), true));
}

$log = sqlsrv_fetch_array($result_log, SQLSRV_FETCH_ASSOC);
$message = isset($log['mesaj']) ? $log['mesaj'] : '';


            
            
        } 
            
        
    } 
        
    
}

// Kullanıcıları listeleme sorgusu
$sql = "EXEC sp_kullanici_listele";
$result = sqlsrv_query($conn, $sql);

if ($result === false) {
    die("Sorgu çalıştırma başarısız: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Kullanıcı Hesapları</title>
    <style>
        /* CSS Kodları */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            background-color: #004d40;
            color: white;
            width: 250px;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
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
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #004d40;
            color: white;
            text-align: left;
        }
        td {
            padding: 10px;
        }
        .delete-btn {
            background-color: red;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c40000;
        }
    </style>
</head>
<body>
    <!-- Sol Menü -->
    <div class="sidebar">
        <h2>Admin Paneli</h2>
        <ul>
            <li><a href="kullanicihesaplari.php">Kullanıcı Hesapları</a></li>
            <li><a href="menuler.php">Menüler</a></li>
            <li><a href="pos.php">POS</a></li>
        </ul>
    </div>

    <!-- İçerik -->
    <div class="content">
        <h1>Kullanıcı Hesapları</h1>
        <p><?php echo isset($message) ? $message : ''; ?></p>

        <table>
            <thead>
                <tr>
                    <th>Kullanıcı Adı</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hasData = false; // Tablo boş mu kontrolü
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) :
                    $hasData = true;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['kullanici_adi']); ?></td>
                        <td>
                            <a href="kullanicihesaplari.php?delete_id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if (!$hasData) : ?>
                    <tr>
                        <td colspan="2">Hiç kullanıcı bulunmamaktadır.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Belleği serbest bırak ve bağlantıyı kapat
sqlsrv_free_stmt($result);
sqlsrv_close($conn);
?>
