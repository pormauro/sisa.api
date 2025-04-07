<?php
// public_html/activate.php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Models\User;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];
$user = new User();
$result = $user->activateUser($token);

if ($result) {
    echo "Cuenta activada exitosamente. Ya puedes iniciar sesión.";
} else {
    echo "El token es inválido o la cuenta ya está activada.";
}
