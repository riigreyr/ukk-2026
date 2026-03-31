<?php
/*
buat ganti user (siswa)
*/ 
require_once '../config.php';
unset($_SESSION['nisn'], $_SESSION['nama'], $_SESSION['kelas']);
redirect('../index.php');
?>