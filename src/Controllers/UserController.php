<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class UserController
{
    /**
     * Crear un comercio nuevo (POST /admin/comercios/crear).
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $required = ['client_code', 'business_name', 'cuit', 'address', 'email', 'password'];
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $_SESSION['flash_error'] = "El campo {$field} es obligatorio.";
                $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
                return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
            }
        }

        // Verificar duplicados
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR cuit = :cuit OR client_code = :code");
        $stmt->execute([':email' => $data['email'], ':cuit' => $data['cuit'], ':code' => $data['client_code']]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Ya existe un comercio con ese email, CUIT o código de cliente.';
            $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $stmt = $db->prepare("
            INSERT INTO users (client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id)
            VALUES (:code, :name, :cuit, :addr, :phone, :email, :pass, :base_rate, 3)
        ");
        $stmt->execute([
            ':code'      => trim($data['client_code']),
            ':name'      => trim($data['business_name']),
            ':cuit'      => trim($data['cuit']),
            ':addr'      => trim($data['address']),
            ':phone'     => trim($data['phone'] ?? ''),
            ':email'     => trim($data['email']),
            ':pass'      => password_hash($data['password'], PASSWORD_DEFAULT),
            ':base_rate' => floatval($data['base_rate'] ?? 0.00),
        ]);

        $newUserId = (int) $db->lastInsertId();

        // Crear notificación de bienvenida
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");
        $stmt->execute([':uid' => $newUserId]);

        // Auditoría
        $adminId = $request->getAttribute('user_id');
        $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address) VALUES (:uid, 'user.create', 'user', :eid, :ip)");
        $stmt->execute([':uid' => $adminId, ':eid' => $newUserId, ':ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = 'Comercio creado exitosamente.';
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }

    /**
     * Actualizar un comercio (POST /admin/comercios/editar/{id}).
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE users SET 
                client_code   = :code,
                business_name = :name,
                cuit          = :cuit,
                address       = :addr,
                phone         = :phone,
                email         = :email,
                is_active     = :active,
                base_rate     = :base_rate
            WHERE id = :id AND role_id = 3
        ");
        $stmt->execute([
            ':code'      => trim($data['client_code']),
            ':name'      => trim($data['business_name']),
            ':cuit'      => trim($data['cuit']),
            ':addr'      => trim($data['address']),
            ':phone'     => trim($data['phone'] ?? ''),
            ':email'     => trim($data['email']),
            ':active'    => isset($data['is_active']) ? 1 : 0,
            ':base_rate' => floatval($data['base_rate'] ?? 0.00),
            ':id'        => $id,
        ]);

        // Actualizar password si se proporcionó
        if (!empty($data['password'])) {
            $stmt = $db->prepare("UPDATE users SET password_hash = :pass WHERE id = :id");
            $stmt->execute([':pass' => password_hash($data['password'], PASSWORD_DEFAULT), ':id' => $id]);
        }

        $_SESSION['flash_success'] = 'Comercio actualizado exitosamente.';
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }

    /**
     * Eliminar (desactivar) un comercio (POST /admin/comercios/eliminar/{id}).
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $db = Database::getConnection();

        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role_id = 3");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash_success'] = 'Comercio desactivado exitosamente.';
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }

    /**
     * Importar comercios desde CSV (POST /admin/comercios/importar).
     */
    public function importCsv(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $db = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';

        if (empty($uploadedFiles['csv_file']) || $uploadedFiles['csv_file']->getError() !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Debe seleccionar un archivo CSV válido.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $csvFile = $uploadedFiles['csv_file'];
        $stream = $csvFile->getStream();
        $filePath = tempnam(sys_get_temp_dir(), 'csv_import');
        file_put_contents($filePath, $stream->getContents());

        $file = fopen($filePath, 'r');
        if (!$file) {
            $_SESSION['flash_error'] = 'No se pudo abrir el archivo subido.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        // Leer cabecera
        $header = fgetcsv($file, 1000, ';'); // Excel en español usa ';'
        if ($header === false) {
            fclose($file);
            unlink($filePath);
            $_SESSION['flash_error'] = 'El archivo CSV está vacío.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        // Si la cabecera no tiene ';' y solo tiene 1 elemento largo, probar con ','
        if (count($header) === 1 && strpos($header[0], ',') !== false) {
            rewind($file);
            $header = fgetcsv($file, 1000, ',');
        }

        // Normalizar cabecera a minúsculas y limpiar caracteres raros
        $header = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h)));
        }, $header);

        // Mapeo esperado de columnas: codigo, razon_social, cuit, domicilio, telefono, email, tasa_base
        $indices = [
            'code'  => array_search('codigo', $header),
            'name'  => array_search('razon_social', $header),
            'cuit'  => array_search('cuit', $header),
            'addr'  => array_search('domicilio', $header),
            'phone' => array_search('telefono', $header),
            'email' => array_search('email', $header),
            'rate'  => array_search('tasa_base', $header),
        ];

        // Validaciones básicas de columnas requeridas
        if ($indices['code'] === false || $indices['name'] === false || $indices['cuit'] === false || $indices['email'] === false) {
            fclose($file);
            unlink($filePath);
            $_SESSION['flash_error'] = 'El CSV debe contener al menos las columnas: codigo, razon_social, cuit, email.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $imported = 0;
        $errors = [];
        $lineNum = 1;

        try {
            $db->beginTransaction();

            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email OR cuit = :cuit OR client_code = :code");
            $stmtInsert = $db->prepare("
                INSERT INTO users (client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id)
                VALUES (:code, :name, :cuit, :addr, :phone, :email, :pass, :base_rate, 3)
            ");
            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");

            $delimiter = (count($header) > 1) ? ';' : ',';
            rewind($file);
            fgetcsv($file, 1000, $delimiter); // Saltar cabecera

            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                $lineNum++;
                if (count($row) < 4 || empty(trim($row[$indices['code']] ?? ''))) {
                    continue;
                }

                $code  = trim($row[$indices['code']]);
                $name  = trim($row[$indices['name']]);
                $cuit  = trim($row[$indices['cuit']]);
                $email = trim($row[$indices['email']]);
                $addr  = ($indices['addr'] !== false) ? trim($row[$indices['addr']]) : 'Domicilio Comercial';
                $phone = ($indices['phone'] !== false) ? trim($row[$indices['phone']]) : '';
                $rate  = ($indices['rate'] !== false) ? floatval(str_replace(',', '.', trim($row[$indices['rate']]))) : 0.00;

                // Validar si ya existe
                $stmtCheck->execute([':email' => $email, ':cuit' => $cuit, ':code' => $code]);
                if ($stmtCheck->fetch()) {
                    $errors[] = "Línea {$lineNum}: El comercio '{$name}' (CUIT: {$cuit}) ya existe o tiene datos duplicados.";
                    continue;
                }

                // Generar password_hash usando el CUIT sin guiones por defecto
                $cuitClean = str_replace('-', '', $cuit);
                $passHash = password_hash($cuitClean, PASSWORD_DEFAULT);

                // Insertar comercio
                $stmtInsert->execute([
                    ':code'      => $code,
                    ':name'      => $name,
                    ':cuit'      => $cuit,
                    ':addr'      => $addr,
                    ':phone'     => $phone,
                    ':email'     => $email,
                    ':pass'      => $passHash,
                    ':base_rate' => $rate,
                ]);

                $newId = (int)$db->lastInsertId();

                // Crear notificación
                $stmtNotif->execute([':uid' => $newId]);

                $imported++;
            }

            if ($imported === 0 && !empty($errors)) {
                throw new \Exception(implode("<br>", $errors));
            }

            $db->commit();
            
            $msg = "Se importaron con éxito {$imported} comercios.";
            if (!empty($errors)) {
                $msg .= "<br>Algunos registros omitidos por duplicación:<br>" . implode("<br>", $errors);
            }
            $_SESSION['flash_success'] = $msg;

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = "Error en importación: " . $e->getMessage();
        }

        fclose($file);
        unlink($filePath);

        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }
}
