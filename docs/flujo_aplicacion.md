# 🏛️ Mapa Conceptual y Flujo del Sistema de Control Tributario Municipal
Este documento detalla el flujo de navegación, la arquitectura de la aplicación, el modelo de datos y los flujos operativos de la plataforma de control tributario (Tasas de Seguridad e Higiene) para el **Municipio de El Pingo**.

---

## 🧭 1. Arquitectura General y Estructura
El sistema está diseñado bajo el patrón **MVC (Modelo-Vista-Controlador)** de manera simplificada sobre el micro-framework **Slim PHP (v4)**.

```mermaid
graph TD
    Client["🌐 Navegador del Cliente (Contribuyente/Administrador)"]
    Htaccess["📄 .htaccess (Raíz)"]
    Index["🚀 public/index.php (Front Controller)"]
    Routes["🛣️ config/routes.php (Rutas)"]
    JwtMiddleware["🔒 JwtMiddleware (Filtro JWT)"]
    RoleMiddleware["🛡️ RoleMiddleware (Filtro de Roles)"]
    
    subgraph Controladores ["src/Controllers"]
        AuthCtrl["🔑 AuthController"]
        AdminCtrl["👨‍💼 AdminController"]
        UserCtrl["🏢 UserController"]
        InvoiceCtrl["📄 InvoiceController"]
    end

    subgraph Servicios ["src/Services"]
        JwtService["🎟️ JwtService"]
        PdfService["🖨️ PdfService (Dompdf)"]
    end

    subgraph BaseDatos ["Database"]
        DB["🛢️ MySQL / PDO Connection"]
    end

    Client -->|Petición HTTP| Htaccess
    Htaccess -->|Redirección Silenciosa| Index
    Index -->|Carga de Entorno y Rutas| Routes
    Routes -->|Ruta Protegida| JwtMiddleware
    JwtMiddleware -->|Verifica Rol| RoleMiddleware
    RoleMiddleware --> AuthCtrl & AdminCtrl & UserCtrl & InvoiceCtrl
    
    AuthCtrl & AdminCtrl & UserCtrl & InvoiceCtrl -->|Consultas y Escritura| DB
    AuthCtrl --> JwtService
    InvoiceCtrl --> PdfService
```

---

## 👥 2. Flujos del Sistema por Rol

### A. Flujo del Contribuyente (Consulta y Descarga)
El contribuyente comercial tiene un flujo de **lectura y autoconsulta**. No ingresa pagos directamente de manera online (se abona presencialmente en ventanilla).

```mermaid
sequenceDiagram
    actor C as Contribuyente
    participant L as Vista Login
    participant A as AuthController
    participant D as DashboardController
    participant I as InvoiceController
    participant P as PdfService

    C->>L: 1. Ingresa CUIT y Contraseña (Clave Fiscal)
    L->>A: POST /login
    A->>A: Valida CUIT y password_hash en DB
    alt Credenciales Válidas
        A-->>C: Establece Access & Refresh Token (Cookies) y Redirige
    else Credenciales Inválidas
        A-->>L: Redirige con mensaje de error
    end
    
    C->>D: 2. Accede a /user/dashboard
    D->>D: Consulta facturas del comercio e intereses acumulados
    D-->>C: Muestra Dashboard con deudas actualizadas (con mora diaria)
    
    C->>I: 3. Clic en "Descargar Boleta" (GET /user/facturas/pdf/{id})
    I->>P: Solicita generación de PDF
    P-->>I: Retorna stream binario de boleta
    I-->>C: Abre/Descarga Boleta PDF lista para imprimir
    
    opt Factura ya pagada en ventanilla
        C->>I: Clic en "Recibo" (GET /user/facturas/recibo/{id})
        I->>P: Solicita generación de PDF de Recibo Oficial
        P-->>I: Retorna stream binario de recibo
        I-->>C: Abre/Descarga Recibo de Caja PDF (Libre Deuda)
    end
```

---

## 💼 B. Flujo del Administrador / Cajero (Cobranza y Gestión)
El administrador del Municipio (cajero de rentas) controla la facturación, registra pagos físicos en efectivo en ventanilla y administra los comercios.

```mermaid
sequenceDiagram
    actor Admin as Cajero / Administrador
    participant V as Vista Facturas (Admin)
    participant I as InvoiceController
    participant DB as Base de Datos
    participant P as PdfService

    Admin->>V: 1. Busca boleta de contribuyente por CUIT
    V->>V: Clic en "Cobrar en Ventanilla" (Abre Modal de Caja)
    Note over V: El modal calcula dinámicamente la mora diaria lineal (0.1% diario)<br>desde el vencimiento hasta hoy.
    
    Admin->>V: 2. Carga efectivo en mano y hace clic en "Confirmar y Emitir Recibo"
    V->>I: POST /admin/facturas/pagar/{id}
    
    critical Registrar Pago (Transacción Segura)
        I->>DB: Inicia Transacción SQL
        I->>DB: Actualiza estado factura a 'paid', surcharge = mora y total_amount = base + mora
        I->>DB: Genera recibo correlativo único (ej: REC-2026-00001)
        I->>DB: Inserta registro en tabla 'payments'
        I->>DB: Inserta notificación de alerta de pago al contribuyente
        I->>DB: Confirma Transacción (Commit)
    end
    
    I-->>V: Retorna JSON de éxito con payment_id
    V->>I: 3. Redirección automática (GET /admin/facturas/recibo/{payment_id})
    I->>P: Genera PDF con plantilla oficial de tesorería y sello de "COBRADO"
    P-->>I: Retorna binario de PDF
    I-->>Admin: Abre el Recibo Oficial en nueva pestaña para imprimir y entregar al contribuyente
```

---

## 🗄️ 3. Modelo de Datos (Base de Datos)
El siguiente diagrama detalla la estructura física de la base de datos `tasas_municipales` y la relación entre sus entidades impositivas.

```mermaid
erDiagram
    roles ||--o{ users : "tiene"
    users ||--o{ jwt_tokens : "posee"
    users ||--o{ invoices : "posee facturas"
    users ||--o{ payments : "registra cobros (admin)"
    users ||--o{ notifications : "recibe"
    users ||--o{ audit_log : "genera acciones"
    invoices ||--|| payments : "tiene comprobante de pago"
    invoices ||--|{ invoice_items : "contiene conceptos"

    roles {
        int id PK
        varchar name "ej: admin, super, contribuyente"
        varchar description
        timestamp created_at
    }

    users {
        int id PK
        varchar client_code "Código único de cliente"
        varchar business_name "Razón Social"
        varchar cuit UK "Formato: XX-XXXXXXXX-X"
        varchar address "Domicilio comercial"
        varchar phone
        varchar email UK
        varchar password_hash
        int role_id FK
        tinyint is_active
        datetime last_login
        timestamp created_at
    }

    invoices {
        int id PK
        int user_id FK "Contribuyente dueño"
        varchar invoice_number UK "Ej: F-2025-0001"
        varchar period "Ej: 2025-05"
        date issue_date
        date due_date
        decimal subtotal
        decimal surcharge "Interés por mora acumulado"
        decimal total_amount "subtotal + surcharge"
        enum status "pending, paid, overdue, cancelled"
        text notes
        varchar pdf_path
        int created_by FK "Admin creador"
        timestamp created_at
    }

    invoice_items {
        int id PK
        int invoice_id FK
        varchar description
        int quantity
        decimal unit_price
        decimal line_total
    }

    payments {
        int id PK
        int invoice_id FK "Factura pagada"
        varchar receipt_number UK "Ej: REC-2026-00001"
        datetime payment_date "Fecha del cobro físico"
        decimal amount_paid "Total cobrado en ventanilla"
        decimal surcharge_paid "Mora cobrada"
        int registered_by FK "Cajero Admin"
        timestamp created_at
    }

    notifications {
        int id PK
        int user_id FK
        enum type "alert, reminder, info, system"
        varchar title
        text message
        tinyint is_read
        timestamp created_at
    }

    audit_log {
        int id PK
        int user_id FK
        varchar action
        varchar entity_type
        int entity_id
        json details
        varchar ip_address
        timestamp created_at
    }
```

---

## 📈 4. Regla de Negocio de Recargos por Mora
1. **Detección de Atraso:** Se evalúa la cantidad de días corridos transcurridos desde `due_date` (Fecha de Vencimiento) hasta `fecha_actual`.
2. **Cálculo impositivo:** Si la fecha actual supera el vencimiento, se aplica una tasa lineal del **0.1% diario** (que equivale al **3% mensual** de recargo).
   $$\text{Mora} = \text{Subtotal} \times (\text{Días de Atraso} \times 0.001)$$
3. **Consolidación en Caja:** El interés acumulado se congela e inscribe en la base de datos únicamente al momento en el que el cajero presiona "Confirmar Cobro" en la ventanilla.
