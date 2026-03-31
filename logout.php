<?php
require_once 'config.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    unset($_SESSION['role'], $_SESSION['username']);
    redirect('login.php');
} else {
    unset($_SESSION['nisn'], $_SESSION['nama'], $_SESSION['kelas']);
    redirect('index.php');
}
?>