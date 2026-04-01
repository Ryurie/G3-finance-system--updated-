<?php
session_start();
// Burahin lahat ng session data
session_unset();
session_destroy();

// Ibalik sa login page
header("Location: login.php");
exit();
?>