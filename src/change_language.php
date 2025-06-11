<?php
session_start();
if (isset($_POST['language']) && in_array($_POST['language'], ['en', 'al'])) {
    $_SESSION['lang'] = $_POST['language'];
}
header("Location: account.php");
exit();
