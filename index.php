<?php

// public/index.php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

// Cargar variables de entorno
$dotenv = Dotenv::createMutable(__DIR__); // Busca .env
$dotenv->load();

// Crear la instancia de la aplicación Slim
$app = AppFactory::create();

// Habilitar el middleware de errores (para desarrollo)
$app->addErrorMiddleware(true, true, true);

// >>> AÑADIMOS este middleware de logging <<<
$app->add(new \App\Middleware\ActivityLogMiddleware());

// Establecer cabeceras para la respuesta JSON (opcional, si quieres global)
header('Content-Type: application/json; charset=utf-8');

// Incluir rutas
require_once __DIR__ . '/src/Routes/api.php';

// Ejecutar la app
$app->run();
