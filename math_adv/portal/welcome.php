<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math Advantage - Portal Educatiu</title>
    
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
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
            --border-color: #e5e7eb;
            --text-muted: #6b7280;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            color: white;
        }
        
        .welcome-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            color: var(--dark-color);
            max-width: 600px;
            width: 100%;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(139, 92, 246, 0.4);
        }
        
        .btn-outline-secondary {
            border: 2px solid var(--text-muted);
            color: var(--text-muted);
            padding: 1rem 2rem;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .btn-outline-secondary:hover {
            background: var(--text-muted);
            color: white;
            transform: translateY(-2px);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .feature {
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 16px;
            text-align: center;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.2rem;
        }
        
        .spinner-border {
            color: var(--primary-color);
        }
        
        .loading-text {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .navbar {
            background: rgba(139, 92, 246, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
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
        }
        
        .navbar-toggler-icon {
            filter: invert(1);
        }
        
        /* ===== RESPONSIVE DESIGN COMPLETO PARA TODAS LAS PANTALLAS ===== */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575.98px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem !important; }
            
            .welcome-container {
                padding: 80px 0.5rem 1rem 0.5rem;
                min-height: 100vh;
            }
            
            .welcome-card {
                padding: 1.5rem 1rem;
                border-radius: 16px;
                max-width: 100%;
            }
            
            .logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .features {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin: 1.5rem 0;
            }
            
            .feature {
                padding: 1rem;
            }
            
            .feature-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .btn {
                font-size: 16px; /* Prevenir zoom iOS */
                padding: 0.8rem 1.5rem;
                margin-bottom: 0.5rem;
            }
            
            .d-flex.flex-column.flex-md-row {
                flex-direction: column !important;
            }
        }
        
        /* Small Devices (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .welcome-container {
                padding: 90px 1rem 2rem 1rem;
            }
            
            .welcome-card {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }
            
            .features {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .logo {
                width: 70px;
                height: 70px;
            }
        }
        
        /* Medium Devices - Tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .welcome-container {
                padding: 100px 1.5rem 2rem 1.5rem;
            }
            
            .welcome-card {
                padding: 2.5rem 2rem;
                max-width: 700px;
            }
            
            .features {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.2rem;
            }
        }
        
        /* Large Devices - Desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .welcome-container {
                padding: 110px 2rem 2rem 2rem;
            }
            
            .welcome-card {
                padding: 3rem 2.5rem;
                max-width: 800px;
            }
            
            .features {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }
        
        /* Extra Large Devices (1200px - 1399px) */
        @media (min-width: 1200px) and (max-width: 1399.98px) {
            .welcome-container {
                padding: 120px 2rem 3rem 2rem;
            }
            
            .welcome-card {
                padding: 3.5rem 3rem;
                max-width: 900px;
            }
        }
        
        /* Ultra Wide Screens (1400px+) */
        @media (min-width: 1400px) {
            .welcome-container {
                padding: 130px 2rem 3rem 2rem;
            }
            
            .welcome-card {
                padding: 4rem 3.5rem;
                max-width: 1000px;
                border-radius: 32px;
            }
            
            .logo {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            .features {
                gap: 2rem;
                margin: 3rem 0;
            }
            
            .feature {
                padding: 2rem;
            }
        }
        
        /* Landscape Mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .welcome-container {
                padding: 60px 1rem 1rem 1rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .features {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.8rem;
                margin: 1rem 0;
            }
        }
        
        /* High DPI Displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 2dppx) {
            .welcome-card {
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
                        <a class="navbar-brand" href="../index.html">
                <img src="../img/logo_math-advantatge.png" alt="Math Advantage" height="40" class="me-2">
                <span>Math Advantage</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
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
                        <a class="btn btn-outline-light btn-sm ms-2" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Portal
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="welcome-container" style="padding-top: 100px;">
        <div class="welcome-card">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            
            <h1 class="fw-bold mb-3">Benvingut a Math Advantage</h1>
            <p class="text-muted mb-4">Portal educatiu digital per a estudiants, famílies i professors</p>
            
            <!-- Features Grid -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h6 class="fw-semibold">Estudiants</h6>
                    <small class="text-muted">Accedeix als teus materials, tasques i qualificacions</small>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h6 class="fw-semibold">Famílies</h6>
                    <small class="text-muted">Seguiment del progrés acadèmic dels teus fills</small>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h6 class="fw-semibold">Professors</h6>
                    <small class="text-muted">Gestiona classes, arxius i comunicació</small>
                </div>
            </div>
            
            <div id="loading" style="display: none;">
                <div class="spinner-border mb-3" role="status">
                    <span class="visually-hidden">Carregant...</span>
                </div>
                <div class="loading-text">Redirigint al portal...</div>
            </div>
            
            <div id="content">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                    <a href="login.php" class="btn btn-primary btn-lg" onclick="showLoading()">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sessió
                    </a>
                    
                    <a href="../index.html" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-home me-2"></i>
                        Tornar al Web Principal
                    </a>
                </div>
                
                <div class="mt-4">
                    <small class="text-muted">
                        Primera vegada al portal? Pots <strong>registrar-te</strong> directament des de la pàgina d'inici de sessió
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showLoading() {
            document.getElementById('content').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
        }
    </script>
</body>
</html>