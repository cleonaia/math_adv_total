#!/bin/bash

# Math Advantage - File System Installation Script
echo "🚀 Instalando Sistema de Archivos de Math Advantage..."

# Verificar si existe la base de datos
echo "📋 Verificando configuración de la base de datos..."

# Crear las tablas necesarias usando PHP
php -r "
\$config = [
    'host' => 'localhost',
    'dbname' => 'math_advantage',
    'username' => 'root',
    'password' => ''
];

try {
    \$pdo = new PDO(
        \"mysql:host={\$config['host']};dbname={\$config['dbname']}\",
        \$config['username'],
        \$config['password']
    );
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo \"✅ Conexión a base de datos establecida\n\";
    
    // Leer y ejecutar el archivo SQL
    \$sql_content = file_get_contents('database/file_system_schema.sql');
    
    // Dividir por sentencias
    \$statements = preg_split('/;\s*$/m', \$sql_content);
    
    foreach (\$statements as \$statement) {
        \$statement = trim(\$statement);
        if (empty(\$statement) || strpos(\$statement, '--') === 0) {
            continue;
        }
        
        try {
            \$pdo->exec(\$statement);
            echo \"✅ Ejecutada sentencia SQL\n\";
        } catch (PDOException \$e) {
            if (strpos(\$e->getMessage(), 'already exists') === false) {
                echo \"⚠️  Error: \" . \$e->getMessage() . \"\n\";
            } else {
                echo \"ℹ️  Tabla ya existe, continuando...\n\";
            }
        }
    }
    
    echo \"✅ Sistema de archivos instalado correctamente\n\";
    
} catch (PDOException \$e) {
    echo \"❌ Error de conexión: \" . \$e->getMessage() . \"\n\";
    echo \"💡 Asegúrate de que MySQL esté ejecutándose y las credenciales sean correctas\n\";
}
"

# Crear directorios de subida si no existen
echo "📁 Creando directorios de archivos..."
mkdir -p uploads/materials
mkdir -p uploads/exercises  
mkdir -p uploads/submissions
mkdir -p uploads/temp

# Establecer permisos
chmod -R 755 uploads/

echo "✅ ¡Sistema de archivos instalado correctamente!"
echo ""
echo "📋 Funcionalidades disponibles:"
echo "   • Profesores pueden subir materiales, teoría, ejercicios y tareas"
echo "   • Estudiantes pueden descargar archivos y entregar tareas"
echo "   • Sistema de permisos basado en roles"
echo "   • Tracking de descargas y actividad"
echo "   • Soporte para múltiples tipos de archivo"
echo ""
echo "🔗 Accesos:"
echo "   • Profesores: /portal/teacher/files.php"
echo "   • Estudiantes: /portal/student/files.php"
echo ""
echo "🎉 ¡Listo para usar!"