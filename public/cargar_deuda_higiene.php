<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== CARGAR DEUDA HISTÓRICA: TASA HIGIENE Y PROFILAXIS ===\n\n";
echo "Crea una factura 'pendiente' por cada período adeudado del padrón anterior,\n";
echo "usando el monto base (sin interés: el interés lo calcula el sistema solo,\n";
echo "con la fórmula de esta tasa — 8% mensual fijo — la primera vez que se abra\n";
echo "Facturación o el Dashboard).\n\n";

// Período (mes/año), fecha de vencimiento y monto base de cada boleta adeudada,
// tal como figuran en el padrón histórico de deudores. El registro identifica
// al comercio (columna legacy_registro, cargada al importar el padrón de comercios).
$deudas = [
    ['registro' => '000065', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 455.0],
    ['registro' => '000065', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 455.0],
    ['registro' => '000065', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2023-03', 'due_date' => '2023-07-12', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 682.5],
    ['registro' => '000065', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 6000.0],
    ['registro' => '000065', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 6000.0],
    ['registro' => '000065', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 12000.0],
    ['registro' => '000080', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 3001.0],
    ['registro' => '000080', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000080', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000080', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3001.0],
    ['registro' => '000080', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 3001.0],
    ['registro' => '000080', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3000.0],
    ['registro' => '000080', 'period' => '2026-01', 'due_date' => '2026-03-13', 'subtotal' => 4000.0],
    ['registro' => '000044', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 1300.0],
    ['registro' => '000058', 'period' => '2022-03', 'due_date' => '2022-07-15', 'subtotal' => 455.0],
    ['registro' => '000058', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 455.0],
    ['registro' => '000058', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 455.0],
    ['registro' => '000058', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 455.0],
    ['registro' => '000058', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2023-03', 'due_date' => '2023-07-12', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 682.5],
    ['registro' => '000058', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2024-03', 'due_date' => '2024-07-12', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2024-04', 'due_date' => '2024-09-13', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000058', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000087', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 1950.0],
    ['registro' => '000087', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2024-03', 'due_date' => '2024-07-12', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2024-04', 'due_date' => '2024-09-13', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 5000.0],
    ['registro' => '000087', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 10000.0],
    ['registro' => '000079', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3000.0],
    ['registro' => '000079', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 3000.0],
    ['registro' => '000079', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3000.0],
    ['registro' => '000079', 'period' => '2026-01', 'due_date' => '2026-03-13', 'subtotal' => 4000.0],
    ['registro' => '000079', 'period' => '2026-02', 'due_date' => '2026-05-08', 'subtotal' => 4000.0],
    ['registro' => '000008', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 1040.0],
    ['registro' => '000076', 'period' => '2023-03', 'due_date' => '2023-07-12', 'subtotal' => 1365.0],
    ['registro' => '000095', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 3001.0],
    ['registro' => '000095', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000095', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000095', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3001.0],
    ['registro' => '000056', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000056', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3001.0],
    ['registro' => '000090', 'period' => '2024-04', 'due_date' => '2024-09-13', 'subtotal' => 1500.5],
    ['registro' => '000090', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000090', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000090', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 3001.0],
    ['registro' => '000090', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000090', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000037', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 6500.0],
    ['registro' => '000037', 'period' => '2022-03', 'due_date' => '2022-07-15', 'subtotal' => 6500.0],
    ['registro' => '000037', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 6500.0],
    ['registro' => '000037', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 6500.0],
    ['registro' => '000037', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 6500.0],
    ['registro' => '000037', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2023-03', 'due_date' => '2023-07-12', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 9750.0],
    ['registro' => '000037', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2024-03', 'due_date' => '2024-07-12', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2024-04', 'due_date' => '2024-09-13', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 19500.0],
    ['registro' => '000037', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 39000.0],
    ['registro' => '000037', 'period' => '2026-01', 'due_date' => '2026-03-13', 'subtotal' => 50000.0],
    ['registro' => '000067', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 2600.0],
    ['registro' => '000067', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 2600.0],
    ['registro' => '000067', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 3900.0],
    ['registro' => '000063', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 14125.69],
    ['registro' => '000063', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 14125.69],
    ['registro' => '000063', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 14125.69],
    ['registro' => '000063', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 23349.94],
    ['registro' => '000043', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 910.0],
    ['registro' => '000043', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 910.0],
    ['registro' => '000043', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 910.0],
    ['registro' => '000043', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1365.0],
    ['registro' => '000072', 'period' => '2026-03', 'due_date' => '2026-07-10', 'subtotal' => 30000.0],
    ['registro' => '000055', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 1300.0],
    ['registro' => '000055', 'period' => '2022-03', 'due_date' => '2022-07-15', 'subtotal' => 1300.0],
    ['registro' => '000055', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 1300.0],
    ['registro' => '000055', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 1300.0],
    ['registro' => '000055', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 1300.0],
    ['registro' => '000055', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1950.0],
    ['registro' => '000055', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 5000.0],
    ['registro' => '000055', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 5000.0],
    ['registro' => '000055', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 10000.0],
    ['registro' => '000091', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000091', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 3001.0],
    ['registro' => '000091', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000091', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000098', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000098', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000097', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000097', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000097', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000003', 'period' => '2021-01', 'due_date' => '2021-03-31', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2021-02', 'due_date' => '2021-05-30', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2021-03', 'due_date' => '2021-07-30', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2021-04', 'due_date' => '2021-09-25', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2021-05', 'due_date' => '2021-11-28', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2021-06', 'due_date' => '2022-01-25', 'subtotal' => 2000.0],
    ['registro' => '000003', 'period' => '2022-01', 'due_date' => '2022-03-10', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2022-03', 'due_date' => '2022-07-15', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 2600.0],
    ['registro' => '000003', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 3900.0],
    ['registro' => '000003', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 3900.0],
    ['registro' => '000060', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 1300.0],
    ['registro' => '000060', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 1300.0],
    ['registro' => '000060', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1950.0],
    ['registro' => '000048', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 1950.0],
    ['registro' => '000018', 'period' => '2026-03', 'due_date' => '2026-07-10', 'subtotal' => 7500.0],
    ['registro' => '000064', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3000.0],
    ['registro' => '000074', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 3000.0],
    ['registro' => '000088', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 11001.0],
    ['registro' => '000088', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 11000.0],
    ['registro' => '000088', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 11000.0],
    ['registro' => '000088', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 11000.0],
    ['registro' => '000069', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 682.5],
    ['registro' => '000094', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000094', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000094', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000075', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 9200.0],
    ['registro' => '000075', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 9200.0],
    ['registro' => '000075', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 18400.0],
    ['registro' => '000038', 'period' => '2021-01', 'due_date' => '2021-03-31', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2021-02', 'due_date' => '2021-05-30', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2021-03', 'due_date' => '2021-07-30', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2021-04', 'due_date' => '2021-09-25', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2021-05', 'due_date' => '2021-11-28', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2021-06', 'due_date' => '2022-01-25', 'subtotal' => 2000.0],
    ['registro' => '000038', 'period' => '2022-01', 'due_date' => '2022-03-10', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2022-03', 'due_date' => '2022-07-15', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 2600.0],
    ['registro' => '000038', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 3900.0],
    ['registro' => '000038', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 3900.0],
    ['registro' => '000038', 'period' => '2023-03', 'due_date' => '2023-07-12', 'subtotal' => 3900.0],
    ['registro' => '000038', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 3900.0],
    ['registro' => '000038', 'period' => '2024-03', 'due_date' => '2024-07-12', 'subtotal' => 11000.0],
    ['registro' => '000038', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 11000.0],
    ['registro' => '000038', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 11000.0],
    ['registro' => '000038', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 22000.0],
    ['registro' => '000086', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 2300.0],
    ['registro' => '000066', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 910.0],
    ['registro' => '000066', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 910.0],
    ['registro' => '000066', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1365.0],
    ['registro' => '000106', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3001.0],
    ['registro' => '000028', 'period' => '2021-01', 'due_date' => '2021-03-31', 'subtotal' => 700.0],
    ['registro' => '000028', 'period' => '2021-02', 'due_date' => '2021-05-30', 'subtotal' => 700.0],
    ['registro' => '000028', 'period' => '2021-05', 'due_date' => '2021-11-28', 'subtotal' => 700.0],
    ['registro' => '000028', 'period' => '2021-06', 'due_date' => '2022-01-25', 'subtotal' => 700.0],
    ['registro' => '000049', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 455.0],
    ['registro' => '000049', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 455.0],
    ['registro' => '000049', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 682.5],
    ['registro' => '000049', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000049', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000049', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000083', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 975.0],
    ['registro' => '000083', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3000.0],
    ['registro' => '000083', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3000.0],
    ['registro' => '000083', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 3000.0],
    ['registro' => '000083', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 4600.0],
    ['registro' => '000083', 'period' => '2026-03', 'due_date' => '2026-07-10', 'subtotal' => 8000.0],
    ['registro' => '000053', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 3001.0],
    ['registro' => '000053', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3001.0],
    ['registro' => '000034', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 780.0],
    ['registro' => '000031', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 3000.0],
    ['registro' => '000019', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 1365.0],
    ['registro' => '000019', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 1365.0],
    ['registro' => '000019', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 4000.0],
    ['registro' => '000085', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000085', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000085', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000054', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 650.0],
    ['registro' => '000054', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 650.0],
    ['registro' => '000054', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 650.0],
    ['registro' => '000054', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 975.0],
    ['registro' => '000054', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 3000.0],
    ['registro' => '000054', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 3000.0],
    ['registro' => '000054', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 6000.0],
    ['registro' => '000023', 'period' => '2021-01', 'due_date' => '2021-03-31', 'subtotal' => 800.0],
    ['registro' => '000023', 'period' => '2021-02', 'due_date' => '2021-05-30', 'subtotal' => 800.0],
    ['registro' => '000023', 'period' => '2021-05', 'due_date' => '2021-11-28', 'subtotal' => 800.0],
    ['registro' => '000023', 'period' => '2021-06', 'due_date' => '2022-01-25', 'subtotal' => 800.0],
    ['registro' => '000023', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 1040.0],
    ['registro' => '000023', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 1040.0],
    ['registro' => '000023', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1560.0],
    ['registro' => '000046', 'period' => '2021-02', 'due_date' => '2021-05-30', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2021-03', 'due_date' => '2021-07-30', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2021-04', 'due_date' => '2021-09-25', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2021-05', 'due_date' => '2021-11-28', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2021-06', 'due_date' => '2022-01-25', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2022-01', 'due_date' => '2022-03-10', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2022-02', 'due_date' => '2022-05-13', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 1500.0],
    ['registro' => '000046', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1950.0],
    ['registro' => '000046', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1950.0],
    ['registro' => '000046', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1950.0],
    ['registro' => '000046', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3900.0],
    ['registro' => '000041', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 780.0],
    ['registro' => '000041', 'period' => '2022-06', 'due_date' => '2023-02-10', 'subtotal' => 780.0],
    ['registro' => '000041', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 1170.0],
    ['registro' => '000041', 'period' => '2023-02', 'due_date' => '2023-05-12', 'subtotal' => 1170.0],
    ['registro' => '000041', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 1170.0],
    ['registro' => '000041', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 4000.0],
    ['registro' => '000041', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 4000.0],
    ['registro' => '000041', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 4000.0],
    ['registro' => '000041', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 4000.0],
    ['registro' => '000041', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 8000.0],
    ['registro' => '000101', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2026-01', 'due_date' => '2026-03-13', 'subtotal' => 10000.0],
    ['registro' => '000101', 'period' => '2026-02', 'due_date' => '2026-05-08', 'subtotal' => 10000.0],
    ['registro' => '000077', 'period' => '2023-06', 'due_date' => '2024-02-10', 'subtotal' => 682.5],
    ['registro' => '000077', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2024-02', 'due_date' => '2024-05-10', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2024-03', 'due_date' => '2024-07-12', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2024-04', 'due_date' => '2024-09-13', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2024-05', 'due_date' => '2024-11-08', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 1500.5],
    ['registro' => '000077', 'period' => '2025-01', 'due_date' => '2025-03-14', 'subtotal' => 3001.0],
    ['registro' => '000077', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000077', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3001.0],
    ['registro' => '000077', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3001.0],
    ['registro' => '000071', 'period' => '2023-04', 'due_date' => '2023-09-13', 'subtotal' => 682.5],
    ['registro' => '000071', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 1500.5],
    ['registro' => '000071', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000082', 'period' => '2025-02', 'due_date' => '2025-05-09', 'subtotal' => 3001.0],
    ['registro' => '000082', 'period' => '2025-03', 'due_date' => '2025-07-11', 'subtotal' => 3000.0],
    ['registro' => '000082', 'period' => '2025-04', 'due_date' => '2025-09-12', 'subtotal' => 3000.0],
    ['registro' => '000082', 'period' => '2025-05', 'due_date' => '2025-11-14', 'subtotal' => 3000.0],
    ['registro' => '000082', 'period' => '2025-06', 'due_date' => '2026-02-14', 'subtotal' => 3000.0],
    ['registro' => '000082', 'period' => '2026-01', 'due_date' => '2026-03-13', 'subtotal' => 4000.0],
    ['registro' => '000050', 'period' => '2024-06', 'due_date' => '2025-02-14', 'subtotal' => 4000.0],
    ['registro' => '000050', 'period' => '2026-02', 'due_date' => '2026-05-08', 'subtotal' => 8000.0],
    ['registro' => '000050', 'period' => '2026-03', 'due_date' => '2026-07-10', 'subtotal' => 8000.0],
    ['registro' => '000015', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 2925.0],
    ['registro' => '000068', 'period' => '2022-04', 'due_date' => '2022-09-09', 'subtotal' => 3510.0],
    ['registro' => '000068', 'period' => '2022-05', 'due_date' => '2022-11-11', 'subtotal' => 3510.0],
    ['registro' => '000068', 'period' => '2023-01', 'due_date' => '2023-03-10', 'subtotal' => 5265.0],
    ['registro' => '000029', 'period' => '2023-05', 'due_date' => '2023-11-10', 'subtotal' => 1365.0],
    ['registro' => '000052', 'period' => '2024-01', 'due_date' => '2024-03-11', 'subtotal' => 5000.0],
];

try {
    $db = \App\Config\Database::getConnection();

    $stmtUser = $db->prepare("SELECT id FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtDup  = $db->prepare("SELECT id FROM invoices WHERE invoice_number = :num");
    $stmtInsertInvoice = $db->prepare("
        INSERT INTO invoices (user_id, invoice_number, period, issue_date, due_date, subtotal, surcharge, total_amount, status, tax_type)
        VALUES (:uid, :num, :period, :issue, :due, :sub, 0.00, :sub, 'pending', 'higiene_profilaxis')
    ");
    $stmtInsertItem = $db->prepare("
        INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
        VALUES (:iid, :desc, 1, :price, :price)
    ");

    $creadas = 0;
    $yaExistian = 0;
    $sinComercio = [];

    $db->beginTransaction();

    foreach ($deudas as $d) {
        $invoiceNumber = 'HP-' . str_replace('-', '', $d['period']) . '-' . $d['registro'];

        $stmtDup->execute([':num' => $invoiceNumber]);
        if ($stmtDup->fetch()) {
            $yaExistian++;
            continue;
        }

        $stmtUser->execute([':reg' => $d['registro']]);
        $user = $stmtUser->fetch();
        if (!$user) {
            $sinComercio[] = $d['registro'] . ' (período ' . $d['period'] . ')';
            continue;
        }

        $stmtInsertInvoice->execute([
            ':uid'    => $user['id'],
            ':num'    => $invoiceNumber,
            ':period' => $d['period'],
            ':issue'  => $d['due_date'],
            ':due'    => $d['due_date'],
            ':sub'    => $d['subtotal'],
        ]);
        $invoiceId = (int) $db->lastInsertId();

        $stmtInsertItem->execute([
            ':iid'   => $invoiceId,
            ':desc'  => 'Tasa Higiene y Profilaxis - ' . $d['period'],
            ':price' => $d['subtotal'],
        ]);

        $creadas++;
    }

    if (!empty($sinComercio)) {
        echo "⚠ No se pudo asociar a un comercio (legacy_registro no encontrado) — se omitieron:\n";
        foreach ($sinComercio as $s) {
            echo "  - Registro {$s}\n";
        }
        echo "\n";
    }

    $auditStmt = $db->prepare("
        INSERT INTO audit_log (action, entity_type, details)
        VALUES ('invoice.bulk_import_legacy', 'invoice', :details)
    ");
    $auditStmt->execute([
        ':details' => json_encode(['creadas' => $creadas, 'ya_existian' => $yaExistian, 'sin_comercio' => count($sinComercio)]),
    ]);

    $db->commit();

    echo "Facturas nuevas creadas: {$creadas}\n";
    echo "Ya existían (se omitieron, script idempotente): {$yaExistian}\n";
    echo "Sin comercio para asociar: " . count($sinComercio) . "\n";
    echo "\n=== LISTO ===\n";
    echo "Entrá a Facturación o al Dashboard una vez para que el sistema calcule\n";
    echo "la mora de estas boletas con la fórmula de Higiene y Profilaxis (8% mensual).\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
