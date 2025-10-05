# Math Advantage - Sistema de Archivos y Recursos Compartidos

## 📁 Descripción General

El **Sistema de Archivos y Recursos Compartidos** es una funcionalidad integral de la Fase 3 que permite a profesores y estudiantes gestionar, compartir y acceder a materiales educativos de forma segura y organizada.

## 🎯 Funcionalidades Implementadas

### 👨‍🏫 **Para Profesores:**
- **Subida de Archivos:** Drag & drop intuitivo con vista previa
- **Tipos de Contenido:**
  - 📚 **Teoría:** Documentos conceptuales y explicaciones
  - ✏️ **Ejercicios:** Problemas y práctica
  - 📋 **Material de Clase:** Recursos generales
  - 🔧 **Recursos:** Herramientas y referencias
  - 📝 **Tareas:** Assignments con fecha límite
- **Gestión Avanzada:** Editar, eliminar, organizar por clase
- **Sistema de Tareas:** Crear assignments con archivos adjuntos
- **Estadísticas:** Tracking de descargas y uso

### 🎓 **Para Estudiantes:**
- **Acceso Seguro:** Solo archivos de su clase asignada
- **Descarga de Materiales:** Por categorías organizadas
- **Sistema de Entregas:** 
  - Subir archivos de respuesta
  - Comentarios en texto
  - Seguimiento de entregas
- **Feedback:** Ver calificaciones y comentarios del profesor
- **Timeline:** Fechas límite y estado de tareas

## 🛠️ Arquitectura Técnica

### **Base de Datos:**
```sql
class_files          - Archivos principales
assignments          - Tareas y ejercicios
student_submissions  - Entregas de estudiantes
file_downloads       - Tracking de descargas
file_comments        - Sistema de comentarios
file_permissions     - Control granular de acceso
```

### **Sistema de Archivos:**
```
uploads/
├── materials/       - Teoría y recursos
├── exercises/       - Ejercicios y práctica
├── submissions/     - Entregas de estudiantes
└── temp/           - Archivos temporales
```

### **Seguridad Implementada:**
- ✅ **Validación de Tipos MIME:** Prevención de archivos maliciosos
- ✅ **Control de Tamaño:** Límite de 50MB por archivo
- ✅ **Permisos por Rol:** Acceso basado en tipo de usuario
- ✅ **Nombres Únicos:** Prevención de conflictos de archivos
- ✅ **Sanitización:** Limpieza de nombres de archivo

## 📋 Tipos de Archivo Soportados

### **Documentos:**
- PDF, DOC, DOCX, TXT
- XLS, XLSX (hojas de cálculo)
- PPT, PPTX (presentaciones)

### **Multimedia:**
- JPG, JPEG, PNG, GIF (imágenes)
- MP4, AVI, MOV (videos)

### **Límites:**
- Tamaño máximo: **50MB** por archivo
- Archivos simultáneos: Sin límite
- Almacenamiento: Basado en espacio del servidor

## 🚀 Casos de Uso

### **Flujo del Profesor:**
1. **Login** → Dashboard del Profesor
2. **"Gestionar Archivos"** → Portal de gestión
3. **Arrastrar archivo** → Seleccionar clase y tipo
4. **Configurar metadatos** → Título y descripción
5. **Subir** → Archivo disponible para estudiantes

### **Flujo del Estudiante:**
1. **Login** → Dashboard del Estudiante  
2. **"Ver Materiales y Tareas"** → Portal de archivos
3. **Navegar por categorías** → Descargar recursos
4. **Ver tareas pendientes** → Subir entrega
5. **Recibir feedback** → Ver calificaciones

### **Flujo de Entregas:**
1. **Profesor crea tarea** con archivo adjunto opcional
2. **Estudiante ve tarea** en su portal
3. **Estudiante descarga** material (si existe)
4. **Estudiante sube entrega** (archivo + comentarios)
5. **Profesor ve entrega** y califica
6. **Estudiante recibe feedback** automáticamente

## 📊 Funcionalidades Avanzadas

### **Dashboard del Profesor:**
- **Vista consolidada** de todos los archivos
- **Filtros por clase y tipo** de contenido
- **Estadísticas de uso** (descargas por archivo)
- **Gestión de tareas** con fechas límite
- **Sistema de calificaciones** integrado

### **Portal del Estudiante:**  
- **Materiales organizados** por categoría
- **Estado de tareas** (pendiente, entregada, calificada)
- **Timeline de entregas** con fechas límite
- **Historial de calificaciones** y feedback
- **Acceso rápido** a recursos frecuentes

### **Sistema de Notificaciones:**
- 🔔 **Nuevos archivos** disponibles
- ⏰ **Recordatorios** de fechas límite
- ✅ **Confirmación** de entregas recibidas
- 📊 **Calificaciones** publicadas

## 🔧 Instalación y Configuración

### **Paso 1: Base de Datos**
```bash
./install_file_system.sh
```

### **Paso 2: Permisos de Directorio**
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/  # En producción
```

### **Paso 3: Archivos de Demostración**
```bash
php create_demo_files.php
```

### **Configuración PHP necesaria:**
```php
; php.ini settings recomendadas
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 300
memory_limit = 256M
```

## 📱 Responsive Design

### **Características Móviles:**
- **Drag & Drop Adaptativo:** Touch-friendly en tablets
- **Compresión Automática:** Optimización para conexiones lentas
- **UI Simplificada:** Interfaz adaptada a pantallas pequeñas
- **Navegación Táctil:** Botones y enlaces optimizados

### **Breakpoints:**
- **Desktop:** Vista completa con drag & drop
- **Tablet:** Navegación por pestañas
- **Mobile:** Lista vertical con acciones principales

## 🎨 Personalización y Temas

### **Variables CSS:**
```css
:root {
  --file-upload-bg: #f8f9fa;
  --file-hover-color: var(--primary-color);
  --assignment-border: 4px solid var(--success-color);
}
```

### **Iconografía por Tipo:**
- 📚 Teoría → `fas fa-book` (Azul)
- ✏️ Ejercicios → `fas fa-pencil-alt` (Verde)  
- 📋 Material → `fas fa-folder` (Gris)
- 🔧 Recursos → `fas fa-tools` (Naranja)
- 📝 Tareas → `fas fa-clipboard-list` (Rojo)

## 🔮 Extensiones Futuras (Fase 4)

### **Funcionalidades Planificadas:**
- 💬 **Comentarios en Archivos:** Sistema de feedback colaborativo
- 🔄 **Versionado:** Control de versiones de archivos
- 🏷️ **Sistema de Tags:** Etiquetado avanzado
- 📊 **Analytics Avanzados:** Métricas de engagement
- 🔗 **Integración Externa:** Google Drive, OneDrive
- 🎥 **Preview Integrado:** Visualización sin descarga
- 🔔 **Notificaciones Push:** Alertas en tiempo real

### **APIs Preparadas:**
- REST endpoints para integración móvil
- Webhooks para notificaciones externas
- Sincronización con sistemas LMS

## ✅ Estado Actual

### **✅ Completado:**
- Sistema completo de gestión de archivos
- Portal del profesor totalmente funcional
- Portal del estudiante con entregas
- Sistema de seguridad y permisos
- Responsive design optimizado
- Base de datos estructurada
- Scripts de instalación automática

### **🎯 Listo para:**
- Uso inmediato en producción
- Carga de archivos reales
- Gestión de clases activas
- Sistema de evaluaciones

## 📞 Soporte y Uso

### **Accesos Directos:**
- **Profesores:** `/portal/teacher/files.php`
- **Estudiantes:** `/portal/student/files.php`
- **Descargas:** `/portal/download_file.php?id={file_id}`

### **Troubleshooting:**
- **Error 413:** Archivo demasiado grande → Verificar `upload_max_filesize`
- **Error 403:** Sin permisos → Verificar rol de usuario
- **Error 404:** Archivo no encontrado → Verificar integridad de base de datos

---

**🎉 El Sistema de Archivos está completamente implementado y listo para revolucionar la experiencia educativa en Math Advantage!**