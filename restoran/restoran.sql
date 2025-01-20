CREATE DATABASE Restorann;

USE [Restorann];

-- Tablo: kullaniciler
IF OBJECT_ID('kullaniciler', 'U') IS NULL
BEGIN
    CREATE TABLE kullaniciler (
        id INT IDENTITY(1,1) PRIMARY KEY,
        kullanici_adi NVARCHAR(255) NOT NULL,
        sifre NVARCHAR(255) NOT NULL
    );
END;

-- Tablo: masa
IF OBJECT_ID('masa', 'U') IS NULL
BEGIN
    CREATE TABLE masa (
        id INT IDENTITY(1,1) PRIMARY KEY,
        icerik NVARCHAR(255) NOT NULL,
        adi NVARCHAR(100) NOT NULL,
        fiyati DECIMAL(18,2) NOT NULL,
        fotograf NVARCHAR(255) NOT NULL
    );
END;

-- Tablo: menü
IF OBJECT_ID('menü', 'U') IS NULL
BEGIN
    CREATE TABLE [menü] (
        id INT IDENTITY(1,1) PRIMARY KEY,
        fotograf NVARCHAR(255) NOT NULL,
        adi NVARCHAR(255) NOT NULL,
        fiyati DECIMAL(18,2) NOT NULL,
        kategorisi NVARCHAR(255) NOT NULL
    );
END;

-- Tablo: stokAded
IF OBJECT_ID('stokAded', 'U') IS NULL
BEGIN
    CREATE TABLE stokAded (
        id INT PRIMARY KEY,
        stok INT NOT NULL
    );
END;

-- Örnek veri ekleme
INSERT INTO kullaniciler (kullanici_adi, sifre)
VALUES ('admin', '$2y$10$CVTvax7lbKgIvymnaJ72d.r0gxU6/5chTlMQ5unZFspaI5C7sV3Da');

-- Kimlik alanı sıralamasını ayarla
DBCC CHECKIDENT ('kullaniciler', RESEED, 8);
DBCC CHECKIDENT ('masa', RESEED, 29);
DBCC CHECKIDENT ('[menü]', RESEED, 16);

-- Stored Procedures
GO
CREATE PROCEDURE sp_kullanici_ekle_v2
    @kullanici_adi NVARCHAR(255),
    @sifre NVARCHAR(255)
AS
BEGIN
    INSERT INTO kullaniciler (kullanici_adi, sifre)
    VALUES (@kullanici_adi, @sifre);
END;
GO

GO
CREATE PROCEDURE sp_masa_ekle_v2
    @icerik NVARCHAR(255),
    @adi NVARCHAR(100),
    @fiyati DECIMAL(18,2),
    @fotograf NVARCHAR(255)
AS
BEGIN
    INSERT INTO masa (icerik, adi, fiyati, fotograf)
    VALUES (@icerik, @adi, @fiyati, @fotograf);
END;
GO

GO
CREATE PROCEDURE sp_masa_sil_v2
    @id INT
AS
BEGIN
    DELETE FROM masa WHERE id = @id;
END;
GO

GO
CREATE PROCEDURE sp_kullanici_listele_v2
AS
BEGIN
    SELECT id, kullanici_adi FROM kullaniciler;
END;
GO

GO
CREATE PROCEDURE sp_kullanici_sil_v2
    @id INT
AS
BEGIN
    DELETE FROM kullaniciler WHERE id = @id;
END;
GO

-- Trigger: stokGuncelle_v2
GO
CREATE TRIGGER stokGuncelle_v2
ON [menü]
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Stok değerini güncelle
    UPDATE stokAded
    SET stok = stok - 1 + 1
    FROM stokAded
    INNER JOIN INSERTED i ON stokAded.id = i.id;
END;
GO

-- Kullanıcı Silme Log Tablosu
IF OBJECT_ID('KullaniciSilmeLog_v2', 'U') IS NULL
BEGIN
    CREATE TABLE KullaniciSilmeLog_v2 (
        log_id INT IDENTITY(1,1) PRIMARY KEY,
        kullanici_id INT,
        kullanici_adi NVARCHAR(100),
        silme_tarihi DATETIME DEFAULT GETDATE(),
        mesaj NVARCHAR(255)
    );
END;

-- Kullanıcı Silme Log Trigger
GO
CREATE TRIGGER trg_KullaniciSilmeLog_v2
ON kullaniciler
AFTER DELETE
AS
BEGIN
    DECLARE @kullanici_id INT, @kullanici_adi NVARCHAR(100);

    SELECT @kullanici_id = id, @kullanici_adi = kullanici_adi FROM DELETED;

    INSERT INTO KullaniciSilmeLog_v2 (kullanici_id, kullanici_adi, mesaj)
    VALUES (
        @kullanici_id, 
        @kullanici_adi, 
        CONCAT(@kullanici_adi, ' adlı kullanıcı silindi.')
    );
END;
GO

-- Masa Ödeme Log Tablosu
IF OBJECT_ID('MasaOdemeLog_v2', 'U') IS NULL
BEGIN
    CREATE TABLE MasaOdemeLog_v2 (
        log_id INT IDENTITY(1,1) PRIMARY KEY,
        masa_id INT NOT NULL,
        masa_adi NVARCHAR(100),
        odeme_tarihi DATETIME DEFAULT GETDATE(),
        mesaj NVARCHAR(255)
    );
END;

-- Masa Ödeme Log Trigger
GO
CREATE TRIGGER trg_MasaOdemeLog_v2
ON masa
AFTER DELETE
AS
BEGIN
    DECLARE @masa_id INT, @masa_adi NVARCHAR(100);

    SELECT @masa_id = id, @masa_adi = adi FROM DELETED;

    INSERT INTO MasaOdemeLog_v2 (masa_id, masa_adi, mesaj)
    VALUES (
        @masa_id, 
        @masa_adi, 
        CONCAT(@masa_adi, ' masası ödendi.')
    );
END;
GO

-- Menü ve Stok Adedi Birleştirme View
GO
CREATE VIEW menuvestokadedibirlestirme_v2
AS
SELECT 
    m.id,
    m.fotograf,
    m.adi,
    m.fiyati,
    m.kategorisi,
    s.stok
FROM [menü] m
LEFT JOIN stokAded s ON m.id = s.id;
GO

-- Yemekler kategorisine göre listeleme fonksiyonu
GO
CREATE FUNCTION dbo.GetMenuByCategory_v2
(
    @kategorisi NVARCHAR(255)
)
RETURNS TABLE
AS
RETURN
(
    SELECT * 
    FROM dbo.menü 
    WHERE kategorisi = @kategorisi
);
GO

-- Tüm menüyü listeleme fonksiyonu
GO
CREATE FUNCTION dbo.GetAllMenus_v2()
RETURNS TABLE
AS
RETURN

    SELECT 
    m.id,
    m.fotograf,
    m.adi,
    m.fiyati,
    m.kategorisi,
    s.stok
FROM [menü] m
LEFT JOIN stokAded s ON m.id = s.id;
go


-- Kullanıcı adıyla kullanıcı bilgilerini getirme fonksiyonu
GO
CREATE FUNCTION dbo.GetUserByUsername_v2
(
    @kullanici_adi NVARCHAR(255)
)
RETURNS TABLE
AS
RETURN
(
    SELECT * 
    FROM dbo.kullaniciler
    WHERE kullanici_adi = @kullanici_adi
);
GO
