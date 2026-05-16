# Especificaciones Técnicas — Sistema de Gestión de Biblioteca

## 1. Descripción General

Sistema de gestión de biblioteca compuesto por tres componentes:

- **API REST en PHP**: Lógica de negocio y acceso a datos
- **Panel de administración web**: Interfaz web para bibliotecarios
- **Cliente de escritorio Python**: Aplicación GUI para bibliotecarios y lectores

---

## 2. Stack Tecnológico

| Componente | Tecnología | Versión mínima |
|---|---|---|
| Backend API | PHP | 7.4 |
| Panel web | PHP + HTML/CSS | 7.4 |
| Base de datos | MySQL | 5.7 |
| Cliente de escritorio | Python | 3.8 |
| GUI | Tkinter | Incluido en Python |
| Comunicación | REST API (JSON) | — |
| Servidor local | XAMPP | — |

---

## 3. Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│  PRESENTACIÓN                                           │
│                                                         │
│  ┌─────────────────┐     ┌────────────────────────┐    │
│  │  Panel Web PHP  │     │  Cliente Python Tkinter │    │
│  │  (Bibliotecario)│     │  (Bibliotecario+Lector) │    │
│  └────────┬────────┘     └──────────┬─────────────┘    │
│           │ BD directa              │ HTTP/JSON         │
└───────────┼─────────────────────────┼──────────────────┘
            │                         │
┌───────────┼─────────────────────────┼──────────────────┐
│  LÓGICA   │                         ▼                   │
│           │              ┌──────────────────────┐       │
│           │              │    API REST PHP       │       │
│           │              │  (18 endpoints)       │       │
│           │              └──────────┬───────────┘       │
└───────────┼────────────────────────┼───────────────────┘
            │                        │
┌───────────▼────────────────────────▼───────────────────┐
│  DATOS                                                  │
│              MySQL — BD: biblioteca                     │
│    (usuarios, libros, prestamos, codigos_qr)            │
└─────────────────────────────────────────────────────────┘
```

---

## 4. Modelo de datos

### Tabla: `usuarios`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | Identificador |
| nombre | VARCHAR(100) | Nombre completo |
| email | VARCHAR(150) UNIQUE | Email de acceso |
| password | VARCHAR(255) | Hash bcrypt |
| tipo | ENUM | Bibliotecario \| Lector |
| activo | TINYINT(1) | 1=activo, 0=inactivo |
| created_at | DATETIME | Fecha de registro |

### Tabla: `libros`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | Identificador |
| titulo | VARCHAR(255) | Título |
| autor | VARCHAR(150) | Autor |
| anio | SMALLINT | Año de publicación |
| isbn | VARCHAR(20) UNIQUE | ISBN (opcional) |
| descripcion | TEXT | Descripción (opcional) |
| cantidad_total | INT UNSIGNED | Total de ejemplares |
| cantidad_disponible | INT UNSIGNED | Ejemplares disponibles |
| disponible | TINYINT(1) GENERATED | Calculado automáticamente |

### Tabla: `prestamos`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | Identificador |
| usuario_id | INT UNSIGNED FK | Usuario que toma el libro |
| libro_id | INT UNSIGNED FK | Libro prestado |
| fecha_prestamo | DATETIME | Fecha de inicio |
| fecha_devolucion_prevista | DATETIME | Fecha límite |
| fecha_devolucion_real | DATETIME NULL | Fecha real de devolución |
| estado | ENUM | Activo \| Devuelto \| Retrasado |
| notas | TEXT NULL | Observaciones |

### Tabla: `codigos_qr`
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | Identificador |
| tipo | ENUM | Libro \| Usuario |
| referencia_id | INT UNSIGNED | ID del libro o usuario |
| contenido_qr | VARCHAR(100) UNIQUE | Ej: "LIBRO:5" |
| imagen_path | VARCHAR(300) | Ruta de imagen generada |

---

## 5. API REST

### Autenticación

Token simple Base64: `base64_encode(id:tipo:timestamp)`  
Se envía en el header: `Authorization: Bearer {token}`

### Control de roles

| Endpoint | Sin auth | Lector | Bibliotecario |
|---|---|---|---|
| GET /libros | ✅ | ✅ | ✅ |
| POST /libros | ❌ | ❌ | ✅ |
| GET /prestamos | ❌ | Solo propios | ✅ todos |
| POST /prestamos | ❌ | ❌ | ✅ |
| GET /usuarios | ❌ | ❌ | ✅ |

### CORS

Headers configurados para desarrollo:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
```

---

## 6. Panel de Administración Web

Interfaz web accesible únicamente por Bibliotecarios.

| Sección | Funcionalidad |
|---|---|
| Login | Autenticación con sesión PHP |
| Dashboard | Stats: libros, usuarios, préstamos activos, retrasados |
| Libros | Añadir, buscar, filtrar, eliminar |
| Usuarios | Crear, listar, activar/desactivar |
| Préstamos | Crear, filtrar por estado, registrar devolución |

---

## 7. Seguridad

- Contraseñas hasheadas con **bcrypt** (`password_hash` / `password_verify`)
- Consultas con **prepared statements** PDO (sin SQL injection)
- Control de acceso por rol en cada endpoint
- Panel web protegido por sesión PHP
- Variables de entorno en `.env` (excluido de git)

---

## 8. Lógica automática (Triggers MySQL)

- `trg_prestamo_insert`: Al crear un préstamo, resta 1 a `cantidad_disponible`
- `trg_prestamo_devolucion`: Al marcar como devuelto, suma 1 a `cantidad_disponible`
- `disponible`: Columna generada automáticamente (`cantidad_disponible > 0`)

---

## 9. Flujos principales

### Login (Cliente Python)
1. Usuario introduce email y contraseña
2. Cliente envía `POST /api/login`
3. API verifica credenciales y devuelve token
4. Token se guarda en sesión de la app

### Préstamo manual
1. Bibliotecario selecciona usuario y libro disponible
2. Cliente envía `POST /api/prestamos`
3. API verifica disponibilidad y registra préstamo
4. Trigger actualiza `cantidad_disponible` automáticamente

### Préstamo por QR
1. Bibliotecario inicia cámara en panel QR
2. Apunta a QR impreso del libro
3. App detecta y envía `POST /api/qr/procesar`
4. API devuelve datos del libro
5. Bibliotecario confirma el préstamo con usuario seleccionado

### Devolución
1. Bibliotecario selecciona préstamo activo
2. Cliente envía `PUT /api/prestamos/{id}/devolver`
3. API actualiza estado a "Devuelto"
4. Trigger restaura disponibilidad del libro

---

## 10. Fase Extra — Códigos QR

Implementada en la Fase 5 del proyecto.

- **Generación**: PHP genera contenido `LIBRO:{id}` / `USUARIO:{id}` y delega la imagen a la API pública `qrserver.com` (sin dependencias Composer adicionales)
- **Almacenamiento**: Los QR generados se guardan en la tabla `codigos_qr`
- **Lectura**: Python usa `opencv-python` + `pyzbar` para leer desde webcam en tiempo real
- **Integración**: Desde la lectura de un QR de libro, el bibliotecario puede registrar un préstamo directamente