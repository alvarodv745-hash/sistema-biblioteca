# Sistema de Gestión de Biblioteca

Sistema completo de gestión de biblioteca con **Backend PHP**, **Panel de Administración Web** y **Cliente de Escritorio Python**.

## 📋 Descripción

Sistema que permite gestionar:
- 📚 Libros (CRUD completo)
- 👥 Usuarios (Bibliotecarios y Lectores)
- 📅 Préstamos y Devoluciones
- 🔐 Autenticación con roles diferenciados
- 🌐 Panel de administración web (solo Bibliotecarios)

### Características Adicionales (Fase Extra)
- 📱 Generación de códigos QR para libros y usuarios
- 📷 Lectura de QR desde webcam

---

## 🏗️ Estructura del Proyecto

```
sistema-biblioteca/
├── backend/
│   ├── api/                  (API REST — lógica del servidor)
│   │   ├── config/           (Conexión a BD)
│   │   ├── models/           (Clases de acceso a datos)
│   │   └── controllers/      (Controladores REST)
│   ├── panel/                (Panel de administración web)
│   │   ├── includes/         (Cabecera, footer, auth, db)
│   │   ├── index.php         (Login del panel)
│   │   ├── dashboard.php     (Estadísticas)
│   │   ├── libros.php        (Gestión de libros)
│   │   ├── usuarios.php      (Gestión de usuarios)
│   │   └── prestamos.php     (Gestión de préstamos)
│   ├── index.php             (Punto de entrada API)
│   └── .htaccess
├── cliente-python/
│   ├── app/                  (Código fuente)
│   └── run.py                (Punto de entrada)
└── docs/                     (Documentación técnica)
```

---

## 🚀 Instalación Rápida

### Requisitos previos
- XAMPP (Apache + MySQL) corriendo
- Python 3.8+
- Proyecto en `C:\xampp\htdocs\proyecto_fct\sistema-biblioteca\`

### 1. Base de datos

Importar en phpMyAdmin o ejecutar:

```bash
mysql -u root -p < docs/schema.sql
```

### 2. Backend PHP

Crear el archivo `.env` en `backend/`:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=biblioteca
```

La API estará en: `http://localhost/proyecto_fct/sistema-biblioteca/backend/api`

### 3. Panel de administración web

Acceder en: `http://localhost/proyecto_fct/sistema-biblioteca/backend/panel/index.php`

Credenciales por defecto:
- Email: `admin@biblioteca.com`
- Contraseña: `password`

### 4. Cliente Python

```bash
cd cliente-python
python -m venv venv
venv\Scripts\activate          # Windows
pip install -r requirements.txt
python run.py
```

---

## 📚 Documentación

- [Especificaciones técnicas](docs/Especificaciones.md)
- [Endpoints API](docs/Endpoints.md)
- [Backend README](backend/README.md)
- [Cliente README](cliente-python/README.md)

---

## 👨‍💻 Stack Tecnológico

| Componente | Tecnología |
|---|---|
| Backend API | PHP 8.2 |
| Panel web | PHP 8.2 + HTML/CSS |
| Base de datos | MySQL 8.0 |
| Cliente de escritorio | Python 3.8+ |
| GUI | Tkinter |
| Comunicación | REST API (JSON) |
| Servidor local | XAMPP (Apache + MySQL) |

---

## 👤 Roles del sistema

| Rol | Acceso |
|---|---|
| **Bibliotecario** | API completa + Panel web + Cliente Python completo |
| **Lector** | Cliente Python (solo catálogo y sus préstamos) |

---

## 📧 Contacto

Para dudas sobre el proyecto, contacta al desarrollador.