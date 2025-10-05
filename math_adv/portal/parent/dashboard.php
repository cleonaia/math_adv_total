<?php
// Math Advantage - Parent Portal Dashboard
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit;
}

// Incluir dependencias
require_once '../auth.php';
require_once '../../php/classes/Database.php';
require_once '../../php/classes/Student.php';

$database = new Database();
$pdo = $database->getConnection();

// Obtener información del padre
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(s.id) as total_children
    FROM parents p
    LEFT JOIN students s ON p.id = s.parent_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$_SESSION['user_id']]);
$parent = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener información de los hijos
$stmt = $pdo->prepare("
    SELECT s.*, 
           c.name as class_name,
           c.schedule,
           c.price,
           COUNT(DISTINCT a.id) as total_activities
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN student_activities a ON s.id = a.student_id
    WHERE s.parent_id = ?
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener próximas clases de todos los hijos
$stmt = $pdo->prepare("
    SELECT c.*, s.first_name as student_name, s.last_name as student_lastname
    FROM classes c
    INNER JOIN students s ON c.id = s.class_id
    WHERE s.parent_id = ?
    ORDER BY c.schedule ASC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades recientes
$stmt = $pdo->prepare("
    SELECT a.*, s.first_name as student_name, s.last_name as student_lastname
    FROM student_activities a
    INNER JOIN students s ON a.student_id = s.id
    WHERE s.parent_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos pendientes
$stmt = $pdo->prepare("
    SELECT s.first_name, s.last_name, c.name as class_name, c.price, s.payment_status, s.created_at
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.parent_id = ? AND (s.payment_status = 'pending' OR s.payment_status IS NULL)
    ORDER BY s.created_at ASC
");
$stmt->execute([$_SESSION['user_id']]);
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas familiares
$total_children = count($children);
$total_pending_payments = array_sum(array_column($pending_payments, 'price'));
$total_classes_this_month = count($upcoming_classes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Famílies - Math Advantage</title>
    
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
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#children"><i class="fas fa-child me-1"></i>Els Meus Fills</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#payments"><i class="fas fa-credit-card me-1"></i>Pagaments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#communication"><i class="fas fa-comments me-1"></i>Comunicació</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?= strtoupper(substr($parent['first_name'], 0, 1) . substr($parent['last_name'], 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($parent['first_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#profile"><i class="fas fa-user me-2"></i>El Meu Perfil</a></li>
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
                <h1>Hola, <?= htmlspecialchars($parent['first_name']) ?>!</h1>
                <p class="welcome-subtitle">Portal de famílies - Seguiment acadèmic dels teus fills</p>
                <div class="student-info">
                    <div class="info-item">
                        <i class="fas fa-child"></i>
                        <span><?= $total_children ?> hijo<?= $total_children !== 1 ? 's' : '' ?> inscrito<?= $total_children !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span><?= $total_classes_this_month ?> clases programadas</span>
                    </div>
                    <?php if ($total_pending_payments > 0): ?>
                    <div class="info-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= $total_pending_payments ?>€ pendiente de pago</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="welcome-image">
                <i class="fas fa-home"></i>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-12">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-primary">
                            <div class="stat-icon">
                                <i class="fas fa-child"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $total_children ?></h3>
                                <p>Hijos Inscritos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-success">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= $total_classes_this_month ?></h3>
                                <p>Clases Activas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-<?= count($pending_payments) > 0 ? 'warning' : 'success' ?>">
                            <div class="stat-icon">
                                <i class="fas fa-<?= count($pending_payments) > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= count($pending_payments) ?></h3>
                                <p>Pagos Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card bg-info">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?= count($recent_activities) ?></h3>
                                <p>Actividades Recientes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Mis Hijos -->
            <div class="col-lg-8">
                <div class="dashboard-card" id="children">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-child me-2"></i>Mis Hijos
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($children)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-child fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay hijos inscritos</h5>
                                <p class="text-muted">Inscribe a tu hijo en nuestras clases de matemáticas</p>
                                <a href="../../index.html#inscripciones" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Inscribir Hijo
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($children as $child): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="student-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <?= strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?></h6>
                                                    <small class="text-muted"><?= $child['age'] ?> años</small>
                                                </div>
                                            </div>
                                            
                                            <?php if ($child['class_name']): ?>
                                                <div class="mb-2">
                                                    <strong>Clase:</strong> <?= htmlspecialchars($child['class_name']) ?>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Horario:</strong> <?= htmlspecialchars($child['schedule']) ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Estado de Pago:</strong>
                                                    <span class="badge bg-<?= $child['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                        <?= $child['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente' ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted mb-3">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    No inscrito en ninguna clase
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary flex-fill">
                                                    <i class="fas fa-eye me-1"></i>Ver Progreso
                                                </button>
                                                <button class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-comments"></i>
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

                <!-- Próximas Clases -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-alt me-2"></i>Próximas Clases
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_classes)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No hay clases programadas</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_classes as $class): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($class['name']) ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($class['student_name'] . ' ' . $class['student_lastname']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= htmlspecialchars($class['schedule']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block"><?= $class['price'] ?>€</small>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Pagos Pendientes -->
                <div class="dashboard-card" id="payments">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-credit-card me-2"></i>Pagos Pendientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_payments)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-success mb-0">¡Todos los pagos al día!</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending_payments as $payment): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></h6>
                                            <p class="mb-1"><?= htmlspecialchars($payment['class_name']) ?></p>
                                            <small class="text-muted">
                                                Inscrito: <?= date('d/m/Y', strtotime($payment['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-warning"><?= $payment['price'] ?>€</strong>
                                            <button class="btn btn-sm btn-warning d-block mt-1">
                                                <i class="fas fa-credit-card me-1"></i>Pagar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($total_pending_payments > 0): ?>
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Total Pendiente:</strong>
                                    <strong class="text-warning"><?= $total_pending_payments ?>€</strong>
                                </div>
                                <button class="btn btn-warning w-100 mt-2">
                                    <i class="fas fa-credit-card me-2"></i>Pagar Todo
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actividades Recientes -->
                <div class="dashboard-card mt-4">
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
                                <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-primary">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6><?= htmlspecialchars($activity['activity_type']) ?></h6>
                                        <p><?= htmlspecialchars($activity['student_name'] . ' ' . $activity['student_lastname']) ?></p>
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

                <!-- Comunicación Rápida -->
                <div class="dashboard-card mt-4" id="communication">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-comments me-2"></i>Comunicación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-envelope text-primary"></i>
                                <span>Enviar Mensaje</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-phone text-success"></i>
                                <span>Solicitar Llamada</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-calendar-plus text-warning"></i>
                                <span>Agendar Reunión</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-question-circle text-info"></i>
                                <span>Preguntas Frecuentes</span>
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
        
        // Add click handlers for quick actions
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                // Aquí se implementaría la funcionalidad específica
                console.log('Action clicked:', this.textContent.trim());
            });
        });
    </script>
</body>
</html>