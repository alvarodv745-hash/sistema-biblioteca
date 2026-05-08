"""
Panel de gestión de libros.
"""

import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
from app.utils.api_client import APIClient


class LibrosPanel(tk.Frame):

    BG        = "#1e1e2e"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    ENTRY_BG  = "#313244"
    SUCCESS   = "#a6e3a1"
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
        self.cargar_libros()

    def _build(self):
        # ── Cabecera ──
        header = tk.Frame(self, bg=self.BG)
        header.pack(fill="x", padx=20, pady=(18, 10))

        tk.Label(header, text="📚  Catálogo de Libros",
                 font=self.FONT_H1, bg=self.BG, fg=self.TEXT).pack(side="left")

        # Botón Nuevo libro (solo bibliotecario)
        if self.api.is_bibliotecario():
            tk.Button(header, text="＋  Nuevo libro",
                      font=self.FONT_BOLD, cursor="hand2",
                      bg=self.ACCENT, fg="white",
                      activebackground=self.ACCENT_H, activeforeground="white",
                      relief="flat", bd=0, padx=14, pady=6,
                      command=self._dialogo_crear).pack(side="right")

        # ── Barra de búsqueda ──
        search_frame = tk.Frame(self, bg=self.BG)
        search_frame.pack(fill="x", padx=20, pady=(0, 10))

        self.buscar_var = tk.StringVar()
        self.buscar_var.trace_add("write", lambda *_: self.cargar_libros())
        entry = tk.Entry(search_frame, textvariable=self.buscar_var,
                         font=self.FONT_MAIN,
                         bg=self.ENTRY_BG, fg=self.TEXT,
                         insertbackground=self.TEXT, relief="flat", bd=0)
        entry.pack(side="left", fill="x", expand=True, ipady=8, padx=(0, 8))
        entry.insert(0, "")

        tk.Label(search_frame, text="🔍", font=("Segoe UI", 13),
                 bg=self.BG, fg=self.TEXT_DIM).pack(side="left")

        # Filtro disponibilidad
        self.filtro_var = tk.StringVar(value="Todos")
        filtro_cb = ttk.Combobox(search_frame, textvariable=self.filtro_var,
                                 values=["Todos", "Disponibles", "No disponibles"],
                                 state="readonly", width=16, font=self.FONT_MAIN)
        filtro_cb.pack(side="right", padx=(8, 0))
        filtro_cb.bind("<<ComboboxSelected>>", lambda _: self.cargar_libros())

        # ── Tabla ──
        cols = ("ID", "Título", "Autor", "Año", "ISBN", "Disponibles", "Total")
        tree_frame = tk.Frame(self, bg=self.BG)
        tree_frame.pack(fill="both", expand=True, padx=20, pady=(0, 10))

        style = ttk.Style()
        style.theme_use("clam")
        style.configure("Lib.Treeview",
                        background=self.ROW_A, foreground=self.TEXT,
                        rowheight=28, fieldbackground=self.ROW_A,
                        bordercolor=self.BG, relief="flat", font=self.FONT_MAIN)
        style.configure("Lib.Treeview.Heading",
                        background=self.PANEL_BG, foreground=self.ACCENT,
                        font=self.FONT_BOLD, relief="flat")
        style.map("Lib.Treeview", background=[("selected", self.SEL_BG)])

        self.tree = ttk.Treeview(tree_frame, columns=cols, show="headings",
                                 style="Lib.Treeview", selectmode="browse")

        widths = {"ID": 40, "Título": 220, "Autor": 160, "Año": 60,
                  "ISBN": 130, "Disponibles": 90, "Total": 60}
        for c in cols:
            self.tree.heading(c, text=c)
            self.tree.column(c, width=widths[c], anchor="center" if c != "Título" and c != "Autor" else "w")

        scrollbar = ttk.Scrollbar(tree_frame, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scrollbar.set)
        self.tree.pack(side="left", fill="both", expand=True)
        scrollbar.pack(side="right", fill="y")

        self.tree.tag_configure("par", background=self.ROW_A)
        self.tree.tag_configure("impar", background=self.ROW_B)
        self.tree.tag_configure("nodisponible", foreground=self.ERROR_CLR)

        # ── Botones de acción (solo bibliotecario) ──
        if self.api.is_bibliotecario():
            btn_frame = tk.Frame(self, bg=self.BG)
            btn_frame.pack(fill="x", padx=20, pady=(0, 16))

            tk.Button(btn_frame, text="✏️  Editar",
                      font=self.FONT_MAIN, cursor="hand2",
                      bg=self.PANEL_BG, fg=self.TEXT,
                      activebackground=self.SEL_BG, relief="flat", bd=0,
                      padx=12, pady=5, command=self._dialogo_editar).pack(side="left", padx=(0, 8))

            tk.Button(btn_frame, text="🗑️  Eliminar",
                      font=self.FONT_MAIN, cursor="hand2",
                      bg=self.PANEL_BG, fg=self.ERROR_CLR,
                      activebackground=self.SEL_BG, relief="flat", bd=0,
                      padx=12, pady=5, command=self._eliminar).pack(side="left")

        # ── Status bar ──
        self.status_var = tk.StringVar(value="Cargando…")
        tk.Label(self, textvariable=self.status_var, font=("Segoe UI", 9),
                 bg=self.BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=20, pady=(0, 8))

    # ── Carga de datos ──────────────────────────────────────────
    def cargar_libros(self):
        buscar  = self.buscar_var.get().strip() if hasattr(self, "buscar_var") else ""
        filtro  = self.filtro_var.get() if hasattr(self, "filtro_var") else "Todos"
        disp    = {"Disponibles": "1", "No disponibles": "0"}.get(filtro, "")

        resultado = self.api.get_libros(buscar=buscar, disponible=disp)

        for row in self.tree.get_children():
            self.tree.delete(row)

        if not resultado.get("success"):
            self.status_var.set(f"Error: {resultado.get('error')}")
            return

        libros = resultado["data"]["libros"]
        for i, libro in enumerate(libros):
            tag = "par" if i % 2 == 0 else "impar"
            if not libro.get("disponible"):
                tag = "nodisponible"
            self.tree.insert("", "end", iid=str(libro["id"]), tags=(tag,),
                             values=(
                                 libro["id"],
                                 libro["titulo"],
                                 libro["autor"],
                                 libro["anio"],
                                 libro.get("isbn") or "—",
                                 libro["cantidad_disponible"],
                                 libro["cantidad_total"],
                             ))

        total = resultado["data"]["total"]
        self.status_var.set(f"{total} libro{'s' if total != 1 else ''} encontrado{'s' if total != 1 else ''}  •  {sum(1 for l in libros if l.get('disponible'))} disponibles")

    # ── Diálogo crear / editar ──────────────────────────────────
    def _dialogo_crear(self):
        self._dialogo_form()

    def _dialogo_editar(self):
        sel = self.tree.focus()
        if not sel:
            messagebox.showwarning("Sin selección", "Selecciona un libro para editar.", parent=self.winfo_toplevel())
            return
        r = self.api.get(f"/libros/{sel}")
        if r.get("success"):
            self._dialogo_form(r["data"])

    def _dialogo_form(self, libro: dict = None):
        es_edicion = libro is not None
        titulo_dlg = "Editar libro" if es_edicion else "Nuevo libro"

        dlg = tk.Toplevel(self.winfo_toplevel())
        dlg.title(titulo_dlg)
        dlg.geometry("420x560")
        dlg.resizable(False, False)
        dlg.configure(bg=self.PANEL_BG)
        dlg.grab_set()

        tk.Label(dlg, text=titulo_dlg, font=self.FONT_H1,
                 bg=self.PANEL_BG, fg=self.TEXT).pack(pady=(20, 14))

        campos = [
            ("titulo",       "Título *",         libro.get("titulo", "")             if libro else ""),
            ("autor",        "Autor *",           libro.get("autor", "")              if libro else ""),
            ("anio",         "Año *",             str(libro.get("anio", ""))          if libro else ""),
            ("isbn",         "ISBN",              libro.get("isbn", "") or ""         if libro else ""),
            ("descripcion",  "Descripción",       libro.get("descripcion", "") or ""  if libro else ""),
            ("cantidad_total","Cantidad total *", str(libro.get("cantidad_total", 1)) if libro else "1"),
        ]

        vars_ = {}
        for key, label, valor in campos:
            tk.Label(dlg, text=label, font=("Segoe UI", 9),
                     bg=self.PANEL_BG, fg=self.TEXT_DIM, anchor="w").pack(fill="x", padx=28)
            v = tk.StringVar(value=valor)
            tk.Entry(dlg, textvariable=v, font=self.FONT_MAIN,
                     bg=self.ENTRY_BG, fg=self.TEXT,
                     insertbackground=self.TEXT, relief="flat", bd=0
                     ).pack(fill="x", padx=28, ipady=6, pady=(2, 8))
            vars_[key] = v

        msg_var = tk.StringVar()
        tk.Label(dlg, textvariable=msg_var, font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.ERROR_CLR, wraplength=360).pack()

        def guardar():
            data = {k: v.get().strip() for k, v in vars_.items()}
            for campo in ["titulo", "autor", "anio", "cantidad_total"]:
                if not data[campo]:
                    msg_var.set(f"El campo '{campo}' es obligatorio.")
                    return
            data["anio"]           = int(data["anio"])
            data["cantidad_total"] = int(data["cantidad_total"])
            if not data["isbn"]:        del data["isbn"]
            if not data["descripcion"]: del data["descripcion"]

            if es_edicion:
                r = self.api.actualizar_libro(libro["id"], data)
            else:
                r = self.api.crear_libro(data)

            if r.get("success"):
                dlg.destroy()
                self.cargar_libros()
            else:
                msg_var.set(r.get("error", "Error al guardar."))

        tk.Button(dlg, text="Guardar",
                  font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white",
                  activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=8,
                  command=guardar).pack(pady=(6, 16))

    def _eliminar(self):
        sel = self.tree.focus()
        if not sel:
            messagebox.showwarning("Sin selección", "Selecciona un libro para eliminar.", parent=self.winfo_toplevel())
            return
        titulo = self.tree.item(sel, "values")[1]
        if not messagebox.askyesno("Confirmar", f"¿Eliminar el libro:\n«{titulo}»?", parent=self.winfo_toplevel()):
            return
        r = self.api.eliminar_libro(int(sel))
        if r.get("success"):
            self.cargar_libros()
        else:
            messagebox.showerror("Error", r.get("error", "No se pudo eliminar."), parent=self.winfo_toplevel())