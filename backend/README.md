# Backend — API REST + Panel de Administración Web

Backend del Sistema de Gestión de Biblioteca. Incluye una API REST completa y un panel de administración web accesible desde el navegador.

## 🛠️ Requisitos

- PHP 7.4 o superior (probado en PHP 8.2)
- MySQL 5.7 o superior
- XAMPP (Apache + MySQL) o servidor equivalente

---

## 📦 Instalación

### 1. Ubicación del proyecto

Copiar la carpeta del proyecto en `htdocs` de XAMPP:

```
C:\xampp\htdocs\proyecto_fct\sistema-biblioteca\
```

### 2. Configurar base de datos

Importar el esquema en phpMyAdmin, o ejecutar desde terminal:

```bash
mysql -u root -p < ../docs/schema.sql
```

### 3. Configurar variables de entorno

Crear el archivo `.env` en la carpeta `backend/`:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=biblioteca
```

### 4. Iniciar XAMPP

Arrancar los módulos **Apache** y **MySQL** desde el panel de control de XAMPP.

---

## 🌐 URLs de acceso

| Recurso | URL |
|---|---|
| API REST (health check) | `http://localhost/proyecto_fct/sistema-biblioteca/backend/api` |
| Panel de administración | `http://localhost/proyecto_fct/sistema-biblioteca/backend/panel/index.php` |

---

## 🔌 Endpoints de la API

| Método | Ruta | Descripción | Rol requerido |
|---|---|---|---|
| POST | `/api/login` | Autenticación | — |
| POST | `/api/registro` | Registrar nuevo lector | — |
| GET | `/api/libros` | Listar libros | — |
| GET | `/api/libros/{id}` | Obtener libro | — |
| POST | `/api/libros` | Crear libro | Bibliotecario |
| PUT | `/api/libros/{id}` | Editar libro | Bibliotecario |
| DELETE | `/api/libros/{id}` | Eliminar libro | Bibliotecario |
| GET | `/api/usuarios` | Listar usuarios | Bibliotecario |
| GET | `/api/usuarios/{id}` | Obtener usuario | Autenticado |
| POST | `/api/usuarios` | Crear usuario | Bibliotecario |
| PUT | `/api/usuarios/{id}` | Editar usuario | Autenticado |
| GET | `/api/prestamos` | Listar préstamos | Autenticado |
| GET | `/api/prestamos/{id}` | Obtener préstamo | Autenticado |
| POST | `/api/prestamos` | Crear préstamo | Bibliotecario |
| PUT | `/api/prestamos/{id}/devolver` | Registrar devolución | Bibliotecario |
| GET | `/api/qr/libro/{id}` | Generar QR de libro | Autenticado |
| GET | `/api/qr/usuario/{id}` | Generar QR de usuario | Bibliotecario |
| POST | `/api/qr/procesar` | Procesar QR escaneado | Autenticado |

Ver documentación completa en `../docs/Endpoints.md`

---

## 📁 Estructura de archivos

```
backend/
├── api/
│   ├── config/
│   │   └── Database.php       (Conexión PDO Singleton)
│   ├── models/
│   │   ├── Usuario.php
│   │   ├── Libro.php
│   │   ├── Prestamo.php
│   │   └── CodigoQR.php
│   └── controllers/
│       ├── AuthController.php
│       ├── LibrosController.php
│       ├── UsuariosController.php
│       ├── PrestamosController.php
│       └── QRController.php
├── panel/
│   ├── includes/
│   │   ├── auth.php           (Verificación de sesión)
│   │   ├── db.php             (Acceso a BD)
│   │   ├── header.php         (Cabecera + navegación)
│   │   └── footer.php         (Cierre HTML)
│   ├── index.php              (Login)
│   ├── dashboard.php          (Estadísticas)
│   ├── libros.php             (CRUD libros)
│   ├── usuarios.php           (Gestión usuarios)
│   ├── prestamos.php          (Gestión préstamos)
│   └── logout.php             (Cerrar sesión)
├── index.php                  (Enrutador principal de la API)
├── .htaccess                  (Reescritura de URLs)
├── composer.json
└── .env                       (Variables de entorno — NO subir a git)
```

---

## 🧪 Probar la API

Usando **Postman**, **Thunder Client** o **curl**:

```bash
# Health check
curl http://localhost/proyecto_fct/sistema-biblioteca/backend/api

# Login
curl -X POST http://localhost/proyecto_fct/sistema-biblioteca/backend/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@biblioteca.com","password":"password"}'

# Listar libros
curl http://localhost/proyecto_fct/sistema-biblioteca/backend/api/libros
```

---

## 🌐 Panel de administración

Acceso exclusivo para usuarios con rol **Bibliotecario**.

Funcionalidades disponibles:
- Dashboard con estadísticas en tiempo real
- Gestión completa de libros (añadir, buscar, eliminar)
- Gestión de usuarios (crear, activar/desactivar)
- Gestión de préstamos (crear, filtrar, registrar devolución)

---

## 🚨 Errores comunes

| Error | Solución |
|---|---|
| "Could not find driver" | Activa la extensión `pdo_mysql` en `php.ini` |
| "Connection refused" | Verifica que MySQL está corriendo en XAMPP |
| "404 Not Found en /api" | Activa `mod_rewrite` en Apache (XAMPP lo trae activo) |
| "Access denied for user root" | Verifica las credenciales en el archivo `.env` |