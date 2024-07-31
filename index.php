<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
  die();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jsonPost = json_decode(file_get_contents("php://input"), 1);

if (empty($jsonPost['pw']) || $jsonPost['pw'] != $_ENV['MAILER_PASSWORD']) {
  http_response_code(404);
  die();
}

$recipients = !empty($jsonPost['recipients']) ? $jsonPost['recipients'] : [];
$ccs = !empty($jsonPost['ccs']) ? $jsonPost['ccs'] : [];
$bccs = !empty($jsonPost['bccs']) ? $jsonPost['bccs']: [];
$subject = !empty($jsonPost['subject']) ? $jsonPost['subject'] : "No Subject";
$body = !empty($jsonPost['body']) ? $jsonPost['body'] : "";
$fromDescription = !empty($jsonPost['from']) ? $jsonPost['from'] : "Mio Mailer";

$mail = new PHPMailer(true);

try {
  //Server settings
  if ($jsonPost['debug']) {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
  }
  $mail->isSMTP();
  $mail->Host = $_ENV['SMTP_HOST'];
  $mail->SMTPAuth = true;
  $mail->Username = $_ENV['SMTP_USER'];
  $mail->Password = $_ENV['SMTP_PASSWORD'];
  $mail->SMTPSecure = '';
  //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
  $mail->Port       = $_ENV['SMTP_PORT'];

  $mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
  );

  //Recipients
  $mail->setFrom('mailer@mio.zone', $fromDescription);
  foreach ($recipients as $recipient) {
    $mail->addAddress($recipient);     //Add a recipient
  }

  foreach ($bccs as $bcc) {
    $mail->addCC($bcc);     //Add a recipient
  }

  foreach ($ccs as $cc) {
    $mail->addBCC($cc);
  }

  //Attachments
  // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
  // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

  //Content
  $mail->isHTML(true);                                  //Set email format to HTML
  $mail->Subject = $subject;
  $mail->Body    = $body;
  //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

  $mail->send();
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['error' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}