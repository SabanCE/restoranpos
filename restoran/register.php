<?php
// Veritabanı bağlantı bilgileri
$serverName = ""; // Escape the backslash
$database = "Restorann";
$uid = "sa";
$pass = "1";
$connectionOptions = array(
    "Database" => $database,
    "Uid" => $uid,
    "PWD" => $pass
);

// Veritabanı bağlantısını kur
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Bağlantıyı kontrol et
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Formdan gelen verilerin işlenmesi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen kullanıcı adı ve şifreyi güvenli şekilde al
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcı adı ve şifre boş olmamalı
    if (empty($username) || empty($password)) {
        echo "Kullanıcı adı ve şifre boş olamaz.";
        exit; // Boşsa işlem yapılmasın
    }

    // Şifreyi hash'leyerek veritabanına kaydediyoruz
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // SQL sorgusu: Kullanıcı adı ve şifreyi veritabanına ekleme
    //$sql = "INSERT INTO kullaniciler (kullanici_adi, sifre) VALUES (?, ?)";
    $sql = "EXEC sp_kullanici_ekle ?, ?";
    $params = array($username, $hashed_password);

    // Veritabanına veri eklemek için sorguyu çalıştır
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Eğer sorgu başarılıysa
    if ($stmt) {
        // Kayıt başarılı olduğunda login sayfasına yönlendir
        header("Location: login.html");
        exit;
    } else {
        // Kayıt başarısız olursa
        echo "Kayıt işlemi başarısız oldu: ";
        die(print_r(sqlsrv_errors(), true));
    }
}

// Veritabanı bağlantısını kapat
sqlsrv_close($conn);
?>
