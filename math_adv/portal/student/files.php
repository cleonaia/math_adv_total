<?php
// Math Advantage - Student Files Access
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Incluir dependencias
require_once '../auth.php';
require_once '../../php/classes/Database.php';
require_once '../../php/classes/FileManager.php';

$database = new Database();
$pdo = $database->getConnection();
$fileManager = new FileManager();

// Obtener información del estudiante y su clase
$stmt = $pdo->prepare("
    SELECT s.*, c.name as class_name, c.id as class_id, c.schedule,
           t.first_name as teacher_name, t.last_name as teacher_lastname
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    WHERE s.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener archivos disponibles para el estudiante
$class_files = [];
if ($student['class_id']) {
    $class_files = $fileManager->getFilesByClass($student['class_id']);
}

// Separar archivos por tipo
$files_by_type = [];
foreach ($class_files as $file) {
    $files_by_type[$file['upload_type']][] = $file;
}

// Obtener tareas/assignments
$assignments = [];
if ($student['class_id']) {
    $stmt = $pdo->prepare("
        SELECT a.*, cf.original_name as file_name, cf.id as file_id,
               ss.id as submission_id, ss.submitted_at, ss.grade, ss.teacher_feedback
        FROM assignments a
        LEFT JOIN class_files cf ON a.file_id = cf.id
        LEFT JOIN student_submissions ss ON a.id = ss.assignment_id AND ss.student_id = ?
        WHERE a.class_id = ? AND a.is_active = 1
        ORDER BY a.due_date ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $student['class_id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Procesar envío de tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    $assignment_id = $_POST['assignment_id'];
    $submission_text = $_POST['submission_text'] ?? '';
    $file_id = null;
    
    // Si se subió un archivo
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $result = $fileManager->uploadFile(
            $_FILES['submission_file'],
            $student['class_id'],
            $_SESSION['user_id'],
            'submission',
            'Entrega - ' . $_POST['assignment_title'],
            'Entrega del estudiante para la tarea'
        );
        
        if ($result['success']) {
            $file_id = $result['file_id'];
        } else {
            $submission_message = $result;
        }
    }
    
    // Guardar la entrega
    if (!isset($submission_message) || $submission_message['success']) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO student_submissions (assignment_id, student_id, file_id, submission_text, submitted_at, is_late)
                VALUES (?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE 
                    file_id = VALUES(file_id),
                    submission_text = VALUES(submission_text),
                    submitted_at = NOW(),
                    attempt_number = attempt_number + 1
            ");
            
            $is_late = strtotime($_POST['due_date']) < time();
            $stmt->execute([$assignment_id, $_SESSION['user_id'], $file_id, $submission_text, $is_late]);
            
            $submission_message = [
                'success' => true,
                'message' => 'Tarea entregada correctamente'
            ];
        } catch (Exception $e) {
            $submission_message = [
                'success' => false,
                'message' => 'Error al entregar la tarea: ' . $e->getMessage()
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials i Tasques - Math Advantage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
        .file-type-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        
        .assignment-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .assignment-card.overdue {
            border-left-color: var(--danger-color);
        }
        
        .assignment-card.submitted {
            border-left-color: var(--success-color);
        }
        
        .assignment-card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .download-btn {
            transition: all 0.2s ease;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
        }
        
        .submission-area {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px dashed #dee2e6;
        }
        
        .grade-display {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* ===== RESPONSIVE DESIGN PARA MATERIALES Y TAREAS ===== */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575.98px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem !important; }
            
            .container.mt-4 { margin-top: 1rem !important; padding: 0 0.5rem; }
            
            .dashboard-card {
                margin-bottom: 1rem;
                border-radius: 12px;
            }
            
            .card-body { padding: 1rem; }
            
            .badge { font-size: 0.7rem; }
            
            .btn {
                font-size: 16px; /* Prevenir zoom iOS */
                padding: 0.5rem 1rem;
            }
            
            .btn-sm { padding: 0.4rem 0.8rem; }
            
            .file-item {
                padding: 0.8rem;
                margin-bottom: 0.8rem;
                border-radius: 6px;
            }
            
            .file-actions {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .assignment-item {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 8px;
            }
            
            .assignment-header h6 { font-size: 0.9rem; }
            
            .grade-display { font-size: 1.2rem; }
            
            .upload-area {
                padding: 1rem;
                border-radius: 6px;
            }
            
            .form-control, .form-select, textarea {
                font-size: 16px; /* Prevenir zoom iOS */
            }
            
            textarea { min-height: 80px; }
            
            .nav-pills .nav-link {
                font-size: 0.85rem;
                padding: 0.5rem 0.8rem;
            }
            
            .table-responsive { font-size: 0.85rem; }
            .table th, .table td { padding: 0.5rem 0.3rem; }
        }
        
        /* Small Devices (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .container.mt-4 { padding: 0 1rem; }
            
            .dashboard-card { margin-bottom: 1.5rem; }
            
            .assignment-item { padding: 1.2rem; }
            
            .upload-area { padding: 1.5rem; }
            
            .file-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .nav-pills .nav-link {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }
        }
        
        /* Medium Devices - Tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .assignment-item { padding: 1.5rem; }
            
            .upload-area { padding: 2rem; }
            
            .grade-display { font-size: 1.3rem; }
        }
        
        /* Large Devices - Desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .container.mt-4 { max-width: 100%; }
            
            .assignment-item { padding: 1.5rem; }
        }
        
        /* Extra Large Devices (1200px+) */
        @media (min-width: 1200px) {
            .container.mt-4 { max-width: 1200px; }
            
            .dashboard-card { border-radius: 16px; }
            
            .assignment-item {
                padding: 2rem;
                border-radius: 12px;
            }
            
            .upload-area { 
                padding: 2.5rem;
                border-radius: 12px;
            }
        }
        
        /* Landscape Mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .navbar { padding: 0.3rem 0; }
            .container.mt-4 { margin-top: 0.5rem !important; }
            
            .assignment-item { padding: 0.8rem; }
            .upload-area { padding: 1rem; }
            
            .nav-pills { 
                display: flex;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .nav-pills .nav-link {
                flex-shrink: 0;
                margin-right: 0.5rem;
            }
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../../img/logo_math-advantatge.png" alt="Math Advantage" height="40" class="me-2">
                <span>Materials i Tasques</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.html">
                            <i class="fas fa-arrow-left me-1"></i>Web Principal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="files.php">
                            <i class="fas fa-folder me-1"></i>Materials i Tasques
                        </a>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../auth.php?action=logout">
                        <i class="fas fa-sign-out-alt me-2"></i>Tancar Sessió
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alert Messages -->
        <?php if (isset($submission_message)): ?>
        <div class="alert alert-<?= $submission_message['success'] ? 'success' : 'danger' ?> alert-dismissible fade show">
            <i class="fas fa-<?= $submission_message['success'] ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($submission_message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Student Info -->
        <div class="dashboard-card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">
                            <i class="fas fa-folder-open me-2"></i>
                            Materiales de <?= htmlspecialchars($student['class_name'] ?? 'Sin clase asignada') ?>
                        </h5>
                        <?php if ($student['teacher_name']): ?>
                        <p class="text-muted mb-0">
                            Profesor: <?= htmlspecialchars($student['teacher_name'] . ' ' . $student['teacher_lastname']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary fs-6">
                            <?= count($class_files) ?> archivos disponibles
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($student['class_id'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No tienes una clase asignada. Contacta con la administración.
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Assignments/Tareas -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-tasks me-2"></i>Tareas y Entregas
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assignments)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No hay tareas asignadas</h6>
                                    <p class="text-muted">Cuando tu profesor asigne tareas, aparecerán aquí</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <?php
                                    $is_overdue = $assignment['due_date'] && strtotime($assignment['due_date']) < time();
                                    $is_submitted = !empty($assignment['submission_id']);
                                    $card_class = $is_submitted ? 'submitted' : ($is_overdue ? 'overdue' : '');
                                    ?>
                                    <div class="assignment-card card mb-3 <?= $card_class ?>">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="card-title">
                                                        <?= htmlspecialchars($assignment['title']) ?>
                                                        
                                                        <?php if ($is_submitted): ?>
                                                            <span class="badge bg-success ms-2">Entregada</span>
                                                        <?php elseif ($is_overdue): ?>
                                                            <span class="badge bg-danger ms-2">Vencida</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning ms-2">Pendiente</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    
                                                    <p class="card-text"><?= htmlspecialchars($assignment['description']) ?></p>
                                                    
                                                    <div class="d-flex gap-3 mb-2">
                                                        <?php if ($assignment['due_date']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            Fecha límite: <?= date('d/m/Y H:i', strtotime($assignment['due_date'])) ?>
                                                        </small>
                                                        <?php endif; ?>
                                                        
                                                        <small class="text-muted">
                                                            <i class="fas fa-star me-1"></i>
                                                            Puntos: <?= $assignment['max_points'] ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <?php if ($assignment['file_name']): ?>
                                                    <div class="mb-2">
                                                        <a href="../download_file.php?id=<?= $assignment['file_id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary download-btn">
                                                            <i class="fas fa-download me-1"></i>
                                                            Descargar: <?= htmlspecialchars($assignment['file_name']) ?>
                                                        </a>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Submission Info -->
                                                    <?php if ($is_submitted): ?>
                                                        <div class="alert alert-success">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <strong>Entregada:</strong> 
                                                                    <?= date('d/m/Y H:i', strtotime($assignment['submitted_at'])) ?>
                                                                    
                                                                    <?php if ($assignment['grade']): ?>
                                                                        <div class="mt-1">
                                                                            <span class="grade-display text-success">
                                                                                <?= $assignment['grade'] ?>/<?= $assignment['max_points'] ?>
                                                                            </span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                
                                                                <?php if ($assignment['grade']): ?>
                                                                <div class="text-center">
                                                                    <div class="grade-display text-success">
                                                                        <?= round(($assignment['grade'] / $assignment['max_points']) * 100) ?>%
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <?php if ($assignment['teacher_feedback']): ?>
                                                                <hr>
                                                                <strong>Feedback del profesor:</strong>
                                                                <p class="mb-0 mt-1"><?= htmlspecialchars($assignment['teacher_feedback']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Submission Area -->
                                                <?php if (!$is_submitted && $assignment['submission_allowed']): ?>
                                                <div class="col-md-4">
                                                    <div class="submission-area">
                                                        <h6 class="mb-3">
                                                            <i class="fas fa-upload me-2"></i>Entregar Tarea
                                                        </h6>
                                                        
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="action" value="submit_assignment">
                                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                                            <input type="hidden" name="assignment_title" value="<?= htmlspecialchars($assignment['title']) ?>">
                                                            <input type="hidden" name="due_date" value="<?= $assignment['due_date'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Archivo (opcional)</label>
                                                                <input type="file" name="submission_file" class="form-control form-control-sm"
                                                                       accept=".pdf,.doc,.docx,.txt,.jpg,.png">
                                                                <small class="text-muted">PDF, DOC, DOCX, TXT, JPG, PNG</small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Comentarios</label>
                                                                <textarea name="submission_text" class="form-control" rows="3"
                                                                          placeholder="Escribe tus comentarios sobre la tarea..."></textarea>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                                <i class="fas fa-paper-plane me-1"></i>
                                                                Entregar Tarea
                                                            </button>
                                                            
                                                            <?php if ($is_overdue && !$assignment['late_submission_allowed']): ?>
                                                                <small class="text-danger d-block mt-2">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                                    Entrega fuera de plazo no permitida
                                                                </small>
                                                            <?php endif; ?>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Materials Sidebar -->
                <div class="col-lg-4">
                    <!-- Materials by Type -->
                    <?php
                    $type_names = [
                        'theory' => ['Teoría', 'fas fa-book', 'primary'],
                        'exercise' => ['Ejercicios', 'fas fa-pencil-alt', 'success'],
                        'material' => ['Material de Clase', 'fas fa-folder', 'secondary'],
                        'resource' => ['Recursos', 'fas fa-tools', 'warning'],
                        'homework' => ['Tareas', 'fas fa-clipboard-list', 'danger']
                    ];
                    
                    foreach ($type_names as $type => $info):
                        if (!isset($files_by_type[$type])) continue;
                    ?>
                    <div class="dashboard-card mb-3">
                        <div class="card-header">
                            <h6 class="card-title">
                                <i class="<?= $info[1] ?> me-2"></i><?= $info[0] ?>
                                <span class="badge bg-<?= $info[2] ?> ms-2"><?= count($files_by_type[$type]) ?></span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($files_by_type[$type] as $file): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <h6 class="mb-1 fs-6">
                                        <?= htmlspecialchars($file['title'] ?: $file['original_name']) ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= number_format($file['file_size'] / 1024, 1) ?> KB
                                        • <?= $file['download_count'] ?> descargas
                                    </small>
                                    <?php if ($file['description']): ?>
                                    <p class="mb-0 mt-1 text-muted small">
                                        <?= htmlspecialchars(substr($file['description'], 0, 80)) ?>
                                        <?= strlen($file['description']) > 80 ? '...' : '' ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <a href="../download_file.php?id=<?= $file['id'] ?>" 
                                   class="btn btn-sm btn-outline-<?= $info[2] ?> download-btn">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Quick Info -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle me-2"></i>Información
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="row">
                                    <div class="col-6">
                                        <h5 class="text-primary"><?= count($class_files) ?></h5>
                                        <small class="text-muted">Archivos</small>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="text-success"><?= count($assignments) ?></h5>
                                        <small class="text-muted">Tareas</small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6>Tipos de archivo soportados:</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-light text-dark">PDF</span>
                                <span class="badge bg-light text-dark">DOC</span>
                                <span class="badge bg-light text-dark">XLS</span>
                                <span class="badge bg-light text-dark">PPT</span>
                                <span class="badge bg-light text-dark">IMG</span>
                                <span class="badge bg-light text-dark">VIDEO</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);

        // File upload preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const size = (file.size / 1024).toFixed(1);
                    const preview = this.parentElement.querySelector('.file-preview') || document.createElement('small');
                    preview.className = 'file-preview text-muted d-block mt-1';
                    preview.textContent = `Archivo seleccionado: ${file.name} (${size} KB)`;
                    if (!this.parentElement.querySelector('.file-preview')) {
                        this.parentElement.appendChild(preview);
                    }
                }
            });
        });
    </script>
</body>
</html>