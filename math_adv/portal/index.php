<?php
// Math Advantage - Portal Index
session_start();
require_once '../php/config.php';

// Verificar si hay una sesión activa y redirigir al dashboard correspondiente
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $redirect_map = [
        'student' => 'student/dashboard.php',
        'parent' => 'parent/dashboard.php',
        'teacher' => 'teacher/dashboard.php',
        'admin' => 'admin/dashboard.php'
    ];
    
    if (isset($redirect_map[$_SESSION['user_type']])) {
        header('Location: ' . $redirect_map[$_SESSION['user_type']]);
        exit;
    }
}

// Si no hay sesión, redirigir a la página de bienvenida
header('Location: welcome-new.php');
exit;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Digital - Math Advantage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="logo-portal me-2">
                    <span class="logo-symbol">∑</span>
                </div>
                <span>Math Advantage Portal</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user"></i>
                        </div>
                        <?php echo htmlspecialchars($user_name); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Perfil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuració</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Tancar Sessió</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <?php if ($user_role === 'student'): ?>
                        <!-- Student Menu -->
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#materials">
                                    <i class="fas fa-book me-2"></i>Materials de Classe
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#schedule">
                                    <i class="fas fa-calendar me-2"></i>Horari Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#progress">
                                    <i class="fas fa-chart-line me-2"></i>El Meu Progrés
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#homework">
                                    <i class="fas fa-tasks me-2"></i>Tasques i Deures
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#messages">
                                    <i class="fas fa-comments me-2"></i>Missatges
                                </a>
                            </li>
                        </ul>
                    <?php elseif ($user_role === 'family'): ?>
                        <!-- Family Menu -->
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Familiar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#children">
                                    <i class="fas fa-users me-2"></i>Els Meus Fills
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#payments">
                                    <i class="fas fa-credit-card me-2"></i>Pagaments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#reports">
                                    <i class="fas fa-chart-bar me-2"></i>Informes Acadèmics
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#communication">
                                    <i class="fas fa-envelope me-2"></i>Comunicació
                                </a>
                            </li>
                        </ul>
                    <?php elseif ($user_role === 'teacher'): ?>
                        <!-- Teacher Menu -->
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Docent
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#classes">
                                    <i class="fas fa-chalkboard me-2"></i>Les Meves Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#evaluations">
                                    <i class="fas fa-clipboard-check me-2"></i>Avaluacions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#resources">
                                    <i class="fas fa-folder me-2"></i>Recursos Educatius
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#students">
                                    <i class="fas fa-user-graduate me-2"></i>Els Meus Alumnes
                                </a>
                            </li>
                        </ul>
                    <?php elseif ($user_role === 'admin'): ?>
                        <!-- Admin Menu -->
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#users">
                                    <i class="fas fa-users-cog me-2"></i>Gestió d'Usuaris
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#finances">
                                    <i class="fas fa-chart-pie me-2"></i>Control Financer
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#reports">
                                    <i class="fas fa-file-alt me-2"></i>Informes i Estadístiques
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#settings">
                                    <i class="fas fa-cogs me-2"></i>Configuració Sistema
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <?php 
                        switch($user_role) {
                            case 'student': echo 'Portal Alumne'; break;
                            case 'family': echo 'Portal Família'; break;
                            case 'teacher': echo 'Portal Docent'; break;
                            case 'admin': echo 'Portal Administració'; break;
                            default: echo 'Portal Digital';
                        }
                        ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-bell me-1"></i>Notificacions
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Content Area -->
                <div id="portal-content">
                    <?php include 'dashboard/' . $user_role . '_dashboard.php'; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/portal.js"></script>
</body>
</html>