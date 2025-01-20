<?php
ini_set('display_errors', 1);  // Hata görüntülemeyi etkinleştir
error_reporting(E_ALL);  // Tüm hataları göster

session_start(); // Oturum başlatma

// Veritabanı bağlantısı
$serverName = "";
$database="Restorann";
$uid="sa";
$pass="1";
$connection=[
    "Database"=>$database,
    "Uid"=>$uid,
    "PWD"=>$pass,
    "CharacterSet" => "UTF-8"
];
$conn=sqlsrv_connect($serverName,$connection);

// // Bağlantıyı kontrol et
if (!$conn) {
    die(print_r(sqlsrv_errors(),true));
}

// Hata mesajını tutacak değişken
$error_message = "";

// Giriş formu gönderildiyse işlemi başlat
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // SQL sorgusunu hazırla
    $sql = "SELECT * FROM dbo.GetUserByUsername(?)";
    $params = array($user); // Parametre olarak kullanıcı adı ekleniyor

    // Hazırlanan sorgu
    $stmt = sqlsrv_prepare($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Sorguyu çalıştır
    if (sqlsrv_execute($stmt)) {
        // Sonuçları al
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Veritabanındaki şifreyi hash ile doğrula
            if (password_verify($pass, $row['sifre'])) { // 'sifre' kısmını veritabanındaki gerçek kolon adıyla değiştirin
                // Giriş başarılı, oturumu başlat
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['kullanici_adi'];

                // Eğer kullanıcı adı "admin" ise, admin paneline yönlendir
                if ($_SESSION['username'] == "admin") {
                    header("Location: adminpaneli.html");
                    exit();
                } else {
                    // Admin olmayan kullanıcıları kullanicipaneli.html sayfasına yönlendir
                    header("Location: kullanicipaneli.php");
                    exit();
                }
            } else {
                // Şifre hatalı
                $error_message = "Hatalı şifre!";
            }
        } else {
            // Kullanıcı adı bulunamadı
            $error_message = "Kullanıcı adı bulunamadı!";
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }

    // Belleği serbest bırak
    sqlsrv_free_stmt($stmt);
}


sqlsrv_close($conn);

// Hata mesajını URL parametresi olarak gönder
if (!empty($error_message)) {
    header("Location: login.html?error=" . urlencode($error_message));
    exit();
}
