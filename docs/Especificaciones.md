# Especificaciones Técnicas - Sistema de Gestión de Biblioteca

## 1. Descripción General

Sistema de gestión de biblioteca con dos componentes:
- **Backend PHP**: API REST que gestiona datos y lógica
- **Cliente Python**: Interfaz gráfica de escritorio

## 2. Arquitectura

### Modelo de datos

**Usuarios**
- ID (PK)
- Nombre
- Email (único)
- Contraseña (hash)
- Tipo (Bibliotecario | Lector)
- Fecha creación
- Activo (boolean)

**Libros**
- ID (PK)
- Título
- Autor
- Año
- Disponible (boolean)
- Cantidad disponible
- ISBN (opcional)
- Descripción (opcional)
- Fecha creación

**Préstamos**
- ID (PK)
- Usuario ID (FK)
- Libro ID (FK)
- Fecha préstamo
- Fecha devolución prevista
- Fecha devolución real (nullable)
- Estado (Activo | Devuelto | Retrasado)

**Códigos QR** (Fase extra)
- ID (PK)
- Tipo (Libro | Usuario)
- Referencia ID (Libro ID o Usuario ID)
- Código QR (string)
- URL del QR (imagen)
- Fecha creación

## 3. API REST

### Estructura de respuestas

**Exitosa (200):**
```json
{
  "success": true,
  "data": {...}
}
```

**Error (4xx/5xx):**
```json
{
  "success": false,
  "error": "Descripción del error",
  "code": "ERROR_CODE"
}
```

### Autenticación

- Método: Bearer Token (JWT recomendado, pero no obligatorio para Fase 1)
- Token en header: `Authorization: Bearer {token}`

### CORS

- Origen permitido: `*` (desarrollo) o dominio específico (producción)

## 4. Base de datos

**Sistema**: MySQL 5.7+

**Credenciales por defecto:**
- Usuario: `root`
- Base de datos: `biblioteca`

## 5. Seguridad (Consideraciones básicas)

- Contraseñas: hash SHA-256 o mejor (recomendado bcrypt)
- Validación de entrada: sanitización en backend
- SQL injection: usar prepared statements
- CORS: configurado en headers HTTP

## 6. Tecnologías

| Componente | Versión mínima |
|---|---|
| PHP | 7.4 |
| MySQL | 5.7 |
| Python | 3.8 |
| Tkinter | Incluido |

## 7. Flujos principales

### Flujo de login
1. Usuario ingresa email y contraseña en cliente Python
2. Cliente envía POST /api/login al backend
3. Backend valida credenciales en BD
4. Backend retorna token o ID de sesión
5. Cliente guarda token en memoria
6. Cliente accede a endpoints protegidos

### Flujo de préstamo
1. Bibliotecario escanea QR del libro (o busca manualmente)
2. Sistema obtiene datos del libro
3. Bibliotecario escanea QR del usuario (o busca manualmente)
4. Cliente envía POST /api/prestamos
5. Backend registra préstamo en BD
6. Sistema actualiza disponibilidad del libro

### Flujo de devolución
1. Bibliotecario escanea QR del libro
2. Sistema busca préstamo activo
3. Cliente envía PUT /api/prestamos/{id}/devolver
4. Backend actualiza BD
5. Sistema actualiza disponibilidad

## 8. Requisitos de interfaz (Cliente Python)

- Ventana principal con menú
- Ventana de login
- Panel de libros (vista de tabla)
- Panel de préstamos
- Panel de usuarios (solo bibliotecario)
- Buscador funcional
- Mensajes de error/éxito

## 9. Roadmap

**Fase 1 (Actual)**
- ✅ Estructura base
- ⏳ BD + Backend
- ⏳ Cliente Python
- ⏳ Integración básica

**Fase 2 (Extra)**
- 📱 Generación de QR
- 📷 Lectura de QR (webcam)
- 🔔 Alertas de retrasos
- 📊 Reportes simples