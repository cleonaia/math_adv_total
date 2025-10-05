<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benvingut al Portal - Math Advantage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Purple/Violet Theme - Igual que la web principal */
            --primary-color: #8b5cf6; /* Main purple */
            --primary-dark: #7c3aed; /* Darker purple */
            --primary-light: #c4b5fd; /* Light purple */
            --secondary-color: #6366f1; /* Indigo complement */
            --accent-color: #ec4899; /* Pink accent */
            --success-color: #10b981; /* Green */
            --warning-color: #f59e0b; /* Orange */
            --dark-color: #1e1b4b; /* Deep navy */
            --light-color: #faf5ff; /* Very light purple */
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --geometry-gold: #fbbf24;
            --physics-blue: #3b82f6;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
        }
        
        .welcome-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            color: var(--math-dark);
            max-width: 500px;
            width: 100%;
        }
        
        .logo-portal {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.25);
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .welcome-subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .portal-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .portal-option {
            padding: 1.5rem 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            text-decoration: none;
            color: var(--math-dark);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .portal-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.2);
            color: var(--primary-color);
        }
        
        .portal-option i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-light);
            transition: all 0.3s ease;
        }
        
        .portal-option:hover i {
            color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .portal-option-title {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .features-list {
            text-align: left;
            margin: 2rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #6b7280;
        }
        
        .feature-item i {
            color: var(--success-color);
            font-size: 1.1rem;
        }
        
        .btn-primary-portal {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        
        .btn-primary-portal:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 16px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        @media (max-width: 768px) {
            .welcome-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .portal-options {
                grid-template-columns: 1fr;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
        
        .animated {
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="welcome-card animated">
                        <!-- Logo -->
                        <div class="logo-portal">
                            <i class="fas fa-infinity"></i>
                        </div>
                        
                        <!-- Título Principal -->
                        <h1 class="welcome-title">Benvingut al Portal Digital</h1>
                        <p class="welcome-subtitle">
                            Accedeix a la plataforma educativa més avançada per a matemàtiques
                        </p>
                        
                        <!-- Estadísticas -->
                        <div class="stats-row">
                            <div class="stat-item">
                                <span class="stat-number">2,847</span>
                                <span class="stat-label">Estudiants</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">45</span>
                                <span class="stat-label">Professors</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">98%</span>
                                <span class="stat-label">Satisfacció</span>
                            </div>
                        </div>
                        
                        <!-- Opciones de Portal -->
                        <div class="portal-options">
                            <a href="student/dashboard.php" class="portal-option">
                                <i class="fas fa-graduation-cap"></i>
                                <span class="portal-option-title">Estudiants</span>
                            </a>
                            <a href="teacher/dashboard.php" class="portal-option">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span class="portal-option-title">Professors</span>
                            </a>
                            <a href="parent/dashboard.php" class="portal-option">
                                <i class="fas fa-users"></i>
                                <span class="portal-option-title">Famílies</span>
                            </a>
                            <a href="admin/dashboard.php" class="portal-option">
                                <i class="fas fa-cog"></i>
                                <span class="portal-option-title">Administració</span>
                            </a>
                        </div>
                        
                        <!-- Funcionalidades -->
                        <div class="features-list">
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Evaluacions online amb correcció automàtica</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-video"></i>
                                <span>Videollamades integrades per a classes virtuals</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-comments"></i>
                                <span>Chat en temps real entre usuaris</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-trophy"></i>
                                <span>Sistema de gamificació amb logros i nivells</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-calendar"></i>
                                <span>Calendari interactiu amb sistema de reserves</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-chart-bar"></i>
                                <span>Analytics avançades i informes detallats</span>
                            </div>
                        </div>
                        
                        <!-- Botón Principal -->
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary btn-primary-portal">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sessió
                            </a>
                        </div>
                        
                        <!-- Enlaces Adicionales -->
                        <div class="mt-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <a href="../fase5/analytics/dashboard.html" class="btn btn-sm" style="background: var(--primary-color); color: white; border: none;">
                                        <i class="fas fa-chart-line me-1"></i> Analytics BI
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase5/security/dashboard.html" class="btn btn-sm" style="background: var(--danger-color); color: white; border: none;">
                                        <i class="fas fa-shield-alt me-1"></i> Seguretat
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-home me-1"></i> Web Principal
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Funcionalidades Avanzadas -->
                        <div class="mt-4 p-3" style="background: rgba(139, 92, 246, 0.05); border-radius: 12px; border: 1px solid rgba(139, 92, 246, 0.1);">
                            <h6 style="color: var(--primary-color); margin-bottom: 1rem;">
                                <i class="fas fa-rocket me-2"></i>Funcionalitats Avançades
                            </h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <a href="../fase4/evaluaciones/evaluaciones.html" class="btn btn-sm mb-2" style="border: 1px solid var(--primary-color); color: var(--primary-color);">
                                        <i class="fas fa-clipboard-check"></i><br>
                                        <small>Exàmens</small>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase4/chat/chat.html" class="btn btn-sm mb-2" style="border: 1px solid var(--success-color); color: var(--success-color);">
                                        <i class="fas fa-comments"></i><br>
                                        <small>Chat</small>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase4/videollamadas/videollamadas.html" class="btn btn-sm mb-2" style="border: 1px solid var(--warning-color); color: var(--warning-color);">
                                        <i class="fas fa-video"></i><br>
                                        <small>Video</small>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase4/calendario/calendario.html" class="btn btn-sm mb-2" style="border: 1px solid var(--physics-blue); color: var(--physics-blue);">
                                        <i class="fas fa-calendar"></i><br>
                                        <small>Calendari</small>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase4/gamificacion/gamificacion.html" class="btn btn-sm mb-2" style="border: 1px solid var(--accent-color); color: var(--accent-color);">
                                        <i class="fas fa-trophy"></i><br>
                                        <small>Jocs</small>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="../fase4/notificaciones/notificaciones.html" class="btn btn-sm mb-2" style="border: 1px solid var(--secondary-color); color: var(--secondary-color);">
                                        <i class="fas fa-bell"></i><br>
                                        <small>Avisos</small>
                                    </a>
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
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.welcome-card');
            setTimeout(() => {
                card.style.transform = 'translateY(0)';
                card.style.opacity = '1';
            }, 100);
        });
        
        // Efectos hover mejorados
        document.querySelectorAll('.portal-option').forEach(option => {
            option.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            option.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Actualizar estadísticas en tiempo real (simulado)
        function updateStats() {
            const stats = document.querySelectorAll('.stat-number');
            stats[0].textContent = Math.floor(Math.random() * 100) + 2800; // Estudiantes
            stats[2].textContent = Math.floor(Math.random() * 3) + 97 + '%'; // Satisfacción
        }
        
        // Actualizar cada 10 segundos
        setInterval(updateStats, 10000);
        
        // Añadir efectos hover a los botones de funcionalidades
        document.querySelectorAll('.btn[style*="border"]').forEach(btn => {
            const originalColor = btn.style.color;
            btn.addEventListener('mouseenter', function() {
                this.style.background = originalColor;
                this.style.color = 'white';
                this.style.transform = 'scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.background = 'transparent';
                this.style.color = originalColor;
                this.style.transform = 'scale(1)';
            });
        });
        
        // Detectar si es PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            document.body.classList.add('pwa-mode');
        }
    </script>
</body>
</html>
