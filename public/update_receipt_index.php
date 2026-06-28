<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getConnection();
    
    // Check if the index exists before dropping it
    $stmt = $db->query("SHOW INDEX FROM payments WHERE Key_name = 'receipt_number'");
    if ($stmt->rowCount() > 0) {
        $db->exec("ALTER TABLE payments DROP INDEX receipt_number");
        echo "Índice UNIQUE 'receipt_number' eliminado correctamente.\n";
    } else {
        echo "El índice 'receipt_number' no existe o ya fue eliminado.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
