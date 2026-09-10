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

        $required = ['business_name', 'cuit', 'address', 'email', 'password'];
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $_SESSION['flash_error'] = "El campo {$field} es obligatorio.";
                $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
                return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
            }
        }

        // Verificar duplicados
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR cuit = :cuit");
        $stmt->execute([':email' => $data['email'], ':cuit' => $data['cuit']]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Ya existe un comercio con ese email o CUIT.';
            $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $clientCode = self::generateNextClientCode($db);

        $stmt = $db->prepare("
            INSERT INTO users (client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id)
            VALUES (:code, :name, :cuit, :addr, :phone, :email, :pass, :base_rate, 3)
        ");
        $stmt->execute([
            ':code'      => $clientCode,
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

        $_SESSION['flash_success'] = "Comercio creado exitosamente. Código asignado: {$clientCode}.";
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }

    /**
     * Genera el próximo código de comercio disponible (COM-000001, COM-000002, ...),
     * a partir del mayor número ya usado. El código de comercio funciona como un
     * identificador fijo: se asigna una sola vez acá y nunca se vuelve a editar.
     */
    private static function generateNextClientCode(\PDO $db): string
    {
        $stmt = $db->query("SELECT client_code FROM users WHERE client_code REGEXP '^COM-[0-9]+$'");
        $max = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $code) {
            $num = (int) substr($code, 4);
            if ($num > $max) {
                $max = $num;
            }
        }

        $stmtCheck = $db->prepare("SELECT id FROM users WHERE client_code = :code");
        do {
            $max++;
            $candidate = 'COM-' . str_pad((string) $max, 6, '0', STR_PAD_LEFT);
            $stmtCheck->execute([':code' => $candidate]);
        } while ($stmtCheck->fetch());

        return $candidate;
    }

    /**
     * Actualizar un comercio (POST /admin/comercios/editar/{id}).
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $ownerName = trim($data['owner_name'] ?? '');
        $rubroCode = trim($data['rubro_code'] ?? '');

        // El rubro se elige de la tabla tarifas (fuente de verdad de la
        // ordenanza) — de ahí sale también el nombre que se guarda en
        // activity_category, para que quede consistente con el código.
        $activityCategory = null;
        if ($rubroCode !== '') {
            $stmtTarifa = $db->prepare("SELECT rubro FROM tarifas WHERE codigo = :codigo");
            $stmtTarifa->execute([':codigo' => $rubroCode]);
            $tarifa = $stmtTarifa->fetch();
            if (!$tarifa) {
                $_SESSION['flash_error'] = 'El rubro seleccionado no existe en el tarifario.';
                $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
                return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
            }
            $activityCategory = $tarifa['rubro'];
        } else {
            $rubroCode = null;
        }

        $stmt = $db->prepare("
            UPDATE users SET
                business_name     = :name,
                cuit              = :cuit,
                address           = :addr,
                phone             = :phone,
                email             = :email,
                is_active         = :active,
                base_rate         = :base_rate,
                owner_name        = :owner,
                rubro_code        = :rubro_code,
                activity_category = :rubro,
                needs_data_review = :needs_review,
                data_review_reason = CASE WHEN :needs_review2 = 0 THEN NULL ELSE data_review_reason END
            WHERE id = :id AND role_id = 3
        ");
        $needsReview = isset($data['needs_data_review']) ? 1 : 0;
        $stmt->execute([
            ':name'          => trim($data['business_name']),
            ':cuit'          => trim($data['cuit']),
            ':addr'          => trim($data['address']),
            ':phone'         => trim($data['phone'] ?? ''),
            ':email'         => trim($data['email']),
            ':active'        => isset($data['is_active']) ? 1 : 0,
            ':base_rate'     => floatval($data['base_rate'] ?? 0.00),
            ':owner'         => $ownerName !== '' ? $ownerName : null,
            ':rubro_code'    => $rubroCode,
            ':rubro'         => $activityCategory,
            ':needs_review'  => $needsReview,
            ':needs_review2' => $needsReview,
            ':id'            => $id,
        ]);

        // Actualizar password si se proporcionó
        $passwordChanged = false;
        if (!empty($data['password'])) {
            $stmt = $db->prepare("UPDATE users SET password_hash = :pass WHERE id = :id");
            $stmt->execute([':pass' => password_hash($data['password'], PASSWORD_DEFAULT), ':id' => $id]);
            $passwordChanged = true;
        }

        $adminId = $request->getAttribute('user_id');
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (:uid, 'user.update', 'user', :eid, :details, :ip)
        ");
        $auditStmt->execute([
            ':uid'     => $adminId,
            ':eid'     => $id,
            ':details' => json_encode(['rubro_code' => $rubroCode, 'is_active' => isset($data['is_active']) ? 1 : 0, 'password_changed' => $passwordChanged]),
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

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

        $adminId = $request->getAttribute('user_id');
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address)
            VALUES (:uid, 'user.deactivate', 'user', :eid, :ip)
        ");
        $auditStmt->execute([
            ':uid' => $adminId,
            ':eid' => $id,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

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
        // Columnas opcionales para padrones migrados de otros sistemas: titular, rubro, fecha_inicio, activo
        $indices = [
            'code'    => array_search('codigo', $header),
            'name'    => array_search('razon_social', $header),
            'cuit'    => array_search('cuit', $header),
            'addr'    => array_search('domicilio', $header),
            'phone'   => array_search('telefono', $header),
            'email'   => array_search('email', $header),
            'rate'    => array_search('tasa_base', $header),
            'owner'   => array_search('titular', $header),
            'rubro'   => array_search('rubro', $header),
            'inicio'  => array_search('fecha_inicio', $header),
            'activo'  => array_search('activo', $header),
            'legacy'  => array_search('registro_legado', $header),
        ];

        // Validaciones básicas de columnas requeridas. El email ya no es obligatorio:
        // los padrones migrados de sistemas anteriores casi nunca lo tienen, así que
        // si falta se genera uno provisorio y el comercio queda marcado para revisión.
        if ($indices['code'] === false || $indices['name'] === false || $indices['cuit'] === false) {
            fclose($file);
            unlink($filePath);
            $_SESSION['flash_error'] = 'El CSV debe contener al menos las columnas: codigo, razon_social, cuit.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $imported = 0;
        $errors = [];
        $credenciales = [];
        $lineNum = 1;

        try {
            $db->beginTransaction();

            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email OR cuit = :cuit OR client_code = :code");
            $stmtInsert = $db->prepare("
                INSERT INTO users (
                    client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id,
                    is_active, owner_name, activity_category, activity_start_date,
                    needs_data_review, data_review_reason, legacy_registro
                )
                VALUES (
                    :code, :name, :cuit, :addr, :phone, :email, :pass, :base_rate, 3,
                    :active, :owner, :rubro, :inicio,
                    :needs_review, :review_reason, :legacy
                )
            ");
            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");

            $delimiter = (count($header) > 1) ? ';' : ',';
            rewind($file);
            fgetcsv($file, 1000, $delimiter); // Saltar cabecera

            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                $lineNum++;
                if (count($row) < 3 || empty(trim($row[$indices['code']] ?? ''))) {
                    continue;
                }

                $code  = trim($row[$indices['code']]);
                $name  = trim($row[$indices['name']]);
                $cuit  = trim($row[$indices['cuit']]);
                $email = ($indices['email'] !== false) ? trim($row[$indices['email']]) : '';
                $addr  = ($indices['addr'] !== false) ? trim($row[$indices['addr']]) : 'Domicilio Comercial';
                $phone = ($indices['phone'] !== false) ? trim($row[$indices['phone']]) : '';
                $rate  = ($indices['rate'] !== false) ? floatval(str_replace(',', '.', trim($row[$indices['rate']]))) : 0.00;
                $owner  = ($indices['owner'] !== false) ? trim($row[$indices['owner']]) : '';
                $rubro  = ($indices['rubro'] !== false) ? trim($row[$indices['rubro']]) : '';
                $inicio = ($indices['inicio'] !== false) ? trim($row[$indices['inicio']]) : '';
                $legacy = ($indices['legacy'] !== false) ? trim($row[$indices['legacy']]) : '';
                $activoRaw = ($indices['activo'] !== false) ? strtolower(trim($row[$indices['activo']])) : '1';
                $active = in_array($activoRaw, ['0', 'false', 'no', 'inactivo'], true) ? 0 : 1;

                // Fecha de inicio: solo se guarda si viene en formato reconocible.
                $inicioDate = null;
                if ($inicio !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $inicio)) {
                    $inicioDate = $inicio;
                } elseif ($inicio !== '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $inicio, $m)) {
                    $inicioDate = "{$m[3]}-{$m[2]}-{$m[1]}";
                }

                $reviewReasons = [];

                // Email: los padrones migrados casi nunca lo traen. Se genera uno
                // provisorio, obviamente falso, y se marca el comercio para revisión.
                if ($email === '') {
                    $email = 'sin-email.' . preg_replace('/[^a-z0-9]+/', '-', strtolower($code)) . '@controltributario.local';
                    $reviewReasons[] = 'Sin email de contacto (se generó uno provisorio)';
                }

                // CUIT: si falta o tiene un formato claramente incompleto, no se
                // rechaza el registro — se guarda lo que haya (o un placeholder
                // único basado en el código) y se marca para revisión.
                $cuitDigits = preg_replace('/\D/', '', $cuit);
                if ($cuitDigits === '' || $cuitDigits === '0') {
                    // Placeholder que entra en el VARCHAR(13) de la columna cuit,
                    // construido a partir del código (que ya es único) en vez de
                    // un valor fijo, para no chocar con la restricción UNIQUE.
                    $codeAlnum = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
                    $cuit = 'SC-' . substr($codeAlnum, -10);
                    $reviewReasons[] = 'Falta el CUIT';
                } elseif (strlen($cuitDigits) !== 11) {
                    $reviewReasons[] = 'CUIT con formato incompleto, verificar con el comercio';
                }

                $needsReview = !empty($reviewReasons) ? 1 : 0;
                $reviewReason = !empty($reviewReasons) ? implode('; ', $reviewReasons) : null;

                // Validar si ya existe
                $stmtCheck->execute([':email' => $email, ':cuit' => $cuit, ':code' => $code]);
                if ($stmtCheck->fetch()) {
                    $errors[] = "Línea {$lineNum}: El comercio '{$name}' (CUIT: {$cuit}) ya existe o tiene datos duplicados.";
                    continue;
                }

                // Generar una contraseña temporal aleatoria (no predecible: antes se
                // usaba el CUIT sin guiones, un dato prácticamente público).
                $tempPassword = self::generateTempPassword();
                $passHash = password_hash($tempPassword, PASSWORD_DEFAULT);

                // Insertar comercio
                $stmtInsert->execute([
                    ':code'          => $code,
                    ':name'          => $name,
                    ':cuit'          => $cuit,
                    ':addr'          => $addr,
                    ':phone'         => $phone,
                    ':email'         => $email,
                    ':pass'          => $passHash,
                    ':base_rate'     => $rate,
                    ':active'        => $active,
                    ':owner'         => $owner !== '' ? $owner : null,
                    ':rubro'         => $rubro !== '' ? $rubro : null,
                    ':inicio'        => $inicioDate,
                    ':needs_review'  => $needsReview,
                    ':review_reason' => $reviewReason,
                    ':legacy'        => $legacy !== '' ? $legacy : null,
                ]);

                $newId = (int)$db->lastInsertId();

                // Crear notificación
                $stmtNotif->execute([':uid' => $newId]);

                $credenciales[] = ['code' => $code, 'name' => $name, 'password' => $tempPassword];
                $imported++;
            }

            if ($imported === 0 && !empty($errors)) {
                throw new \Exception(implode("<br>", $errors));
            }

            $db->commit();
            
            $msg = "Se importaron con éxito {$imported} comercios. Anotá o copiá las contraseñas temporales de abajo antes de salir de esta pantalla: no se van a volver a mostrar.";
            if (!empty($errors)) {
                $msg .= "<br>Algunos registros omitidos por duplicación:<br>" . implode("<br>", $errors);
            }
            $_SESSION['flash_success'] = $msg;
            if (!empty($credenciales)) {
                $_SESSION['flash_credentials'] = $credenciales;
            }

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = "Error en importación: " . $e->getMessage();
        }

        fclose($file);
        unlink($filePath);

        return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
    }

    /**
     * Resetea la contraseña de TODOS los comercios activos a una nueva
     * aleatoria, y entrega el listado completo como CSV para descargar
     * (POST /admin/comercios/resetear-passwords). Requiere sesión de
     * admin — a diferencia de los scripts de una sola vez, esto queda
     * protegido por el login normal del panel.
     */
    public function resetAllPasswords(Request $request, Response $response): Response
    {
        $db = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';

        $stmt = $db->query("SELECT id, client_code, business_name, cuit FROM users WHERE role_id = 3 AND is_active = 1 ORDER BY client_code ASC");
        $comercios = $stmt->fetchAll();

        if (empty($comercios)) {
            $_SESSION['flash_error'] = 'No hay comercios activos para resetear.';
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        try {
            $db->beginTransaction();

            $stmtUpdate = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $filas = [];
            foreach ($comercios as $c) {
                $passwordNueva = self::generateTempPassword();
                $stmtUpdate->execute([
                    ':hash' => password_hash($passwordNueva, PASSWORD_DEFAULT),
                    ':id'   => $c['id'],
                ]);
                $filas[] = [$c['client_code'], $c['business_name'], $c['cuit'], $passwordNueva];
            }

            $adminId = $request->getAttribute('user_id');
            $auditStmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, entity_type, details, ip_address)
                VALUES (:uid, 'users.bulk_reset_passwords', 'user', :details, :ip)
            ");
            $auditStmt->execute([
                ':uid'     => $adminId,
                ':details' => json_encode(['cantidad' => count($filas)]),
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Error al resetear contraseñas: ' . $e->getMessage();
            return $response->withHeader('Location', $basePath . '/admin/comercios')->withStatus(302);
        }

        $csv = "\xEF\xBB\xBF" . "Codigo;Comercio;Usuario (CUIT);Contrasena Nueva\r\n";
        foreach ($filas as $f) {
            $escaped = array_map(function ($v) {
                return '"' . str_replace('"', '""', (string) $v) . '"';
            }, $f);
            $csv .= implode(';', $escaped) . "\r\n";
        }

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="credenciales_comercios.csv"');
    }

    /**
     * Genera una contraseña temporal aleatoria, legible (sin caracteres
     * ambiguos como 0/O o 1/l/I), para asignar a comercios importados por CSV.
     */
    private static function generateTempPassword(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password;
    }
}
