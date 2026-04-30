# Documentación de Endpoints - API REST

**Base URL**: `http://localhost:8000/api`

---

## 🔐 Autenticación

### POST /login
Autentica un usuario

**Request:**
```json
{
  "email": "usuario@example.com",
  "password": "contraseña123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan Pérez",
    "email": "usuario@example.com",
    "tipo": "Bibliotecario",
    "token": "eyJhbGciOiJIUzI1NiIs..."
  }
}
```

**Response (401):**
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

---

### POST /registro
Registra un nuevo usuario

**Request:**
```json
{
  "nombre": "Juan Pérez",
  "email": "usuario@example.com",
  "password": "contraseña123",
  "tipo": "Lector"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan Pérez",
    "email": "usuario@example.com",
    "tipo": "Lector"
  }
}
```

---

## 📚 Libros

### GET /libros
Obtiene lista de todos los libros

**Query Parameters (opcionales):**
- `disponible` (boolean): filtrar por disponibilidad
- `autor` (string): buscar por autor
- `titulo` (string): buscar por título
- `pagina` (int): paginación (por defecto 1)
- `limite` (int): resultados por página (por defecto 10)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "titulo": "Don Quijote",
      "autor": "Miguel de Cervantes",
      "año": 1605,
      "disponible": true,
      "cantidad_disponible": 2,
      "isbn": "978-84-9754-000-0",
      "descripcion": "La novela más famosa del mundo..."
    }
  ],
  "total": 150,
  "pagina": 1
}
```

---

### POST /libros
Crea un nuevo libro (solo Bibliotecario)

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "titulo": "Cien años de soledad",
  "autor": "Gabriel García Márquez",
  "año": 1967,
  "cantidad_disponible": 3,
  "isbn": "978-84-376-0494-6",
  "descripcion": "Novela de realismo mágico..."
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "titulo": "Cien años de soledad",
    "autor": "Gabriel García Márquez",
    "año": 1967,
    "disponible": true,
    "cantidad_disponible": 3,
    "isbn": "978-84-376-0494-6"
  }
}
```

---

### PUT /libros/{id}
Actualiza información de un libro (solo Bibliotecario)

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "titulo": "Cien años de soledad (edición revisada)",
  "cantidad_disponible": 5
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "titulo": "Cien años de soledad (edición revisada)",
    "cantidad_disponible": 5
  }
}
```

---

### DELETE /libros/{id}
Elimina un libro (solo Bibliotecario)

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Libro eliminado correctamente"
}
```

---

## 👥 Usuarios

### GET /usuarios
Obtiene lista de usuarios (solo Bibliotecario)

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (opcionales):**
- `tipo` (string): Bibliotecario | Lector
- `pagina` (int)
- `limite` (int)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Juan Pérez",
      "email": "juan@example.com",
      "tipo": "Lector",
      "activo": true,
      "fecha_creacion": "2024-01-15T10:30:00Z"
    }
  ],
  "total": 50
}
```

---

### POST /usuarios
Crea un nuevo usuario (solo Bibliotecario)

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "nombre": "María López",
  "email": "maria@example.com",
  "password": "contraseña123",
  "tipo": "Lector"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "nombre": "María López",
    "email": "maria@example.com",
    "tipo": "Lector"
  }
}
```

---

## 📅 Préstamos

### GET /prestamos
Obtiene lista de préstamos

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `usuario_id` (int): filtrar por usuario
- `estado` (string): Activo | Devuelto | Retrasado
- `pagina` (int)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "usuario_id": 3,
      "usuario_nombre": "Carlos García",
      "libro_id": 5,
      "libro_titulo": "El Quijote",
      "fecha_prestamo": "2024-01-20T14:00:00Z",
      "fecha_devolucion_prevista": "2024-02-03T14:00:00Z",
      "fecha_devolucion_real": null,
      "estado": "Activo",
      "dias_retraso": 0
    }
  ],
  "total": 15
}
```

---

### POST /prestamos
Registra un nuevo préstamo

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "usuario_id": 3,
  "libro_id": 5,
  "dias": 15
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "usuario_id": 3,
    "libro_id": 5,
    "fecha_prestamo": "2024-01-25T10:00:00Z",
    "fecha_devolucion_prevista": "2024-02-09T10:00:00Z",
    "estado": "Activo"
  }
}
```

**Response (400):**
```json
{
  "success": false,
  "error": "El libro no está disponible"
}
```

---

### PUT /prestamos/{id}/devolver
Registra la devolución de un libro

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
  "notas": "Libro en buen estado"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "fecha_devolucion_real": "2024-02-01T11:00:00Z",
    "estado": "Devuelto",
    "dias_retraso": 0
  }
}
```

---

## 📱 Códigos QR (Fase Extra)

### GET /qr/libro/{id}
Obtiene el código QR de un libro

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tipo": "Libro",
    "referencia_id": 5,
    "url_qr": "https://example.com/qr/libro_5.png",
    "contenido": "LIBRO:5"
  }
}
```

---

### POST /qr/procesar
Procesa un código QR escaneado

**Request:**
```json
{
  "contenido": "LIBRO:5",
  "tipo": "libro"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "tipo": "Libro",
    "id": 5,
    "titulo": "El Quijote",
    "autor": "Cervantes",
    "disponible": true
  }
}
```

---

## 🔴 Códigos de Error

| Código HTTP | Error | Significado |
|---|---|---|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 400 | Bad Request | Datos inválidos |
| 401 | Unauthorized | No autenticado o token inválido |
| 403 | Forbidden | Acceso denegado (permisos insuficientes) |
| 404 | Not Found | Recurso no encontrado |
| 409 | Conflict | Conflicto (ej: email ya existe) |
| 500 | Internal Server Error | Error del servidor |

---

## 📝 Notas Generales

1. **Timestamps**: Todos en formato ISO 8601 con timezone UTC
2. **Autenticación**: Token requerido para operaciones protegidas
3. **Paginación**: Por defecto 10 resultados, máximo 100
4. **Búsqueda**: Case-insensitive, búsqueda parcial permitida
5. **Validación**: Todos los campos requeridos deben estar presentes