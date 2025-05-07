<?php
session_start();
session_unset();     // Clear session data
session_destroy();   // Fully destroy the session
header("Location: login.php"); // Redirect to login
exit();
