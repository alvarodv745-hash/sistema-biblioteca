# Documentación de Endpoints — API REST

**Base URL**: `http://localhost/proyecto_fct/sistema-biblioteca/backend/api`

**Autenticación**: Bearer Token en header `Authorization: Bearer {token}`

**Formato**: JSON en todas las peticiones y respuestas.

---

## Estructura de respuestas

**Éxito:**
```json
{ "success": true, "data": { ... } }
```

**Error:**
```json
{ "success": false, "error": "Descripción del error" }
```

---

## 🔐 Autenticación

### POST /login

Autentica un usuario y devuelve un token.

**Request:**
```json
{
  "email": "admin@biblioteca.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "usuario": {
      "id": 1,
      "nombre": "Administrador",
      "email": "admin@biblioteca.com",
      "tipo": "Bibliotecario"
    },
    "token": "MTo..."
  }
}
```

**Response (401):** Credenciales inválidas.
**Response (403):** Usuario desactivado.

---

### POST /registro

Registra un nuevo usuario con rol Lector.

**Request:**
```json
{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "password": "minimo6"
}
```

**Response (201):** Datos del usuario creado.
**Response (409):** Email ya registrado.

---

## 📚 Libros

### GET /libros

Lista libros con búsqueda y paginación.

**Query params (opcionales):**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `buscar` | string | Busca en título, autor e ISBN |
| `titulo` | string | Filtra por título |
| `autor` | string | Filtra por autor |
| `disponible` | 0\|1 | Filtra por disponibilidad |
| `pagina` | int | Página (defecto: 1) |
| `limite` | int | Resultados por página (defecto: 10, máx: 100) |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "libros": [
      {
        "id": 1,
        "titulo": "Don Quijote de la Mancha",
        "autor": "Miguel de Cervantes",
        "anio": 1605,
        "isbn": "978-84-9754-000-0",
        "descripcion": "...",
        "cantidad_total": 3,
        "cantidad_disponible": 3,
        "disponible": 1
      }
    ],
    "total": 7,
    "pagina": 1
  }
}
```

---

### GET /libros/{id}

Obtiene un libro por ID.

**Response (200):** Objeto libro. **Response (404):** No encontrado.

---

### POST /libros *(Bibliotecario)*

Crea un nuevo libro.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "titulo": "Cien años de soledad",
  "autor": "Gabriel García Márquez",
  "anio": 1967,
  "isbn": "978-84-376-0494-6",
  "descripcion": "Opcional",
  "cantidad_total": 2
}
```

**Response (201):** Objeto libro creado.
**Response (409):** ISBN duplicado.

---

### PUT /libros/{id} *(Bibliotecario)*

Actualiza un libro. Solo se actualizan los campos enviados.

**Response (200):** Objeto libro actualizado. **Response (404):** No encontrado.

---

### DELETE /libros/{id} *(Bibliotecario)*

Elimina un libro. No permite eliminar si tiene préstamos activos.

**Response (200):** `{ "message": "Libro eliminado correctamente" }`
**Response (409):** Tiene préstamos activos.

---

## 👥 Usuarios

### GET /usuarios *(Bibliotecario)*

Lista todos los usuarios con paginación.

**Query params:** `tipo` (Bibliotecario|Lector), `activo` (0|1), `pagina`, `limite`.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "usuarios": [
      {
        "id": 1,
        "nombre": "Administrador",
        "email": "admin@biblioteca.com",
        "tipo": "Bibliotecario",
        "activo": 1,
        "created_at": "2026-01-01 00:00:00"
      }
    ],
    "total": 4,
    "pagina": 1
  }
}
```

---

### GET /usuarios/{id} *(Autenticado)*

Obtiene un usuario. Los Lectores solo pueden ver su propio perfil.

**Response (200):** Objeto usuario. **Response (403):** Acceso denegado. **Response (404):** No encontrado.

---

### POST /usuarios *(Bibliotecario)*

Crea un usuario con cualquier rol.

**Request:**
```json
{
  "nombre": "Nuevo Usuario",
  "email": "nuevo@example.com",
  "password": "minimo6",
  "tipo": "Lector"
}
```

**Response (201):** Objeto usuario creado. **Response (409):** Email duplicado.

---

### PUT /usuarios/{id} *(Autenticado)*

Actualiza un usuario. Los Lectores solo pueden editar su propio perfil y no pueden cambiar el campo `tipo`.

**Response (200):** Objeto usuario actualizado.

---

## 📋 Préstamos

### GET /prestamos *(Autenticado)*

Lista préstamos. Los Lectores solo ven los suyos.

**Query params:** `estado` (Activo|Devuelto|Retrasado), `usuario_id`, `pagina`, `limite`.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "prestamos": [
      {
        "id": 1,
        "usuario_id": 2,
        "usuario_nombre": "María López",
        "libro_id": 1,
        "libro_titulo": "Don Quijote de la Mancha",
        "fecha_prestamo": "2026-05-01 10:00:00",
        "fecha_devolucion_prevista": "2026-05-16 10:00:00",
        "fecha_devolucion_real": null,
        "estado": "Activo",
        "dias_retraso": 0
      }
    ],
    "total": 3,
    "pagina": 1
  }
}
```

---

### GET /prestamos/{id} *(Autenticado)*

Obtiene un préstamo. Los Lectores solo pueden ver los suyos.

**Response (200):** Objeto préstamo. **Response (403):** Acceso denegado.

---

### POST /prestamos *(Bibliotecario)*

Registra un nuevo préstamo.

**Request:**
```json
{
  "usuario_id": 2,
  "libro_id": 1,
  "dias": 15
}
```

**Response (201):** Objeto préstamo creado.
**Response (400):** Libro no disponible.
**Response (409):** El usuario ya tiene ese libro prestado.

---

### PUT /prestamos/{id}/devolver *(Bibliotecario)*

Registra la devolución de un préstamo activo.

**Request:**
```json
{
  "notas": "Libro devuelto en buen estado"
}
```

**Response (200):** Objeto préstamo actualizado con estado "Devuelto".
**Response (409):** El préstamo ya fue devuelto.

---

## 📱 Códigos QR

### GET /qr/libro/{id} *(Autenticado)*

Genera o recupera el código QR de un libro.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tipo": "Libro",
    "referencia_id": 1,
    "contenido_qr": "LIBRO:1",
    "url_imagen": "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=LIBRO%3A1",
    "libro": {
      "id": 1,
      "titulo": "Don Quijote de la Mancha",
      "autor": "Miguel de Cervantes"
    }
  }
}
```

---

### GET /qr/usuario/{id} *(Bibliotecario)*

Genera o recupera el código QR de un usuario.

**Response (200):** Similar al anterior con datos del usuario.

---

### POST /qr/procesar *(Autenticado)*

Procesa el contenido de un QR escaneado y devuelve los datos del objeto.

**Request:**
```json
{ "contenido": "LIBRO:1" }
```

**Response (200) — Libro:**
```json
{
  "success": true,
  "data": {
    "tipo": "Libro",
    "id": 1,
    "titulo": "Don Quijote de la Mancha",
    "autor": "Miguel de Cervantes",
    "anio": 1605,
    "isbn": "978-84-9754-000-0",
    "disponible": true,
    "cantidad_disponible": 3
  }
}
```

**Response (200) — Usuario:**
```json
{
  "success": true,
  "data": {
    "tipo": "Usuario",
    "id": 2,
    "nombre": "María López",
    "email": "maria@biblioteca.com",
    "rol": "Lector",
    "activo": true
  }
}
```

**Response (400):** Formato de QR no reconocido.

---

## 🔴 Códigos HTTP

| Código | Significado |
|---|---|
| 200 | Éxito |
| 201 | Recurso creado |
| 400 | Datos inválidos |
| 401 | No autenticado |
| 403 | Acceso denegado |
| 404 | No encontrado |
| 409 | Conflicto (duplicado o restricción) |
| 500 | Error interno del servidor |

--- 