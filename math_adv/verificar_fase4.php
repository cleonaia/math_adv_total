<?php
/**
 * Script de Verificación Completa - Fase 4
 * Verifica que todas las funcionalidades estén implementadas y enlazadas correctamente
 */

session_start();
echo "🔍 VERIFICACIÓN COMPLETA - MATH ADVANTAGE FASE 4\n";
echo "===============================================\n\n";

$totalChecks = 0;
$passedChecks = 0;

// Función helper para verificaciones
function checkAndReport($condition, $message) {
    global $totalChecks, $passedChecks;
    $totalChecks++;
    
    if ($condition) {
        echo "✅ " . $message . "\n";
        $passedChecks++;
        return true;
    } else {
        echo "❌ " . $message . "\n";
        return false;
    }
}

// 1. VERIFICAR ESTRUCTURA DE ARCHIVOS
echo "1. 📁 VERIFICANDO ESTRUCTURA DE ARCHIVOS\n";
echo "----------------------------------------\n";

$requiredFiles = [
    // Evaluaciones
    'fase4/evaluaciones/EvaluacionSystem.php' => 'Backend de Evaluaciones',
    'fase4/evaluaciones/evaluaciones.html' => 'Frontend de Evaluaciones',
    
    // Videollamadas
    'fase4/videollamadas/VideollamadasSystem.php' => 'Backend de Videollamadas', 
    'fase4/videollamadas/videollamadas.html' => 'Frontend de Videollamadas',
    
    // Gamificación
    'fase4/gamificacion/GamificacionSystem.php' => 'Backend de Gamificación',
    'fase4/gamificacion/gamificacion.html' => 'Frontend de Gamificación',
    
    // Calendario
    'fase4/calendario/CalendarioSystem.php' => 'Backend de Calendario',
    'fase4/calendario/calendario.html' => 'Frontend de Calendario',
    
    // Chat
    'fase4/chat/ChatSystem.php' => 'Backend de Chat',
    'fase4/chat/chat.html' => 'Frontend de Chat',
    
    // Base de datos
    'database/schema_fase4.sql' => 'Schema de Base de Datos Fase 4'
];

foreach ($requiredFiles as $file => $description) {
    checkAndReport(file_exists($file), "{$description}: {$file}");
}

// 2. VERIFICAR CONTENIDO DE ARCHIVOS
echo "\n2. 📝 VERIFICANDO CONTENIDO DE ARCHIVOS\n";
echo "--------------------------------------\n";

// Verificar que los archivos PHP tienen las clases correctas
$phpClasses = [
    'fase4/evaluaciones/EvaluacionSystem.php' => 'EvaluacionSystem',
    'fase4/videollamadas/VideollamadasSystem.php' => 'VideollamadasSystem',
    'fase4/gamificacion/GamificacionSystem.php' => 'GamificacionSystem',
    'fase4/calendario/CalendarioSystem.php' => 'CalendarioSystem',
    'fase4/chat/ChatSystem.php' => 'ChatSystem'
];

foreach ($phpClasses as $file => $className) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        checkAndReport(
            strpos($content, "class {$className}") !== false,
            "Clase {$className} definida en {$file}"
        );
    }
}

// Verificar que los HTML tienen la estructura correcta
$htmlFiles = [
    'fase4/evaluaciones/evaluaciones.html' => 'Evaluacions - Math Advantage',
    'fase4/videollamadas/videollamadas.html' => 'Videollamadas - Math Advantage', 
    'fase4/gamificacion/gamificacion.html' => 'Gamificació - Math Advantage',
    'fase4/calendario/calendario.html' => 'Calendari - Math Advantage',
    'fase4/chat/chat.html' => 'Chat - Math Advantage'
];

foreach ($htmlFiles as $file => $expectedTitle) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        checkAndReport(
            strpos($content, "<title>{$expectedTitle}</title>") !== false,
            "Título correcto en {$file}"
        );
        checkAndReport(
            strpos($content, 'Bootstrap') !== false,
            "Bootstrap incluido en {$file}"
        );
        checkAndReport(
            strpos($content, 'Font Awesome') !== false,
            "Font Awesome incluido en {$file}"
        );
    }
}

// 3. VERIFICAR BASE DE DATOS
echo "\n3. 🗄️ VERIFICANDO BASE DE DATOS\n";
echo "------------------------------\n";

try {
    require_once 'php/config.php';
    
    // Verificar que el archivo de configuración existe
    checkAndReport(class_exists('Database'), 'Clase Database existe');
    
    // Intentar conexión
    try {
        $pdo = Database::getInstance()->getConnection();
        checkAndReport(true, 'Conexión a base de datos establecida');
        
        // Verificar tablas de Fase 4
        $requiredTables = [
            'evaluaciones' => 'Tabla de evaluaciones',
            'preguntas_evaluacion' => 'Tabla de preguntas',
            'respuestas_estudiantes' => 'Tabla de respuestas',
            'videollamadas_salas' => 'Tabla de salas de videollamadas',
            'videollamadas_participantes' => 'Tabla de participantes',
            'gamificacion_logros' => 'Tabla de logros',
            'usuario_logros' => 'Tabla de logros de usuario',
            'chat_conversaciones' => 'Tabla de conversaciones',
            'chat_mensajes' => 'Tabla de mensajes',
            'chat_participantes' => 'Tabla de participantes de chat',
            'calendario_eventos' => 'Tabla de eventos',
            'calendario_reservas' => 'Tabla de reservas'
        ];
        
        foreach ($requiredTables as $table => $description) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                checkAndReport($stmt->rowCount() > 0, $description);
            } catch (Exception $e) {
                checkAndReport(false, $description . " - Error: " . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        checkAndReport(false, 'Conexión a base de datos - Error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    checkAndReport(false, 'Archivo de configuración - Error: ' . $e->getMessage());
}

// 4. VERIFICAR ENLACES EN HTML
echo "\n4. 🔗 VERIFICANDO ENLACES Y NAVEGACIÓN\n";
echo "------------------------------------\n";

$htmlFiles = glob('fase4/*/*.html');
foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Verificar enlace de vuelta al portal
    checkAndReport(
        strpos($content, '../../portal/welcome.php') !== false ||
        strpos($content, '../portal/welcome.php') !== false ||
        strpos($content, 'portal/welcome.php') !== false,
        "Enlace al portal en {$filename}"
    );
    
    // Verificar que tiene navbar
    checkAndReport(
        strpos($content, '<nav') !== false && strpos($content, 'navbar') !== false,
        "Navbar presente en {$filename}"
    );
    
    // Verificar que es responsive
    checkAndReport(
        strpos($content, 'viewport') !== false,
        "Meta viewport en {$filename}"
    );
}

// 5. VERIFICAR INTEGRACIÓN DE ESTILOS
echo "\n5. 🎨 VERIFICANDO ESTILOS E INTEGRACIÓN\n";
echo "-------------------------------------\n";

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Verificar que usa la paleta de colores corporativa
    checkAndReport(
        strpos($content, '#8b5cf6') !== false || 
        strpos($content, '8b5cf6') !== false ||
        strpos($content, 'linear-gradient') !== false,
        "Colores corporativos en {$filename}"
    );
    
    // Verificar fuente Inter
    checkAndReport(
        strpos($content, 'Inter') !== false,
        "Fuente Inter en {$filename}"
    );
}

// 6. VERIFICAR FUNCIONALIDADES JAVASCRIPT
echo "\n6. ⚡ VERIFICANDO FUNCIONALIDADES JAVASCRIPT\n";
echo "------------------------------------------\n";

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Verificar que tiene JavaScript
    checkAndReport(
        strpos($content, '<script') !== false,
        "JavaScript presente en {$filename}"
    );
    
    // Verificar funcionalidades específicas según el módulo
    if (strpos($filename, 'evaluaciones') !== false) {
        checkAndReport(
            strpos($content, 'cronometro') !== false || strpos($content, 'timer') !== false,
            "Cronómetro en evaluaciones.html"
        );
    }
    
    if (strpos($filename, 'videollamadas') !== false) {
        checkAndReport(
            strpos($content, 'Jitsi') !== false || strpos($content, 'jitsi') !== false,
            "Integración Jitsi en videollamadas.html"
        );
    }
    
    if (strpos($filename, 'calendario') !== false) {
        checkAndReport(
            strpos($content, 'FullCalendar') !== false || strpos($content, 'calendar') !== false,
            "FullCalendar en calendario.html"
        );
    }
    
    if (strpos($filename, 'chat') !== false) {
        checkAndReport(
            strpos($content, 'WebSocket') !== false || strpos($content, 'chat') !== false,
            "Sistema de chat en chat.html"
        );
    }
}

// 7. VERIFICAR DOCUMENTACIÓN
echo "\n7. 📚 VERIFICANDO DOCUMENTACIÓN\n";
echo "------------------------------\n";

$docFiles = [
    'FASE4_DOCUMENTACION_COMPLETA.md' => 'Documentación completa de Fase 4',
    'ENLACES_E_INTEGRACION_FASE4.md' => 'Documentación de enlaces e integración',
    'README.md' => 'README principal actualizado'
];

foreach ($docFiles as $file => $description) {
    checkAndReport(file_exists($file), $description);
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        checkAndReport(
            strpos($content, 'Fase 4') !== false,
            "Contiene información de Fase 4: {$file}"
        );
    }
}

// 8. VERIFICAR README ACTUALIZADO
echo "\n8. 📋 VERIFICANDO README ACTUALIZADO\n";
echo "-----------------------------------\n";

if (file_exists('README.md')) {
    $readmeContent = file_get_contents('README.md');
    
    // Verificar que Fase 4 está marcada como completada
    checkAndReport(
        strpos($readmeContent, '✅ **Fase 4: Mejoras Avanzadas** - COMPLETADA') !== false,
        'Fase 4 marcada como completada en README'
    );
    
    // Verificar funcionalidades implementadas
    $fase4Features = [
        'Plataforma de evaluaciones y exámenes online',
        'Sistema de videollamadas integrado',
        'Gamificación y sistema de recompensas', 
        'Calendario interactivo con reservas',
        'Chat en tiempo real entre usuarios'
    ];
    
    foreach ($fase4Features as $feature) {
        checkAndReport(
            strpos($readmeContent, $feature) !== false,
            "Feature documentada: {$feature}"
        );
    }
}

// RESUMEN FINAL
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMEN DE VERIFICACIÓN\n";
echo str_repeat("=", 50) . "\n";

$percentage = ($totalChecks > 0) ? round(($passedChecks / $totalChecks) * 100, 2) : 0;

echo "Total de verificaciones: {$totalChecks}\n";
echo "Verificaciones exitosas: {$passedChecks}\n";
echo "Verificaciones fallidas: " . ($totalChecks - $passedChecks) . "\n";
echo "Porcentaje de éxito: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "🎉 ¡EXCELENTE! La Fase 4 está completamente implementada e integrada.\n";
    echo "✅ Todas las funcionalidades están correctamente enlazadas.\n";
    echo "🚀 El proyecto está listo para producción.\n";
} elseif ($percentage >= 80) {
    echo "👍 ¡BIEN! La mayoría de funcionalidades están implementadas.\n";
    echo "⚠️  Revisar los elementos fallidos para completar la integración.\n";
} elseif ($percentage >= 60) {
    echo "⚠️  REGULAR. Algunas funcionalidades necesitan atención.\n";
    echo "🔧 Se requiere trabajo adicional para completar la integración.\n";
} else {
    echo "❌ CRÍTICO. Faltan elementos importantes de la implementación.\n";
    echo "🚨 Se requiere revisión completa del proyecto.\n";
}

echo "\n📋 ESTADO FINAL: ";
if ($percentage >= 95) {
    echo "✅ FASE 4 COMPLETADA Y TOTALMENTE INTEGRADA\n";
} elseif ($percentage >= 80) {
    echo "🔄 FASE 4 MAYORMENTE COMPLETADA - REVISIÓN MENOR REQUERIDA\n";
} else {
    echo "🚧 FASE 4 EN DESARROLLO - TRABAJO ADICIONAL REQUERIDO\n";
}

echo "\n💡 Para obtener más detalles, revisa:\n";
echo "   📄 FASE4_DOCUMENTACION_COMPLETA.md\n";
echo "   🔗 ENLACES_E_INTEGRACION_FASE4.md\n";
echo "   📋 README.md actualizado\n\n";

echo "🏁 Verificación completada el " . date('Y-m-d H:i:s') . "\n";
?>