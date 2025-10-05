<?php

class NotificationSystem {
    private $emailConfig;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Email configuration
        $this->emailConfig = [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => 'info@math-advantage.com',
            'smtp_password' => '', // Set in production
            'from_name' => 'Math Advantage',
            'from_email' => 'info@math-advantage.com'
        ];
    }
    
    public function sendWelcomeEmail($studentData) {
        $subject = "Benvingut a Math Advantage!";
        
        $template = $this->getEmailTemplate('welcome', [
            'nom' => $studentData['nom'],
            'cognoms' => $studentData['cognoms'],
            'nivell' => $studentData['nivell_educatiu'],
            'centre' => $studentData['centre_educatiu']
        ]);
        
        $result = $this->sendEmail($studentData['email'], $subject, $template);
        
        // Log notification
        $this->logNotification([
            'type' => 'welcome_email',
            'recipient_email' => $studentData['email'],
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed',
            'student_id' => $studentData['id'] ?? null,
            'data' => json_encode($studentData)
        ]);
        
        return $result;
    }
    
    public function sendEnrollmentConfirmation($studentData) {
        $subject = "Confirmació d'inscripció - Math Advantage";
        
        $template = $this->getEmailTemplate('enrollment_confirmation', [
            'nom' => $studentData['nom'],
            'cognoms' => $studentData['cognoms'],
            'nivell' => $studentData['nivell_educatiu'],
            'centre' => $studentData['centre_educatiu']
        ]);
        
        $result = $this->sendEmail($studentData['email'], $subject, $template);
        
        $this->logNotification([
            'type' => 'enrollment_confirmation',
            'recipient_email' => $studentData['email'],
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed',
            'student_id' => $studentData['id'] ?? null,
            'data' => json_encode($studentData)
        ]);
        
        return $result;
    }
    
    public function sendContactAutoResponse($email, $nom) {
        $subject = "Gràcies pel teu contacte - Math Advantage";
        
        $template = $this->getEmailTemplate('contact_autoresponse', [
            'nom' => $nom
        ]);
        
        $result = $this->sendEmail($email, $subject, $template);
        
        $this->logNotification([
            'type' => 'general',
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed'
        ]);
        
        return $result;
    }
    
    public function sendPaymentReminder($email, $nom, $customMessage = null) {
        $subject = "Recordatori de pagament - Math Advantage";
        
        $template = $this->getEmailTemplate('payment_reminder', [
            'nom' => $nom,
            'custom_message' => $customMessage
        ]);
        
        $result = $this->sendEmail($email, $subject, $template);
        
        $this->logNotification([
            'type' => 'payment_reminder',
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed'
        ]);
        
        return $result;
    }
    
    public function sendClassReminder($email, $nom, $customMessage = null) {
        $subject = "Recordatori de classe - Math Advantage";
        
        $template = $this->getEmailTemplate('class_reminder', [
            'nom' => $nom,
            'custom_message' => $customMessage
        ]);
        
        $result = $this->sendEmail($email, $subject, $template);
        
        $this->logNotification([
            'type' => 'class_reminder',
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed'
        ]);
        
        return $result;
    }
    
    public function sendEnrollmentFollowup($email, $nom, $customMessage = null) {
        $subject = "Seguiment d'inscripció - Math Advantage";
        
        $template = $this->getEmailTemplate('enrollment_followup', [
            'nom' => $nom,
            'custom_message' => $customMessage
        ]);
        
        $result = $this->sendEmail($email, $subject, $template);
        
        $this->logNotification([
            'type' => 'general',
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $result ? 'sent' : 'failed'
        ]);
        
        return $result;
    }
    
    public function sendBulkNotification($type, $recipients, $subject, $message, $data = []) {
        $results = [];
        $successCount = 0;
        
        foreach ($recipients as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $result = $this->sendEmail($recipient, $subject, $message);
                $results[] = ['email' => $recipient, 'success' => $result];
                
                if ($result) $successCount++;
                
                $this->logNotification([
                    'type' => $type,
                    'recipient_email' => $recipient,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => $result ? 'sent' : 'failed',
                    'data' => json_encode($data)
                ]);
            }
        }
        
        return [
            'total_sent' => count($recipients),
            'successful' => $successCount,
            'failed' => count($recipients) - $successCount,
            'results' => $results
        ];
    }
    
    public function processNotification($notification) {
        // Process a specific notification from the queue
        switch ($notification['type']) {
            case 'welcome_email':
            case 'enrollment_confirmation':
            case 'general':
                return $this->sendEmail(
                    $notification['recipient_email'],
                    $notification['subject'],
                    $notification['message']
                );
                
            case 'whatsapp':
                return $this->sendWhatsAppMessage(
                    $notification['recipient_phone'],
                    $notification['message']
                );
                
            default:
                return false;
        }
    }
    
    private function sendEmail($to, $subject, $body) {
        // For development, log emails instead of sending
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
            error_log("Email to: $to | Subject: $subject | Body: " . substr($body, 0, 100) . "...");
            return true;
        }
        
        // Production email sending with PHPMailer or similar
        // This is a placeholder implementation
        $headers = [
            'From: ' . $this->emailConfig['from_name'] . ' <' . $this->emailConfig['from_email'] . '>',
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    private function sendWhatsAppMessage($phone, $message) {
        // WhatsApp API integration would go here
        // For now, just log and return true
        error_log("WhatsApp to: $phone | Message: $message");
        return true;
    }
    
    private function getEmailTemplate($template, $variables = []) {
        $templates = [
            'welcome' => $this->getWelcomeTemplate($variables),
            'enrollment_confirmation' => $this->getEnrollmentConfirmationTemplate($variables),
            'contact_autoresponse' => $this->getContactAutoResponseTemplate($variables),
            'payment_reminder' => $this->getPaymentReminderTemplate($variables),
            'class_reminder' => $this->getClassReminderTemplate($variables),
            'enrollment_followup' => $this->getEnrollmentFollowupTemplate($variables)
        ];
        
        return $templates[$template] ?? $this->getDefaultTemplate($variables);
    }
    
    private function getWelcomeTemplate($vars) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #8b5cf6, #a855f7); color: white; padding: 30px; text-align: center;'>
                <h1>Benvingut a Math Advantage!</h1>
                <p style='font-size: 18px; margin: 0;'>Estem encantats de tenir-te amb nosaltres</p>
            </div>
            <div style='padding: 30px; background: #f9f9f9;'>
                <h2>Hola {$vars['nom']} {$vars['cognoms']}!</h2>
                <p>Gràcies per confiar en Math Advantage per millorar el teu rendiment acadèmic en <strong>{$vars['nivell']}</strong>.</p>
                <p>A continuació trobaràs la informació sobre el teu procés d'inscripció i els propers passos.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3>Informació de l'estudiant:</h3>
                    <ul>
                        <li><strong>Nom:</strong> {$vars['nom']} {$vars['cognoms']}</li>
                        <li><strong>Nivell educatiu:</strong> {$vars['nivell']}</li>
                        <li><strong>Centre educatiu:</strong> {$vars['centre']}</li>
                    </ul>
                </div>
                
                <p>Aviat ens posarem en contacte amb tu per coordinar les classes i horaris.</p>
                <p>Si tens alguna pregunta, no dubtis en contactar-nos:</p>
                <ul>
                    <li>📞 Telèfon: 931 16 34 57</li>
                    <li>📱 WhatsApp: 658 174 783</li>
                    <li>📧 Email: info@math-advantage.com</li>
                </ul>
            </div>
            <div style='background: #8b5cf6; color: white; padding: 20px; text-align: center;'>
                <p>Math Advantage - Pare Sallarès, 67, Sabadell</p>
                <p>El teu èxit acadèmic és la nostra prioritat!</p>
            </div>
        </div>";
    }
    
    private function getEnrollmentConfirmationTemplate($vars) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #10b981; color: white; padding: 30px; text-align: center;'>
                <h1>Inscripció Confirmada! ✅</h1>
                <p style='font-size: 18px; margin: 0;'>Math Advantage</p>
            </div>
            <div style='padding: 30px; background: #f0fdf4;'>
                <h2>Perfecte, {$vars['nom']}!</h2>
                <p>La teva inscripció a Math Advantage ha estat <strong>confirmada</strong> amb èxit.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;'>
                    <h3>Detalls de la inscripció:</h3>
                    <ul>
                        <li><strong>Estudiant:</strong> {$vars['nom']} {$vars['cognoms']}</li>
                        <li><strong>Nivell:</strong> {$vars['nivell']}</li>
                        <li><strong>Centre:</strong> {$vars['centre']}</li>
                        <li><strong>Data d'inscripció:</strong> " . date('d/m/Y') . "</li>
                    </ul>
                </div>
                
                <h3>Propers passos:</h3>
                <ol>
                    <li>Rebràs una trucada o missatge per coordinar horaris</li>
                    <li>T'informarem sobre els materials necessaris</li>
                    <li>Començaràs les classes segons l'horari acordat</li>
                </ol>
                
                <p><strong>Recordatori:</strong> Si tens alguna pregunta o necessites canviar alguna cosa, contacta'ns immediatament.</p>
            </div>
        </div>";
    }
    
    private function getContactAutoResponseTemplate($vars) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #3b82f6; color: white; padding: 20px; text-align: center;'>
                <h1>Gràcies pel teu missatge!</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hola {$vars['nom']},</p>
                <p>Hem rebut el teu missatge i et respondem al més aviat possible.</p>
                <p>Normalment responem en menys de 24 hores durant els dies laborables.</p>
                <p>Per a consultes urgents, pots contactar-nos directament:</p>
                <ul>
                    <li>📞 931 16 34 57</li>
                    <li>📱 WhatsApp: 658 174 783</li>
                </ul>
                <p>Gràcies per confiar en Math Advantage!</p>
            </div>
        </div>";
    }
    
    private function getPaymentReminderTemplate($vars) {
        $message = $vars['custom_message'] ?? 'Aquest és un recordatori amable sobre el pagament pendent.';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #f59e0b; color: white; padding: 20px; text-align: center;'>
                <h1>Recordatori de Pagament</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hola {$vars['nom']},</p>
                <p>$message</p>
                <p>Si ja has efectuat el pagament, pots ignorar aquest missatge.</p>
                <p>Per qualsevol aclariment, contacta'ns:</p>
                <ul>
                    <li>📞 931 16 34 57</li>
                    <li>📧 info@math-advantage.com</li>
                </ul>
            </div>
        </div>";
    }
    
    private function getClassReminderTemplate($vars) {
        $message = $vars['custom_message'] ?? 'Recordatori de la teva pròxima classe.';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #8b5cf6; color: white; padding: 20px; text-align: center;'>
                <h1>Recordatori de Classe</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hola {$vars['nom']},</p>
                <p>$message</p>
                <p>No oblidis portar el material necessari per a la classe.</p>
                <p>Ens veiem aviat!</p>
            </div>
        </div>";
    }
    
    private function getEnrollmentFollowupTemplate($vars) {
        $message = $vars['custom_message'] ?? 'Seguiment de la teva inscripció a Math Advantage.';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #06b6d4; color: white; padding: 20px; text-align: center;'>
                <h1>Seguiment d'Inscripció</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hola {$vars['nom']},</p>
                <p>$message</p>
                <p>Si necessites ajuda o tens preguntes, contacta'ns:</p>
                <ul>
                    <li>📞 931 16 34 57</li>
                    <li>📧 info@math-advantage.com</li>
                </ul>
            </div>
        </div>";
    }
    
    private function getDefaultTemplate($vars) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #8b5cf6; color: white; padding: 20px; text-align: center;'>
                <h1>Math Advantage</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hola {$vars['nom']},</p>
                <p>Aquest és un missatge de Math Advantage.</p>
                <p>Per qualsevol consulta, contacta'ns:</p>
                <ul>
                    <li>📞 931 16 34 57</li>
                    <li>📧 info@math-advantage.com</li>
                </ul>
            </div>
        </div>";
    }
    
    private function logNotification($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('notifications_log', $data);
    }
    
    public function getNotificationStats($days = 30) {
        $sql = "SELECT 
                    type,
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM notifications_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                GROUP BY type, status, DATE(created_at)
                ORDER BY date DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}