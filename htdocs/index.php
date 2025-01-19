<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Europe/Bucharest');

$host = 'sql107.iceiy.com';
$username = 'icei_37820574';
$password = 'dutelaspital';
$database = 'icei_37820574_DU_TE_LA_SPITAL';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexiunea la baza de date a eșuat: " . $conn->connect_error);
}

$ip = $_SERVER['REMOTE_ADDR'];

$momentulVizitei = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO `Analytics` (`ADRESA IP`, `MOMENTUL VIZITARII`) VALUES (?, ?)");
$stmt->bind_param("ss", $ip, $momentulVizitei);
$stmt->execute();
$stmt->close();

$conn->close();

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$recaptchaSecret = '6Lf9mbYqAAAAAEH9mYLyItaHQL3ptM-5DcvMCEIQ';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        die("Verificarea CAPTCHA a eșuat. Încercați din nou.");
    }

    $email = $_POST['email'];
    $message = $_POST['message'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Eroare: Adresa de e-mail nu este validă!";
        exit;
    }

    $mailAdmin = new PHPMailer;
    $mailAdmin->isSMTP();
    $mailAdmin->Host = 'smtp.mail.yahoo.com';
    $mailAdmin->SMTPAuth = true;
    $mailAdmin->Username = 'mark_warcraftistu2006@yahoo.com';
    $mailAdmin->Password = 'sywjefbqlrijipgh';
    $mailAdmin->SMTPSecure = 'tls';
    $mailAdmin->Port = 587;

    $mailAdmin->setFrom('mark_warcraftistu2006@yahoo.com', 'Du-te la spital!');
    $mailAdmin->addAddress('mark_warcraftistu2006@yahoo.com');
    $mailAdmin->isHTML(true);
    $mailAdmin->Subject = "Mesaj de contact de la $email";
    $mailAdmin->Body = "Mesajul primit de la utilizator:<br><br>$message";

    if ($mailAdmin->send()) {
        echo "Mesajul a fost trimis cu succes către administrator!";
    } else {
        echo "Eroare la trimiterea e-mailului către administrator: " . $mailAdmin->ErrorInfo;
    }

    $mailUser = new PHPMailer;
    $mailUser->isSMTP();
    $mailUser->Host = 'smtp.mail.yahoo.com';
    $mailUser->SMTPAuth = true;
    $mailUser->Username = 'mark_warcraftistu2006@yahoo.com';
    $mailUser->Password = 'sywjefbqlrijipgh';
    $mailUser->SMTPSecure = 'tls';
    $mailUser->Port = 587;

    $mailUser->setFrom('mark_warcraftistu2006@yahoo.com', 'Du-te la spital!');
    $mailUser->addAddress($email);
    $mailUser->isHTML(true);
    $mailUser->Subject = "Mulțumim pentru mesaj!";
    $mailUser->Body = "Bună ziua!<br><br>Am primit mesajul dumneavoastră: <br><br>$message<br><br>Mulțumim că ne-ați contactat!";

    if ($mailUser->send()) {
        echo "O copie a mesajului a fost trimisă și la adresa ta de e-mail.";
    } else {
        echo "Eroare la trimiterea e-mailului către utilizator: " . $mailUser->ErrorInfo;
    }
}
?>


<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Du-te la spital!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 50px;
        }

        h1 {
            color: green;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            width: 250px;
            text-align: center;
            display: inline-block;
            text-decoration: none;
            border-radius: 5px;
        }

        .button:hover {
            background-color: #45a049;
        }

        .button-container {
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <h1>Du-te la spital!</h1>

    <div id="register-buttons" class="button-container">
        <button class="button" onclick="window.location.href='inregistrare/inregistrare_pacient.php'">Înregistrează-te ca pacient</button>
        <button class="button" onclick="window.location.href='inregistrare/inregistrare_medic.php'">Înregistrează-te ca medic</button>
    </div>

    <div id="login-buttons" class="button-container">
        <button class="button" onclick="window.location.href='autentificare/autentificare_pacient.php'">Autentifică-te ca pacient</button>
        <button class="button" onclick="window.location.href='autentificare/autentificare_medic.php'">Autentifică-te ca medic</button>
    </div>

    <div id="admin-login-button-container" class="button-container">
        <button class="button" onclick="window.location.href='autentificare/autentificare_administrator.php'">Autentifică-te ca administrator</button>
    </div>

    <div id="report-button-container" class="button-container">
        <button class="button" onclick="showReportForm()">Raportează o problemă</button>
    </div>

    <div id="description-button-container" class="button-container">
        <button class="button" onclick="window.location.href='descriere.html'">Descrierea aplicației</button>
    </div>

    <div id="formContainer" style="display: none; margin-top: 40px; background: #4CAF50; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); color: white; width: 300px; margin-left: auto; margin-right: auto;">
        <h2>Raportează o problemă</h2>
        <form id="reportForm" method="POST" action="">
            <label for="email">Adresă de e-mail:</label>
            <input type="email" id="email" name="email" placeholder="Introduceți adresa de e-mail" required style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 4px; border: none; font-size: 14px;">

            <label for="message">Mesaj:</label>
            <textarea id="message" name="message" placeholder="Descrie problema" required style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 4px; border: none; font-size: 14px; height: 90px;"></textarea>

            <div class="g-recaptcha" data-sitekey="6Lf9mbYqAAAAAOpW99Otfk9cki67_34G6wco-Ykn"></div>

            <button type="submit" style="background-color: #45a049; color: white; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 4px; border: none;">Trimite mesajul</button>
        </form>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        function showReportForm() {
            document.getElementById('formContainer').style.display = 'block';
            document.getElementById('report-button-container').style.display = 'none';
        }
    </script>

</body>
</html>
