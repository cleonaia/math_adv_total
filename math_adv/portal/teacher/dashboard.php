<?php
// Math Advantage - Teacher Portal Dashboard
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

// Incluir dependencias
require_once '../auth.php';
require_once '../../php/classes/Database.php';
require_once '../../php/classes/Teacher.php';

$database = new Database();
$pdo = $database->getConnection();

// Obtener información del profesor
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener clases asignadas al profesor
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(s.id) as student_count,
           AVG(CASE WHEN s.payment_status = 'paid' THEN 1 ELSE 0 END) * 100 as payment_rate
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.schedule ASC
");
$stmt->execute([$_SESSION['user_id']]);
$teacher_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estudiantes del profesor
$stmt = $pdo->prepare("
    SELECT s.*, c.name as class_name, c.schedule,
           COALESCE(sa.activity_count, 0) as activity_count
    FROM students s
    INNER JOIN classes c ON s.class_id = c.id
    LEFT JOIN (
        SELECT student_id, COUNT(*) as activity_count
        FROM student_activities
        GROUP BY student_id
    ) sa ON s.id = sa.student_id
    WHERE c.teacher_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener próximas clases de hoy
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(s.id) as student_count
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    WHERE c.teacher_id = ? 
    AND (c.schedule LIKE ? OR c.schedule LIKE ?)
    GROUP BY c.id
    ORDER BY c.schedule ASC
    LIMIT 3
");
$stmt->execute([$_SESSION['user_id'], '%' . date('l') . '%', '%' . date('N') . '%']);
$today_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades recientes del profesor
$stmt = $pdo->prepare("
    SELECT sa.*, s.first_name, s.last_name, c.name as class_name
    FROM student_activities sa
    INNER JOIN students s ON sa.student_id = s.id
    INNER JOIN classes c ON s.class_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY sa.created_at DESC
    LIMIT 15
");
$stmt->execute([$_SESSION['user_id']]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
$total_classes = count($teacher_classes);
$total_students = array_sum(array_column($teacher_classes, 'student_count'));
$avg_payment_rate = $total_classes > 0 ? array_sum(array_column($teacher_classes, 'payment_rate')) / $total_classes : 0;
$total_activities = count($recent_activities);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Professor - Math Advantage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
                        <a class="navbar-brand" href="../../index.html">
                <img src="../../img/logo_math-advantatge.png" alt="Math Advantage" height="40" class="me-2">
                <span>Math Advantage</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.html" title="Tornar al web principal">
                            <i class="fas fa-arrow-left me-1"></i>Web Principal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="files.php">
                            <i class="fas fa-folder me-1"></i>Gestió d'Arxius
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#classes"><i class="fas fa-chalkboard-teacher me-1"></i>Les Meves Classes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#students"><i class="fas fa-users me-1"></i>Estudiants</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#activities"><i class="fas fa-tasks me-1"></i>Activitats</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?= strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($teacher['first_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#profile"><i class="fas fa-user me-2"></i>El Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="#schedule"><i class="fas fa-calendar me-2"></i>Horari</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth.php?action=logout"><i class="fas fa-sign-out-alt me-2"></i>Tancar Sessió</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h1>¡Hola, Profesor <?= htmlspecialchars($teacher['first_name']) ?>!</h1>
                <p class="welcome-subtitle">Panel de control del profesor - Gestiona tus clases y estudiantes</p>
                <div class="student-info">
                    <div class="info-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span><?= $total_classes ?> clase<?= $total_classes !== 1 ? 's' : '' ?> asignada<?= $total_classes !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?= $total_students ?> estudiante<?= $total_students !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-day"></i>
                        <span><?= count($today_classes) ?> clase<?= count($today_classes) !== 1 ? 's' : '' ?> hoy</span>
                    </div>
                </div>
            </div>
            <div class="welcome-image">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-12">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-primary">
                            <div class="stat-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $total_classes ?></h3>
                                <p>Clases Asignadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-success">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $total_students ?></h3>
                                <p>Estudiantes Totales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-info">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= count($today_classes) ?></h3>
                                <p>Clases Hoy</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-warning">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= round($avg_payment_rate) ?>%</h3>
                                <p>Tasa de Pago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Clases de Hoy -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-day me-2"></i>Clases de Hoy
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($today_classes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes clases programadas para hoy</h5>
                                <p class="text-muted">¡Disfruta de tu día libre!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($today_classes as $class): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="card-title text-primary"><?= htmlspecialchars($class['name']) ?></h6>
                                            <p class="card-text">
                                                <i class="fas fa-clock text-muted me-2"></i>
                                                <?= htmlspecialchars($class['schedule']) ?>
                                            </p>
                                            <p class="card-text">
                                                <i class="fas fa-users text-muted me-2"></i>
                                                <?= $class['student_count'] ?> estudiantes
                                            </p>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-primary flex-fill">
                                                    <i class="fas fa-play me-1"></i>Iniciar Clase
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mis Clases -->
                <div class="dashboard-card mt-4" id="classes">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Todas Mis Clases
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teacher_classes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes clases asignadas</h5>
                                <p class="text-muted">Contacta con administración para obtener clases</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Clase</th>
                                            <th>Horario</th>
                                            <th>Estudiantes</th>
                                            <th>Precio</th>
                                            <th>Tasa de Pago</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teacher_classes as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($class['name']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($class['description']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($class['schedule']) ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= $class['student_count'] ?></span>
                                            </td>
                                            <td><?= $class['price'] ?>€</td>
                                            <td>
                                                <div class="progress" style="width: 60px; height: 8px;">
                                                    <div class="progress-bar bg-<?= $class['payment_rate'] >= 80 ? 'success' : ($class['payment_rate'] >= 50 ? 'warning' : 'danger') ?>" 
                                                         style="width: <?= $class['payment_rate'] ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= round($class['payment_rate']) ?>%</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success" title="Gestionar estudiantes">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" title="Configuración">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Estudiantes Recientes -->
                <div class="dashboard-card" id="students">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users me-2"></i>Estudiantes Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-plus fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No hay estudiantes inscritos</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($students, 0, 8) as $student): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="student-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 0.875rem;">
                                            <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($student['class_name']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block"><?= $student['activity_count'] ?> actividades</small>
                                            <span class="badge bg-<?= $student['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                <?= $student['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actividades Recientes -->
                <div class="dashboard-card mt-4" id="activities">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-history me-2"></i>Actividad Reciente
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No hay actividad reciente</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-timeline">
                                <?php foreach (array_slice($recent_activities, 0, 6) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-primary">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6><?= htmlspecialchars($activity['activity_type']) ?></h6>
                                        <p><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></p>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Herramientas Rápidas -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-tools me-2"></i>Herramientas Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-user-plus text-primary"></i>
                                <span>Nuevo Estudiante</span>
                            </a>
                            <a href="files.php" class="quick-action-btn">
                                <i class="fas fa-folder-open text-success"></i>
                                <span>Gestionar Archivos</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-chart-line text-info"></i>
                                <span>Ver Progreso</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-envelope text-warning"></i>
                                <span>Contactar Familias</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-calendar-plus text-danger"></i>
                                <span>Programar Clase</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
        
        // Add click handlers for class management
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Teacher action:', this.textContent.trim());
                // Implementar funcionalidades específicas del profesor
            });
        });

        // Handle class start buttons
        document.querySelectorAll('button').forEach(btn => {
            if (btn.textContent.includes('Iniciar Clase')) {
                btn.addEventListener('click', function() {
                    // Funcionalidad para iniciar clase
                    console.log('Starting class...');
                });
            }
        });
    </script>
</body>
</html>