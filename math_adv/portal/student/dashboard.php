<?php
/**
 * Math Advantage - Student Dashboard
 * Fase 3: Portal de Familias - Dashboard de Estudiante
 */

require_once '../auth.php';

$auth = new AuthenticationSystem();
$auth->requireAuth(['student']);

$currentUser = $auth->getCurrentUser();

// Obtener datos del estudiante
$db = Database::getInstance();

$student = $db->query(
    "SELECT * FROM students WHERE id = ?",
    [$currentUser['id']]
)->fetch(PDO::FETCH_ASSOC);

// Obtener clases inscritas
$classes = $db->query(
    "SELECT c.*, e.data_inscripcio, e.estat as enrollment_status
     FROM classes c
     JOIN enrollments e ON c.id = e.class_id
     WHERE e.student_id = ? AND e.estat = 'actiu'
     ORDER BY c.dia_setmana, c.hora_inici",
    [$currentUser['id']]
)->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats = [
    'classes_actives' => count($classes),
    'total_hours_week' => 0,
    'next_class' => null
];

$totalMinutes = 0;
foreach ($classes as $class) {
    $start = new DateTime($class['hora_inici']);
    $end = new DateTime($class['hora_fi']);
    $totalMinutes += $start->diff($end)->i + ($start->diff($end)->h * 60);
}
$stats['total_hours_week'] = round($totalMinutes / 60, 1);

// Próxima clase
$nextClass = $db->query(
    "SELECT c.*, e.data_inscripcio
     FROM classes c
     JOIN enrollments e ON c.id = e.class_id
     WHERE e.student_id = ? AND e.estat = 'actiu'
     ORDER BY 
        CASE 
            WHEN c.dia_setmana = 'Dilluns' THEN 1
            WHEN c.dia_setmana = 'Dimarts' THEN 2
            WHEN c.dia_setmana = 'Dimecres' THEN 3
            WHEN c.dia_setmana = 'Dijous' THEN 4
            WHEN c.dia_setmana = 'Divendres' THEN 5
            WHEN c.dia_setmana = 'Dissabte' THEN 6
            WHEN c.dia_setmana = 'Diumenge' THEN 7
        END,
        c.hora_inici
     LIMIT 1",
    [$currentUser['id']]
)->fetch(PDO::FETCH_ASSOC);

$stats['next_class'] = $nextClass;
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiant - Math Advantage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
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
                            <i class="fas fa-folder me-1"></i>Arxius i Tasques
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="classes">
                            <i class="fas fa-calendar-alt me-1"></i>Les Meves Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="progress">
                            <i class="fas fa-chart-line me-1"></i>Progrés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="messages">
                            <i class="fas fa-comments me-1"></i>Missatges
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user"></i>
                            </div>
                            <span><?= htmlspecialchars($student['nom']) ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-section="profile">
                                <i class="fas fa-user-edit me-2"></i>El Meu Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-section="settings">
                                <i class="fas fa-cog me-2"></i>Configuració
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Tancar Sessió
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h1>Benvingut, <?= htmlspecialchars($student['nom']) ?>! 👋</h1>
                        <p class="welcome-subtitle">
                            Aquí pots veure el teu progrés acadèmic, les teves classes i molt més.
                        </p>
                        <div class="student-info">
                            <span class="info-item">
                                <i class="fas fa-graduation-cap text-primary"></i>
                                <?= htmlspecialchars($student['nivell_educatiu']) ?>
                            </span>
                            <span class="info-item">
                                <i class="fas fa-school text-success"></i>
                                <?= htmlspecialchars($student['centre_educatiu']) ?>
                            </span>
                            <span class="info-item">
                                <i class="fas fa-id-badge text-info"></i>
                                Codi: <?= htmlspecialchars($student['student_code']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="welcome-image">
                        <i class="fas fa-book-reader"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['classes_actives'] ?></h3>
                        <p>Classes Actives</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $stats['total_hours_week'] ?>h</h3>
                        <p>Hores Setmanals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= date('d') ?></h3>
                        <p>Dia del Mes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <h3>A+</h3>
                        <p>Objectiu</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Dashboard Content -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Next Class Card -->
                <?php if ($stats['next_class']): ?>
                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            Pròxima Classe
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="next-class-info">
                            <div class="class-main-info">
                                <h4><?= htmlspecialchars($stats['next_class']['nom']) ?></h4>
                                <p class="class-subject"><?= htmlspecialchars($stats['next_class']['assignatura']) ?></p>
                                <div class="class-schedule">
                                    <span class="schedule-day">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= $stats['next_class']['dia_setmana'] ?>
                                    </span>
                                    <span class="schedule-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('H:i', strtotime($stats['next_class']['hora_inici'])) ?> - 
                                        <?= date('H:i', strtotime($stats['next_class']['hora_fi'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="class-actions">
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-video me-1"></i>Unir-se
                                </button>
                                <button class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-info-circle me-1"></i>Detalls
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Weekly Schedule -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-week text-success me-2"></i>
                            Horari Setmanal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="schedule-grid">
                            <?php
                            $days = ['Dilluns', 'Dimarts', 'Dimecres', 'Dijous', 'Divendres', 'Dissabte'];
                            $dayClasses = [];
                            
                            foreach ($classes as $class) {
                                $dayClasses[$class['dia_setmana']][] = $class;
                            }
                            ?>
                            
                            <?php foreach ($days as $day): ?>
                            <div class="schedule-day">
                                <div class="day-header">
                                    <h6><?= $day ?></h6>
                                </div>
                                <div class="day-classes">
                                    <?php if (isset($dayClasses[$day])): ?>
                                        <?php foreach ($dayClasses[$day] as $class): ?>
                                        <div class="class-block">
                                            <div class="class-time">
                                                <?= date('H:i', strtotime($class['hora_inici'])) ?>
                                            </div>
                                            <div class="class-info">
                                                <div class="class-name"><?= htmlspecialchars($class['nom']) ?></div>
                                                <div class="class-subject"><?= htmlspecialchars($class['assignatura']) ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-classes">
                                            <i class="fas fa-coffee text-muted"></i>
                                            <small class="text-muted">Sense classes</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-history text-info me-2"></i>
                            Activitat Recent
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <div class="activity-item">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <h6>Inscripció completada</h6>
                                    <p>T'has inscrit a Math Advantage amb èxit</p>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($student['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php foreach (array_slice($classes, 0, 3) as $class): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <h6>Classe afegida</h6>
                                    <p>T'has inscrit a <?= htmlspecialchars($class['nom']) ?></p>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($class['data_inscripcio'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bolt text-warning me-2"></i>
                            Accions Ràpides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="files.php" class="quick-action-btn">
                                <i class="fas fa-book text-primary"></i>
                                <span>Ver Materiales y Tareas</span>
                            </a>
                            <a href="files.php" class="quick-action-btn">
                                <i class="fas fa-tasks text-success"></i>
                                <span>Entregas Pendientes</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-calendar-plus text-info"></i>
                                <span>Reservar Clase Extra</span>
                            </a>
                            <a href="tel:931163457" class="quick-action-btn">
                                <i class="fas fa-phone text-danger"></i>
                                <span>Contactar Acadèmia</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-address-book text-secondary me-2"></i>
                            Contacte
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="contact-items">
                            <div class="contact-item">
                                <i class="fas fa-phone text-success"></i>
                                <div>
                                    <strong>Telèfon</strong>
                                    <p>931 16 34 57</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fab fa-whatsapp text-success"></i>
                                <div>
                                    <strong>WhatsApp</strong>
                                    <p>658 174 783</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope text-primary"></i>
                                <div>
                                    <strong>Email</strong>
                                    <p>info@math-advantage.com</p>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <div>
                                    <strong>Ubicació</strong>
                                    <p>Pare Sallarès, 67<br>Sabadell</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tips & Advice -->
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Consells d'Estudi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="tip-of-the-day">
                            <div class="tip-icon">
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <div class="tip-content">
                                <h6>Consell del Dia</h6>
                                <p>Practica 15 minuts diaris per mantenir els conceptes frescos a la memòria.</p>
                            </div>
                        </div>
                        
                        <div class="study-resources mt-3">
                            <h6>Recursos Útils:</h6>
                            <ul class="resource-list">
                                <li><a href="#" class="resource-link">
                                    <i class="fas fa-calculator me-1"></i>Calculadora Online
                                </a></li>
                                <li><a href="#" class="resource-link">
                                    <i class="fas fa-book me-1"></i>Formulari Matemàtic
                                </a></li>
                                <li><a href="#" class="resource-link">
                                    <i class="fas fa-video me-1"></i>Vídeos Explicatius
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
        <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* ===== RESPONSIVE DESIGN PARA DASHBOARD ESTUDIANTE ===== */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575.98px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem !important; }
            
            .container-fluid { padding: 0 0.5rem; }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
                border-radius: 8px;
            }
            
            .stat-value { font-size: 1.5rem; }
            
            .dashboard-sections {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .section-card {
                padding: 1rem;
                border-radius: 8px;
            }
            
            .section-header h5 { font-size: 1rem; }
            
            .class-card {
                padding: 0.8rem;
                margin-bottom: 0.8rem;
            }
            
            .progress-item {
                margin-bottom: 0.8rem;
            }
            
            .user-avatar { 
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .navbar-nav .dropdown-menu {
                position: absolute;
                right: 0;
                left: auto;
            }
        }
        
        /* Small Devices (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .dashboard-sections {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        
        /* Medium Devices - Tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .dashboard-stats {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .dashboard-sections {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Large Devices - Desktop (992px+) */
        @media (min-width: 992px) {
            .dashboard-stats {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .dashboard-sections {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Extra Large Devices (1200px+) */
        @media (min-width: 1200px) {
            .container-fluid {
                max-width: 1200px;
                margin: 0 auto;
            }
        }
        
        /* Landscape Mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .navbar { padding: 0.3rem 1rem; }
            
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            
            .stat-card { padding: 0.8rem; }
        }
    </style>
</body>
</html>
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>