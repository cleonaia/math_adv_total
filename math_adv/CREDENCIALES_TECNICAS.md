# 🔑 CREDENCIALES Y ACCESO TÉCNICO
## 📋 Información Completa para Math Advantage

---

## 🌐 **URLS DE ACCESO**

### 🖥️ **Entorno Local (Desarrollo):**
```
Web Principal:     http://localhost:8080/
Portal Usuarios:   http://localhost:8080/portal/
Analytics:         http://localhost:8080/fase5/analytics/dashboard.html
Seguridad:         http://localhost:8080/fase5/security/dashboard.html
Gamificación:      http://localhost:8080/fase4/gamificacion/
Chat:              http://localhost:8080/fase4/chat/
Videollamadas:     http://localhost:8080/fase4/videollamadas/
Calendario:        http://localhost:8080/fase4/calendario/
Evaluaciones:      http://localhost:8080/fase4/evaluaciones/
```

### 🌍 **URLs Producción (cuando se publique):**
```
Web Principal:     https://mathadvantage.es/
Portal Usuarios:   https://mathadvantage.es/portal/
Analytics:         https://mathadvantage.es/admin/analytics/
Seguridad:         https://mathadvantage.es/admin/security/
```

---

## 🔐 **CREDENCIALES DE ACCESO**

### 👥 **Portal Multi-Usuario:**

#### 🎓 **Estudiante de Prueba:**
- **Email:** `estudiante@mathadvantage.es`  
- **Contraseña:** `Estudiante123!`
- **Acceso:** Dashboard estudiante, gamificación, chat, calendario

#### 👨‍🏫 **Profesor de Prueba:**
- **Email:** `profesor@mathadvantage.es`
- **Contraseña:** `Profesor123!`  
- **Acceso:** Gestión clases, analytics, evaluaciones, comunicación

#### 👨‍👩‍👧‍👦 **Padre de Familia:**
- **Email:** `padre@mathadvantage.es`
- **Contraseña:** `Padre123!`
- **Acceso:** Seguimiento hijos, informes, comunicación profesores

#### ⚙️ **Administrador Principal:**
- **Email:** `admin@mathadvantage.es`
- **Contraseña:** `Admin2025!@#`
- **Acceso:** Control total, analytics empresariales, seguridad

### 📊 **Analytics y Seguridad:**
- **Acceso protegido** con login de administrador
- **Autenticación requerida** para todas las funciones avanzadas
- **Tokens CSRF** automáticos para seguridad adicional

---

## 🗄️ **BASE DE DATOS**

### 📋 **Configuración Actual:**
```php
HOST: localhost
DATABASE: math_advantage
USER: root  
PASSWORD: (vacía en desarrollo local)
PORT: 3306
CHARSET: utf8mb4
```

### 📊 **Tablas Principales:**
```sql
users               # Usuarios del sistema (456 registros ejemplo)
students            # Información estudiantes  
teachers            # Información profesores
parents             # Información padres/familias
enrollments         # Inscripciones y matrículas
courses             # Cursos y asignaturas
evaluations         # Sistema evaluaciones
gamification        # Puntos, niveles, logros
security_logs       # Logs de seguridad
login_attempts      # Intentos de login
notifications       # Sistema notificaciones
file_uploads        # Gestión de archivos
```

### 🔧 **Comandos de Instalación BD:**
```bash
# Crear base de datos completa
mysql -u root -p < database/schema_complete.sql

# Solo esquema principal
mysql -u root -p < database/schema.sql

# Esquema de seguridad
mysql -u root -p < database/security_schema.sql
```

---

## ⚙️ **CONFIGURACIÓN TÉCNICA**

### 🐘 **PHP (php/config.php):**
```php
<?php
// Configuración Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'math_advantage');  
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración Seguridad
define('ENCRYPTION_KEY', 'math_advantage_2025_secure_key');
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600);

// Configuración Email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'info@mathadvantage.es');
define('SMTP_PASS', '[CONFIGURAR]');

// Configuración Notificaciones Push
define('VAPID_PUBLIC_KEY', '[GENERAR]');
define('VAPID_PRIVATE_KEY', '[GENERAR]');
?>
```

### 🌐 **Servidor Web:**
- **PHP:** 8.0+ requerido
- **Apache/Nginx:** Con mod_rewrite habilitado
- **MySQL:** 5.7+ o MariaDB 10.2+  
- **SSL:** Certificado necesario para PWA y notificaciones push
- **HTTPS:** Obligatorio para funcionalidades avanzadas

---

## 📱 **CONFIGURACIÓN PWA**

### 📄 **manifest.json:**
```json
{
  "name": "Math Advantage",
  "short_name": "MathAdv",
  "description": "Plataforma Educativa Math Advantage",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#8B5CF6",
  "theme_color": "#8B5CF6",
  "icons": [
    {
      "src": "img/logo_math-advantatge.png",
      "sizes": "192x192",
      "type": "image/png"
    }
  ]
}
```

### 🔔 **Service Worker (sw.js):**
- ✅ Cache de recursos estáticos
- ✅ Funcionamiento offline  
- ✅ Notificaciones push
- ✅ Sincronización en background

---

## 🔐 **CONFIGURACIÓN SEGURIDAD**

### 🛡️ **SecurityManager.php - Funciones Principales:**

#### **Métodos de Encriptación:**
```php
encrypt($data)           # Encripta datos sensibles AES-256
decrypt($encryptedData)  # Desencripta datos  
hashPassword($password)  # Hash seguro contraseñas
verifyPassword()         # Verifica contraseñas
```

#### **Detección de Ataques:**
```php
detectSQLInjection($input)  # Detecta patrones SQL injection
detectXSS($input)          # Detecta intentos XSS
validateCSRF($token)       # Valida tokens CSRF
rateLimitCheck($ip)        # Control límite peticiones
```

#### **Gestión de Sesiones:**
```php
createSecureSession($userId)  # Crea sesión segura
validateSession()            # Valida sesión actual  
regenerateSessionId()        # Regenera ID sesión
logSecurityEvent()           # Registra eventos seguridad
```

### 🚨 **Alertas de Seguridad Configuradas:**
- ✅ **Intentos login fallidos** (>5 intentos)
- ✅ **IPs sospechosas** (patrones ataque)
- ✅ **Accesos no autorizados** (URLs protegidas)
- ✅ **Errores sistema** (logs PHP)
- ✅ **Cambios configuración** (modificaciones admin)

---

## 📊 **ANALYTICS - MÉTRICAS DISPONIBLES**

### 📈 **Dashboard Principal:**

#### **Métricas Estudiantes:**
```sql
-- Total estudiantes activos
SELECT COUNT(*) FROM users WHERE user_type='student' AND active=1

-- Engagement rate (% activos últimos 30 días)  
SELECT (COUNT(DISTINCT user_id) * 100.0 / 
        (SELECT COUNT(*) FROM users WHERE user_type='student')) 
FROM user_activity WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)

-- Puntuación media evaluaciones
SELECT AVG(score) FROM evaluations WHERE completed=1
```

#### **Métricas Operacionales:**
- 👥 Total usuarios por tipo
- 📊 Tasa de engagement semanal/mensual
- 🎯 Rendimiento académico promedio
- 😊 Satisfacción familias (encuestas)
- 📱 Uso por dispositivo (móvil/desktop)
- ⏱️ Tiempo promedio sesiones

#### **Exportación Datos:**
```javascript
// Funciones JavaScript disponibles
exportToCSV()    # Exporta métricas a CSV
exportToPDF()    # Genera reporte PDF
exportToJSON()   # Datos en formato JSON
scheduleReport() # Programa informes automáticos
```

---

## 🎮 **SISTEMA GAMIFICACIÓN**

### 🏆 **Configuración Puntos y Niveles:**

#### **Acciones que Dan Puntos:**
```php
COMPLETAR_TAREA = 10 puntos
APROBAR_EVALUACION = 25 puntos  
PARTICIPAR_CHAT = 5 puntos
CONEXION_DIARIA = 15 puntos
RACHA_ESTUDIO = 50 puntos
AYUDAR_COMPAÑERO = 20 puntos
```

#### **Niveles y Rangos:**
```php
NOVATO:     0-100 puntos
APRENDIZ:   101-300 puntos
ESTUDIANTE: 301-600 puntos
EXPERTO:    601-1000 puntos
MAESTRO:    1001-1500 puntos
GENIO:      1500+ puntos
```

#### **Logros Especiales:**
- 🥇 **Primera Evaluación** - Completar primera evaluación
- 📚 **Estudioso** - 7 días consecutivos conectado
- 🤝 **Colaborador** - Ayudar a 10 compañeros
- 🎯 **Perfeccionista** - 10 evaluaciones con nota perfecta
- 💬 **Comunicador** - Participar en 50 chats
- ⚡ **Velocista** - Completar evaluación en tiempo récord

---

## 📞 **SOPORTE Y MANTENIMIENTO**

### 🛠️ **Contactos Técnicos:**

#### **Soporte Inmediato:**
- 📧 **Email:** `soporte@mathadvantage.es`
- 📞 **Teléfono:** `+34 XXX XXX XXX`
- 💬 **Chat:** Integrado en la plataforma
- 🚨 **Emergencias:** 24/7 disponible

#### **Documentación Técnica:**
- 📚 **Manual Administrador:** `/docs/admin-manual.pdf`
- 👨‍🏫 **Guía Profesores:** `/docs/teacher-guide.pdf`  
- 👨‍👩‍👧‍👦 **Tutorial Familias:** `/docs/parent-tutorial.pdf`
- 🔧 **API Documentation:** `/docs/api-reference.md`

### 📋 **Tareas de Mantenimiento:**

#### **Diarias (Automáticas):**
- ✅ Backup base de datos
- ✅ Limpieza logs antiguos
- ✅ Verificación seguridad
- ✅ Estadísticas uso

#### **Semanales:**
- ✅ Revisión rendimiento
- ✅ Actualización métricas
- ✅ Verificación backups
- ✅ Análisis alertas seguridad

#### **Mensuales:**
- ✅ Optimización base datos
- ✅ Revisión logs completa
- ✅ Actualización documentación
- ✅ Planificación mejoras

---

## 🚀 **PLAN DE LANZAMIENTO**

### 📅 **Cronograma Sugerido:**

#### **Semana 1: Preparación**
- [ ] Configurar servidor producción
- [ ] Migrar base de datos
- [ ] Configurar SSL y dominio
- [ ] Testing completo funcionalidades
- [ ] Crear usuarios iniciales

#### **Semana 2: Lanzamiento Suave**  
- [ ] Formar equipo administrativo
- [ ] Capacitar profesores (2 horas)
- [ ] Crear primeros cursos y materiales
- [ ] Probar comunicaciones email/SMS
- [ ] Verificar analytics funcionando

#### **Semana 3: Expansión**
- [ ] Incorporar estudiantes por grupos
- [ ] Enviar credenciales a familias  
- [ ] Tutorial uso PWA móvil
- [ ] Activar gamificación completa
- [ ] Monitorizar métricas iniciales

#### **Semana 4: Operación Completa**
- [ ] Todos los usuarios activos
- [ ] Promoción marketing nuevas funcionalidades
- [ ] Recopilar feedback usuarios
- [ ] Optimizaciones basadas en uso real
- [ ] Planificar siguiente fase mejoras

---

## ⚠️ **CONSIDERACIONES IMPORTANTES**

### 🔐 **Seguridad:**
- ✅ **Cambiar contraseñas** por defecto antes lanzamiento
- ✅ **Configurar HTTPS** obligatorio para producción
- ✅ **Activar firewall** con reglas específicas
- ✅ **Monitorización 24/7** desde día 1
- ✅ **Política backups** automáticos diarios

### 📱 **PWA y Móvil:**
- ✅ **Certificado SSL** requerido para notificaciones push
- ✅ **Generar claves VAPID** para push notifications
- ✅ **Iconos PWA** en todas las resoluciones
- ✅ **Testing** instalación en iOS y Android

### 📊 **Analytics:**
- ✅ **Configurar Google Analytics** opcional
- ✅ **Cumplimiento GDPR** en cookies
- ✅ **Anonimización datos** según normativa
- ✅ **Política privacidad** actualizada

---

*Documento actualizado: 5 de octubre de 2025*  
*Math Advantage - Plataforma Educativa Integral*

**🎓 ¡Lista para revolucionar la educación digital!** 🚀