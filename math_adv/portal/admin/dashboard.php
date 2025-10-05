<?php
// Math Advantage - Admin Portal Dashboard
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Incluir dependencias
require_once '../auth.php';
require_once '../../php/classes/Database.php';

$database = new Database();
$pdo = $database->getConnection();

// Obtener estadísticas generales
$stats = [];

// Total de estudiantes
$stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
$stats['total_students'] = $stmt->fetch()['count'];

// Total de profesores
$stmt = $pdo->query("SELECT COUNT(*) as count FROM teachers");
$stats['total_teachers'] = $stmt->fetch()['count'];

// Total de clases
$stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
$stats['total_classes'] = $stmt->fetch()['count'];

// Total de padres
$stmt = $pdo->query("SELECT COUNT(*) as count FROM parents");
$stats['total_parents'] = $stmt->fetch()['count'];

// Ingresos totales
$stmt = $pdo->query("
    SELECT SUM(c.price) as total_revenue
    FROM students s
    INNER JOIN classes c ON s.class_id = c.id
    WHERE s.payment_status = 'paid'
");
$stats['total_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;

// Pagos pendientes
$stmt = $pdo->query("
    SELECT COUNT(*) as count, SUM(c.price) as amount
    FROM students s
    INNER JOIN classes c ON s.class_id = c.id
    WHERE s.payment_status = 'pending' OR s.payment_status IS NULL
");
$pending_payments = $stmt->fetch();
$stats['pending_payments'] = $pending_payments['count'];
$stats['pending_amount'] = $pending_payments['amount'] ?? 0;

// Inscripciones recientes (últimos 30 días)
$stmt = $pdo->query("
    SELECT COUNT(*) as count
    FROM students
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stats['recent_enrollments'] = $stmt->fetch()['count'];

// Obtener inscripciones recientes con detalles
$stmt = $pdo->query("
    SELECT s.*, c.name as class_name, c.price, p.first_name as parent_name, p.last_name as parent_lastname
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN parents p ON s.parent_id = p.id
    ORDER BY s.created_at DESC
    LIMIT 10
");
$recent_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener clases con más estudiantes
$stmt = $pdo->query("
    SELECT c.*, COUNT(s.id) as student_count, 
           t.first_name as teacher_name, t.last_name as teacher_lastname
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    GROUP BY c.id
    ORDER BY student_count DESC
    LIMIT 5
");
$popular_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividad del sistema (últimas 20)
$stmt = $pdo->query("
    (SELECT 'student_enrollment' as type, s.first_name, s.last_name, c.name as detail, s.created_at
     FROM students s 
     LEFT JOIN classes c ON s.class_id = c.id 
     ORDER BY s.created_at DESC LIMIT 10)
    UNION ALL
    (SELECT 'contact_form' as type, name as first_name, '' as last_name, email as detail, created_at
     FROM contact_submissions 
     ORDER BY created_at DESC LIMIT 10)
    ORDER BY created_at DESC
    LIMIT 20
");
$system_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Datos para gráficos (inscripciones por mes - últimos 6 meses)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM students
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$enrollment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distribución de pagos
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN s.payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN s.payment_status = 'pending' OR s.payment_status IS NULL THEN 1 ELSE 0 END) as pending
    FROM students s
    INNER JOIN classes c ON s.class_id = c.id
");
$payment_distribution = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panell d'Administració - Math Advantage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
        .admin-dashboard .stat-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .admin-dashboard .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .system-health {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            color: white;
        }
        
        .pending-card {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body class="dashboard-page admin-dashboard">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
                        <a class="nav-link" href="#users"><i class="fas fa-users me-1"></i>Usuaris</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#classes"><i class="fas fa-chalkboard-teacher me-1"></i>Classes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#payments"><i class="fas fa-credit-card me-1"></i>Pagaments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports"><i class="fas fa-chart-bar me-1"></i>Informes</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <span>Admin</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#settings"><i class="fas fa-cog me-2"></i>Configuració</a></li>
                            <li><a class="dropdown-item" href="#backup"><i class="fas fa-database me-2"></i>Backup</a></li>
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
        <div class="welcome-card system-health">
            <div class="welcome-content">
                <h1>Panell d'Administració</h1>
                <p class="welcome-subtitle">Control total del sistema Math Advantage</p>
                <div class="student-info">
                    <div class="info-item">
                        <i class="fas fa-server"></i>
                        <span>Sistema Operatiu</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-database"></i>
                        <span>Base de Dades Connectada</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Seguretat Activa</span>
                    </div>
                </div>
            </div>
            <div class="welcome-image">
                <i class="fas fa-tachometer-alt"></i>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="quick-stats">
            <div class="stat-card bg-primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_students'] ?></h3>
                    <p>Estudiantes Totales</p>
                </div>
            </div>
            
            <div class="stat-card bg-success">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_teachers'] ?></h3>
                    <p>Profesores Activos</p>
                </div>
            </div>
            
            <div class="stat-card bg-info">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_classes'] ?></h3>
                    <p>Clases Disponibles</p>
                </div>
            </div>
            
            <div class="stat-card bg-warning">
                <div class="stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_parents'] ?></h3>
                    <p>Familias Registradas</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Revenue and Financial Overview -->
            <div class="col-lg-8">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="dashboard-card revenue-card">
                            <div class="card-body text-center text-white">
                                <i class="fas fa-euro-sign fa-3x mb-3"></i>
                                <h2><?= number_format($stats['total_revenue'], 2) ?>€</h2>
                                <p class="mb-0">Ingresos Totales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card pending-card">
                            <div class="card-body text-center text-white">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <h2><?= $stats['pending_payments'] ?></h2>
                                <p class="mb-0">Pagos Pendientes</p>
                                <small><?= number_format($stats['pending_amount'], 2) ?>€</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line me-2"></i>Inscripciones por Mes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Enrollments -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-user-plus me-2"></i>Inscripciones Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Clase</th>
                                        <th>Padre/Tutor</th>
                                        <th>Precio</th>
                                        <th>Estado Pago</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $student['age'] ?> años</small>
                                        </td>
                                        <td><?= htmlspecialchars($student['class_name'] ?? 'No asignada') ?></td>
                                        <td><?= htmlspecialchars(($student['parent_name'] ?? '') . ' ' . ($student['parent_lastname'] ?? '')) ?></td>
                                        <td><?= $student['price'] ?? 0 ?>€</td>
                                        <td>
                                            <span class="badge bg-<?= $student['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                <?= $student['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($student['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- System Activity -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-activity me-2"></i>Actividad del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php foreach (array_slice($system_activity, 0, 8) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-<?= $activity['type'] === 'student_enrollment' ? 'primary' : 'info' ?>">
                                    <i class="fas fa-<?= $activity['type'] === 'student_enrollment' ? 'user-plus' : 'envelope' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h6>
                                        <?= $activity['type'] === 'student_enrollment' ? 'Nueva Inscripción' : 'Nuevo Contacto' ?>
                                    </h6>
                                    <p><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></p>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Popular Classes -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-star me-2"></i>Clases Populares
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popular_classes as $class): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($class['name']) ?></h6>
                                <small class="text-muted">
                                    Prof. <?= htmlspecialchars(($class['teacher_name'] ?? '') . ' ' . ($class['teacher_lastname'] ?? '')) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary"><?= $class['student_count'] ?></span>
                                <div>
                                    <small class="text-muted"><?= $class['price'] ?>€</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Distribution -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-pie-chart me-2"></i>Distribución de Pagos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 200px;">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-user-plus text-primary"></i>
                                <span>Nuevo Usuario</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-plus text-success"></i>
                                <span>Nueva Clase</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-file-export text-info"></i>
                                <span>Exportar Datos</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-database text-warning"></i>
                                <span>Backup Sistema</span>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <i class="fas fa-cog text-secondary"></i>
                                <span>Configuración</span>
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
        // Enrollment Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        const enrollmentChart = new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return "'" . date('M Y', strtotime($item['month'] . '-01')) . "'"; }, $enrollment_data)) ?>],
                datasets: [{
                    label: 'Inscripciones',
                    data: [<?= implode(',', array_column($enrollment_data, 'count')) ?>],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Payment Distribution Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pagados', 'Pendientes'],
                datasets: [{
                    data: [<?= $payment_distribution['paid'] ?? 0 ?>, <?= $payment_distribution['pending'] ?? 0 ?>],
                    backgroundColor: ['#28a745', '#ffc107'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Auto-refresh every 10 minutes
        setTimeout(() => {
            location.reload();
        }, 600000);

        // Quick actions functionality
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Admin action:', this.textContent.trim());
                // Implementar funcionalidades de administración
            });
        });

        // Stat cards click handlers
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', function() {
                const title = this.querySelector('p').textContent;
                console.log('Navigating to:', title);
                // Implementar navegación a secciones específicas
            });
        });
    </script>
</body>
</html>