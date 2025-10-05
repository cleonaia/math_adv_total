// Math Advantage - Enhanced Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavigation();
    initSmoothScrolling();
    initFormValidation();
    initChatbot();
    initAnimations();
    initContactForm();
    initScrollAnimations();
    initMathematicalEffects();
    initAnalyticsDashboard();
    initChatbotEnterKey();
});

// Navigation functionality
function initNavigation() {
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelectorAll('.nav-link');

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });

    // Active nav link highlighting
    window.addEventListener('scroll', function() {
        let current = '';
        const sections = document.querySelectorAll('section');
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (window.scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    // Close mobile menu when clicking on a link
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                bsCollapse.hide();
            }
        });
    });
}

// Smooth scrolling for anchor links
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Form validation and submission
function initFormValidation() {
    const form = document.getElementById('inscripcioForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm(this)) {
            submitForm(this);
        }
    });

    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Validate email format
    const emailField = form.querySelector('#email');
    if (emailField && !isValidEmail(emailField.value)) {
        showFieldError(emailField, 'Format d\'email invàlid');
        isValid = false;
    }
    
    // Validate phone format
    const phoneField = form.querySelector('#telefon');
    if (phoneField && !isValidPhone(phoneField.value)) {
        showFieldError(phoneField, 'Format de telèfon invàlid');
        isValid = false;
    }
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Aquest camp és obligatori');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Format d\'email invàlid');
        return false;
    }
    
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        showFieldError(field, 'Format de telèfon invàlid');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[+]?[\d\s\-\(\)]{9,}$/;
    return phoneRegex.test(phone);
}

async function submitForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviant...';
    submitButton.disabled = true;
    form.classList.add('loading');
    
    try {
        const formData = new FormData(form);
        
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Sol·licitud enviada correctament! Et contactarem aviat.', 'success');
            form.reset();
            
            // Send to WhatsApp if available
            if (result.whatsapp_link) {
                setTimeout(() => {
                    window.open(result.whatsapp_link, '_blank');
                }, 1500);
            }
        } else {
            showNotification(result.message || 'Error en enviar la sol·licitud. Torna-ho a provar.', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de connexió. Torna-ho a provar més tard.', 'error');
    } finally {
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        form.classList.remove('loading');
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Chatbot functionality
function initChatbot() {
    const chatbotButton = document.getElementById('chatbot-button');
    const chatbot = document.getElementById('chatbot');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    
    if (!chatbotButton || !chatbot) return;
    
    // Handle Enter key in chat input
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Auto-scroll chat messages
    const observer = new MutationObserver(() => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
    
    observer.observe(chatMessages, { childList: true });
}

function toggleChatbot() {
    const chatbot = document.getElementById('chatbot');
    const button = document.getElementById('chatbot-button');
    
    if (chatbot.style.display === 'none' || !chatbot.style.display) {
        chatbot.style.display = 'block';
        chatbot.style.animation = 'slideInUp 0.5s ease-out';
        if (button) button.querySelector('i').className = 'fas fa-times';
        
        // Focus input when opened
        setTimeout(() => {
            document.getElementById('chat-input').focus();
        }, 300);
    } else {
        chatbot.style.animation = 'slideOutDown 0.3s ease-in';
        setTimeout(() => {
            chatbot.style.display = 'none';
        }, 300);
        if (button) button.querySelector('i').className = 'fas fa-comments';
    }
}

function minimizeChatbot() {
    const chatbot = document.getElementById('chatbot');
    // Minimize functionality - could expand this later
    toggleChatbot();
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    addUserMessage(message);
    input.value = '';
    
    // Show typing indicator
    showTypingIndicator();
    
    // Simulate bot response with more realistic delay
    setTimeout(() => {
        hideTypingIndicator();
        const response = getBotResponse(message);
        addBotMessage(response);
    }, 1500 + Math.random() * 1000); // Random delay for realism
}

function sendQuickMessage(message) {
    addUserMessage(message);
    
    // Show typing indicator
    showTypingIndicator();
    
    setTimeout(() => {
        hideTypingIndicator();
        const response = getBotResponse(message);
        addBotMessage(response);
    }, 1200);
}

function addUserMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message user-message mb-3';
    
    messageDiv.innerHTML = `
        <div class="d-flex justify-content-end">
            <div class="user-message-bubble bg-primary text-white p-3 rounded-3 shadow-sm" style="max-width: 80%; border-radius: 20px 20px 5px 20px !important;">
                ${message}
            </div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

function addBotMessage(message) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message bot-message mb-3';
    
    messageDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="bot-avatar me-3 flex-shrink-0">
                <div class="avatar-sm bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 35px; height: 35px;">
                    <span class="fw-bold" style="font-size: 0.8rem;">∫π</span>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="message-header mb-1">
                    <small class="text-muted fw-medium">Dr. Pythagoras</small>
                </div>
                <div class="message-bubble bg-white p-3 rounded-3 shadow-sm position-relative" style="border-left: 4px solid #667eea;">
                    ${message}
                </div>
            </div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

function showTypingIndicator() {
    const chatMessages = document.getElementById('chat-messages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typing-indicator';
    typingDiv.className = 'message bot-message mb-3';
    
    typingDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="bot-avatar me-3 flex-shrink-0">
                <div class="avatar-sm bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 35px; height: 35px;">
                    <span class="fw-bold" style="font-size: 0.8rem;">∫π</span>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="message-header mb-1">
                    <small class="text-muted fw-medium">Dr. Pythagoras</small>
                    <span class="typing-indicator ms-2">
                        <span></span><span></span><span></span>
                    </span>
                </div>
            </div>
        </div>
    `;
    
    chatMessages.appendChild(typingDiv);
    scrollToBottom();
}

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function initChatbotEnterKey() {
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        });
    }
}

function getBotResponse(userMessage) {
    const message = userMessage.toLowerCase();
    
    // Math Advantage Enhanced Chatbot Responses
    if (message.includes('hola') || message.includes('bon dia') || message.includes('salut') || message === '') {
        return `
            <strong class="text-primary">🔬 Salutacions científiques!</strong><br><br>
            Sóc el <strong>Dr. Pythagoras</strong>, el vostre assistent d'IA especialitzat en matemàtiques, física i química de Math Advantage.<br><br>
            <div class="mt-3">
                <strong>🎯 Podem ajudar-te amb:</strong><br>
                • Informació sobre cursos i nivells<br>
                • Metodologia personalitzada<br>
                • Horaris i preus<br>
                • Projectes Erasmus+<br><br>
                <em>Com puc ajudar-te a descobrir l'univers de les ciències exactes?</em> ∑∫π
            </div>
        `;
    }
    
    if (message.includes('informació sobre cursos') || message.includes('cursos') || message.includes('matèries')) {
        return `
            <strong class="text-primary">📚 Els Nostres Cursos Especialitzats</strong><br><br>
            <strong>🎓 NIVELLS DISPONIBLES:</strong><br>
            • ESO (1r - 4t) - Matemàtiques<br>
            • Batxillerat - Matemàtiques I & II<br>
            • Batxillerat - Física i Química<br>
            • Universitat - Càlcul i Àlgebra<br>
            • Preparació PAU/Selectivitat<br><br>
            
            <strong>⚡ MODALITATS:</strong><br>
            • Classes presencials (Sabadell)<br>
            • Online en temps real<br>
            • Sessions individuals<br>
            • Grups reduïts (màx. 6 alumnes)<br><br>
            
            <em>Vols saber més d'algun nivell específic?</em> 🤔
        `;
    }
    
    if (message.includes('horaris i preus') || message.includes('horari') || message.includes('preus') || message.includes('preu')) {
        return `
            <strong class="text-primary">📅 Horaris & 💰 Preus</strong><br><br>
            <strong>🕒 HORARIS D'ATENCIÓ:</strong><br>
            • Matins: 10:00-13:00h<br>
            • Tardes: 16:30-20:30h<br>
            • Dilluns a divendres<br><br>
            
            <strong>💰 PREUS ORIENTATIUS:</strong><br>
            • Individual: Des de 25€/hora<br>
            • Grup petit (2-3): Des de 18€/hora<br>
            • Online: Des de 20€/hora<br><br>
            
            <strong>📞 Per informació detallada:</strong><br>
            • Telèfon: 931 16 34 57 / 658 174 783<br>
            • Email: info@math-advantage.com<br><br>
            
            <em>Podem fer un pressupost personalitzat!</em> 💡
        `;
    }
    
    if (message.includes('metodologia') || message.includes('com funciona') || message.includes('ensenyament')) {
        return `
            <strong class="text-primary">🧮 La Nostra Metodologia Científica</strong><br><br>
            <strong>🔬 ENFOCAMENT PERSONALITZAT:</strong><br>
            1. <strong>Diagnòstic inicial</strong> - Avaluem el nivell actual<br>
            2. <strong>Pla personalitzat</strong> - f(x) = Talent × Esforç<br>
            3. <strong>Seguiment continu</strong> - Monitoratge del progrés<br>
            4. <strong>Suport integral</strong> - Dubtes 24/7<br><br>
            
            <strong>🎯 OBJECTIUS:</strong><br>
            • Perdre la por a les matemàtiques<br>
            • Desenvolupar pensament lògic<br>
            • Millorar notes i confiança<br>
            • Preparar per al futur acadèmic<br><br>
            
            <em>"La meva principal motivació és que els alumnes perdin la por a les matemàtiques"</em> - Lucia Emilova, Directora
        `;
    }
    
    if (message.includes('preu') || message.includes('cost') || message.includes('quan costa')) {
        return '💰 Els preus varien segons nivell i modalitat. Per una valoració personalitzada:<br><br>📞 <strong>931 16 34 57</strong> o <strong>658 174 783</strong><br>📧 info@math-advantage.com<br><br>Oferim pla a mida per cada estudiant!';
    }
    
    if (message.includes('inscri') || message.includes('apunta') || message.includes('com em puc')) {
        return '✅ <strong>INSCRIU-TE FÀCILMENT:</strong><br>1️⃣ Omple el formulari de la web<br>2️⃣ Truca\'ns: 931 16 34 57<br>3️⃣ Visita\'ns: Pare Sallarès, 67, Sabadell<br><br>T\'ajudem a trobar la millor opció per tu! 🎯';
    }
    
    if (message.includes('online') || message.includes('distància') || message.includes('virtual')) {
        return '💻 <strong>SÍ! Classes online disponibles:</strong><br>• Mateixa qualitat que presencial<br>• Tecnologia interactiva avançada<br>• Professors especialitzats<br>• Modalitat mixta també possible<br><br>🆓 Prova una classe gratuïta!';
    }
    
    if (message.includes('professor') || message.includes('equip') || message.includes('qui ensenya')) {
        return '👩‍🏫 <strong>EL NOSTRE EQUIP EXPERT:</strong><br>• <strong>Lucia Emilova</strong> - Propietària, Llicenciada Matemàtiques (20+ anys)<br>• <strong>Irene Valderrama</strong> - Graduada Física UAB<br><br>Professors especialitzats i metodologia personalitzada! ⭐';
    }
    
    if (message.includes('ubicació') || message.includes('adreça') || message.includes('on sou') || message.includes('sabadell')) {
        return '📍 <strong>VINE A VEURE\'NS:</strong><br>📍 Pare Sallarès, 67<br>🏙️ 08201 Sabadell<br>🚇 Ben comunicat amb transport públic<br><br>📞 931 16 34 57 per indicacions específiques!';
    }
    
    if (message.includes('erasmus') || message.includes('europa') || message.includes('internacional')) {
        return '🇪🇺 <strong>ERASMUS+ PARTNER des de 2016!</strong><br>• Mobilitat educativa europea<br>• Projectes col·laboratius internacionals<br>• Qualitat certificada EU<br>• Formació professorat actualitzada<br><br>Excel·lència reconeguda! ⭐';
    }
    
    if (message.includes('nivell') || message.includes('curso') || message.includes('eso') || message.includes('batx')) {
        return '📚 <strong>TOTS ELS NIVELLS:</strong><br>🎓 ESO (1r, 2n, 3r, 4t)<br>🎓 Batxillerat (1r i 2n)<br>🎓 Universitari<br>🔬 Matemàtiques, Física i Química<br><br>Metodologia personalitzada per cada alumne! 🎯';
    }
    
    if (message.includes('gràcies') || message.includes('merci')) {
        return 'De res! Si tens més dubtes, no dubtis en preguntar. També pots contactar-nos directament al 933 123 456.';
    }
    
    if (message.includes('adéu') || message.includes('fins')) {
        return 'Fins aviat! Esperem veure\'t aviat a Math Advantage. Que tinguis un bon dia!';
    }
    
    // Default response
    return 'Interessant pregunta! Per obtenir informació més detallada, et recomano que contactis directament amb nosaltres al 933 123 456 o info@math-advantage.com. També pots veure més informació a la nostra web.';
}

// Animation on scroll
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);
    
    // Observe cards and sections
    const elementsToAnimate = document.querySelectorAll('.card, .feature-icon, h2, h1');
    elementsToAnimate.forEach(el => observer.observe(el));
}

// Contact form (if separate from inscription)
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) return;
    
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('php/contacte.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Missatge enviat correctament!', 'success');
                this.reset();
            } else {
                showNotification(result.message || 'Error en enviar el missatge', 'error');
            }
        } catch (error) {
            showNotification('Error de connexió', 'error');
        }
    });
}

// WhatsApp integration
function openWhatsApp() {
    const phone = '34644789012'; // Replace with actual phone
    const message = 'Hola! M\'agradaria obtenir més informació sobre els cursos de Math Advantage.';
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}

// Statistics counter animation
function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.dataset.counter);
                const duration = 2000; // 2 seconds
                const step = target / (duration / 16); // 60fps
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    counter.textContent = Math.floor(current);
                    
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    }
                }, 16);
                
                observer.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => observer.observe(counter));
}

// Call counter animation when page loads
document.addEventListener('DOMContentLoaded', animateCounters);

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add to window for global access
window.toggleChatbot = toggleChatbot;
window.sendMessage = sendMessage;
window.openWhatsApp = openWhatsApp;

// Portal Functions - Phase 3
function openStudentPortal() {
    // Check if user is logged in
    checkAuthAndRedirect('student');
}

function openFamilyPortal() {
    checkAuthAndRedirect('family');
}

function openTeacherPortal() {
    checkAuthAndRedirect('teacher');
}

function openAdminPortal() {
    checkAuthAndRedirect('admin');
}

function checkAuthAndRedirect(role) {
    // Redirect directly to portal
    window.open('portal/index.php', '_blank');
}

function getPortalIcon(role) {
    const icons = {
        student: '<i class="fas fa-user-graduate"></i>',
        family: '<i class="fas fa-users"></i>',
        teacher: '<i class="fas fa-chalkboard-teacher"></i>',
        admin: '<i class="fas fa-cogs"></i>'
    };
    return icons[role] || '<i class="fas fa-user"></i>';
}

function getPortalTitle(role) {
    const titles = {
        student: 'Alumnes',
        family: 'Famílies',
        teacher: 'Docents',
        admin: 'Administració'
    };
    return titles[role] || 'Digital';
}

function showDemoInfo(role) {
    const demoFeatures = {
        student: [
            '📚 Materials de classe interactius',
            '📅 Horari personalitzat amb recordatoris',
            '📊 Seguiment del teu progrés acadèmic',
            '✅ Tasques i deures organitzats',
            '🏆 Sistema de badges i achievements',
            '💬 Xat directe amb professors'
        ],
        family: [
            '👨‍👩‍👧‍👦 Visió completa de tots els fills',
            '📈 Informes detallats de progrés',
            '💳 Gestió de pagaments i facturació',
            '📧 Comunicació directa amb professors',
            '📅 Calendari familiar sincronitzat',
            '🔔 Notificacions personalitzades'
        ],
        teacher: [
            '🎓 Gestió completa de classes',
            '📝 Sistema d\'avaluació digital',
            '📚 Biblioteca de recursos educatius',
            '📊 Anàlisi detallada d\'alumnes',
            '📧 Comunicació eficient amb famílies',
            '📅 Planificació curricular avançada'
        ],
        admin: [
            '📊 Dashboard analític en temps real',
            '👥 Gestió completa d\'usuaris i rols',
            '💰 Control financer i facturació',
            '📈 Informes i estadístiques detallades',
            '🔧 Configuració avançada del sistema',
            '📢 Eines de comunicació massiva'
        ]
    };
    
    const features = demoFeatures[role] || [];
    const featuresHtml = features.map(feature => `<li class="list-group-item border-0">${feature}</li>`).join('');
    
    document.querySelector('#portalModal .modal-body').innerHTML = `
        <div class="demo-info">
            <div class="mb-3" style="font-size: 2.5rem; color: var(--primary-color);">
                ${getPortalIcon(role)}
            </div>
            <h5 class="fw-bold mb-3">Funcionalitats del Portal ${getPortalTitle(role)}</h5>
            <ul class="list-group list-group-flush">
                ${featuresHtml}
            </ul>
            <div class="mt-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Demo disponible:</strong> Contacta amb nosaltres per veure una demostració completa del portal.
                </div>
                <div class="d-grid gap-2">
                    <a href="portal/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Accedir al Portal Real
                    </a>
                    <a href="#contacte" class="btn btn-outline-primary" data-bs-dismiss="modal">
                        <i class="fas fa-phone me-2"></i>Sol·licitar Demo
                    </a>
                </div>
            </div>
        </div>
    `;
}

// Enhanced Analytics Initialization
function initAnalyticsDashboard() {
    const ctx = document.getElementById('analyticsChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Gen', 'Feb', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Des'],
            datasets: [{
                label: 'Inscripcions',
                data: [12, 19, 15, 25, 22, 30, 28, 35, 32, 40, 38, 45],
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Visites Web',
                data: [65, 89, 80, 95, 88, 110, 105, 125, 120, 140, 135, 150],
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(139, 92, 246, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(139, 92, 246, 0.1)'
                    }
                }
            }
        }
    });
}

// Enhanced Mathematical Effects
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-on-scroll');
            }
        });
    }, observerOptions);

    // Observe course cards
    document.querySelectorAll('.course-card').forEach(card => {
        observer.observe(card);
    });

    // Observe team cards
    document.querySelectorAll('.team-card').forEach(card => {
        observer.observe(card);
    });

    // Observe feature sections
    document.querySelectorAll('.glass-card').forEach(card => {
        observer.observe(card);
    });
}

function initMathematicalEffects() {
    // Add dynamic mathematical symbols
    createFloatingMathSymbols();
    
    // Enhanced formula animations
    animateFormulas();
    
    // Add particle effects to buttons
    enhanceButtons();
}

function createFloatingMathSymbols() {
    const symbols = ['∑', '∫', 'π', '∞', '∂', 'Δ', 'α', 'β', 'θ', '√', '≤', '≥'];
    const container = document.querySelector('.hero-section');
    
    if (!container) return;
    
    symbols.slice(0, 6).forEach((symbol, index) => {
        const element = document.createElement('div');
        element.className = `floating-math-symbol symbol-${index}`;
        element.textContent = symbol;
        element.style.cssText = `
            position: absolute;
            font-size: ${Math.random() * 15 + 12}px;
            color: rgba(139, 92, 246, ${Math.random() * 0.2 + 0.05});
            font-family: 'Times New Roman', serif;
            pointer-events: none;
            z-index: 1;
            top: ${Math.random() * 70 + 15}%;
            left: ${Math.random() * 80 + 10}%;
            animation: floatMath ${Math.random() * 8 + 12}s ease-in-out infinite ${Math.random() * 3}s;
        `;
        
        container.appendChild(element);
    });
    
    // Add CSS for floating animation if not exists
    if (!document.querySelector('#float-math-css')) {
        const style = document.createElement('style');
        style.id = 'float-math-css';
        style.textContent = `
            @keyframes floatMath {
                0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.05; }
                25% { transform: translateY(-15px) rotate(1deg); opacity: 0.15; }
                50% { transform: translateY(-8px) rotate(-0.5deg); opacity: 0.08; }
                75% { transform: translateY(-20px) rotate(1.5deg); opacity: 0.12; }
            }
        `;
        document.head.appendChild(style);
    }
}

function animateFormulas() {
    const formulas = document.querySelectorAll('.formula-line, .formula-mini');
    
    formulas.forEach((formula, index) => {
        formula.style.animationDelay = `${index * 0.2}s`;
        formula.style.transition = 'all 0.3s ease';
        
        formula.addEventListener('mouseenter', () => {
            formula.style.transform = 'scale(1.05)';
            formula.style.color = 'var(--primary-color)';
            formula.style.textShadow = '0 0 8px rgba(139, 92, 246, 0.4)';
        });
        
        formula.addEventListener('mouseleave', () => {
            formula.style.transform = 'scale(1)';
            formula.style.textShadow = 'none';
        });
    });
}

function enhanceButtons() {
    const buttons = document.querySelectorAll('.btn-primary');
    
    buttons.forEach(button => {
        button.style.transition = 'all 0.3s ease';
        
        button.addEventListener('mouseenter', () => {
            button.style.boxShadow = '0 0 15px rgba(139, 92, 246, 0.5)';
            button.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.boxShadow = '';
            button.style.transform = 'translateY(0) scale(1)';
        });
        
        button.addEventListener('click', (e) => {
            // Create enhanced ripple effect
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.4);
                border-radius: 50%;
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
                z-index: 1;
            `;
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // Add enhanced ripple animation CSS
    if (!document.querySelector('#ripple-css')) {
        const style = document.createElement('style');
        style.id = 'ripple-css';
        style.textContent = `
            @keyframes rippleEffect {
                to {
                    transform: scale(3);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// =============================================
// Ábaco Decorativo Animado - Solo Visual
// =============================================

document.addEventListener('DOMContentLoaded', () => {
    // Las animaciones secuenciales se manejan completamente por CSS
    // No necesitamos JavaScript para las animaciones del ábaco
    console.log('Ábaco decorativo cargado con animaciones secuenciales');
    
    // Instagram Feed Functionality
    initializeInstagramFeed();
    
    // Facebook Widget Functionality
    initializeFacebookWidget();
});

// =============================================
// Instagram Feed Functionality Mejorado
// =============================================

function initializeInstagramFeed() {
    const instagramPosts = document.querySelectorAll('.instagram-post');
    const instagramContainer = document.querySelector('.instagram-feed-container');
    
    instagramPosts.forEach((post, index) => {
        // Click handler mejorado
        post.addEventListener('click', (e) => {
            // Efecto de clic más suave
            post.style.transform = 'scale(0.95) translateY(-4px)';
            
            setTimeout(() => {
                window.open('https://www.instagram.com/mathadvantage/', '_blank');
                post.style.transform = '';
            }, 150);
        });
        
        // Animación de entrada más elegante
        post.style.opacity = '0';
        post.style.transform = 'translateY(30px) scale(0.8)';
        
        setTimeout(() => {
            post.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            post.style.opacity = '1';
            post.style.transform = 'translateY(0) scale(1)';
        }, index * 150 + 300);
        
        // Efecto de hover mejorado para stats
        const overlay = post.querySelector('.post-overlay');
        if (overlay) {
            post.addEventListener('mouseenter', () => {
                const stats = overlay.querySelectorAll('.post-stats span');
                stats.forEach((stat, i) => {
                    setTimeout(() => {
                        stat.style.transform = 'translateY(0)';
                        stat.style.opacity = '1';
                    }, i * 100);
                });
            });
            
            post.addEventListener('mouseleave', () => {
                const stats = overlay.querySelectorAll('.post-stats span');
                stats.forEach(stat => {
                    stat.style.transform = 'translateY(10px)';
                    stat.style.opacity = '0.7';
                });
            });
        }
    });
    
    // Animación del contenedor principal
    if (instagramContainer) {
        instagramContainer.style.opacity = '0';
        instagramContainer.style.transform = 'translateY(40px)';
        
        setTimeout(() => {
            instagramContainer.style.transition = 'all 1s ease';
            instagramContainer.style.opacity = '1';
            instagramContainer.style.transform = 'translateY(0)';
        }, 200);
    }
}

// =============================================
// Facebook Widget Functionality Mejorado
// =============================================

function initializeFacebookWidget() {
    const facebookWidget = document.querySelector('.facebook-widget-enhanced');
    
    if (facebookWidget) {
        // Animación de entrada más espectacular
        facebookWidget.style.opacity = '0';
        facebookWidget.style.transform = 'translateX(50px) scale(0.9)';
        
        setTimeout(() => {
            facebookWidget.style.transition = 'all 1.2s cubic-bezier(0.4, 0, 0.2, 1)';
            facebookWidget.style.opacity = '1';
            facebookWidget.style.transform = 'translateX(0) scale(1)';
        }, 800);
        
        // Animación de stats individuales
        const statItems = facebookWidget.querySelectorAll('.stat-item');
        statItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.6s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 1200 + (index * 200));
        });
        
        // Interacciones mejoradas con los botones
        const primaryBtn = facebookWidget.querySelector('.btn-facebook-primary');
        const secondaryBtns = facebookWidget.querySelectorAll('.btn-facebook-secondary');
        
        if (primaryBtn) {
            primaryBtn.addEventListener('mouseenter', () => {
                primaryBtn.style.transform = 'translateY(-3px) scale(1.02)';
            });
            
            primaryBtn.addEventListener('mouseleave', () => {
                primaryBtn.style.transform = 'translateY(0) scale(1)';
            });
        }
        
        secondaryBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Efecto de ripple
                const ripple = document.createElement('span');
                ripple.className = 'ripple-effect';
                btn.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
                
                // Feedback visual
                btn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    btn.style.transform = 'scale(1)';
                }, 150);
            });
        });
    }
}

// =============================================
// PWA y Service Worker Registration
// =============================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
                
                // Verificar actualizaciones
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Nueva versión disponible
                            showUpdateNotification();
                        }
                    });
                });
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

function showUpdateNotification() {
    if (confirm('Nueva versión disponible. ¿Actualizar ahora?')) {
        window.location.reload();
    }
}