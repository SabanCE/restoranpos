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



// Seçilen kategoriye göre verileri çek
$kategori = isset($_POST['kategori']) ? $_POST['kategori'] : 'yemek'; // Varsayılan kategori: yemek


// SQL sorgusunu hazırlayın
$sql = "SELECT m.id,m.fotograf,m.adi,m.fiyati,m.kategorisi,s.stok from [menü] m full join stokAded s on m.id=s.id where kategorisi=?";
$params = array($kategori); // Parametre olarak kategori adı ekleniyor

// Sorguyu hazırlayın
$query = sqlsrv_prepare($db, $sql, $params);

if ($query === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Sorguyu çalıştırın
if (sqlsrv_execute($query)) {
    $urunler = [];

    // Sonuçları döngüyle alın
    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        $urunler[] = $row;
    }
} else {
    die(print_r(sqlsrv_errors(), true));
}

// Sorguyu serbest bırakın
sqlsrv_free_stmt($query);


// Masa isimlerini çekmek için sorgu
$sql = "SELECT * FROM masa";
$masaQuery = sqlsrv_query($db, $sql);

if ($masaQuery === false) {
    die(print_r(sqlsrv_errors(), true));
}

$masalar = [];
// Sonuçları döngüyle al
while ($row = sqlsrv_fetch_array($masaQuery, SQLSRV_FETCH_ASSOC)) {
    $masalar[] = [
        'id' => $row['id'],
        'adi' => $row['adi'],
        'icerik' => $row['icerik']
    ];
}

// Belleği serbest bırak
sqlsrv_free_stmt($masaQuery);

// Kategori isimleri düzeltildi
$kategoriBasliklari = [
    'yemek' => 'Yemek Kategorisi',
    'tatli' => 'Tatlı Kategorisi',
    'sicakicecek' => 'Sıcak İçecek Kategorisi',
    'sogukicecek' => 'Soğuk İçecek Kategorisi'
];
$baslik = isset($kategoriBasliklari[$kategori]) ? $kategoriBasliklari[$kategori] : 'Kategori';

// Ürün eklemesi için POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['masa'], $_POST['urunler'])) {
    $masaId = intval($_POST['masa']);
    $urunler = json_decode($_POST['urunler'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decoding hatası: " . json_last_error_msg();
        exit;
    }
    
    // // Stokları azaltma işlemi
    // foreach ($urunler as $urun) {
    //     $urunId = $urun['id']; // Ürünün ID'sini alıyoruz
    //     $sql = "UPDATE stokAded  WHERE id = (SELECT id FROM menü WHERE adi = ?)";
    //     $params = array(&$urunId); // Parametre olarak ürün ID'sini ekliyoruz
    //     $query = sqlsrv_prepare($db, $sql, $params);

    //     if ($query === false) {
    //         die(print_r(sqlsrv_errors(), true));
    //     }

    //     // Stok adedini azaltma işlemi
    //     if (!sqlsrv_execute($query)) {
    //         die(print_r(sqlsrv_errors(), true));
    //     }
    //     sqlsrv_free_stmt($query);
    // }

    // Veritabanı bağlantısını yeniden oluşturuyoruz (sqlsrv)
    $conn = sqlsrv_connect($serverName, $connection);
    if (!$conn) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Seçilen masanın mevcut içerik ve toplam fiyatını al
    $masaQuery = sqlsrv_prepare($conn, "SELECT icerik, fiyati FROM masa WHERE id = ?", array($masaId));
    if (sqlsrv_execute($masaQuery)) {
        $masaResult = sqlsrv_fetch_array($masaQuery, SQLSRV_FETCH_ASSOC);
        
        if ($masaResult) {
            $mevcutIcerik = $masaResult['icerik'];
            $mevcutFiyat = $masaResult['fiyati'];
        } else {
            $mevcutIcerik = '';
            $mevcutFiyat = 0;
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($masaQuery);

    // Yeni ürünlerin adlarını ve fiyatlarını ekle
    $urunListesi = implode(", ", array_map(function ($urun) {
        return $urun['adi']; // Sadece ürün adı ekleniyor
    }, $urunler));

    $toplamFiyat = array_sum(array_map(function ($urun) {
        return $urun['fiyat']; // Fiyatları topluyoruz
    }, $urunler));

    // Yeni toplam fiyat, mevcut fiyat ile yeni fiyatın toplamı
    $yeniToplamFiyat = $mevcutFiyat + $toplamFiyat;

    // Masa güncelleme veya ekleme işlemi
    $updateQuery = null; // Varsayılan olarak null
    $insertQuery = null; // Varsayılan olarak null

    if ($masaResult) {
        // Eğer masa zaten varsa, içerik ve toplam fiyat güncellenir
        $updateQuery = sqlsrv_prepare($conn, "UPDATE masa SET icerik = CONCAT(ISNULL(icerik, ''), ?), fiyati = ? WHERE id = ?", array($urunListesi, $yeniToplamFiyat, $masaId));
    } else {
        // Eğer masa yeni eklenmişse, içerik ve toplam fiyat ilk kez eklenir
        $insertQuery = sqlsrv_prepare($conn, "INSERT INTO masa (id, icerik, fiyati) VALUES (?, ?, ?)", array($masaId, $urunListesi, $yeniToplamFiyat));
    }

    // Sorguyu çalıştır
    if ($insertQuery) {
        sqlsrv_execute($insertQuery);
    } elseif ($updateQuery) {
        sqlsrv_execute($updateQuery);
    } else {
        echo "Hata: Sorgu oluşturulamadı.";
        sqlsrv_close($conn);
        exit;
    }

    echo "Siparişler başarıyla kaydedildi.";
    sqlsrv_free_stmt($insertQuery);
    sqlsrv_free_stmt($updateQuery);
    sqlsrv_close($conn);
    exit;
}



?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garson Paneli</title>
    
</head>
<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Horizontal scrollbar'ı engelle */
        }

        .header {
            background-color: #004d40;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            position: sticky; /* Butonları sayfa kaydırıldığında sabit tutar */
            top: 0; /* Yeşil barın hemen bitiminden başlasın */
            width: 100%;
            background-color: #f4f4f4;
            padding: 10px 0;
            z-index: 1000; /* Butonlar diğer içeriklerin önünde olacak şekilde ayarlanır */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .buttons form {
            display: inline;
        }

        .buttons button {
            background-color: #00796b;
            color: white;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1rem;
            min-width: 100px; /* Butonları sabit boyutlandırmak */
            box-sizing: border-box;
        }

        .buttons button:hover {
            background-color: #004d40;
        }

        .content {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 100px; /* Butonlardan sonra içerik başlasın */
        }

        .urun-listesi {
            display: flex;
            flex-wrap: nowrap;
            gap: 20px;
            justify-content: flex-start;
            padding-bottom: 10px;
            overflow-x: auto; /* Yatay kaydırma */
            width: 48%;
        }

        .urun {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 180px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .urun:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .urun img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .urun span {
            display: block;
            margin-top: 8px;
        }

        .urun strong {
            font-size: 1.1rem;
            color: #333;
        }

        .urun .fiyat {
            color: #00796b;
            font-weight: bold;
        }

        .urun .ekle-btn {
            margin-top: 12px;
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .urun .ekle-btn:hover {
            background-color: #d84315;
        }

        .siparis-ayetleri {
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 48%;
        }

        .siparis-ayetleri h3 {
            margin-bottom: 20px;
        }

        .siparis-ayetleri ul {
            list-style-type: none;
            padding: 0;
        }

        .siparis-ayetleri li {
            margin-bottom: 10px;
            font-size: 1rem;
            color: #333;
            display: flex;
            justify-content: space-between;
        }

        .toplam-fiyat {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00796b;
        }

        .sil-btn {
            background-color: #e53935;
            color: white;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .sil-btn:hover {
            background-color: #c62828;
        }

        .masa-select {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .masa-select select {
            padding: 10px;
            font-size: 1rem;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
        }

        /* Ödeme butonu stil */
        .odeme-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 20px;
            width: 100%;
        }

        .odeme-btn:hover {
            background-color: #388e3c;
        }

        /* Çıkış yap butonu için stil */
        .exit-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #e53935;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1rem;
        }

        .exit-btn:hover {
            background-color: #c62828;
        }
    </style>
<body>
    <div class="header">
        <h1>Garson Paneli</h1>
    </div>

    <div class="buttons">
        <!-- Kategori butonları -->
        <form method="POST">
            <input type="hidden" name="kategori" value="yemek">
            <button type="submit">Yemek</button>
        </form>
        <form method="POST">
            <input type="hidden" name="kategori" value="tatli">
            <button type="submit">Tatlı</button>
        </form>
        <form method="POST">
            <input type="hidden" name="kategori" value="sicakicecek">
            <button type="submit">Sıcak İçecek</button>
        </form>
        <form method="POST">
            <input type="hidden" name="kategori" value="sogukicecek">
            <button type="submit">Soğuk İçecek</button>
        </form>
    </div>
    <div class="content">
        <div class="urun-listesi">
            <h2><?php echo htmlspecialchars($baslik); ?></h2>
            <?php if (count($urunler) > 0): ?>
                <?php foreach ($urunler as $urun): ?>
                    <div class="urun">
                        <img src="<?php echo htmlspecialchars($urun['fotograf']); ?>" alt="<?php echo htmlspecialchars($urun['adi']); ?>">
                        <span><strong><?php echo htmlspecialchars($urun['adi']); ?></strong></span>
                        <span class="fiyat"><?php echo number_format($urun['fiyati'], 2); ?> TL</span>
                        <span class="stok"><?php echo number_format($urun['stok'], 2); ?> Tane Stok </span>
                        <button class="ekle-btn" onclick="ekle('<?php echo htmlspecialchars($urun['adi']); ?>', <?php echo $urun['fiyati']; ?>)">Ekle</button>
                       

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Bu kategoride ürün bulunmamaktadır.</p>
            <?php endif; ?>
        </div>

        <div class="siparis-ayetleri">
            <h3>Menü Sipariş Ayrıntıları</h3>
            <ul id="siparis-listesi"></ul>
            <p class="toplam-fiyat">Toplam Fiyat: <span id="toplam-fiyat">0.00</span> TL</p>

            <div class="masa-select">
                <label for="masa">Masa Seçin:</label>
                <select id="masa">
                    <?php foreach ($masalar as $masa): ?>
                        <option value="<?php echo htmlspecialchars($masa['id']); ?>"><?php echo htmlspecialchars($masa['adi']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="odeme-btn" onclick="odemeYap()">Siparişleri Kaydet</button>
        </div>
    </div>

    <!-- Çıkış Yap Butonu -->
    <button class="exit-btn" onclick="window.location.href='login.html'">Çıkış Yap</button>

    <script>
        // Sipariş listesi ve toplam fiyatı saklamak için
let siparisler = JSON.parse(localStorage.getItem('siparisler')) || [];
let toplamFiyat = parseFloat(localStorage.getItem('toplamFiyat')) || 0;

// Sayfa yüklendiğinde siparişleri ve toplam fiyatı güncelle
window.onload = function() {
    // localStorage'dan siparişleri ve toplam fiyatı al
    siparisler = JSON.parse(localStorage.getItem('siparisler')) || [];
    toplamFiyat = parseFloat(localStorage.getItem('toplamFiyat')) || 0;

    // Sayfa yüklendiğinde siparişleri ve toplam fiyatı güncelle
    siparisler.forEach(item => {
        ekle(item.adi, item.fiyat, item.stok, false); // false parametresi, siparişi sadece ekler, silme işlemi yapmaz
    });

    // Toplam fiyatı sayfada güncelle
    document.getElementById("toplam-fiyat").textContent = toplamFiyat.toFixed(2);
};

 //Ekleme fonksiyonu
 // Ekleme fonksiyonu
function ekle(adi, fiyat, stok, kaydet = true) {
    // if (stok <= 0) {
    //     alert(adi + " stoğu tükendi.");
    //     return;  // Stok bitmişse işlem yapılmaz
    // }

    // Yeni siparişi listeye ekle
    siparisler.push({ adi: adi, fiyat: fiyat });

    // Sipariş listesine yeni ürün ekle
    const li = document.createElement("li");
    li.textContent = adi + " - " + fiyat.toFixed(2) + " TL";

    // Sil butonu ekleyelim
    const silBtn = document.createElement("button");
    silBtn.textContent = "Sil";
    silBtn.classList.add("sil-btn");
    silBtn.onclick = function() {
        sil(adi, fiyat, li);
    };

    li.appendChild(silBtn);
    document.getElementById("siparis-listesi").appendChild(li);

    // Toplam fiyatı güncelle
    toplamFiyat += fiyat;
    document.getElementById("toplam-fiyat").textContent = toplamFiyat.toFixed(2);

    // Eğer kaydet parametresi true ise siparişi localStorage'a kaydet
    if (kaydet) {
        localStorage.setItem('siparisler', JSON.stringify(siparisler));
        localStorage.setItem('toplamFiyat', toplamFiyat);
    }

    // Stok değerini azalt
    //stok--;

    // Stok veritabanına da yansıtılmalı
    fetch('stokAzalt.php', {
        method: 'POST',
        body: JSON.stringify({ urunAdi: adi })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Stok azaldı.");
        } else {
            console.error("Stok azaltma hatası:", data.error);
        }
    })
    .catch(error => {
        console.error("Hata:", error);
    });
}



// Silme fonksiyonu
function sil(adi, fiyat, li) {
    // Sipariş listesinden çıkar
    siparisler = siparisler.filter(item => item.adi !== adi);
    
    // Listeden sil
    li.remove();

    // Toplam fiyatı güncelle
    toplamFiyat -= fiyat;
    document.getElementById("toplam-fiyat").textContent = toplamFiyat.toFixed(2);

    // Siparişi localStorage'dan sil
    localStorage.setItem('siparisler', JSON.stringify(siparisler));
    localStorage.setItem('toplamFiyat', toplamFiyat);
}

//  function ekle(adi, fiyat) {
//              siparisler.push({ adi, fiyat });
//              const li = document.createElement("li");
//              li.textContent = adi + " - " + fiyat.toFixed(2) + " TL";
//              document.getElementById("siparis-listesi").appendChild(li);

//             toplamFiyat += fiyat;
//              document.getElementById("toplam-fiyat").textContent = toplamFiyat.toFixed(2);
//          }
function odemeYap() {
    const masa = document.getElementById("masa").value;

    if (siparisler.length === 0) {
        alert("Sipariş listesi boş!");
        return;
    }

    const data = new FormData();
    data.append("masa", masa);
    data.append("urunler", JSON.stringify(siparisler));

    fetch("", {
        method: "POST",
        body: data
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
        
        // Siparişleri ve toplam fiyatı sıfırlama
        siparisler = []; // Sipariş listesini sıfırla
        toplamFiyat = 0; // Toplam fiyatı sıfırla

        // Sipariş listesini ve toplam fiyatı sıfırlayın
        document.getElementById("siparis-listesi").innerHTML = "";
        document.getElementById("toplam-fiyat").textContent = "0.00";

        // LocalStorage'dan verileri temizle
        localStorage.removeItem('siparisler');
        localStorage.removeItem('toplamFiyat');
    })
    .catch(error => {
        console.error("Hata:", error);
    });
}
        
    </script>
</body>
</html>

