<?php
// debug.php - Endpoint de depuración
// Este archivo mostrará información de la petición que llega al servidor.

header('Content-Type: application/json; charset=utf-8');

// Recopilar información de la solicitud
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$queryParams = $_GET;
$postParams = $_POST;
$rawInput = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

// Armar la respuesta de depuración
$response = [
    'method'        => $method,
    'uri'           => $uri,
    'query_params'  => $queryParams,
    'post_params'   => $postParams,
    'raw_input'     => $rawInput,
    'headers'       => $headers,
];

// Imprimir la respuesta en formato JSON
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
