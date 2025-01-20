<?php
// Oturum başlat
session_start();

// Oturumu sonlandır
session_unset();
session_destroy();

// Login sayfasına yönlendir
header("Location: login.html");
exit();
