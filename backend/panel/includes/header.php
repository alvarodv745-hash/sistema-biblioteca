<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administración — Biblioteca</title>
    <style>
        /* Variables CSS — mismo tema oscuro del cliente Python */
        :root {
            --bg: #1e1e2e;
            --sidebar: #181825;
            --panel: #2a2a3e;
            --accent: #7c6ff7;
            --text: #cdd6f4;
            --text-dim: #6c7086;
            --entry: #313244;
            --error: #f38ba8;
            --success: #a6e3a1;
            --warn: #fab387;
            --sel: #45475a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: var(--sidebar);
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar-logo {
            text-align: center;
            padding: 0 16px 24px;
            border-bottom: 1px solid var(--panel);
        }

        .sidebar-logo .icon {
            font-size: 2rem;
        }

        .sidebar-logo h2 {
            font-size: 1rem;
            color: var(--text);
            margin-top: 4px;
        }

        .sidebar-logo p {
            font-size: 0.7rem;
            color: var(--text-dim);
        }

        .nav {
            padding: 16px 0;
            flex: 1;
        }

        .nav a {
            display: block;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.95rem;
            transition: background 0.15s;
        }

        .nav a:hover,
        .nav a.active {
            background: var(--sel);
            color: var(--accent);
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--panel);
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        .sidebar-footer strong {
            color: var(--accent);
            display: block;
        }

        .sidebar-footer a {
            color: var(--text-dim);
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
        }

        .sidebar-footer a:hover {
            color: var(--error);
        }

        /* Main content */
        .main {
            margin-left: 220px;
            flex: 1;
            padding: 28px;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text);
        }

        /* Cards de stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--panel);
            border-radius: 8px;
            padding: 20px;
        }

        .stat-card .label {
            font-size: 0.8rem;
            color: var(--text-dim);
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-top: 4px;
        }

        .stat-card .sub {
            font-size: 0.8rem;
            color: var(--text-dim);
            margin-top: 2px;
        }

        /* Tabla */
        .table-wrap {
            background: var(--panel);
            border-radius: 8px;
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--sel);
        }

        .table-header h3 {
            font-size: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th {
            background: var(--sel);
            padding: 10px 16px;
            text-align: left;
            color: var(--accent);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 10px 16px;
            border-bottom: 1px solid var(--sel);
            color: var(--text);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-ok {
            background: rgba(166, 227, 161, 0.15);
            color: var(--success);
        }

        .badge-error {
            background: rgba(243, 139, 168, 0.15);
            color: var(--error);
        }

        .badge-warn {
            background: rgba(250, 179, 135, 0.15);
            color: var(--warn);
        }

        .badge-accent {
            background: rgba(124, 111, 247, 0.15);
            color: var(--accent);
        }

        /* Formularios */
        .form-card {
            background: var(--panel);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            max-width: 520px;
        }

        .form-card h3 {
            margin-bottom: 16px;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-dim);
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            background: var(--entry);
            border: 1px solid var(--sel);
            border-radius: 5px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
        }

        /* Botones */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: #9d98f5;
        }

        .btn-danger {
            background: transparent;
            color: var(--error);
            border: 1px solid var(--error);
        }

        .btn-danger:hover {
            background: rgba(243, 139, 168, 0.1);
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 0.78rem;
        }

        /* Alerts */
        .alert {
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .alert-ok {
            background: rgba(166, 227, 161, 0.12);
            color: var(--success);
            border-left: 3px solid var(--success);
        }

        .alert-err {
            background: rgba(243, 139, 168, 0.12);
            color: var(--error);
            border-left: 3px solid var(--error);
        }

        /* Search bar */
        .search-bar {
            display: flex;
            gap: 10px;
            padding: 14px 20px;
            background: var(--panel);
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .search-bar input,
        .search-bar select {
            background: var(--entry);
            border: 1px solid var(--sel);
            border-radius: 5px;
            padding: 7px 12px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
        }

        .search-bar input {
            flex: 1;
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="icon">📚</div>
            <h2>Biblioteca</h2>
            <p>Panel de Administración</p>
        </div>
        <nav class="nav">
            <a href="dashboard.php" <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'class="active"' : '' ?>>🏠
                Dashboard</a>
            <a href="libros.php" <?= (basename($_SERVER['PHP_SELF']) === 'libros.php') ? 'class="active"' : '' ?>>📚
                Libros</a>
            <a href="usuarios.php" <?= (basename($_SERVER['PHP_SELF']) === 'usuarios.php') ? 'class="active"' : '' ?>>👥
                Usuarios</a>
            <a href="prestamos.php" <?= (basename($_SERVER['PHP_SELF']) === 'prestamos.php') ? 'class="active"' : '' ?>>📋
                Préstamos</a>
        </nav>
        <div class="sidebar-footer">
            <strong><?= htmlspecialchars($_SESSION['panel_usuario']['nombre'] ?? '') ?></strong>
            <?= htmlspecialchars($_SESSION['panel_usuario']['tipo'] ?? '') ?>
            <br>
            <a style="margin-left: 46px; margin-top: 12px;" href="logout.php">⏻ Cerrar sesión</a>
        </div>
    </aside>

    <main class="main">