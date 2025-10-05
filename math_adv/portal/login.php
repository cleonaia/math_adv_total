<?php
session_start();

// Si ya está logueado, redirigir al dashboard correspondiente
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

// Procesar registro si se envió el formulario
$registration_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    require_once '../php/classes/Database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $user_type = $_POST['user_type'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM (
                SELECT email FROM students WHERE email = ?
                UNION ALL
                SELECT email FROM parents WHERE email = ?
            ) as combined_emails
        ");
        $stmt->execute([$email, $email]);
        
        if ($stmt->fetch()['count'] > 0) {
            $registration_message = [
                'type' => 'error',
                'message' => 'El correu ja està registrat. Prova d\'iniciar sessió.'
            ];
        } else {
            // Registrar según el tipo de usuario
            if ($user_type === 'student') {
                $age = (int)$_POST['age'];
                $stmt = $pdo->prepare("
                    INSERT INTO students (first_name, last_name, email, phone, age, password, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $age, $password]);
            } else { // parent
                $stmt = $pdo->prepare("
                    INSERT INTO parents (first_name, last_name, email, phone, password, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $password]);
            }
            
            $registration_message = [
                'type' => 'success',
                'message' => 'Registre exitós! Ja pots iniciar sessió amb el teu email i contrasenya.'
            ];
        }
    } catch (Exception $e) {
        $registration_message = [
            'type' => 'error',
            'message' => 'Error al registrar: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math Advantage - Portal de Acceso</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Portal Responsive CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <style>
        :root {
            /* Colores de la web principal */
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
            --border-color: #e5e7eb;
            --text-muted: #6b7280;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 30%;
            left: 80%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .navbar {
            background: rgba(139, 92, 246, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
        }
        
        .navbar-brand img {
            height: 40px;
            filter: brightness(0) invert(1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            padding: 0.4rem 0.6rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .navbar-toggler-icon {
            filter: invert(1);
            width: 1.2em;
            height: 1.2em;
        }
        
        .main-container {
            padding: 100px 1rem 2rem 1rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        /* Responsive Breakpoints */
        @media (max-width: 576px) {
            .main-container {
                padding: 80px 0.5rem 1rem 0.5rem;
            }
        }
        
        @media (min-width: 1400px) {
            .main-container {
                padding: 120px 2rem 3rem 2rem;
            }
        }
        
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border-radius: 24px;
            overflow: hidden;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
            color: white;
            padding: 3rem;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #059669 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin: 0 auto 1.5rem;
        }
        
        .form-section {
            padding: 3rem;
        }
        
        .tab-navigation {
            display: flex;
            background: var(--light-bg);
            border-radius: 15px;
            padding: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        
        .user-type-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .user-type-btn {
            padding: 1.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .user-type-btn:hover {
            border-color: var(--primary-color);
            background: rgba(139, 92, 246, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.15);
        }
        
        .user-type-btn.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
        }
        
        .user-type-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }
        
        .user-type-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .user-type-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .form-floating {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
            background: #ffffff;
            font-size: 1rem;
        }
        
        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
            transform: translateY(-2px);
        }
        
        .form-floating > label {
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(139, 92, 246, 0.4);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            font-weight: 600;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .registration-section {
            background: rgba(16, 185, 129, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            border: 2px solid rgba(16, 185, 129, 0.2);
        }
        
        .feature-highlight {
            text-align: center;
            padding: 1rem;
        }
        
        .feature-highlight i {
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .feature-highlight h6 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .feature-highlight small {
            opacity: 0.8;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-left: 4px solid var(--secondary-color);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .form-section h3 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .form-section .subtitle {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        
        .toggle-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .toggle-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* ===== RESPONSIVE DESIGN COMPLETO PARA TODAS LAS PANTALLAS ===== */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575.98px) {
            body { font-size: 14px; }
            
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem !important; }
            
            .main-container { padding: 70px 0.5rem 1rem 0.5rem; }
            
            .main-card {
                margin: 0;
                border-radius: 0;
                min-height: calc(100vh - 140px);
            }
            
            .hero-section { display: none; }
            
            .form-section { padding: 1.5rem 1rem; }
            
            .user-type-selector {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }
            
            .user-type-btn {
                padding: 0.8rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .user-type-desc { display: none; }
            
            .form-floating input,
            .btn {
                font-size: 16px; /* Prevenir zoom iOS */
            }
            
            .tab-btn {
                font-size: 0.9rem;
                padding: 0.7rem 0.5rem;
            }
            
            h3 { font-size: 1.3rem; }
        }
        
        /* Small Devices (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .main-container { padding: 80px 1rem 2rem 1rem; }
            
            .main-card {
                border-radius: 16px;
                margin: 0 0.5rem;
            }
            
            .hero-section {
                padding: 2rem 1.5rem;
                text-align: center;
            }
            
            .form-section { padding: 2rem 1.5rem; }
            
            .user-type-selector {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
        }
        
        /* Medium Devices - Tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .main-container { padding: 90px 1rem 2rem 1rem; }
            
            .main-card {
                border-radius: 20px;
                max-width: 900px;
                margin: 0 auto;
            }
            
            .hero-section,
            .form-section { padding: 2.5rem 2rem; }
            
            .user-type-selector {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }
        
        /* Large Devices - Desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .main-container { padding: 100px 1.5rem 2rem 1.5rem; }
            
            .main-card {
                max-width: 1000px;
                margin: 0 auto;
            }
            
            .hero-section,
            .form-section { padding: 3rem 2.5rem; }
            
            .user-type-selector {
                grid-template-columns: repeat(4, 1fr);
                gap: 1.2rem;
            }
        }
        
        /* Extra Large Devices (1200px - 1399px) */
        @media (min-width: 1200px) and (max-width: 1399.98px) {
            .main-container { padding: 110px 2rem 3rem 2rem; }
            
            .main-card {
                max-width: 1100px;
                margin: 0 auto;
            }
            
            .hero-section,
            .form-section { padding: 3.5rem 3rem; }
        }
        
        /* Ultra Wide Screens (1400px+) */
        @media (min-width: 1400px) {
            .main-container { padding: 120px 2rem 3rem 2rem; }
            
            .main-card {
                max-width: 1200px;
                margin: 0 auto;
                border-radius: 32px;
            }
            
            .hero-section,
            .form-section { padding: 4rem 3.5rem; }
            
            .user-type-btn {
                padding: 1.5rem 1rem;
                font-size: 1rem;
            }
        }
        
        /* Landscape Mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .main-container { padding: 60px 1rem 1rem 1rem; }
            .hero-section { padding: 1.5rem; }
            .form-section { padding: 1.5rem; }
            .user-type-selector { grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
        }
        
        /* High DPI Displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 2dppx) {
            .main-card { box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2); }
        }
    </style>
</head>
<body>
    <!-- Floating Shapes Background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
                        <a class="navbar-brand" href="../index.html">
                <img src="../img/logo_math-advantatge.png" alt="Math Advantage" height="40" class="me-2">
                <span>Math Advantage</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">
                            <i class="fas fa-home me-1"></i>Inici
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#cursos">
                            <i class="fas fa-book me-1"></i>Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#metodologia">
                            <i class="fas fa-lightbulb me-1"></i>Metodologia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#contacte">
                            <i class="fas fa-envelope me-1"></i>Contacte
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="welcome.php">
                            <i class="fas fa-arrow-left me-1"></i>Portal
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="main-card">
                    <div class="row g-0">
                        <!-- Left Panel - Hero Section -->
                        <div class="col-lg-6 hero-section d-flex align-items-center">
                            <div class="w-100 position-relative">
                                <div class="text-center mb-4">
                                    <div class="feature-icon mx-auto">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <h2 class="fw-bold mb-3">Benvingut a Math Advantage!</h2>
                                    <p class="opacity-90 mb-4">La teva plataforma educativa on les matemàtiques prenen vida</p>
                                </div>

                                <div class="row text-center g-3 mb-4">
                                    <div class="col-6">
                                        <div class="feature-highlight">
                                            <i class="fas fa-users fa-2x mb-2"></i>
                                            <h6>Comunitat</h6>
                                            <small>Professors, estudiants i famílies connectades</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="feature-highlight">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <h6>Progrés</h6>
                                            <small>Seguiment personalitzat de l'aprenentatge</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="feature-highlight">
                                            <i class="fas fa-clock fa-2x mb-2"></i>
                                            <h6>24/7</h6>
                                            <small>Accés als recursos en qualsevol moment</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="feature-highlight">
                                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                            <h6>Segur</h6>
                                            <small>Dades protegides i accés controlat</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="registration-section">
                                    <h5 class="mb-3 text-center">
                                        <i class="fas fa-user-plus me-2"></i>
                                        ¿Primera vez aquí?
                                    </h5>
                                    <p class="mb-3 text-center opacity-90">
                                        Si eres estudiante o padre de familia y aún no tienes cuenta, puedes registrarte fácilmente.
                                    </p>
                                    <div class="text-center">
                                        <button class="btn btn-light btn-lg" onclick="showRegistration()">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Crear Nueva Cuenta
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel - Forms -->
                        <div class="col-lg-6 form-section">
                            <!-- Alert Messages -->
                            <?php if (!empty($registration_message)): ?>
                            <div class="alert alert-<?= $registration_message['type'] === 'success' ? 'success' : 'danger' ?>">
                                <i class="fas fa-<?= $registration_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                                <?= htmlspecialchars($registration_message['message']) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Tab Navigation -->
                            <div class="tab-navigation">
                                <button class="tab-btn active" id="loginTab" onclick="switchTab('login')">
                                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sessió
                                </button>
                                <button class="tab-btn" id="registerTab" onclick="switchTab('register')">
                                    <i class="fas fa-user-plus me-2"></i>Registrar-se
                                </button>
                            </div>

                            <!-- Login Form -->
                            <div id="loginForm">
                                <div class="text-center mb-4">
                                    <h3>Accedir al Portal</h3>
                                    <p class="subtitle">Introdueix les teves credencials per accedir</p>
                                </div>

                                <form id="login-form" method="POST" action="login_handler.php">
                                    <!-- User Type Selection -->
                                    <div class="user-type-selector">
                                        <div class="user-type-btn" onclick="selectUserType('student')">
                                            <div class="user-type-icon">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div class="user-type-label">Estudiant</div>
                                            <div class="user-type-desc">Accedeix a les teves classes i materials</div>
                                        </div>
                                        
                                        <div class="user-type-btn" onclick="selectUserType('parent')">
                                            <div class="user-type-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="user-type-label">Família</div>
                                            <div class="user-type-desc">Seguiment dels teus fills</div>
                                        </div>
                                        
                                        <div class="user-type-btn" onclick="selectUserType('teacher')">
                                            <div class="user-type-icon">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </div>
                                            <div class="user-type-label">Professor</div>
                                            <div class="user-type-desc">Gestiona les teves classes</div>
                                        </div>
                                        
                                        <div class="user-type-btn" onclick="selectUserType('admin')">
                                            <div class="user-type-icon">
                                                <i class="fas fa-cog"></i>
                                            </div>
                                            <div class="user-type-label">Admin</div>
                                            <div class="user-type-desc">Panell de control</div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="user_type" id="login_user_type" required>

                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com" required>
                                        <label for="email">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Contrasenya" required>
                                        <label for="password">
                                            <i class="fas fa-lock me-2"></i>Contrasenya
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember">
                                            <label class="form-check-label" for="remember">
                                                Recorda'm
                                            </label>
                                        </div>
                                        <a href="#" class="toggle-link">Has oblidat la contrasenya?</a>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Iniciar Sessió
                                    </button>
                                </form>

                                <div class="text-center">
                                    <p class="text-muted">
                                        No tens compte? 
                                        <a href="#" class="toggle-link" onclick="switchTab('register')">Registra't aquí</a>
                                    </p>
                                </div>
                            </div>

                            <!-- Registration Form -->
                            <div id="registerForm" style="display: none;">
                                <div class="text-center mb-4">
                                    <h3>Crear Nou Compte</h3>
                                    <p class="subtitle">Uneix-te a la nostra comunitat educativa</p>
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="register">

                                    <!-- User Type Selection for Registration -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Qui ets?</label>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="user-type-btn" onclick="selectRegisterType('student')">
                                                    <div class="user-type-icon">
                                                        <i class="fas fa-user-graduate"></i>
                                                    </div>
                                                    <div class="user-type-label">Estudiant</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="user-type-btn" onclick="selectRegisterType('parent')">
                                                    <div class="user-type-icon">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                    <div class="user-type-label">Família</div>                                    <input type="hidden" name="user_type" id="register_user_type" required>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="first_name" placeholder="Nombre" required>
                                                <label><i class="fas fa-user me-2"></i>Nombre</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="last_name" placeholder="Apellidos" required>
                                                <label><i class="fas fa-user me-2"></i>Apellidos</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-floating">
                                        <input type="email" class="form-control" name="email" placeholder="tu@email.com" required>
                                        <label><i class="fas fa-envelope me-2"></i>Email</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="tel" class="form-control" name="phone" placeholder="Teléfono">
                                        <label><i class="fas fa-phone me-2"></i>Teléfono</label>
                                    </div>

                                    <div id="ageField" class="form-floating" style="display: none;">
                                        <input type="number" class="form-control" name="age" placeholder="Edad" min="5" max="100">
                                        <label><i class="fas fa-birthday-cake me-2"></i>Edad</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" name="password" placeholder="Contraseña" required minlength="6">
                                        <label><i class="fas fa-lock me-2"></i>Contraseña</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="confirm_password" placeholder="Confirmar Contraseña" required>
                                        <label><i class="fas fa-lock me-2"></i>Confirmar Contraseña</label>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            Acepto los <a href="#" class="toggle-link">términos y condiciones</a>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Crear Cuenta
                                    </button>
                                </form>

                                <div class="text-center">
                                    <p class="text-muted">
                                        ¿Ya tienes cuenta? 
                                        <a href="#" class="toggle-link" onclick="switchTab('login')">Inicia sesión aquí</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedUserType = '';
        let selectedRegisterType = '';

        function switchTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            if (tab === 'login') {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                registerTab.classList.add('active');
                loginTab.classList.remove('active');
                registerForm.style.display = 'block';
                loginForm.style.display = 'none';
            }
        }

        function selectUserType(type) {
            // Reset all buttons
            document.querySelectorAll('#loginForm .user-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Activate selected button
            event.currentTarget.classList.add('active');
            selectedUserType = type;
            document.getElementById('login_user_type').value = type;
        }

        function selectRegisterType(type) {
            // Reset all buttons
            document.querySelectorAll('#registerForm .user-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Activate selected button
            event.currentTarget.classList.add('active');
            selectedRegisterType = type;
            document.getElementById('register_user_type').value = type;

            // Show/hide age field for students
            const ageField = document.getElementById('ageField');
            if (type === 'student') {
                ageField.style.display = 'block';
                ageField.querySelector('input').required = true;
            } else {
                ageField.style.display = 'none';
                ageField.querySelector('input').required = false;
            }
        }

        function showRegistration() {
            switchTab('register');
        }

        // Form validation
        document.querySelector('form[action=""]').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }

            if (!selectedRegisterType) {
                e.preventDefault();
                alert('Por favor selecciona si eres estudiante o padre/madre');
                return false;
            }
        });

        // Login form validation
        document.getElementById('login-form').addEventListener('submit', function(e) {
            if (!selectedUserType) {
                e.preventDefault();
                alert('Por favor selecciona tu tipo de usuario');
                return false;
            }
        });

        // Auto-show registration message
        <?php if (!empty($registration_message) && $registration_message['type'] === 'success'): ?>
        switchTab('login');
        <?php endif; ?>
    </script>
</body>
</html>