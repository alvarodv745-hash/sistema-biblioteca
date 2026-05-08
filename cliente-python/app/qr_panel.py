"""
Panel de códigos QR.
- Ver y descargar QR de cualquier libro o usuario
- Escanear QR con webcam y procesar la acción
"""

import tkinter as tk
from tkinter import ttk, messagebox
import threading
import urllib.request
import io

# Variables para dependencias opcionales (evitar NameError si faltan)
Image, ImageTk = None, None
cv2, qr_decode = None, None

try:
    from PIL import Image, ImageTk
    PIL_OK = True
except ImportError:
    PIL_OK = False

try:
    import cv2
    from pyzbar.pyzbar import decode as qr_decode
    CAM_OK = True
except ImportError:
    CAM_OK = False

from app.utils.api_client import APIClient


class QRPanel(tk.Frame):

    BG        = "#1e1e2e"
    PANEL_BG  = "#2a2a3e"
    ACCENT    = "#7c6ff7"
    ACCENT_H  = "#9d98f5"
    TEXT      = "#cdd6f4"
    TEXT_DIM  = "#6c7086"
    ENTRY_BG  = "#313244"
    ERROR_CLR = "#f38ba8"
    SUCCESS   = "#a6e3a1"
    WARN_CLR  = "#fab387"
    SEL_BG    = "#45475a"
    FONT_MAIN = ("Segoe UI", 10)
    FONT_BOLD = ("Segoe UI", 10, "bold")
    FONT_H1   = ("Segoe UI", 14, "bold")

    def __init__(self, parent):
        super().__init__(parent, bg=self.BG)
        self.api      = APIClient()
        self._cam_activa = False
        self._cam_thread = None
        self._cap        = None
        self._build()

    def _build(self):
        # ── Cabecera ──
        header = tk.Frame(self, bg=self.BG)
        header.pack(fill="x", padx=20, pady=(18, 10))
        tk.Label(header, text="📱  Códigos QR",
                 font=self.FONT_H1, bg=self.BG, fg=self.TEXT).pack(side="left")

        # ── Dos columnas ──
        body = tk.Frame(self, bg=self.BG)
        body.pack(fill="both", expand=True, padx=20, pady=(0, 16))

        # ── Columna izquierda: Generar QR ──
        left = tk.LabelFrame(body, text="  Generar QR  ",
                             bg=self.PANEL_BG, fg=self.ACCENT,
                             font=self.FONT_BOLD, bd=1, relief="groove")
        left.pack(side="left", fill="both", expand=True, padx=(0, 10))

        tk.Label(left, text="Tipo:", font=self.FONT_MAIN,
                 bg=self.PANEL_BG, fg=self.TEXT_DIM).pack(anchor="w", padx=14, pady=(12, 2))
        self.tipo_var = tk.StringVar(value="Libro")
        cb_tipo = ttk.Combobox(left, textvariable=self.tipo_var,
                               values=["Libro", "Usuario"],
                               state="readonly", font=self.FONT_MAIN, width=14)
        cb_tipo.pack(anchor="w", padx=14)

        tk.Label(left, text="ID:", font=self.FONT_MAIN,
                 bg=self.PANEL_BG, fg=self.TEXT_DIM).pack(anchor="w", padx=14, pady=(10, 2))
        self.id_var = tk.StringVar()
        tk.Entry(left, textvariable=self.id_var, font=self.FONT_MAIN,
                 bg=self.ENTRY_BG, fg=self.TEXT,
                 insertbackground=self.TEXT, relief="flat", bd=0, width=10
                 ).pack(anchor="w", padx=14, ipady=6)

        tk.Button(left, text="Generar QR",
                  font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white",
                  activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=7,
                  command=self._generar_qr).pack(padx=14, pady=12, anchor="w")

        # Imagen QR generada
        self.qr_label = tk.Label(left, bg=self.PANEL_BG,
                                 text="El QR aparecerá aquí",
                                 font=("Segoe UI", 9), fg=self.TEXT_DIM)
        self.qr_label.pack(padx=14, pady=(0, 8))

        # Info del objeto
        self.qr_info_var = tk.StringVar()
        tk.Label(left, textvariable=self.qr_info_var,
                 font=("Segoe UI", 9), bg=self.PANEL_BG,
                 fg=self.SUCCESS, wraplength=240, justify="center").pack(padx=14, pady=(0, 14))

        # ── Columna derecha: Escáner webcam ──
        right = tk.LabelFrame(body, text="  Escáner de Webcam  ",
                              bg=self.PANEL_BG, fg=self.ACCENT,
                              font=self.FONT_BOLD, bd=1, relief="groove")
        right.pack(side="left", fill="both", expand=True)

        if not CAM_OK:
            tk.Label(right,
                     text="⚠️  Módulos de cámara no instalados\n\n"
                          "Instala con:\npip install opencv-python pyzbar\n\n"
                          "(Puede requerir 'zbar' en Linux:\nsudo apt install libzbar0)",
                     font=self.FONT_MAIN, bg=self.PANEL_BG,
                     fg=self.WARN_CLR, justify="center", wraplength=240
                     ).pack(expand=True, pady=40)
            return

        # Vista previa de webcam
        self.cam_canvas = tk.Canvas(right, width=320, height=240,
                                    bg="#000000", highlightthickness=0)
        self.cam_canvas.pack(padx=14, pady=(14, 6))

        # Botones cámara
        btn_row = tk.Frame(right, bg=self.PANEL_BG)
        btn_row.pack(pady=(0, 8))

        self.btn_cam = tk.Button(btn_row, text="▶  Iniciar cámara",
                                 font=self.FONT_BOLD, cursor="hand2",
                                 bg=self.ACCENT, fg="white",
                                 activebackground=self.ACCENT_H,
                                 relief="flat", bd=0, padx=12, pady=7,
                                 command=self._toggle_camara)
        self.btn_cam.pack(side="left", padx=(0, 8))

        tk.Button(btn_row, text="📷  Capturar",
                  font=self.FONT_MAIN, cursor="hand2",
                  bg=self.PANEL_BG, fg=self.TEXT,
                  activebackground=self.SEL_BG,
                  relief="flat", bd=0, padx=10, pady=7,
                  command=self._capturar_qr).pack(side="left")

        # Resultado del escaneo
        tk.Label(right, text="Resultado del escaneo:",
                 font=self.FONT_BOLD, bg=self.PANEL_BG, fg=self.TEXT_DIM).pack(anchor="w", padx=14)

        self.scan_result_var = tk.StringVar(value="Apunta la cámara a un código QR y pulsa Capturar")
        tk.Label(right, textvariable=self.scan_result_var,
                 font=("Segoe UI", 9), bg=self.PANEL_BG,
                 fg=self.TEXT, wraplength=300, justify="left"
                 ).pack(anchor="w", padx=14, pady=(4, 8))

        # Botón de acción rápida (aparece tras escanear)
        self.btn_accion = tk.Button(right, text="",
                                    font=self.FONT_BOLD, cursor="hand2",
                                    bg=self.PANEL_BG, fg=self.ACCENT,
                                    relief="flat", bd=0, padx=12, pady=6)
        # No empaquetado aún; aparece cuando hay resultado

    # ── Generar QR ───────────────────────────────────────────────
    def _generar_qr(self):
        tipo = self.tipo_var.get()
        obj_id = self.id_var.get().strip()

        if not obj_id.isdigit():
            self.qr_info_var.set("⚠️ Introduce un ID numérico válido.")
            return

        endpoint = f"/qr/libro/{obj_id}" if tipo == "Libro" else f"/qr/usuario/{obj_id}"
        r = self.api.get(endpoint)

        if not r.get("success"):
            self.qr_info_var.set(f"Error: {r.get('error')}")
            return

        data = r["data"]
        url  = data.get("url_imagen", "")

        if not PIL_OK:
            self.qr_info_var.set(
                f"QR generado ✓\nContenido: {data.get('contenido_qr')}\n"
                f"(Instala Pillow para ver la imagen:\npip install Pillow)"
            )
            return

        # Descargar imagen QR en un hilo aparte
        def cargar_imagen():
            try:
                with urllib.request.urlopen(url, timeout=5) as resp:
                    img_data = resp.read()
                img = Image.open(io.BytesIO(img_data)).resize((200, 200))
                
                # Crear PhotoImage en el hilo principal
                def update_ui(p_img=img):
                    photo = ImageTk.PhotoImage(p_img)
                    self.qr_label.config(image=photo, text="")
                    self.qr_label.image = photo  # evitar GC
                    
                    nombre = data.get("libro", {}).get("titulo") or \
                             data.get("usuario", {}).get("nombre") or obj_id
                    self.qr_info_var.set(f"✓ {tipo}: {nombre}\nCódigo: {data.get('contenido_qr')}")

                self.after(0, update_ui)
            except Exception as e:
                self.after(0, lambda: self.qr_info_var.set(f"No se pudo cargar la imagen.\n{e}"))

        threading.Thread(target=cargar_imagen, daemon=True).start()

    # ── Cámara ───────────────────────────────────────────────────
    def _toggle_camara(self):
        if self._cam_activa:
            self._detener_camara()
        else:
            self._iniciar_camara()

    def _iniciar_camara(self):
        if not CAM_OK:
            return
        self._cap = cv2.VideoCapture(0)
        if not self._cap.isOpened():
            messagebox.showerror("Error", "No se encontró ninguna webcam.", parent=self.winfo_toplevel())
            return
        self._cam_activa = True
        self.btn_cam.config(text="⏹  Detener cámara", bg="#585b70")
        self._cam_thread = threading.Thread(target=self._loop_camara, daemon=True)
        self._cam_thread.start()

    def _detener_camara(self):
        self._cam_activa = False
        if self._cap:
            self._cap.release()
            self._cap = None
        self.btn_cam.config(text="▶  Iniciar cámara", bg=self.ACCENT)
        if hasattr(self, "cam_canvas"):
            self.cam_canvas.delete("all")

    def _loop_camara(self):
        """Lee frames de la webcam y los muestra en el canvas."""
        if not PIL_OK or not CAM_OK:
            return
        while self._cam_activa and self._cap and self._cap.isOpened():
            ret, frame = self._cap.read()
            if not ret:
                break
            frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            img = Image.fromarray(frame_rgb).resize((320, 240))

            # IMPORTANTE: PhotoImage debe crearse en el hilo principal
            def update_canvas(p_img=img):
                try:
                    if not self.winfo_exists() or not self._cam_activa:
                        return
                    photo = ImageTk.PhotoImage(p_img)
                    self.cam_canvas.create_image(0, 0, anchor="nw", image=photo)
                    self.cam_canvas.image = photo # evitar GC
                except Exception:
                    pass

            self.after(0, update_canvas)

    def _capturar_qr(self):
        """Captura un frame e intenta leer el QR."""
        if not CAM_OK:
            messagebox.showerror("Error", "Librerías de cámara no instaladas.", parent=self.winfo_toplevel())
            return
        if not self._cam_activa or not self._cap:
            messagebox.showwarning("Cámara inactiva", "Inicia la cámara primero.", parent=self.winfo_toplevel())
            return

        ret, frame = self._cap.read()
        if not ret:
            self.scan_result_var.set("⚠️ No se pudo capturar el frame.")
            return

        codigos = qr_decode(frame)
        if not codigos:
            self.scan_result_var.set("⚠️ No se detectó ningún código QR en este frame. Inténtalo de nuevo.")
            return

        contenido = codigos[0].data.decode("utf-8").strip()
        self.scan_result_var.set(f"QR detectado: {contenido}\nConsultando API…")
        self.update()

        r = self.api.post("/qr/procesar", {"contenido": contenido})

        if not r.get("success"):
            self.scan_result_var.set(f"Error: {r.get('error')}")
            return

        data = r["data"]
        tipo = data.get("tipo", "")

        if tipo == "Libro":
            disp = "✅ Disponible" if data.get("disponible") else "❌ No disponible"
            texto = (
                f"📚 LIBRO ENCONTRADO\n"
                f"Título: {data.get('titulo')}\n"
                f"Autor:  {data.get('autor')}\n"
                f"Año:    {data.get('anio')}\n"
                f"Estado: {disp}  ({data.get('cantidad_disponible')} uds.)"
            )
            self.scan_result_var.set(texto)

            # Botón para crear préstamo rápido
            if self.api.is_bibliotecario() and data.get("disponible"):
                self.btn_accion.config(
                    text=f"＋ Prestar «{data.get('titulo')[:30]}»",
                    command=lambda d=data: self._prestamo_rapido(d)
                )
                self.btn_accion.pack(padx=14, pady=(0, 12), anchor="w")
            else:
                self.btn_accion.pack_forget()

        elif tipo == "Usuario":
            activo = "✅ Activo" if data.get("activo") else "❌ Inactivo"
            texto = (
                f"👤 USUARIO ENCONTRADO\n"
                f"Nombre: {data.get('nombre')}\n"
                f"Email:  {data.get('email')}\n"
                f"Rol:    {data.get('rol')}\n"
                f"Estado: {activo}"
            )
            self.scan_result_var.set(texto)
            self.btn_accion.pack_forget()

    def _prestamo_rapido(self, libro_data: dict):
        """Abre diálogo rápido para elegir usuario y confirmar préstamo."""
        res_u = self.api.get_usuarios()
        if not res_u.get("success"):
            messagebox.showerror("Error", "No se pudieron cargar los usuarios.", parent=self.winfo_toplevel())
            return

        usuarios = res_u["data"]["usuarios"]
        if not usuarios:
            messagebox.showwarning("Sin usuarios", "No hay usuarios registrados.", parent=self.winfo_toplevel())
            return

        dlg = tk.Toplevel(self.winfo_toplevel())
        dlg.title("Préstamo rápido por QR")
        dlg.geometry("380x260")
        dlg.resizable(False, False)
        dlg.configure(bg=self.PANEL_BG)
        dlg.grab_set()

        tk.Label(dlg, text="Préstamo por QR", font=self.FONT_H1,
                 bg=self.PANEL_BG, fg=self.TEXT).pack(pady=(18, 6))

        tk.Label(dlg, text=f"📚 {libro_data.get('titulo')}",
                 font=self.FONT_BOLD, bg=self.PANEL_BG, fg=self.ACCENT,
                 wraplength=320).pack(pady=(0, 12))

        tk.Label(dlg, text="Selecciona el usuario:",
                 font=self.FONT_MAIN, bg=self.PANEL_BG, fg=self.TEXT_DIM).pack(anchor="w", padx=28)

        usuario_var = tk.StringVar()
        nombres = [f"{u['id']} — {u['nombre']} ({u['tipo']})" for u in usuarios]
        ttk.Combobox(dlg, textvariable=usuario_var, values=nombres,
                     state="readonly", font=self.FONT_MAIN
                     ).pack(fill="x", padx=28, pady=(4, 12))

        msg_var = tk.StringVar()
        tk.Label(dlg, textvariable=msg_var, font=("Segoe UI", 9),
                 bg=self.PANEL_BG, fg=self.ERROR_CLR).pack()

        def confirmar():
            if not usuario_var.get():
                msg_var.set("Selecciona un usuario.")
                return
            uid = int(usuario_var.get().split(" — ")[0])
            r = self.api.crear_prestamo(uid, libro_data["id"])
            if r.get("success"):
                dlg.destroy()
                messagebox.showinfo("✅ Préstamo registrado",
                                    f"Préstamo de «{libro_data.get('titulo')}» registrado correctamente.",
                                    parent=self.winfo_toplevel())
                self.scan_result_var.set("Préstamo registrado ✓")
            else:
                msg_var.set(r.get("error", "Error al crear el préstamo."))

        tk.Button(dlg, text="Confirmar Préstamo",
                  font=self.FONT_BOLD, cursor="hand2",
                  bg=self.ACCENT, fg="white",
                  activebackground=self.ACCENT_H,
                  relief="flat", bd=0, padx=14, pady=8,
                  command=confirmar).pack(pady=(4, 16))

    def destroy(self):
        """Asegurar que la cámara se detiene al cerrar."""
        self._detener_camara()
        super().destroy()