# Cliente de Escritorio - Python

Aplicación de escritorio para gestión de biblioteca. Interfaz gráfica que se comunica con el backend PHP mediante API REST.

## 🛠️ Requisitos

- Python 3.8 o superior
- pip (gestor de paquetes Python)
- Conexión a API en `http://localhost:8000`

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

### 4. Configurar variables de entorno

Crea archivo `.env` en la carpeta `cliente-python/`:

```
API_URL=http://localhost:8000/api
API_TIMEOUT=5
```

### 5. Ejecutar aplicación

```bash
python app/main.py
```

---

## 🎯 Funcionalidades

- ✅ Login de usuarios
- ✅ Visualizar catálogo de libros
- ✅ Buscar libros
- ✅ Registrar préstamos
- ✅ Registrar devoluciones
- ✅ Panel de bibliotecario
- 📱 Lectura de QR (fase extra)

---

## 📁 Estructura

```
cliente-python/
├── app/
│   ├── __init__.py
│   ├── main.py              (Punto de entrada)
│   ├── login.py             (Ventana de login)
│   ├── libros.py            (Gestión de libros)
│   ├── prestamos.py         (Gestión de préstamos)
│   └── utils/
│       ├── __init__.py
│       └── api_client.py    (Cliente HTTP)
├── requirements.txt
└── .env
```

---

## 🔌 Comunicación con API

El cliente utiliza la clase `APIClient` para comunicarse con el backend:

```python
from app.utils.api_client import APIClient

client = APIClient()
libros = client.get('/libros')
```

---

## 🚨 Solución de problemas

| Problema | Solución |
|---|---|
| "ModuleNotFoundError" | Verifica que estés en el entorno virtual |
| "Connection refused" | Asegúrate de que el backend está corriendo |
| "No module named 'tkinter'" | Instala `python3-tk` (Linux) |

---

## 📝 Notas

- La aplicación asume que el API está en `http://localhost:8000`
- Tkinter viene incluido en Python (Windows y macOS), en Linux requiere instalación
- Las credenciales se guardan en sesión, no en disco