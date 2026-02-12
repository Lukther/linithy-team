<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');


function loadEnvFile(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

loadEnvFile(__DIR__ . '/.env');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido.']);
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer não encontrado no servidor. Execute: composer require phpmailer/phpmailer'
    ]);
    exit;
}

require $autoloadPath;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$fromEmail = getenv('MAIL_FROM') ?: $smtpUser;
$fromName = getenv('MAIL_FROM_NAME') ?: 'Linithy Team';
$toEmail = getenv('MAIL_TO') ?: $smtpUser;

if ($smtpUser === '' || $smtpPass === '' || $fromEmail === '' || $toEmail === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Variáveis SMTP não configuradas. Veja o arquivo .env.example.'
    ]);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $fromName);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Novo contato do site: ' . $subject;

    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $mail->Body = "
        <h2>Novo contato recebido pelo site</h2>
        <p><strong>Nome:</strong> {$safeName}</p>
        <p><strong>E-mail:</strong> {$safeEmail}</p>
        <p><strong>Assunto:</strong> {$safeSubject}</p>
        <p><strong>Mensagem:</strong><br>{$safeMessage}</p>
    ";

    $mail->AltBody = "Novo contato recebido pelo site\n\n"
        . "Nome: {$name}\n"
        . "E-mail: {$email}\n"
        . "Assunto: {$subject}\n"
        . "Mensagem:\n{$message}";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível enviar o e-mail no momento.',
        'error' => $mail->ErrorInfo,
    ]);
}