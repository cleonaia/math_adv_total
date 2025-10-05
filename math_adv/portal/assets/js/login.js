// Math Advantage Portal - Login JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initLogin();
    initPasswordToggle();
    initUserTypeSelector();
    initFormValidation();
});

function initLogin() {
    const form = document.getElementById('loginForm');
    const errorAlert = document.getElementById('errorAlert');
    const infoAlert = document.getElementById('infoAlert');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const userType = document.querySelector('input[name="userType"]:checked').value;
        
        const loginData = {
            email: formData.get('email'),
            password: formData.get('password'),
            userType: userType,
            rememberMe: formData.get('rememberMe') === 'on'
        };
        
        // Validar campos
        if (!loginData.email || !loginData.password) {
            showAlert('errorAlert', 'Tots els camps són obligatoris');
            return;
        }
        
        // Mostrar estado de carga
        setLoading(true);
        hideAlerts();
        
        try {
            const response = await fetch('login_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(loginData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Login exitoso
                showAlert('infoAlert', 'Login exitós! Redirigint...', 'success');
                
                // Guardar información del usuario en localStorage si "recordar"
                if (loginData.rememberMe) {
                    localStorage.setItem('mathAdvantage_userEmail', loginData.email);
                    localStorage.setItem('mathAdvantage_userType', userType);
                }
                
                // Redireccionar después de 1 segundo
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
                
            } else {
                // Login fallido
                showAlert('errorAlert', result.message || 'Error en el login');
                
                // Si está bloqueado, mostrar información adicional
                if (result.blocked) {
                    showAlert('errorAlert', result.message + ' Contacta amb administració si necessites ajuda.');
                }
            }
            
        } catch (error) {
            console.error('Login error:', error);
            showAlert('errorAlert', 'Error de connexió. Torna-ho a provar.');
        } finally {
            setLoading(false);
        }
    });
    
    // Restaurar email y tipo de usuario si están guardados
    const savedEmail = localStorage.getItem('mathAdvantage_userEmail');
    const savedUserType = localStorage.getItem('mathAdvantage_userType');
    
    if (savedEmail) {
        document.getElementById('email').value = savedEmail;
        document.getElementById('rememberMe').checked = true;
    }
    
    if (savedUserType) {
        const userTypeRadio = document.getElementById(savedUserType);
        if (userTypeRadio) {
            userTypeRadio.checked = true;
            updateHelpText(savedUserType);
        }
    }
}

function initPasswordToggle() {
    const toggleButton = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    toggleButton.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        const icon = toggleButton.querySelector('i');
        if (type === 'password') {
            icon.className = 'fas fa-eye';
        } else {
            icon.className = 'fas fa-eye-slash';
        }
    });
}

function initUserTypeSelector() {
    const userTypeRadios = document.querySelectorAll('input[name="userType"]');
    
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateHelpText(this.value);
            
            // Limpiar campos y errores al cambiar tipo de usuario
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            hideAlerts();
            
            // Agregar animación visual
            const helpContent = document.querySelector('.help-content');
            helpContent.classList.add('fade-in');
            setTimeout(() => {
                helpContent.classList.remove('fade-in');
            }, 300);
        });
    });
}

function updateHelpText(userType) {
    // Ocultar todos los textos de ayuda
    document.querySelectorAll('.help-item').forEach(item => {
        item.classList.add('d-none');
    });
    
    // Mostrar el texto correspondiente
    const helpElement = document.getElementById(userType + 'Help');
    if (helpElement) {
        helpElement.classList.remove('d-none');
    }
    
    // Actualizar placeholder del email según el tipo
    const emailField = document.getElementById('email');
    const emailLabel = document.querySelector('label[for="email"]');
    
    switch (userType) {
        case 'student':
            emailField.placeholder = 'correu@estudiant.com';
            break;
        case 'parent':
            emailField.placeholder = 'correu@familia.com';
            break;
        case 'teacher':
            emailField.placeholder = 'professor@math-advantage.com';
            break;
        case 'admin':
            emailField.placeholder = 'admin@math-advantage.com';
            break;
    }
}

function initFormValidation() {
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    
    emailField.addEventListener('blur', function() {
        validateEmail(this.value);
    });
    
    emailField.addEventListener('input', function() {
        clearFieldValidation(this);
    });
    
    passwordField.addEventListener('blur', function() {
        validatePassword(this.value);
    });
    
    passwordField.addEventListener('input', function() {
        clearFieldValidation(this);
    });
}

function validateEmail(email) {
    const emailField = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        showFieldError(emailField, 'Format d\'email invàlid');
        return false;
    }
    
    clearFieldValidation(emailField);
    return true;
}

function validatePassword(password) {
    const passwordField = document.getElementById('password');
    
    if (password && password.length < 4) {
        showFieldError(passwordField, 'La contrasenya ha de tenir almenys 4 caràcters');
        return false;
    }
    
    clearFieldValidation(passwordField);
    return true;
}

function showFieldError(field, message) {
    clearFieldValidation(field);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldValidation(field) {
    field.classList.remove('is-invalid', 'is-valid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function showAlert(alertId, message, type = 'danger') {
    const alert = document.getElementById(alertId);
    const messageElement = document.getElementById(alertId === 'errorAlert' ? 'errorMessage' : 'infoMessage');
    
    messageElement.textContent = message;
    alert.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info');
    
    if (type === 'success') {
        alert.classList.add('alert-success');
    } else if (type === 'info') {
        alert.classList.add('alert-info');
    } else {
        alert.classList.add('alert-danger');
    }
    
    alert.classList.add('fade-in');
    
    // Auto-hide después de 5 segundos para mensajes de éxito
    if (type === 'success') {
        setTimeout(() => {
            hideAlert(alertId);
        }, 5000);
    }
}

function hideAlert(alertId) {
    const alert = document.getElementById(alertId);
    alert.classList.add('d-none');
}

function hideAlerts() {
    hideAlert('errorAlert');
    hideAlert('infoAlert');
}

function setLoading(isLoading) {
    const form = document.getElementById('loginForm');
    const submitButton = form.querySelector('button[type="submit"]');
    const btnText = submitButton.querySelector('.btn-text');
    const btnSpinner = submitButton.querySelector('.btn-spinner');
    
    if (isLoading) {
        form.classList.add('loading');
        submitButton.disabled = true;
        btnText.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
    } else {
        form.classList.remove('loading');
        submitButton.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
    }
}

// Funcionalidades adicionales

// Detectar Enter en campos de formulario
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const form = document.getElementById('loginForm');
        if (document.activeElement.form === form) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    }
});

// Gestión de estados de conexión
window.addEventListener('online', function() {
    hideAlert('errorAlert');
    showAlert('infoAlert', 'Connexió restaurada', 'success');
    setTimeout(() => hideAlert('infoAlert'), 3000);
});

window.addEventListener('offline', function() {
    showAlert('errorAlert', 'Sense connexió a internet. Comprova la teva connexió.');
});

// Prevenir envío múltiple del formulario
let isSubmitting = false;
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    // Reset después de 5 segundos por seguridad
    setTimeout(() => {
        isSubmitting = false;
    }, 5000);
});

// Debug helpers (solo en desarrollo)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    // Agregar botones de login rápido para testing
    setTimeout(() => {
        addQuickLoginButtons();
    }, 1000);
}

function addQuickLoginButtons() {
    const helpSection = document.querySelector('.login-help');
    
    const quickLoginDiv = document.createElement('div');
    quickLoginDiv.className = 'mt-3 p-3 bg-light rounded';
    quickLoginDiv.innerHTML = `
        <small class="text-muted d-block mb-2">🛠️ Testing Quick Login:</small>
        <div class="btn-group-vertical w-100" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickLogin('student')">
                Test Student Login
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickLogin('admin')">
                Test Admin Login
            </button>
        </div>
    `;
    
    helpSection.appendChild(quickLoginDiv);
}

function quickLogin(type) {
    const testCredentials = {
        student: {
            email: 'maria.garcia@email.com',
            password: 'MA2024001',
            userType: 'student'
        },
        admin: {
            email: 'admin@math-advantage.com',
            password: 'password',
            userType: 'admin'
        }
    };
    
    const creds = testCredentials[type];
    if (creds) {
        document.getElementById('email').value = creds.email;
        document.getElementById('password').value = creds.password;
        document.getElementById(creds.userType).checked = true;
        updateHelpText(creds.userType);
    }
}