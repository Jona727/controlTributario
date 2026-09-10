<?php
/**
 * Sistema de Control Tributario Municipal
 * Bootstrap / Front Controller
 */

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Iniciar sesión para flash messages
session_start();

// Crear instancia de Slim
$app = AppFactory::create();

// Base path (ajustar si el proyecto no está en la raíz del dominio)
$basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
$app->setBasePath($basePath);

// Middleware de errores
$app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Middleware para parsear body
$app->addBodyParsingMiddleware();

// Registrar rutas
require __DIR__ . '/../config/routes.php';

// Ejecutar
$app->run();
