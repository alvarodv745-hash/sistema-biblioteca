# Cliente de Escritorio — Python

Aplicación de escritorio para gestión de biblioteca. Interfaz gráfica que se comunica con el backend PHP mediante API REST.

## 🛠️ Requisitos

- Python 3.8 o superior
- XAMPP corriendo (Apache + MySQL)
- Backend PHP en funcionamiento

---

## 📦 Instalación

### 1. Crear entorno virtual

```bash
cd cliente-python
python -m venv venv
```

### 2. Activar entorno

**Windows:**
```bash
venv\Scripts\activate
```

**macOS/Linux:**
```bash
source venv/bin/activate
```

### 3. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 4. Ejecutar aplicación

```bash
python run.py
```

---

## 🎯 Funcionalidades

### Rol Lector
- ✅ Login con email y contraseña
- ✅ Ver catálogo completo de libros
- ✅ Buscar libros por título, autor o ISBN
- ✅ Filtrar por disponibilidad
- ✅ Ver sus propios préstamos

### Rol Bibliotecario (todo lo anterior más)
- ✅ Crear, editar y eliminar libros
- ✅ Registrar nuevos préstamos
- ✅ Registrar devoluciones
- ✅ Gestionar usuarios (crear, listar)
- ✅ Ver todos los préstamos con filtro por estado
- 📱 Generar códigos QR para libros y usuarios
- 📷 Escanear QR desde webcam y registrar préstamos

---

## 📦 Dependencias

### Obligatorias (incluidas en requirements.txt)

```
requests          # Peticiones HTTP a la API
python-dotenv     # Variables de entorno
Pillow            # Visualización de imágenes QR
opencv-python     # Captura de webcam
pyzbar            # Lectura de códigos QR
```

### En Linux, instalar también

```bash
sudo apt install libzbar0
```

### En macOS, instalar también

```bash
brew install zbar
```

---

## 📁 Estructura

```
cliente-python/
├── app/
│   ├── __init__.py
│   ├── main.py              (Ventana principal + panel usuarios)
│   ├── login.py             (Ventana de login)
│   ├── libros.py            (Panel de catálogo)
│   ├── prestamos.py         (Panel de préstamos)
│   ├── qr_panel.py          (Panel de generación y escaneo QR)
│   └── utils/
│       ├── __init__.py
│       └── api_client.py    (Cliente HTTP singleton)
├── requirements.txt
├── run.py                   (Punto de entrada)
└── .env.example
```

---

## ⚙️ Configuración (opcional)

Si necesitas cambiar la URL de la API, crea un archivo `.env` en `cliente-python/`:

```
API_URL=http://localhost/proyecto_fct/sistema-biblioteca/backend/api
API_TIMEOUT=5
```

Por defecto ya apunta a la URL correcta para XAMPP local.

---

## 🔌 Comunicación con la API

El cliente utiliza la clase `APIClient` (patrón Singleton) para todas las peticiones:

```python
from app.utils.api_client import APIClient

client = APIClient()
libros = client.get_libros(buscar="Quijote")
```

---

## 🔑 Credenciales de prueba

| Usuario | Email | Contraseña | Rol |
|---|---|---|---|
| Administrador | admin@biblioteca.com | password | Bibliotecario |
| María López | maria@biblioteca.com | password | Lector |

---

## 🚨 Solución de problemas

| Problema | Solución |
|---|---|
| "No se puede conectar al servidor" | Verifica que XAMPP (Apache + MySQL) está corriendo |
| "ModuleNotFoundError: requests" | Ejecuta `pip install -r requirements.txt` con el venv activo |
| "No module named 'tkinter'" | En Linux: `sudo apt install python3-tk` |
| "No module named 'cv2'" | Ejecuta `pip install opencv-python` |
| "No module named 'pyzbar'" | Ejecuta `pip install pyzbar` (Linux: `sudo apt install libzbar0`) |
| "No se encontró ninguna webcam" | Verifica que la cámara no está en uso por otra aplicación |

---

## 📝 Notas

- Las credenciales se guardan en sesión, no en disco.
- El panel QR requiere `Pillow`, `opencv-python` y `pyzbar`. Sin ellas, la app funciona igualmente pero sin funcionalidad de cámara.
- Los lectores no tienen acceso al panel de usuarios ni pueden crear/editar/eliminar libros.