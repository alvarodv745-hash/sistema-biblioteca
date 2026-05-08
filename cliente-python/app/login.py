"""
Ventana de login del Sistema de Gestión de Biblioteca.
"""

import tkinter as tk
from tkinter import ttk, messagebox
from app.utils.api_client import APIClient


class LoginWindow:
    """Ventana de inicio de sesión."""

    BG        = "#1e1e2e"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    ENTRY_BG  = "#313244"
    ERROR_CLR = "#f38ba8"
    SUCCESS   = "#a6e3a1"
    FONT_MAIN = ("Segoe UI", 10)
    FONT_BOLD = ("Segoe UI", 10, "bold")
    FONT_H1   = ("Segoe UI", 20, "bold")
    FONT_H2   = ("Segoe UI", 12)

    def __init__(self, on_success_callback):
        self.api = APIClient()
        self.on_success = on_success_callback
        self._build()

    def _build(self):
        self.root = tk.Tk()
        self.root.title("Biblioteca — Iniciar Sesión")
        self.root.geometry("420x520")
        self.root.resizable(False, False)
        self.root.configure(bg=self.BG)
        self.root.eval("tk::PlaceWindow . center")

        # ── Panel central ──
        panel = tk.Frame(self.root, bg=self.PANEL_BG, bd=0)
        panel.place(relx=0.5, rely=0.5, anchor="center", width=340, height=440)

        # ── Icono / título ──
        tk.Label(panel, text="📚", font=("Segoe UI", 40),
                 bg=self.PANEL_BG, fg=self.ACCENT).pack(pady=(36, 4))

        tk.Label(panel, text="Biblioteca",
                 font=self.FONT_H1, bg=self.PANEL_BG, fg=self.TEXT).pack()

        tk.Label(panel, text="Sistema de Gestión",
                 font=self.FONT_H2, bg=self.PANEL_BG, fg=self.TEXT_DIM).pack(pady=(0, 28))

        # ── Email ──
        tk.Label(panel, text="Correo electrónico",
                 font=self.FONT_BOLD, bg=self.PANEL_BG, fg=self.TEXT_DIM,
                 anchor="w").pack(fill="x", padx=32)

        self.email_var = tk.StringVar()
        email_entry = tk.Entry(panel, textvariable=self.email_var,
                               font=self.FONT_MAIN,
                               bg=self.ENTRY_BG, fg=self.TEXT,
                               insertbackground=self.TEXT,
                               relief="flat", bd=0)
        email_entry.pack(fill="x", padx=32, ipady=8, pady=(4, 14))
        email_entry.insert(0, "admin@biblioteca.com")

        # ── Contraseña ──
        tk.Label(panel, text="Contraseña",
                 font=self.FONT_BOLD, bg=self.PANEL_BG, fg=self.TEXT_DIM,
                 anchor="w").pack(fill="x", padx=32)

        self.pass_var = tk.StringVar()
        pass_entry = tk.Entry(panel, textvariable=self.pass_var,
                              show="•", font=self.FONT_MAIN,
                              bg=self.ENTRY_BG, fg=self.TEXT,
                              insertbackground=self.TEXT,
                              relief="flat", bd=0)
        pass_entry.pack(fill="x", padx=32, ipady=8, pady=(4, 6))
        pass_entry.insert(0, "password")
        pass_entry.bind("<Return>", lambda e: self._do_login())

        # ── Mensaje de estado ──
        self.msg_var = tk.StringVar()
        self.msg_label = tk.Label(panel, textvariable=self.msg_var,
                                  font=("Segoe UI", 9), bg=self.PANEL_BG,
                                  fg=self.ERROR_CLR, wraplength=280)
        self.msg_label.pack(pady=(4, 0))

        # ── Botón login ──
        self.btn = tk.Button(
            panel, text="Entrar",
            font=self.FONT_BOLD, cursor="hand2",
            bg=self.ACCENT, fg="white", activebackground=self.ACCENT_H,
            activeforeground="white", relief="flat", bd=0,
            command=self._do_login
        )
        self.btn.pack(fill="x", padx=32, ipady=10, pady=(14, 0))

    # ── Lógica de login ─────────────────────────────────────────
    def _do_login(self):
        email    = self.email_var.get().strip()
        password = self.pass_var.get().strip()

        if not email or not password:
            self._set_msg("Rellena todos los campos.", error=True)
            return

        self.btn.config(text="Conectando…", state="disabled")
        self.root.update()

        resultado = self.api.login(email, password)

        self.btn.config(text="Entrar", state="normal")

        if resultado.get("success"):
            data = resultado["data"]
            self.api.set_token(data["token"], data["usuario"])
            self._set_msg(f"Bienvenido, {data['usuario']['nombre']} ✓", error=False)
            self.root.after(600, self._abrir_app)
        else:
            self._set_msg(resultado.get("error", "Error desconocido."), error=True)

    def _abrir_app(self):
        self.root.destroy()
        self.on_success()

    def _set_msg(self, texto: str, error: bool = True):
        self.msg_label.config(fg=self.ERROR_CLR if error else self.SUCCESS)
        self.msg_var.set(texto)

    def run(self):
        self.root.mainloop()