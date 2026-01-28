<?php
/**
 * SMTP TEST - Poort 465 SSL
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1 style='font-family:Arial;'>📧 Brevo SMTP Test (Poort 465 SSL)</h1>";
echo "<pre style='background:#1a1a1a;color:#0f0;padding:20px;font-size:14px;'>";

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    echo "🔧 Config: smtp-relay.brevo.com:465 (SSL)\n\n";

    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        $str = trim($str);
        if (!empty($str)) echo "   $str\n";
    };

    $mail->isSMTP();
    $mail->Host = 'smtp-relay.brevo.com';
    $mail->Port = 465;                                    // Poort 465
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // SSL (niet TLS!)
    $mail->SMTPAuth = true;
    $mail->Username = 'a0ed10001@smtp-brevo.com';
    $mail->Password = 'xsmtpsib-651451c613f015d84de8639158f86ca78af7adf19b0f16be80959238d2c02fd1-XInT4P03QFzgaSwo';
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 10;

    $mail->setFrom('cdivyun@gmail.com', 'De Gouden Schoen');
    $mail->addAddress('cdivyun@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test ' . date('H:i:s');

    $code = rand(100000, 999999);
    $mail->Body = "<h1>Test code: $code</h1><p>Als je dit ziet werkt het!</p>";

    echo "\n⏳ Verbinden...\n\n";
    $mail->send();

    echo "\n</pre>";
    echo "<div style='background:#4CAF50;color:white;padding:30px;text-align:center;font-size:24px;border-radius:8px;max-width:400px;margin:20px auto;'>";
    echo "✅ SUCCESS!<br>Code: $code<br><small>Check je inbox!</small></div>";

} catch (Exception $e) {
    echo "\n</pre>";
    echo "<div style='background:#f44336;color:white;padding:20px;border-radius:8px;max-width:600px;margin:20px auto;'>";
    echo "<h2>❌ Error</h2><p>" . htmlspecialchars($mail->ErrorInfo) . "</p></div>";
}
?>