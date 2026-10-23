<?php
declare(strict_types=1);
namespace GymFlow;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

/** Transport SMTP configurable sin credenciales en el repositorio. */
final class MailService
{
    public function sendInvitation(string $to, string $name, string $gymName, string $role, string $rawToken): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('PHPMailer no está instalado. Reconstruí Docker con Composer.');
        }
        $base = trim((string)(getenv('APP_URL') ?: ''));
        if (!filter_var($base, FILTER_VALIDATE_URL) || !in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('APP_URL no está configurada correctamente.');
        }
        $link = rtrim($base, '/') . '/?page=activar&token=' . rawurlencode($rawToken);
        $host = trim((string)(getenv('SMTP_HOST') ?: ''));
        if ($host === '') throw new RuntimeException('SMTP_HOST no está configurado.');
        $from = trim((string)(getenv('MAIL_FROM') ?: ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('MAIL_FROM no es válido.');

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int)(getenv('SMTP_PORT') ?: 587);
        $mail->Timeout = 8;
        $mail->SMTPAuth = filter_var((string)(getenv('SMTP_AUTH') ?: 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($mail->SMTPAuth) {
            $mail->Username = (string)(getenv('SMTP_USER') ?: '');
            $mail->Password = (string)(getenv('SMTP_PASS') ?: '');
        }
        $encryption = (string)(getenv('SMTP_ENCRYPTION') ?: 'starttls');
        if ($encryption === 'starttls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        elseif ($encryption === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        elseif ($encryption === 'none') {
            // Solo para el SMTP de pruebas de Mailpit. Producción: usar TLS.
            if ((string)(getenv('APP_ENV') ?: '') !== 'local') {
                throw new RuntimeException('SMTP sin cifrado solo está permitido en APP_ENV=local.');
            }
            $mail->SMTPAutoTLS = false;
        } else throw new RuntimeException('SMTP_ENCRYPTION inválida.');

        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($from, (string)(getenv('MAIL_FROM_NAME') ?: 'GymFlow'));
        $mail->addAddress($to, $name);
        $mail->Subject = 'Activá tu cuenta en GymFlow';
        $nameHtml = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $gymHtml = htmlspecialchars($gymName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $roleHtml = htmlspecialchars($role, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $linkHtml = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mail->isHTML(true);
        $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:580px;margin:auto;color:#17304a">'
            . '<h1 style="color:#15786e">GymFlow</h1><h2>¡Bienvenido, ' . $nameHtml . '!</h2>'
            . '<p>Se creó una cuenta para vos en <strong>' . $gymHtml . '</strong> (rol: ' . $roleHtml . ').</p>'
            . '<p>Activá tu cuenta y elegí una contraseña. Este enlace vence en 24 horas y solo puede utilizarse una vez.</p>'
            . '<p><a href="' . $linkHtml . '" style="display:inline-block;background:#15786e;color:white;padding:12px 18px;border-radius:8px;text-decoration:none">Activar mi cuenta</a></p>'
            . '<p>Si no solicitaste este acceso, podés ignorar el mensaje.</p></div>';
        $mail->AltBody = "Hola, $name. Te invitaron a GymFlow ($gymName, $role). Activá tu cuenta usando este enlace (válido 24 horas, un solo uso): $link\nSi no esperabas esta invitación, ignorala.";
        $mail->send();
    }
}
