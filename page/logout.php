<?php
require_once '../inc/connexion.php';

// mamotika anilay session 
session_destroy();

// mamerina any @ ilay page de login 
header('Location: login.php');
exit();
?>
