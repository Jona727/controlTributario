-- =====================================================
-- Sistema de Control Tributario Municipal
-- Tasas de Seguridad e Higiene
-- Schema de Base de Datos
-- =====================================================

CREATE DATABASE IF NOT EXISTS tasas_municipales
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tasas_municipales;

-- =====================================================
-- Tabla: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: users
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código único de cliente',
    business_name VARCHAR(255) NOT NULL COMMENT 'Razón Social',
    cuit VARCHAR(13) NOT NULL UNIQUE COMMENT 'CUIT formato XX-XXXXXXXX-X',
    address VARCHAR(500) NOT NULL COMMENT 'Domicilio',
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    base_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Tasa Base Fija',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_users_cuit (cuit),
    INDEX idx_users_client_code (client_code),
    INDEX idx_users_role (role_id)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: jwt_tokens (refresh tokens)
-- =====================================================
CREATE TABLE IF NOT EXISTS jwt_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    refresh_token VARCHAR(512) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jwt_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_jwt_user (user_id),
    INDEX idx_jwt_token (refresh_token(255))
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: invoices (cabecera de factura)
-- =====================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    invoice_number VARCHAR(30) NOT NULL UNIQUE COMMENT 'Ej: F-2025-0001',
    period VARCHAR(20) DEFAULT NULL COMMENT 'Período facturado, ej: 2025-05',
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    surcharge DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Recargos por mora',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    pdf_path VARCHAR(500) DEFAULT NULL,
    created_by INT DEFAULT NULL COMMENT 'Admin que generó la factura',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_creator FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_invoices_user (user_id),
    INDEX idx_invoices_status (status),
    INDEX idx_invoices_due (due_date),
    INDEX idx_invoices_number (invoice_number)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: invoice_items (detalle de factura)
-- =====================================================
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(500) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_items_invoice (invoice_id)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('alert','reminder','info','system') NOT NULL DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: audit_log (registro de auditoría)
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Ej: user.create, invoice.generate',
    entity_type VARCHAR(50) DEFAULT NULL COMMENT 'Ej: user, invoice',
    entity_id INT DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_date (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- Tabla: payments (Registros de Cobranza y Recibos)
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    receipt_number VARCHAR(50) NOT NULL COMMENT 'Ej: REC-2026-00001',
    payment_date DATETIME NOT NULL COMMENT 'Fecha y hora del cobro en ventanilla',
    amount_paid DECIMAL(12,2) NOT NULL COMMENT 'Subtotal + Mora cobrada',
    surcharge_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Mora cobrada',
    registered_by INT NOT NULL COMMENT 'Usuario administrador (cajero) que cobró',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_admin FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_payments_date (payment_date)
) ENGINE=InnoDB;

