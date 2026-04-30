import tkinter as tk
from dotenv import load_dotenv
import os

load_dotenv()

def main():
    root = tk.Tk()
    root.title("Sistema de Biblioteca")
    root.geometry("800x600")
    
    label = tk.Label(root, text="Bienvenido al Sistema de Biblioteca", font=("Arial", 20))
    label.pack(pady=50)
    
    root.mainloop()

if __name__ == "__main__":
    main()
