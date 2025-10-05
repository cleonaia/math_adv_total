<?php
// Math Advantage - Teacher File Management
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
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

// Obtener clases del profesor
$stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$teacher_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload' && isset($_FILES['file'])) {
        $result = $fileManager->uploadFile(
            $_FILES['file'],
            $_POST['class_id'],
            $_SESSION['user_id'],
            $_POST['file_type'],
            $_POST['title'],
            $_POST['description']
        );
        
        $upload_message = $result;
    }
}

// Obtener archivos de todas las clases del profesor
$all_files = [];
foreach ($teacher_classes as $class) {
    $files = $fileManager->getFilesByClass($class['id']);
    $all_files = array_merge($all_files, $files);
}

// Ordenar por fecha de creación
usort($all_files, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestió d'Arxius - Professor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(139, 92, 246, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(139, 92, 246, 0.1);
        }
        
        .file-item {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }
        
        .file-item:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        
        .file-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .file-type-theory { color: #007bff; }
        .file-type-exercise { color: #28a745; }
        .file-type-material { color: #6f42c1; }
        .file-type-resource { color: #fd7e14; }
        .file-type-homework { color: #dc3545; }
        
        /* ===== RESPONSIVE DESIGN PARA GESTIÓN DE ARCHIVOS ===== */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575.98px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem !important; }
            
            .container.mt-4 { margin-top: 1rem !important; padding: 0 0.5rem; }
            
            .row { margin: 0; }
            .col-lg-4, .col-lg-8 { padding: 0 0.5rem; }
            
            .dashboard-card {
                margin-bottom: 1rem;
                border-radius: 12px;
            }
            
            .card-body { padding: 1rem; }
            
            .file-upload-area {
                padding: 1.5rem 1rem;
                border-radius: 8px;
            }
            
            .file-upload-area i { font-size: 2rem; }
            .file-upload-area h6 { font-size: 0.9rem; }
            
            .form-select, .form-control, .btn {
                font-size: 16px; /* Prevenir zoom iOS */
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
            
            .file-actions .btn {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
            }
            
            .badge { font-size: 0.7rem; }
        }
        
        /* Small Devices (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .container.mt-4 { padding: 0 1rem; }
            
            .dashboard-card { margin-bottom: 1.5rem; }
            
            .file-upload-area { padding: 2rem 1.5rem; }
            
            .col-lg-4 { margin-bottom: 2rem; }
            
            .file-item { padding: 1rem; }
            
            .file-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
        
        /* Medium Devices - Tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .col-lg-4 { 
                flex: 0 0 40%;
                max-width: 40%;
                margin-bottom: 2rem;
            }
            
            .col-lg-8 {
                flex: 0 0 60%;
                max-width: 60%;
            }
            
            .file-upload-area { padding: 2rem; }
        }
        
        /* Large Devices - Desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .container.mt-4 { max-width: 100%; }
        }
        
        /* Extra Large Devices (1200px+) */
        @media (min-width: 1200px) {
            .container.mt-4 { max-width: 1200px; }
            
            .file-upload-area { 
                padding: 2.5rem;
                border-radius: 16px;
            }
            
            .dashboard-card { border-radius: 16px; }
        }
        
        /* Landscape Mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .navbar { padding: 0.3rem 0; }
            .container.mt-4 { margin-top: 0.5rem !important; }
            
            .file-upload-area { padding: 1rem; }
            .file-upload-area i { font-size: 1.5rem; }
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../../img/logo_math-advantatge.png" alt="Math Advantage" height="40" class="me-2">
                <span>Gestió d'Arxius</span>
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
                            <i class="fas fa-folder me-1"></i>Gestió d'Arxius
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
        <?php if (isset($upload_message)): ?>
        <div class="alert alert-<?= $upload_message['success'] ? 'success' : 'danger' ?> alert-dismissible fade show">
            <i class="fas fa-<?= $upload_message['success'] ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($upload_message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Upload Section -->
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-upload me-2"></i>Subir Archivo
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="action" value="upload">
                            
                            <!-- Drag and Drop Area -->
                            <div class="file-upload-area mb-3" id="dropArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <h6>Arrastra archivos aquí</h6>
                                <p class="text-muted">o haz clic para seleccionar</p>
                                <input type="file" name="file" id="fileInput" class="d-none" required>
                            </div>
                            
                            <!-- File Info Display -->
                            <div id="fileInfo" class="mb-3 d-none">
                                <div class="alert alert-info">
                                    <i class="fas fa-file me-2"></i>
                                    <span id="fileName"></span>
                                    <small class="d-block text-muted" id="fileSize"></small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Clase</label>
                                <select name="class_id" id="class_id" class="form-select" required>
                                    <option value="">Seleccionar clase...</option>
                                    <?php foreach ($teacher_classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="file_type" class="form-label">Tipo de Archivo</label>
                                <select name="file_type" id="file_type" class="form-select" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="theory">📚 Teoría</option>
                                    <option value="exercise">✏️ Ejercicios</option>
                                    <option value="material">📋 Material de Clase</option>
                                    <option value="resource">🔧 Recursos</option>
                                    <option value="homework">📝 Tarea</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Título</label>
                                <input type="text" name="title" id="title" class="form-control" 
                                       placeholder="Título descriptivo del archivo">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea name="description" id="description" class="form-control" rows="3"
                                          placeholder="Descripción detallada del contenido"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" id="uploadBtn" disabled>
                                <i class="fas fa-upload me-2"></i>Subir Archivo
                            </button>
                        </form>
                        
                        <!-- File Type Info -->
                        <div class="mt-4">
                            <h6>Tipos de archivo permitidos:</h6>
                            <small class="text-muted">
                                PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG, GIF, MP4, AVI, MOV
                                <br>Tamaño máximo: 50MB
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="text-primary"><?= count($all_files) ?></h4>
                                <small class="text-muted">Total Archivos</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-success"><?= count($teacher_classes) ?></h4>
                                <small class="text-muted">Clases</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-info"><?= array_sum(array_column($all_files, 'download_count')) ?></h4>
                                <small class="text-muted">Descargas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Files List -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-folder-open me-2"></i>Mis Archivos
                        </h5>
                        
                        <!-- Filter Options -->
                        <div class="d-flex gap-2">
                            <select id="filterType" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Todos los tipos</option>
                                <option value="theory">Teoría</option>
                                <option value="exercise">Ejercicios</option>
                                <option value="material">Material</option>
                                <option value="resource">Recursos</option>
                                <option value="homework">Tarea</option>
                            </select>
                            
                            <select id="filterClass" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Todas las clases</option>
                                <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($all_files)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay archivos subidos</h5>
                                <p class="text-muted">Comienza subiendo tu primer archivo de clase</p>
                            </div>
                        <?php else: ?>
                            <div id="filesList">
                                <?php foreach ($all_files as $file): ?>
                                <div class="file-item" 
                                     data-type="<?= $file['upload_type'] ?>" 
                                     data-class="<?= $file['class_id'] ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="file-icon file-type-<?= $file['upload_type'] ?>">
                                                <?php
                                                $icon_map = [
                                                    'pdf' => 'fas fa-file-pdf',
                                                    'doc' => 'fas fa-file-word',
                                                    'docx' => 'fas fa-file-word',
                                                    'xls' => 'fas fa-file-excel',
                                                    'xlsx' => 'fas fa-file-excel',
                                                    'ppt' => 'fas fa-file-powerpoint',
                                                    'pptx' => 'fas fa-file-powerpoint',
                                                    'txt' => 'fas fa-file-alt',
                                                    'jpg' => 'fas fa-file-image',
                                                    'jpeg' => 'fas fa-file-image',
                                                    'png' => 'fas fa-file-image',
                                                    'gif' => 'fas fa-file-image',
                                                    'mp4' => 'fas fa-file-video',
                                                    'avi' => 'fas fa-file-video',
                                                    'mov' => 'fas fa-file-video'
                                                ];
                                                $icon = $icon_map[$file['file_type']] ?? 'fas fa-file';
                                                ?>
                                                <i class="<?= $icon ?>"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($file['title'] ?: $file['original_name']) ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <?= htmlspecialchars($file['description']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <span class="badge bg-<?= 
                                                    $file['upload_type'] === 'theory' ? 'primary' : 
                                                    ($file['upload_type'] === 'exercise' ? 'success' : 
                                                    ($file['upload_type'] === 'material' ? 'secondary' : 'warning'))
                                                ?>">
                                                    <?= ucfirst($file['upload_type']) ?>
                                                </span>
                                                • <?= number_format($file['file_size'] / 1024, 1) ?> KB
                                                • <?= $file['download_count'] ?> descargas
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">
                                                <?= date('d/m/Y H:i', strtotime($file['created_at'])) ?>
                                            </small>
                                            <?php
                                            // Obtener nombre de la clase
                                            $class_name = '';
                                            foreach ($teacher_classes as $class) {
                                                if ($class['id'] == $file['class_id']) {
                                                    $class_name = $class['name'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <small class="text-muted"><?= htmlspecialchars($class_name) ?></small>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="download_file.php?id=<?= $file['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteFile(<?= $file['id'] ?>)" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
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
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Drag and Drop functionality
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');
        
        dropArea.addEventListener('click', () => fileInput.click());
        
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });
        
        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('dragover');
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showFileInfo(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                showFileInfo(e.target.files[0]);
            }
        });
        
        function showFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('d-none');
            uploadBtn.disabled = false;
            
            // Auto-fill title if empty
            if (!document.getElementById('title').value) {
                const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
                document.getElementById('title').value = nameWithoutExt;
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Filter functionality
        const filterType = document.getElementById('filterType');
        const filterClass = document.getElementById('filterClass');
        
        function filterFiles() {
            const typeFilter = filterType.value;
            const classFilter = filterClass.value;
            const fileItems = document.querySelectorAll('.file-item');
            
            fileItems.forEach(item => {
                const itemType = item.dataset.type;
                const itemClass = item.dataset.class;
                
                const typeMatch = !typeFilter || itemType === typeFilter;
                const classMatch = !classFilter || itemClass === classFilter;
                
                if (typeMatch && classMatch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        filterType.addEventListener('change', filterFiles);
        filterClass.addEventListener('change', filterFiles);
        
        // Delete file function
        function deleteFile(fileId) {
            if (confirm('¿Estás seguro de que quieres eliminar este archivo? Esta acción no se puede deshacer.')) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'file_id=' + fileId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar el archivo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al comunicarse con el servidor');
                });
            }
        }
        
        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Por favor selecciona un archivo');
                return false;
            }
            
            if (!document.getElementById('class_id').value) {
                e.preventDefault();
                alert('Por favor selecciona una clase');
                return false;
            }
            
            if (!document.getElementById('file_type').value) {
                e.preventDefault();
                alert('Por favor selecciona el tipo de archivo');
                return false;
            }
        });
    </script>
</body>
</html>