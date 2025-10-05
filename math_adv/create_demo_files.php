<?php
// Math Advantage - Demo Files Creator
// Este script crea archivos de demostración para probar el sistema

require_once 'php/classes/Database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "🎭 Creando archivos de demostración para Math Advantage...\n\n";

// Crear directorios si no existen
$upload_dirs = [
    'uploads/materials/',
    'uploads/exercises/',
    'uploads/submissions/',
    'uploads/temp/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Creado directorio: {$dir}\n";
    }
}

// Archivos de demostración
$demo_files = [
    [
        'title' => 'Álgebra Básica - Introducción',
        'description' => 'Conceptos fundamentales del álgebra: variables, ecuaciones lineales y sistemas de ecuaciones.',
        'type' => 'theory',
        'class_id' => 1,
        'content' => "# Álgebra Básica - Introducción

## ¿Qué es el Álgebra?

El álgebra es una rama de las matemáticas que utiliza símbolos y letras para representar números y cantidades en fórmulas y ecuaciones.

## Conceptos Clave:

### Variables
Una variable es un símbolo (generalmente una letra) que representa un número desconocido.
Ejemplo: x, y, z, a, b

### Ecuaciones Lineales
Una ecuación lineal es una ecuación algebraica en la que el mayor exponente de la variable es 1.
Ejemplo: 2x + 3 = 7

### Propiedades de las Ecuaciones:
1. Propiedad aditiva: Si a = b, entonces a + c = b + c
2. Propiedad multiplicativa: Si a = b, entonces a × c = b × c
3. Propiedad simétrica: Si a = b, entonces b = a

## Ejercicios Básicos:
1. Resolver: x + 5 = 12
2. Resolver: 3x = 21
3. Resolver: 2x - 4 = 10

¡Practica estos conceptos para dominar el álgebra!"
    ],
    [
        'title' => 'Ejercicios de Ecuaciones Lineales',
        'description' => 'Colección de ejercicios para practicar la resolución de ecuaciones lineales de primer grado.',
        'type' => 'exercise',
        'class_id' => 1,
        'content' => "# Ejercicios de Ecuaciones Lineales

## Instrucciones:
Resuelve las siguientes ecuaciones lineales. Muestra todos los pasos de tu trabajo.

## Ejercicios Nivel Básico:

1. x + 7 = 15
2. x - 3 = 9
3. 2x = 16
4. x/3 = 4
5. 3x + 2 = 14

## Ejercicios Nivel Intermedio:

6. 2x + 5 = 3x - 7
7. 4(x - 3) = 2x + 6
8. (x + 2)/3 = (x - 1)/2
9. 5x - 3(x + 2) = 4
10. 2(3x - 1) + 5 = 3(x + 4)

## Ejercicios Nivel Avanzado:

11. (2x + 1)/4 - (x - 3)/6 = 1/2
12. 0.3x + 0.7(20 - x) = 0.5(20)
13. |2x - 3| = 7
14. √(x + 4) = 6
15. x² - 5x + 6 = 0

## Problemas de Aplicación:

16. La edad de Juan es el triple de la edad de María. Si la suma de sus edades es 48 años, ¿cuántos años tiene cada uno?

17. Un rectángulo tiene un perímetro de 40 cm. Si el largo es 6 cm mayor que el ancho, ¿cuáles son las dimensiones?

18. En una tienda, el precio de un artículo con descuento del 20% es €48. ¿Cuál era el precio original?

## Respuestas:
(Para uso del profesor)
1. x = 8    2. x = 12    3. x = 8    4. x = 12    5. x = 4
6. x = 12   7. x = 9     8. x = 8    9. x = 5     10. x = 3"
    ],
    [
        'title' => 'Fórmulas Geométricas Esenciales',
        'description' => 'Compendio de las fórmulas más importantes de geometría para áreas, perímetros y volúmenes.',
        'type' => 'resource',
        'class_id' => 2,
        'content' => "# Fórmulas Geométricas Esenciales

## Figuras Planas

### Cuadrado
- Perímetro: P = 4l
- Área: A = l²
- Diagonal: d = l√2

### Rectángulo  
- Perímetro: P = 2(a + b)
- Área: A = a × b
- Diagonal: d = √(a² + b²)

### Triángulo
- Perímetro: P = a + b + c
- Área: A = (base × altura)/2
- Área (Herón): A = √[s(s-a)(s-b)(s-c)] donde s = P/2

### Círculo
- Perímetro: P = 2πr
- Área: A = πr²
- Diámetro: d = 2r

### Trapecio
- Perímetro: P = a + b + c + d
- Área: A = [(B + b) × h]/2

## Figuras 3D

### Cubo
- Volumen: V = l³
- Área superficial: As = 6l²

### Prisma Rectangular
- Volumen: V = largo × ancho × altura
- Área superficial: As = 2(lw + lh + wh)

### Cilindro
- Volumen: V = πr²h
- Área superficial: As = 2πr² + 2πrh

### Esfera
- Volumen: V = (4/3)πr³
- Área superficial: As = 4πr²

### Cono
- Volumen: V = (1/3)πr²h
- Área superficial: As = πr² + πrs (s = altura inclinada)

## Teoremas Importantes

### Teorema de Pitágoras
En un triángulo rectángulo: c² = a² + b²

### Teorema de Tales
Si dos rectas son cortadas por varias rectas paralelas, los segmentos correspondientes son proporcionales.

## Constantes Importantes
- π ≈ 3.14159
- √2 ≈ 1.414
- √3 ≈ 1.732"
    ],
    [
        'title' => 'Tarea: Problemas de la Vida Real',
        'description' => 'Ejercicios prácticos que aplican conceptos matemáticos a situaciones cotidianas. Fecha límite: una semana.',
        'type' => 'homework',
        'class_id' => 1,
        'content' => "# Tarea: Problemas de la Vida Real

## Instrucciones Generales:
- Resuelve todos los problemas mostrando tu trabajo completo
- Fecha de entrega: Una semana desde la asignación
- Puedes trabajar en grupos de máximo 2 personas
- Presenta tus respuestas de forma clara y ordenada

## Problema 1: Planificación de Presupuesto (25 puntos)

María tiene un trabajo de medio tiempo y gana €600 al mes. Sus gastos mensuales son:
- Alquiler: €200
- Comida: €150  
- Transporte: €80
- Entretenimiento: €70
- Ahorros: €100

a) ¿Cuánto dinero le queda después de todos sus gastos?
b) Si quiere ahorrar €150 al mes en lugar de €100, ¿en qué categoría debería reducir gastos y en cuánto?
c) Si su salario aumenta un 15%, ¿cuál sería su nuevo salario y cuánto podría ahorrar manteniendo los mismos gastos?

## Problema 2: Diseño de Jardín (25 puntos)

Un arquitecto paisajista debe diseñar un jardín rectangular de 240 m² para un cliente.

a) Si el jardín debe tener un largo que sea el doble del ancho, ¿cuáles son las dimensiones?
b) ¿Cuántos metros de cerca se necesitan para rodear completamente el jardín?
c) Si el césped cuesta €12 por m², ¿cuál será el costo total del césped?

## Problema 3: Análisis de Ventas (25 puntos)

Una tienda de ropa registró las siguientes ventas durante una semana:
- Lunes: €450
- Martes: €320
- Miércoles: €580
- Jueves: €390
- Viernes: €720
- Sábado: €890
- Domingo: €240

a) ¿Cuál fue el promedio de ventas diarias?
b) ¿Qué día tuvo las mayores ventas y cuánto fue la diferencia con respecto al día de menores ventas?
c) Si la meta mensual es €12,000, ¿está la tienda en camino de cumplir su objetivo? (Asume que este patrón se mantiene)

## Problema 4: Cocina y Proporciones (25 puntos)

Una receta para 6 personas requiere:
- 2 tazas de harina
- 3 huevos
- 1.5 tazas de leche
- 0.5 tazas de aceite

a) ¿Qué cantidad de cada ingrediente se necesita para cocinar para 15 personas?
b) Si solo tienes 5 huevos disponibles, ¿para cuántas personas puedes cocinar?
c) Si quieres hacer la receta pero solo tienes 1 taza de harina, ¿qué cantidades necesitas de los otros ingredientes?

## Criterios de Evaluación:
- Procedimiento correcto: 40%
- Respuesta correcta: 30%
- Presentación y claridad: 20%
- Aplicación práctica: 10%

## Recursos Adicionales:
- Calculadora permitida
- Puedes consultar tus apuntes de clase
- En caso de dudas, programa una cita de tutoría

¡Buena suerte!"
    ]
];

// Insertar archivos de demostración
$teacher_id = 1; // ID del primer profesor

foreach ($demo_files as $file_data) {
    // Crear archivo físico
    $filename = date('Y-m-d_H-i-s') . '_' . strtolower(str_replace([' ', '-', ':', '¿', '?', '¡', '!'], '_', $file_data['title'])) . '.txt';
    $subdir = $file_data['type'] === 'theory' || $file_data['type'] === 'resource' ? 'materials/' : 'exercises/';
    $file_path = 'uploads/' . $subdir . $filename;
    
    // Escribir contenido
    file_put_contents($file_path, $file_data['content']);
    $file_size = filesize($file_path);
    
    // Insertar en base de datos
    try {
        $stmt = $pdo->prepare("
            INSERT INTO class_files (
                filename, original_name, file_path, file_size, file_type,
                class_id, uploaded_by, upload_type, title, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $filename,
            $file_data['title'] . '.txt',
            $subdir . $filename,
            $file_size,
            'txt',
            $file_data['class_id'],
            $teacher_id,
            $file_data['type'],
            $file_data['title'],
            $file_data['description']
        ]);
        
        echo "✅ Creado: " . $file_data['title'] . "\n";
        
    } catch (PDOException $e) {
        echo "❌ Error creando " . $file_data['title'] . ": " . $e->getMessage() . "\n";
    }
}

// Crear algunas tareas de ejemplo
echo "\n📝 Creando tareas de ejemplo...\n";

$assignments = [
    [
        'title' => 'Ejercicios de Ecuaciones Lineales',
        'description' => 'Completa los ejercicios del 1 al 15 del documento proporcionado. Muestra todo tu trabajo.',
        'class_id' => 1,
        'teacher_id' => $teacher_id,
        'due_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        'max_points' => 100
    ],
    [
        'title' => 'Problemas de la Vida Real',
        'description' => 'Resuelve los 4 problemas prácticos aplicando los conceptos aprendidos en clase.',
        'class_id' => 1,
        'teacher_id' => $teacher_id,
        'due_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
        'max_points' => 100
    ]
];

foreach ($assignments as $assignment) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO assignments (
                title, description, class_id, teacher_id, due_date, max_points, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $assignment['title'],
            $assignment['description'],
            $assignment['class_id'],
            $assignment['teacher_id'],
            $assignment['due_date'],
            $assignment['max_points']
        ]);
        
        echo "✅ Tarea creada: " . $assignment['title'] . "\n";
        
    } catch (PDOException $e) {
        echo "❌ Error creando tarea: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 ¡Archivos de demostración creados exitosamente!\n";
echo "📋 Se han creado:\n";
echo "   • " . count($demo_files) . " archivos de contenido\n";
echo "   • " . count($assignments) . " tareas de ejemplo\n";
echo "   • Directorios de organización\n";
echo "\n🔗 Puedes acceder a ellos desde:\n";
echo "   • Portal del profesor: /portal/teacher/files.php\n";
echo "   • Portal del estudiante: /portal/student/files.php\n";
echo "\n💡 Los archivos están listos para descargar y probar el sistema completo.\n";
?>