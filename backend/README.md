# Backend - API REST en PHP

API REST para gestión de biblioteca. Proporciona endpoints para autenticación, libros, usuarios y préstamos.

## 🛠️ Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (gestor de dependencias PHP)

## 📦 Instalación

### 1. Instalar dependencias

```bash
cd backend
composer install
```

### 2. Configurar base de datos

```bash
mysql -u root -p < ../docs/schema.sql
```

### 3. Configurar variables de entorno

Copia `.env.example` a `.env` y edita:

```bash
cp .env.example .env
```

Edita los valores:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=tu_contraseña
DB_NAME=biblioteca
```

### 4. Iniciar servidor local

```bash
php -S localhost:8000
```

La API estará disponible en: **http://localhost:8000/api** 

---

## 🔌 Estructura del API

### Endpoints principales

- `POST /api/login` - Autenticación
- `GET /api/libros` - Listar libros
- `POST /api/libros` - Crear libro
- `PUT /api/libros/{id}` - Editar libro
- `DELETE /api/libros/{id}` - Eliminar libro
- `GET /api/usuarios` - Listar usuarios
- `POST /api/prestamos` - Registrar préstamo
- `PUT /api/prestamos/{id}/devolver` - Registrar devolución
- `GET /api/prestamos` - Listar préstamos

Ver documentación completa en `../docs/endpoints.md`

---

## 📁 Estructura de archivos

```
backend/
├── api/
│   ├── config/          (Conexión a BD)
│   ├── models/          (Clases de BD)
│   ├── controllers/      (Lógica de negocio)
│   └── routes.php       (Enrutamiento)
├── index.php            (Punto de entrada)
├── .htaccess            (Reescritura de URLs)
└── composer.json        (Dependencias)
```

---

## 🧪 Testing

Usa **Postman/Thunder Client** o **curl** para probar los endpoints:

```bash
curl -X GET http://localhost:8000/api/libros
```

---

## 📝 Variables de entorno (.env)

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=biblioteca
```

---

## 🚨 Errores comunes

| Error | Solución |
|---|---|
| "Could not find driver" | Instala la extensión PDO de MySQL en PHP |
| "Connection refused" | Verifica que MySQL está corriendo |
| "404 Not Found" | Habilita mod_rewrite en Apache |