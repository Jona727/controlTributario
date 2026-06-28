-- =====================================================
-- Datos de prueba (seed)
-- =====================================================

USE tasas_municipales;

-- Roles
INSERT INTO roles (name, description) VALUES
('super',  'Super Administrador – Acceso total al sistema'),
('admin',  'Administrador Municipal – Gestión de comercios y facturación'),
('user',   'Usuario/Comercio – Acceso a su estado de cuenta');

-- Usuarios de prueba
-- Contraseña para todos: 123456 (bcrypt hash)
INSERT INTO users (client_code, business_name, cuit, address, phone, email, password_hash, is_active, base_rate, role_id) VALUES
('ADMIN-001', 'Municipalidad',            '30-99999999-0', 'Av. Municipal 100',         '0388-4000000', 'admin@municipio.gob.ar',     '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 0.00, 1),
('COM-001',   'Panadería San José',        '20-12345678-9', 'Calle Belgrano 450',        '0388-4123456', 'panaderia@email.com',        '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 3500.00, 3),
('COM-002',   'Farmacia del Centro',       '20-23456789-0', 'Av. San Martín 200',        '0388-4234567', 'farmacia@email.com',         '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 5200.00, 3),
('COM-003',   'Ferretería La Unión',       '20-34567890-1', 'Calle Lavalle 890',         '0388-4345678', 'ferreteria@email.com',       '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 2800.00, 3),
('COM-004',   'Kiosco Don Pedro',          '20-45678901-2', 'Calle Güemes 123',          '0388-4456789', 'kiosco@email.com',           '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 1500.00, 3),
('COM-005',   'Librería El Saber',         '20-56789012-3', 'Av. Senador Pérez 567',     '0388-4567890', 'libreria@email.com',         '$2y$10$DiKyItv3iNFaNIxOh/9Q8ugp7JeOB0BNOKpFitKNPQ2EWhF32KW22', 1, 2200.00, 3);

-- Facturas de prueba
INSERT INTO invoices (user_id, invoice_number, period, issue_date, due_date, subtotal, surcharge, total_amount, status, created_by) VALUES
(2, 'F-2025-0001', '2025-01', '2025-01-05', '2025-01-20', 3500.00, 0.00, 3500.00, 'paid',    1),
(2, 'F-2025-0002', '2025-02', '2025-02-05', '2025-02-20', 3500.00, 0.00, 3500.00, 'paid',    1),
(2, 'F-2025-0003', '2025-03', '2025-03-05', '2025-03-20', 3500.00, 0.00, 3500.00, 'overdue', 1),
(3, 'F-2025-0004', '2025-01', '2025-01-05', '2025-01-20', 5200.00, 0.00, 5200.00, 'paid',    1),
(3, 'F-2025-0005', '2025-02', '2025-02-05', '2025-02-20', 5200.00, 520.00, 5720.00, 'overdue', 1),
(4, 'F-2025-0006', '2025-01', '2025-01-05', '2025-01-20', 2800.00, 0.00, 2800.00, 'paid',    1),
(4, 'F-2025-0007', '2025-02', '2025-02-05', '2025-02-20', 2800.00, 0.00, 2800.00, 'pending', 1),
(5, 'F-2025-0008', '2025-01', '2025-01-05', '2025-01-20', 1500.00, 0.00, 1500.00, 'paid',    1),
(6, 'F-2025-0009', '2025-01', '2025-01-05', '2025-01-20', 2200.00, 0.00, 2200.00, 'pending', 1);

-- Items de factura de prueba
INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES
(1, 'Tasa de Seguridad e Higiene - Enero 2025', 1, 3500.00, 3500.00),
(2, 'Tasa de Seguridad e Higiene - Febrero 2025', 1, 3500.00, 3500.00),
(3, 'Tasa de Seguridad e Higiene - Marzo 2025', 1, 3500.00, 3500.00),
(4, 'Tasa de Seguridad e Higiene - Enero 2025', 1, 5200.00, 5200.00),
(5, 'Tasa de Seguridad e Higiene - Febrero 2025', 1, 5200.00, 5200.00),
(5, 'Recargo por mora (10%)', 1, 520.00, 520.00),
(6, 'Tasa de Seguridad e Higiene - Enero 2025', 1, 2800.00, 2800.00),
(7, 'Tasa de Seguridad e Higiene - Febrero 2025', 1, 2800.00, 2800.00),
(8, 'Tasa de Seguridad e Higiene - Enero 2025', 1, 1500.00, 1500.00),
(9, 'Tasa de Seguridad e Higiene - Enero 2025', 1, 2200.00, 2200.00);

-- Notificaciones de prueba
INSERT INTO notifications (user_id, type, title, message) VALUES
(2, 'alert',    'Factura vencida',             'Su factura F-2025-0003 correspondiente al período Marzo 2025 se encuentra vencida. Por favor regularice su situación.'),
(2, 'reminder', 'Próximo vencimiento',         'Recuerde que su factura F-2025-0002 vence el 20/02/2025.'),
(3, 'alert',    'Factura vencida con recargo', 'Su factura F-2025-0005 ha generado un recargo por mora de $520.00.'),
(4, 'info',     'Factura disponible',          'Su factura F-2025-0007 para el período Febrero 2025 ya está disponible para su consulta.'),
(5, 'system',   'Bienvenido al sistema',       'Bienvenido al Sistema de Control Tributario Municipal. Aquí podrá consultar su estado de cuenta.');

-- Pagos de prueba (para facturas pagadas)
INSERT INTO payments (invoice_id, receipt_number, payment_date, amount_paid, surcharge_paid, registered_by) VALUES
(1, 'REC-2025-00001', '2025-01-18 10:30:00', 3500.00, 0.00, 1),
(2, 'REC-2025-00002', '2025-02-19 11:15:00', 3500.00, 0.00, 1),
(4, 'REC-2025-00003', '2025-01-15 09:45:00', 5200.00, 0.00, 1),
(6, 'REC-2025-00004', '2025-01-19 15:20:00', 2800.00, 0.00, 1),
(8, 'REC-2025-00005', '2025-01-14 08:30:00', 1500.00, 0.00, 1);

