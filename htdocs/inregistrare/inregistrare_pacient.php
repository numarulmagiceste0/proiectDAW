<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

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

    $host = 'sql107.iceiy.com';
    $user = 'icei_37820574';
    $password = 'dutelaspital';
    $dbname = 'icei_37820574_DU_TE_LA_SPITAL';

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        die("Conexiunea a eșuat: " . $conn->connect_error);
    }

    $nume = $_POST['nume'];
    $email = $_POST['email'];
    $parola = $_POST['parola'];


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Eroare: Adresa de e-mail nu este validă!";
        exit;
    }

    $token = bin2hex(random_bytes(16));

    $sql_check = "SELECT * FROM Pacient WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        echo "Eroare: Adresa de e-mail este deja utilizată!";
    } else {
        $sql_insert = "INSERT INTO Pacient (nume, email, parola, token, confirmat) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $confirmat = 0;
        $stmt->bind_param("ssssi", $nume, $email, $parola, $token, $confirmat);

        if ($stmt->execute()) {
            $subject = "Confirmare înregistrare cont";
            $message = "Bună, $nume!<br><br>";
            $message .= "Te-am înregistrat cu succes! Pentru a-ți activa contul, te rugăm să dai clic pe următorul link:<br>";
            $message .= "<a href='https://du-te-la-spital.iceiy.com/confirmare_pacienti.php?token=$token'>Confirmă-ți contul</a><br><br>";
            $message .= "Mulțumim!";

            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dezvoltareaaplicatiilorweb@gmail.com';
            $mail->Password = 'tncv asnd cbdt hphp';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('dezvoltareaaplicatiilorweb@gmail.com', 'Du-te la spital!');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            if ($mail->send()) {
                echo "Înregistrare reușită! Te rog verifică-ți e-mailul pentru a-ți confirma contul. Du-te la spital!";
            } else {
                echo "Eroare la trimiterea e-mailului: " . $mail->ErrorInfo;
            }
        } else {
            echo "Eroare la înregistrare: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare Pacient</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 50px;
        }
        h2 {
            color: green;
        }
        form {
            display: inline-block;
            text-align: left;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Înregistrare Pacient</h2>
    <form action="" method="POST">
        <label for="nume">Nume complet:</label>
        <input type="text" id="nume" name="nume" placeholder="Introduceți numele complet" required>
        
        <label for="email">Adresă de e-mail:</label>
        <input type="email" id="email" name="email" placeholder="Introduceți adresa de e-mail" required>
        
        <label for="parola">Parolă:</label>
        <input type="password" id="parola" name="parola" placeholder="Introduceți parola" required>
        <div class="g-recaptcha" data-sitekey="6Lf9mbYqAAAAAOpW99Otfk9cki67_34G6wco-Ykn"></div><!-- reCAPTCHA v2 widget --> 
        <button type="submit">Înregistrează-te</button>
    </form>
</body>
</html>
