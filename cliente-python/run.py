"""
Punto de entrada del Sistema de Gestión de Biblioteca.
Ejecuta: python run.py
"""

from app.login import LoginWindow
from app.main import MainApp


def main():
    def on_login_success():
        app = MainApp()
        app.run()

    login = LoginWindow(on_login_success)
    login.run()


if __name__ == "__main__":
    main()