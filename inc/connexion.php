<?php
session_start();
ini_set("display_errors", "1");

function dbconnect()
{
    static $connect = null;

    if ($connect === null) {
        $connect = mysqli_connect('localhost', 'ETU4087', '5Eh4iq1O', 'db_s2_ETU004087');

        if (!$connect) {
            die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
        }
        mysqli_set_charset($connect, 'utf8mb4');
    }

    return $connect;
}

// Fonction anilay utlisateur hoe connécter v 
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction anilay mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction  mivérifier anilay mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>