"""
Cliente HTTP para comunicación con la API REST del backend PHP.
Centraliza todas las peticiones y manejo de errores.
"""

import os
import requests
from typing import Optional

# URL base de la API (ajustable por variable de entorno)
API_URL = os.getenv("API_URL", "http://localhost/proyecto_fct/sistema-biblioteca/backend/api")
TIMEOUT = int(os.getenv("API_TIMEOUT", "5"))


class APIClient:
    """Clase singleton que gestiona las peticiones HTTP a la API."""

    _instance: Optional["APIClient"] = None

    def __new__(cls) -> "APIClient":
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._instance.token = None
            cls._instance.usuario = None
        return cls._instance

    # ── Auth ────────────────────────────────────────────────────
    def set_token(self, token: str, usuario: dict) -> None:
        self.token = token
        self.usuario = usuario

    def clear_session(self) -> None:
        self.token = None
        self.usuario = None

    def get_headers(self) -> dict:
        headers = {"Content-Type": "application/json"}
        if self.token:
            headers["Authorization"] = f"Bearer {self.token}"
        return headers

    def is_authenticated(self) -> bool:
        return self.token is not None

    def is_bibliotecario(self) -> bool:
        return (
            self.usuario is not None
            and self.usuario.get("tipo") == "Bibliotecario"
        )

    # ── Métodos HTTP genéricos ──────────────────────────────────
    def get(self, endpoint: str, params: dict = None) -> dict:
        try:
            r = requests.get(
                f"{API_URL}{endpoint}",
                headers=self.get_headers(),
                params=params,
                timeout=TIMEOUT,
            )
            return r.json()
        except requests.exceptions.ConnectionError:
            return {"success": False, "error": "No se puede conectar al servidor. ¿Está XAMPP corriendo?"}
        except requests.exceptions.Timeout:
            return {"success": False, "error": "El servidor tardó demasiado en responder."}
        except Exception as e:
            return {"success": False, "error": str(e)}

    def post(self, endpoint: str, data: dict) -> dict:
        try:
            r = requests.post(
                f"{API_URL}{endpoint}",
                headers=self.get_headers(),
                json=data,
                timeout=TIMEOUT,
            )
            return r.json()
        except requests.exceptions.ConnectionError:
            return {"success": False, "error": "No se puede conectar al servidor. ¿Está XAMPP corriendo?"}
        except requests.exceptions.Timeout:
            return {"success": False, "error": "El servidor tardó demasiado en responder."}
        except Exception as e:
            return {"success": False, "error": str(e)}

    def put(self, endpoint: str, data: dict) -> dict:
        try:
            r = requests.put(
                f"{API_URL}{endpoint}",
                headers=self.get_headers(),
                json=data,
                timeout=TIMEOUT,
            )
            return r.json()
        except requests.exceptions.ConnectionError:
            return {"success": False, "error": "No se puede conectar al servidor. ¿Está XAMPP corriendo?"}
        except requests.exceptions.Timeout:
            return {"success": False, "error": "El servidor tardó demasiado en responder."}
        except Exception as e:
            return {"success": False, "error": str(e)}

    def delete(self, endpoint: str) -> dict:
        try:
            r = requests.delete(
                f"{API_URL}{endpoint}",
                headers=self.get_headers(),
                timeout=TIMEOUT,
            )
            return r.json()
        except requests.exceptions.ConnectionError:
            return {"success": False, "error": "No se puede conectar al servidor. ¿Está XAMPP corriendo?"}
        except requests.exceptions.Timeout:
            return {"success": False, "error": "El servidor tardó demasiado en responder."}
        except Exception as e:
            return {"success": False, "error": str(e)}

    # ── Métodos de dominio ──────────────────────────────────────
    def login(self, email: str, password: str) -> dict:
        return self.post("/login", {"email": email, "password": password})

    def get_libros(self, buscar: str = "", disponible: str = "") -> dict:
        params = {"limite": 100}
        if buscar:
            params["buscar"] = buscar
        if disponible != "":
            params["disponible"] = disponible
        return self.get("/libros", params)

    def crear_libro(self, data: dict) -> dict:
        return self.post("/libros", data)

    def actualizar_libro(self, libro_id: int, data: dict) -> dict:
        return self.put(f"/libros/{libro_id}", data)

    def eliminar_libro(self, libro_id: int) -> dict:
        return self.delete(f"/libros/{libro_id}")

    def get_usuarios(self) -> dict:
        return self.get("/usuarios", {"limite": 100})

    def crear_usuario(self, data: dict) -> dict:
        return self.post("/usuarios", data)

    def get_prestamos(self, estado: str = "", usuario_id: str = "") -> dict:
        params = {"limite": 100}
        if estado:
            params["estado"] = estado
        if usuario_id:
            params["usuario_id"] = usuario_id
        return self.get("/prestamos", params)

    def crear_prestamo(self, usuario_id: int, libro_id: int, dias: int = 15) -> dict:
        return self.post("/prestamos", {
            "usuario_id": usuario_id,
            "libro_id": libro_id,
            "dias": dias,
        })

    def devolver_prestamo(self, prestamo_id: int, notas: str = "") -> dict:
        return self.put(f"/prestamos/{prestamo_id}/devolver", {"notas": notas})