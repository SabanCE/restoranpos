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
    die(json_encode(["error" => sqlsrv_errors()]));
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$urunAdi = $data['urunAdi'] ?? '';

// Stok azaltma işlemi
if ($urunAdi) {
    $sql = "UPDATE menü SET adi = adi WHERE adi = ?";
    //$sql="UPDATE stokAded set stok=stok-1 WHERE id = 1)";
    $params = [$urunAdi];
    $query = sqlsrv_prepare($db, $sql, $params);

    if ($query === false) {
        die(json_encode(["error" => sqlsrv_errors()]));
    }

    // Stok adedini azaltma işlemi
    if (sqlsrv_execute($query)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => sqlsrv_errors()]);
    }

    sqlsrv_free_stmt($query);
} else {
    echo json_encode(["error" => "Ürün adı geçersiz"]);
}

sqlsrv_close($db);
?>
