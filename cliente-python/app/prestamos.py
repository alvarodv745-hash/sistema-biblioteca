"""
Panel de gestión de préstamos.
"""

import tkinter as tk
from tkinter import ttk, messagebox
from app.utils.api_client import APIClient


class PrestamosPanel(tk.Frame):

    BG        = "#1e1e2e"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    ENTRY_BG  = "#313244"
    ERROR_CLR = "#f38ba8"
    WARN_CLR  = "#fab387"
    SUCCESS   = "#a6e3a1"
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
        self.cargar_prestamos()

    def _build(self):
        # ── Cabecera ──
        header = tk.Frame(self, bg=self.BG)
        header.pack(fill="x", padx=20, pady=(18, 10))

        tk.Label(header, text="📋  Préstamos",
                 font=self.FONT_H1, bg=self.BG, fg=self.TEXT).pack(side="left")

        if self.api.is_bibliotecario():
            tk.Button(header, text="＋  Nuevo préstamo",
                      font=self.FONT_BOLD, cursor="hand2",
                      bg=self.ACCENT, fg="white",
                      activebackground=self.ACCENT_H, activeforeground="white",
                      relief="flat", bd=0, padx=14, pady=6,
                      command=self._dialogo_crear).pack(side="right")

        # ── Filtros ──
        filtros_frame = tk.Frame(self, bg=self.BG)
        filtros_frame.pack(fill="x", padx=20, pady=(0, 10))

        tk.Label(filtros_frame, text="Estado:",
                 font=self.FONT_MAIN, bg=self.BG, fg=self.TEXT_DIM).pack(side="left")

        self.estado_var = tk.StringVar(value="Todos")
        cb = ttk.Combobox(filtros_frame, textvariable=self.estado_var,
                          values=["Todos", "Activo", "Devuelto", "Retrasado"],
                          state="readonly", width=14, font=self.FONT_MAIN)
        cb.pack(side="left", padx=(6, 0))
        cb.bind("<<ComboboxSelected>>", lambda _: self.cargar_prestamos())

        tk.Button(filtros_frame, text="🔄 Actualizar",
                  font=self.FONT_MAIN, cursor="hand2",
                  bg=self.PANEL_BG, fg=self.TEXT,
                  activebackground=self.SEL_BG, relief="flat", bd=0,
                  padx=10, pady=4, command=self.cargar_prestamos).pack(side="right")

        # ── Tabla ──
        cols = ("ID", "Usuario", "Libro", "Préstamo", "Devolución Prevista", "Devuelto", "Estado", "Retraso")
        tree_frame = tk.Frame(self, bg=self.BG)
        tree_frame.pack(fill="both", expand=True, padx=20, pady=(0, 10))

        style = ttk.Style()
        style.configure("Pre.Treeview",
                        background=self.ROW_A, foreground=self.TEXT,
                        rowheight=28, fieldbackground=self.ROW_A,
                        bordercolor=self.BG, relief="flat", font=self.FONT_MAIN)
        style.configure("Pre.Treeview.Heading",
                        background=self.PANEL_BG, foreground=self.ACCENT,
                        font=self.FONT_BOLD, relief="flat")
        style.map("Pre.Treeview", background=[("selected", self.SEL_BG)])

        self.tree = ttk.Treeview(tree_frame, columns=cols, show="headings",
                                 style="Pre.Treeview", selectmode="browse")

        widths = {"ID": 40, "Usuario": 130, "Libro": 180, "Préstamo": 100,
                  "Devolución Prevista": 130, "Devuelto": 100, "Estado": 80, "Retraso": 70}
        for c in cols:
            self.tree.heading(c, text=c)
            self.tree.column(c, width=widths[c], anchor="center" if c not in ("Usuario", "Libro") else "w")

        scrollbar = ttk.Scrollbar(tree_frame, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)
        self.tree.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")

        self.tree.tag_configure("activo",    foreground=self.SUCCESS)
        self.tree.tag_configure("devuelto",  foreground=self.TEXT_DIM)
        self.tree.tag_configure("retrasado", foreground=self.ERROR_CLR)
        self.tree.tag_configure("par",       background=self.ROW_A)
        self.tree.tag_configure("impar",     background=self.ROW_B)

        # ── Botón devolver ──
        if self.api.is_bibliotecario():
            btn_frame = tk.Frame(self, bg=self.BG)
            btn_frame.pack(fill="x", padx=20, pady=(0, 16))

            tk.Button(btn_frame, text="↩️  Registrar Devolución",
                      font=self.FONT_MAIN, cursor="hand2",
                      bg=self.PANEL_BG, fg=self.SUCCESS,
                      activebackground=self.SEL_BG, relief="flat", bd=0,
                      padx=12, pady=5, command=self._devolver).pack(side="left")

        # ── Status ──
        self.status_var = tk.StringVar(value="Cargando…")
        tk.Label(self, textvariable=self.status_var, font=("Segoe UI", 9),
                 bg=self.BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=20, pady=(0, 8))

    # ── Carga ────────────────────────────────────────────────────
    def cargar_prestamos(self):
        estado = self.estado_var.get() if hasattr(self, "estado_var") else "Todos"
        params_estado = "" if estado == "Todos" else estado

        resultado = self.api.get_prestamos(estado=params_estado)

        for row in self.tree.get_children():
            self.tree.delete(row)

        if not resultado.get("success"):
            self.status_var.set(f"Error: {resultado.get('error')}")
            return

        prestamos = resultado["data"]["prestamos"]

        def fmt_fecha(f):
            if not f:
                return "—"
            return f[:10] if len(f) >= 10 else f

        for i, p in enumerate(prestamos):
            estado_p = p["estado"]
            tag = estado_p.lower()
            bg_tag = "par" if i % 2 == 0 else "impar"
            retraso = int(p.get("dias_retraso", 0))
            self.tree.insert("", "end", iid=str(p["id"]),
                             tags=(tag, bg_tag),
                             values=(
                                 p["id"],
                                 p["usuario_nombre"],
                                 p["libro_titulo"],
                                 fmt_fecha(p["fecha_prestamo"]),
                                 fmt_fecha(p["fecha_devolucion_prevista"]),
                                 fmt_fecha(p.get("fecha_devolucion_real")),
                                 estado_p,
                                 f"{retraso}d" if retraso > 0 else "—",
                             ))

        total    = resultado["data"]["total"]
        activos  = sum(1 for p in prestamos if p["estado"] == "Activo")
        retrasados = sum(1 for p in prestamos if p["estado"] == "Retrasado")
        self.status_var.set(
            f"{total} préstamo{'s' if total != 1 else ''}  •  "
            f"{activos} activo{'s' if activos != 1 else ''}  •  "
            f"{retrasados} retrasado{'s' if retrasados != 1 else ''}"
        )

    # ── Crear préstamo ───────────────────────────────────────────
    def _dialogo_crear(self):
        # Cargar usuarios y libros disponibles
        res_u = self.api.get_usuarios()
        res_l = self.api.get_libros(disponible="1")

        if not res_u.get("success"):
            messagebox.showerror("Error", "No se pudieron cargar los usuarios.", parent=self.winfo_toplevel())
            return
        if not res_l.get("success"):
            messagebox.showerror("Error", "No se pudieron cargar los libros disponibles.", parent=self.winfo_toplevel())
            return

        usuarios = res_u["data"]["usuarios"]
        libros   = res_l["data"]["libros"]

        if not usuarios:
            messagebox.showwarning("Sin usuarios", "No hay usuarios registrados.", parent=self.winfo_toplevel())
            return
        if not libros:
            messagebox.showwarning("Sin libros", "No hay libros disponibles en este momento.", parent=self.winfo_toplevel())
            return

        dlg = tk.Toplevel(self.winfo_toplevel())
        dlg.title("Nuevo Préstamo")
        dlg.geometry("400x360")
        dlg.resizable(False, False)
        dlg.configure(bg=self.PANEL_BG)
        dlg.grab_set()

        tk.Label(dlg, text="Nuevo Préstamo", font=self.FONT_H1,
                 bg=self.PANEL_BG, fg=self.TEXT).pack(pady=(20, 14))

        # Usuario
        tk.Label(dlg, text="Usuario *", font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
        usuario_names = [f"{u['id']} — {u['nombre']} ({u['tipo']})" for u in usuarios]
        usuario_var = tk.StringVar()
        cb_u = ttk.Combobox(dlg, textvariable=usuario_var,
                             values=usuario_names, state="readonly",
                             font=self.FONT_MAIN)
        cb_u.pack(fill="x", padx=28, pady=(2, 12))

        # Libro
        tk.Label(dlg, text="Libro *", font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
        libro_names = [f"{l['id']} — {l['titulo']} ({l['cantidad_disponible']} disp.)" for l in libros]
        libro_var = tk.StringVar()
        cb_l = ttk.Combobox(dlg, textvariable=libro_var,
                             values=libro_names, state="readonly",
                             font=self.FONT_MAIN)
        cb_l.pack(fill="x", padx=28, pady=(2, 12))

        # Días
        tk.Label(dlg, text="Días de préstamo *", font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
        dias_var = tk.StringVar(value="15")
        tk.Entry(dlg, textvariable=dias_var, font=self.FONT_MAIN,
                 bg=self.ENTRY_BG, fg=self.TEXT,
                 insertbackground=self.TEXT, relief="flat", bd=0
                 ).pack(fill="x", padx=28, ipady=6, pady=(2, 12))

        msg_var = tk.StringVar()
        tk.Label(dlg, textvariable=msg_var, font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.ERROR_CLR, wraplength=340).pack()

        def crear():
            if not usuario_var.get():
                msg_var.set("Selecciona un usuario.")
                return
            if not libro_var.get():
                msg_var.set("Selecciona un libro.")
                return
            if not dias_var.get().isdigit() or int(dias_var.get()) < 1:
                msg_var.set("Los días deben ser un número positivo.")
                return

            uid = int(usuario_var.get().split(" — ")[0])
            lid = int(libro_var.get().split(" — ")[0])

            r = self.api.crear_prestamo(uid, lid, int(dias_var.get()))
            if r.get("success"):
                dlg.destroy()
                self.cargar_prestamos()
            else:
                msg_var.set(r.get("error", "Error al crear el préstamo."))

        tk.Button(dlg, text="Registrar Préstamo",
                  font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white",
                  activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=8,
                  command=crear).pack(pady=(6, 16))

    # ── Devolver ─────────────────────────────────────────────────
    def _devolver(self):
        sel = self.tree.focus()
        if not sel:
            messagebox.showwarning("Sin selección", "Selecciona un préstamo para registrar la devolución.", parent=self.winfo_toplevel())
            return

        valores = self.tree.item(sel, "values")
        estado_actual = valores[6]

        if estado_actual == "Devuelto":
            messagebox.showinfo("Info", "Este préstamo ya fue devuelto.", parent=self.winfo_toplevel())
            return

        libro   = valores[2]
        usuario = valores[1]

        if not messagebox.askyesno("Confirmar devolución",
                                   f"¿Registrar devolución de:\n«{libro}»\nPor: {usuario}?",
                                   parent=self.winfo_toplevel()):
            return

        r = self.api.devolver_prestamo(int(sel))
        if r.get("success"):
            self.cargar_prestamos()
        else:
            messagebox.showerror("Error", r.get("error", "No se pudo registrar la devolución."), parent=self.winfo_toplevel())