"""
Ventana principal del Sistema de Gestión de Biblioteca.
Versión 1.1.0 - incluye panel QR en la navegación.
"""

import tkinter as tk
from tkinter import ttk, messagebox
from app.utils.api_client import APIClient
from app.libros import LibrosPanel
from app.prestamos import PrestamosPanel
from app.qr_panel import QRPanel


class MainApp:

    BG        = "#1e1e2e"
    SIDEBAR   = "#181825"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    SEL_BG    = "#313244"
    FONT_MAIN = ("Segoe UI", 10)
    FONT_BOLD = ("Segoe UI", 10, "bold")
    FONT_SIDE = ("Segoe UI", 11)

    def __init__(self):
        self.api = APIClient()
        self._panel_activo = None
        self._panel_widget  = None
        self._build()

    def _build(self):
        self.root = tk.Tk()
        self.root.title("Sistema de Gestión de Biblioteca")
        self.root.geometry("1100x660")
        self.root.minsize(900, 540)
        self.root.configure(bg=self.BG)
        self.root.eval("tk::PlaceWindow . center")
        self.root.protocol("WM_DELETE_WINDOW", self._on_close)

        self.sidebar = tk.Frame(self.root, bg=self.SIDEBAR, width=210)
        self.sidebar.pack(side="left", fill="y")
        self.sidebar.pack_propagate(False)

        self.content = tk.Frame(self.root, bg=self.BG)
        self.content.pack(side="left", fill="both", expand=True)

        self._build_sidebar()
        self._mostrar_panel("libros")

    def _build_sidebar(self):
        # Logo
        tk.Label(self.sidebar, text="📚", font=("Segoe UI", 30),
                 bg=self.SIDEBAR, fg=self.ACCENT).pack(pady=(28, 4))
        tk.Label(self.sidebar, text="Biblioteca",
                 font=("Segoe UI", 13, "bold"), bg=self.SIDEBAR, fg=self.TEXT).pack()
        tk.Label(self.sidebar, text="Sistema de Gestión",
                 font=("Segoe UI", 8), bg=self.SIDEBAR, fg=self.TEXT_DIM).pack(pady=(0, 24))

        tk.Frame(self.sidebar, bg=self.PANEL_BG, height=1).pack(fill="x", padx=16, pady=(0, 14))

        # Items de navegación
        nav_items = [
            ("libros",    "📚  Catálogo"),
            ("prestamos", "📋  Préstamos"),
            ("qr",        "📱  Códigos QR"),
        ]
        if self.api.is_bibliotecario():
            nav_items.append(("usuarios", "👥  Usuarios"))

        self._nav_btns = {}
        for key, label in nav_items:
            btn = tk.Button(
                self.sidebar, text=label,
                font=self.FONT_SIDE, anchor="w", cursor="hand2",
                bg=self.SIDEBAR, fg=self.TEXT,
                activebackground=self.SEL_BG, activeforeground=self.TEXT,
                relief="flat", bd=0, padx=20, pady=10,
                command=lambda k=key: self._mostrar_panel(k)
            )
            btn.pack(fill="x")
            self._nav_btns[key] = btn

        # Espaciador
        tk.Frame(self.sidebar, bg=self.SIDEBAR).pack(fill="both", expand=True)
        tk.Frame(self.sidebar, bg=self.PANEL_BG, height=1).pack(fill="x", padx=16, pady=(0, 10))

        # Info usuario
        usuario = self.api.usuario or {}
        tk.Label(self.sidebar, text=usuario.get("nombre", ""),
                 font=self.FONT_BOLD, bg=self.SIDEBAR, fg=self.TEXT,
                 wraplength=178).pack(padx=16, anchor="w")
        tk.Label(self.sidebar, text=usuario.get("tipo", ""),
                 font=("Segoe UI", 8), bg=self.SIDEBAR, fg=self.ACCENT).pack(padx=16, anchor="w")
        tk.Label(self.sidebar, text=usuario.get("email", ""),
                 font=("Segoe UI", 8), bg=self.SIDEBAR, fg=self.TEXT_DIM,
                 wraplength=178).pack(padx=16, anchor="w", pady=(2, 0))

        tk.Button(self.sidebar, text="⏻  Cerrar sesión",
                  font=("Segoe UI", 9), cursor="hand2",
                  bg=self.SIDEBAR, fg=self.TEXT_DIM,
                  activebackground=self.SEL_BG, activeforeground=self.TEXT,
                  relief="flat", bd=0, padx=20, pady=8,
                  command=self._cerrar_sesion).pack(fill="x", pady=(8, 16))

    def _mostrar_panel(self, key: str):
        if self._panel_activo == key:
            return

        # Resaltar botón activo
        for k, btn in self._nav_btns.items():
            activo = k == key
            btn.config(
                bg=self.SEL_BG if activo else self.SIDEBAR,
                fg=self.ACCENT if activo else self.TEXT,
                font=(self.FONT_SIDE[0], self.FONT_SIDE[1], "bold") if activo else self.FONT_SIDE
            )

        # Destruir panel anterior (detiene cámara si está activa)
        if self._panel_widget:
            self._panel_widget.destroy()
            self._panel_widget = None

        for widget in self.content.winfo_children():
            widget.destroy()

        # Crear nuevo panel
        if key == "libros":
            panel = LibrosPanel(self.content)
        elif key == "prestamos":
            panel = PrestamosPanel(self.content)
        elif key == "qr":
            panel = QRPanel(self.content)
        elif key == "usuarios":
            panel = UsuariosPanel(self.content)
        else:
            return

        panel.pack(fill="both", expand=True)
        self._panel_widget  = panel
        self._panel_activo  = key

    def _cerrar_sesion(self):
        if messagebox.askyesno("Cerrar sesión", "¿Seguro que quieres cerrar sesión?", parent=self.root):
            if self._panel_widget:
                self._panel_widget.destroy()
            self.api.clear_session()
            self.root.destroy()
            from app.login import LoginWindow
            LoginWindow(lambda: MainApp().run()).run()

    def _on_close(self):
        if self._panel_widget:
            self._panel_widget.destroy()
        self.root.destroy()

    def run(self):
        self.root.mainloop()


# ── Panel de Usuarios ──────────────────────────────────────────

class UsuariosPanel(tk.Frame):

    BG        = "#1e1e2e"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    ENTRY_BG  = "#313244"
    ERROR_CLR = "#f38ba8"
    ROW_A     = "#2a2a3e"
    ROW_B     = "#252535"
    SEL_BG    = "#45475a"
    FONT_MAIN = ("Segoe UI", 10)
    FONT_BOLD = ("Segoe UI", 10, "bold")
    FONT_H1   = ("Segoe UI", 14, "bold")

    def __init__(self, parent):
        super().__init__(parent, bg=self.BG)
        self.api = APIClient()
        self._build()
        self.cargar_usuarios()

    def _build(self):
        header = tk.Frame(self, bg=self.BG)
        header.pack(fill="x", padx=20, pady=(18, 10))
        tk.Label(header, text="👥  Usuarios",
                 font=self.FONT_H1, bg=self.BG, fg=self.TEXT).pack(side="left")
        tk.Button(header, text="＋  Nuevo usuario",
                  font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white",
                  activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=6,
                  command=self._dialogo_crear).pack(side="right")

        cols = ("ID", "Nombre", "Email", "Tipo", "Activo", "Registro")
        tree_frame = tk.Frame(self, bg=self.BG)
        tree_frame.pack(fill="both", expand=True, padx=20, pady=(0, 10))

        style = ttk.Style()
        style.configure("Usr.Treeview",
                        background=self.ROW_A, foreground=self.TEXT,
                        rowheight=28, fieldbackground=self.ROW_A,
                        bordercolor=self.BG, relief="flat", font=self.FONT_MAIN)
        style.configure("Usr.Treeview.Heading",
                        background=self.PANEL_BG, foreground=self.ACCENT,
                        font=self.FONT_BOLD, relief="flat")
        style.map("Usr.Treeview", background=[("selected", self.SEL_BG)])

        self.tree = ttk.Treeview(tree_frame, columns=cols, show="headings",
                                 style="Usr.Treeview", selectmode="browse")
        widths = {"ID": 40, "Nombre": 180, "Email": 210, "Tipo": 110, "Activo": 70, "Registro": 110}
        for c in cols:
            self.tree.heading(c, text=c)
            self.tree.column(c, width=widths[c], anchor="w" if c in ("Nombre", "Email") else "center")

        sb = ttk.Scrollbar(tree_frame, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=sb.set)
        self.tree.pack(side="left", fill="both", expand=True)
        sb.pack(side="right", fill="y")

        self.tree.tag_configure("bibliotecario", foreground=self.ACCENT)
        self.tree.tag_configure("par",   background=self.ROW_A)
        self.tree.tag_configure("impar", background=self.ROW_B)

        self.status_var = tk.StringVar(value="Cargando…")
        tk.Label(self, textvariable=self.status_var, font=("Segoe UI", 9),
                 bg=self.BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=20, pady=(0, 8))

    def cargar_usuarios(self):
        r = self.api.get_usuarios()
        for row in self.tree.get_children():
            self.tree.delete(row)
        if not r.get("success"):
            self.status_var.set(f"Error: {r.get('error')}")
            return
        usuarios = r["data"]["usuarios"]
        for i, u in enumerate(usuarios):
            tag = "bibliotecario" if u["tipo"] == "Bibliotecario" else ("par" if i % 2 == 0 else "impar")
            self.tree.insert("", "end", iid=str(u["id"]), tags=(tag,),
                             values=(u["id"], u["nombre"], u["email"],
                                     u["tipo"], "✓" if u["activo"] else "✗",
                                     u.get("created_at", "")[:10]))
        self.status_var.set(f"{r['data']['total']} usuarios registrados")

    def _dialogo_crear(self):
        dlg = tk.Toplevel(self.winfo_toplevel())
        dlg.title("Nuevo Usuario")
        dlg.geometry("400x390")
        dlg.resizable(False, False)
        dlg.configure(bg=self.PANEL_BG)
        dlg.grab_set()

        tk.Label(dlg, text="Nuevo Usuario", font=self.FONT_H1,
                 bg=self.PANEL_BG, fg=self.TEXT).pack(pady=(20, 14))

        campos = [("nombre", "Nombre *", ""), ("email", "Email *", ""), ("password", "Contraseña *", "")]
        vars_ = {}
        for key, label, val in campos:
            tk.Label(dlg, text=label, font=("Segoe UI", 9),
                     bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
            v = tk.StringVar(value=val)
            tk.Entry(dlg, textvariable=v, show="•" if key == "password" else "",
                     font=self.FONT_MAIN, bg=self.ENTRY_BG, fg=self.TEXT,
                     insertbackground=self.TEXT, relief="flat", bd=0
                     ).pack(fill="x", padx=28, ipady=6, pady=(2, 10))
            vars_[key] = v

        tk.Label(dlg, text="Tipo *", font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
        tipo_var = tk.StringVar(value="Lector")
        ttk.Combobox(dlg, textvariable=tipo_var, values=["Lector", "Bibliotecario"],
                     state="readonly", font=self.FONT_MAIN).pack(fill="x", padx=28, pady=(2, 10))

        msg_var = tk.StringVar()
        tk.Label(dlg, textvariable=msg_var, font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.ERROR_CLR, wraplength=340).pack()

        def guardar():
            data = {k: v.get().strip() for k, v in vars_.items()}
            data["tipo"] = tipo_var.get()
            for c in ["nombre", "email", "password"]:
                if not data[c]:
                    msg_var.set(f"El campo '{c}' es obligatorio.")
                    return
            r = self.api.crear_usuario(data)
            if r.get("success"):
                dlg.destroy()
                self.cargar_usuarios()
            else:
                msg_var.set(r.get("error", "Error al crear usuario."))

        tk.Button(dlg, text="Crear Usuario", font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white", activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=8, command=guardar).pack(pady=(6, 16))