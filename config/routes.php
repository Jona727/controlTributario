<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\InvoiceController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;

/** @var App $app */

// ─── Rutas públicas ───
$app->get('/login', [AuthController::class, 'showLogin']);
$app->post('/login', [AuthController::class, 'login']);
$app->get('/logout', [AuthController::class, 'logout']);

// Redirigir raíz al login
$app->get('/', function ($request, $response) {
    $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
    return $response->withHeader('Location', $basePath . '/login')->withStatus(302);
});

// ─── Rutas protegidas: Admin ───
$app->group('/admin', function ($group) {
    $group->get('/dashboard', [AdminController::class, 'dashboard']);
    $group->get('/comercios', [AdminController::class, 'comercios']);
    $group->get('/facturas',  [AdminController::class, 'facturas']);

    // CRUD Comercios
    $group->post('/comercios/crear',          [UserController::class, 'store']);
    $group->post('/comercios/editar/{id}',    [UserController::class, 'update']);
    $group->post('/comercios/eliminar/{id}',  [UserController::class, 'delete']);
    $group->post('/comercios/importar',       [UserController::class, 'importCsv']);

    // CRUD Facturas
    $group->post('/facturas/crear',           [InvoiceController::class, 'store']);
    $group->post('/facturas/estado/{id}',     [InvoiceController::class, 'updateStatus']);
    $group->get('/facturas/pdf/{id}',         [InvoiceController::class, 'downloadPdf']);
    $group->post('/facturas/pagar/{id}',      [InvoiceController::class, 'payInVentanilla']);
    $group->post('/facturas/pagar-lote',      [InvoiceController::class, 'payBulkInVentanilla']);
    $group->post('/facturas/revertir/{id}',   [InvoiceController::class, 'revertPayment']);
    $group->get('/facturas/recibo/{id}',      [InvoiceController::class, 'downloadReceiptPdf']);
    $group->post('/facturas/generar-lote',    [InvoiceController::class, 'generateBatchInvoices']);
    
    // Deuda & Indicadores
    $group->get('/deuda',                     [AdminController::class, 'deuda']);
    $group->get('/cierre-caja',               [AdminController::class, 'cierreCaja']);
    $group->get('/cierre-caja/pdf',           [InvoiceController::class, 'downloadCierreCajaPdf']);
})
->add(new RoleMiddleware(['admin', 'super']))
->add(new JwtMiddleware());

// ─── Rutas protegidas: Usuario/Comercio ───
$app->group('/user', function ($group) {
    $group->get('/dashboard', [DashboardController::class, 'index']);
    $group->get('/facturas/pdf/{id}',         [InvoiceController::class, 'downloadPdf']);
    $group->get('/facturas/recibo/{id}',      [InvoiceController::class, 'downloadReceiptPdf']);
})
->add(new JwtMiddleware());
