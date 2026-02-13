<?php
include 'includes/header.php';
require 'includes/conn.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$amount = $_POST['amount'];
$email = $_SESSION['email'];
$name = $_SESSION['name'];
$total = $amount * 45;
$event = "The Big Summer Festival 2026";

// --- PDF & QR GENERATIE START ---
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$html = "<html><head><style>
    body { font-family: sans-serif; }
    .ticket { border: 2px solid #764ba2; padding: 50px; text-align: center; border-radius: 15px; }
    .qr-code { margin-top: 30px; }
    h1 { color: #764ba2; }
    .persons { font-size: 24px; font-weight: bold; color: #764ba2; margin: 20px 0; }
</style></head><body>";

$writer = new PngWriter();

// Genereer 1 unieke code voor de hele groep
$uniqueCode = 'TICK-' . strtoupper(uniqid()) . '-GRP' . $amount;

// Voeg het aantal personen toe aan de QR code data
$qrData = json_encode([
    'code' => $uniqueCode,
    'persons' => $amount,
    'event' => $event,
    'name' => $name
]);

// QR code maken voor dit groepsticket
$qr = new QrCode($qrData);
$qrResult = $writer->write($qr);
$qrBase64 = base64_encode($qrResult->getString());

$html .= "<div class='ticket'>
            <h1>GROEPSTICKET</h1>
            <h2>{$event}</h2>
            <p><strong>Hoofdbezoeker:</strong> {$name}</p>
            <p><strong>Locatie:</strong> Festivalterrein A, Antwerpen</p>
            <p class='persons'>Geldig voor {$amount} " . ($amount == 1 ? 'persoon' : 'personen') . "</p>
            <p><strong>Unieke Code:</strong> {$uniqueCode}</p>
            <div class='qr-code'><img src='data:image/png;base64,{$qrBase64}' width='250'></div>
        </div>";

$html .= "</body></html>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

$directory = 'uploads/pdf_tickets/';
if (!is_dir($directory)) {
    mkdir($directory);
}

$filename = 'Ticket_' . $amount . 'persons_' . time() . '.pdf';
$filepath = $directory . $filename;
file_put_contents($filepath, $pdfOutput);
// --- PDF & QR GENERATIE EINDE ---

// --- DATABASE PUSH START ---
$user_id = $_SESSION['user_id'];
$ppticket = 45.0;
$status = 'unused'; // Status hersteld naar een waarde die in de ENUM staat
$order_date = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO tickets (status, email, amount, user_id, ppticket, order_date, unique_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssiidss", $status, $email, $amount, $user_id, $ppticket, $order_date, $uniqueCode);
$stmt->execute();
$stmt->close();
// --- DATABASE PUSH EINDE ---


$body = "
<html>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
        <h2 style='color: #764ba2;'>Bedankt voor je bestelling, {$name}!</h2>
        <p>Je hebt succesvol een <strong>groepsticket</strong> gekocht voor <strong>{$amount} " . ($amount == 1 ? 'persoon' : 'personen') . "</strong> voor <strong>{$event}</strong>.</p>
        <p><strong>Totaalbedrag:</strong> &euro;{$total}</p>
        <hr style='border: 0; border-top: 1px solid #eee;'>
        <p>Je groepsticket is bijgevoegd als bijlage bij deze e-mail. Deze ene QR-code geeft toegang aan alle {$amount} personen.</p>
        <p>We zien je graag op het evenement!</p>
        <br>
        <br>
        <p>Met vriendelijke groet,<br><strong>Het TicketMaster Team</strong></p>
    </div>
</body>
</html>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = '0ff01c6e27eeed';
    $mail->Password   = '3463f55aa8c5a3';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 2525;

    $mail->setFrom('tickets@example.com', 'TicketMaster');
    $mail->addAddress($email, $name);

    // Voeg de PDF toe als bijlage vanuit het geheugen
    $mail->addStringAttachment($pdfOutput, 'GroupTicket_' . $amount . 'persons.pdf');

    $mail->isHTML(true);
    $mail->Subject = 'Je groepsticket voor: ' . $event;
    $mail->Body    = $body;
    $mail->AltBody = "Bedankt voor je bestelling, {$name}. Je hebt een groepsticket voor {$amount} " . ($amount == 1 ? 'persoon' : 'personen') . " gekocht voor {$event}.";

    $mail->send();
    $mail_status = "De e-mail met je tickets is verzonden naar " . htmlspecialchars($email);
} catch (Exception $e) {
    $mail_status = "De e-mail kon niet verzonden worden. Mailer Error: {$mail->ErrorInfo}";
}
?>
<div class="container">
    <div class="row">
        <div class="col">
            <h2>Bedankt voor je bestelling</h2>
            <p>Je moet <?php echo $total ?> EUR betalen voor je groepsticket (<?php echo $amount ?> <?php echo $amount == 1 ? 'persoon' : 'personen' ?>)</p>
            <p><?php echo $mail_status; ?></p>
            <p>Email: <?php echo $email ?></p>
            <a href="<?php echo $filepath; ?>" download="<?php echo $filename; ?>" class="btn btn-primary">Download je groepsticket (PDF)</a>
        </div>
    </div>
</div>