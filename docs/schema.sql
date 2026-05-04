-- ============================================================
-- Sistema de Gestión de Biblioteca
-- Schema de Base de Datos MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE biblioteca;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- bcrypt hash
    tipo ENUM('Bibliotecario', 'Lector') NOT NULL DEFAULT 'Lector',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: libros
-- ============================================================
CREATE TABLE IF NOT EXISTS libros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    anio YEAR NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL UNIQUE,
    descripcion TEXT DEFAULT NULL,
    cantidad_total INT UNSIGNED NOT NULL DEFAULT 1,
    cantidad_disponible INT UNSIGNED NOT NULL DEFAULT 1,
    disponible TINYINT(1) GENERATED ALWAYS AS (cantidad_disponible > 0) STORED,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_cantidad CHECK (
        cantidad_disponible <= cantidad_total
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: prestamos
-- ============================================================
CREATE TABLE IF NOT EXISTS prestamos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    libro_id INT UNSIGNED NOT NULL,
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_prevista DATETIME NOT NULL,
    fecha_devolucion_real DATETIME DEFAULT NULL,
    estado ENUM(
        'Activo',
        'Devuelto',
        'Retrasado'
    ) NOT NULL DEFAULT 'Activo',
    notas TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: codigos_qr  (Fase Extra – QR)
-- ============================================================
CREATE TABLE IF NOT EXISTS codigos_qr (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('Libro', 'Usuario') NOT NULL,
    referencia_id INT UNSIGNED NOT NULL,
    contenido_qr VARCHAR(100) NOT NULL UNIQUE, -- ej: "LIBRO:5"
    imagen_path VARCHAR(300) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- ÍNDICES (rendimiento en búsquedas frecuentes)
-- ============================================================
CREATE INDEX idx_libros_autor ON libros (autor);

CREATE INDEX idx_libros_titulo ON libros (titulo);

CREATE INDEX idx_prestamos_estado ON prestamos (estado);

CREATE INDEX idx_prestamos_usuario ON prestamos (usuario_id);

CREATE INDEX idx_prestamos_libro ON prestamos (libro_id);

-- ============================================================
-- TRIGGER: actualizar cantidad_disponible al crear préstamo
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_prestamo_insert
AFTER INSERT ON prestamos
FOR EACH ROW
BEGIN
  IF NEW.estado = 'Activo' THEN
    UPDATE libros
    SET cantidad_disponible = cantidad_disponible - 1
    WHERE id = NEW.libro_id;
  END IF;
END$$

-- ============================================================
-- TRIGGER: restaurar cantidad_disponible al devolver
-- ============================================================
CREATE TRIGGER trg_prestamo_devolucion
AFTER UPDATE ON prestamos
FOR EACH ROW
BEGIN
  IF OLD.estado = 'Activo' AND NEW.estado = 'Devuelto' THEN
    UPDATE libros
    SET cantidad_disponible = cantidad_disponible + 1
    WHERE id = NEW.libro_id;
  END IF;
END$$

DELIMITER;

-- ============================================================
-- DATOS INICIALES (seed)
-- ============================================================

-- Bibliotecario por defecto  (password: admin1234)
INSERT INTO
    usuarios (nombre, email, password, tipo)
VALUES (
        'Administrador',
        'admin@biblioteca.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Bibliotecario'
    );

-- Lector de ejemplo  (password: lector1234)
INSERT INTO
    usuarios (nombre, email, password, tipo)
VALUES (
        'María López',
        'maria@biblioteca.com',
        '$2y$10$TKh8H1.PFbuSpgvguEe/CuJrcYs8yR25q1fgCxmjSCDJ2w.cBNVQe',
        'Lector'
    );

-- Libros de ejemplo
INSERT INTO
    libros (
        titulo,
        autor,
        anio,
        isbn,
        descripcion,
        cantidad_total,
        cantidad_disponible
    )
VALUES (
        'Don Quijote de la Mancha',
        'Miguel de Cervantes',
        1605,
        '978-84-9754-000-0',
        'La novela más universal de la literatura española.',
        3,
        3
    ),
    (
        'Cien años de soledad',
        'Gabriel García Márquez',
        1967,
        '978-84-376-0494-6',
        'Obra cumbre del realismo mágico latinoamericano.',
        2,
        2
    ),
    (
        '1984',
        'George Orwell',
        1949,
        '978-84-450-7596-2',
        'Distopía clásica sobre el totalitarismo y la vigilancia.',
        2,
        2
    ),
    (
        'El Principito',
        'Antoine de Saint-Exupéry',
        1943,
        '978-84-665-0494-2',
        'Fábula poética sobre la amistad, la infancia y la soledad.',
        4,
        4
    ),
    (
        'Harry Potter y la Piedra Filosofal',
        'J.K. Rowling',
        1997,
        '978-84-9838-185-4',
        'El inicio de la saga de Harry Potter.',
        3,
        3
    ),
    (
        'El nombre de la rosa',
        'Umberto Eco',
        1980,
        '978-84-322-0246-0',
        'Novela de misterio ambientada en una abadía medieval.',
        1,
        1
    ),
    (
        'Fahrenheit 451',
        'Ray Bradbury',
        1953,
        '978-84-450-7770-6',
        'Un bombero cuyo trabajo es quemar libros en una sociedad futura.',
        2,
        2
    );