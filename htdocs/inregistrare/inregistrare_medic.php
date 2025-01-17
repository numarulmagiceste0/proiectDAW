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
    $specialitate = $_POST['specialitate'];
    $cod_medic = $_POST['cod_medic'];
    $parola = $_POST['parola'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Eroare: Adresa de e-mail nu este validă!";
        exit;
    }

    if (!preg_match("/^\d{10}$/", $cod_medic)) {
        echo "Eroare: Codul medicului trebuie să conțină exact 10 cifre!";
        exit;
    }

    $token = bin2hex(random_bytes(16));

    $sql_check_code = "SELECT * FROM Medic WHERE `cod medic` = ?";
    $stmt_check_code = $conn->prepare($sql_check_code);
    $stmt_check_code->bind_param("s", $cod_medic);
    $stmt_check_code->execute();
    $result_code = $stmt_check_code->get_result();

    if ($result_code->num_rows > 0) {
        echo "Eroare: Codul medicului nu este unic!";
        exit;
    }

    $sql_check_email = "SELECT * FROM Medic WHERE email = ?";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_email = $stmt_check_email->get_result();

    if ($result_email->num_rows > 0) {
        echo "Eroare: Adresa de e-mail este deja utilizată!";
    } else {
        $sql_insert = "INSERT INTO Medic (nume, email, specialitate, `cod medic`, parola, token, confirmat) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $confirmat = 0;
        $stmt->bind_param("ssssssi", $nume, $email, $specialitate, $cod_medic, $parola, $token, $confirmat);

        if ($stmt->execute()) {
            $subject = "Confirmare înregistrare cont medic";
            $message = "Bună, $nume!<br><br>";
            $message .= "Te-am înregistrat cu succes ca medic! Pentru a-ți activa contul, te rugăm să dai clic pe următorul link:<br>";
            $message .= "<a href='http://du-te-la-spital.iceiy.com//confirmare_medici.php?token=$token'>Confirmă-ți contul</a><br><br>";
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
    <title>Înregistrare Medic</title>
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
        input, select {
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
        .error-message {
            color: red;
        }
    </style>
</head>
<body>
    <h2>Înregistrare Medic</h2>
    <form action="" method="POST">
        <label for="nume">Nume complet:</label>
        <input type="text" id="nume" name="nume" placeholder="Introduceți numele complet" required>
        
        <label for="email">Adresă de e-mail:</label>
        <input type="email" id="email" name="email" placeholder="Introduceți adresa de e-mail" required>
        
        <label for="specialitate">Specialitate:</label>
        <select id="specialitate" name="specialitate" required>
            <option value="">Selectează specialitatea</option>
            <option value="Cardiologie">Cardiologie</option>
            <option value="Dermatologie">Dermatologie</option>
            <option value="Pediatrie">Pediatrie</option>
            <option value="Chirurgie">Chirurgie</option>
            <option value="Oftalmologie">Oftalmologie</option>
            <option value="Stomatologie">Stomatologie</option>
            <option value="Ortopedie">Ortopedie</option>
            <option value="Neurologie">Neurologie</option>
            <option value="Psihiatrie">Psihiatrie</option>
            <option value="Endocrinologie">Endocrinologie</option>
            <option value="Ginecologie">Ginecologie</option>
        </select>
        
        <label for="cod_medic">Cod medic (10 cifre):</label>
        <input type="text" id="cod_medic" name="cod_medic" placeholder="Introduceți codul medicului" required>
        
        <label for="parola">Parolă:</label>
        <input type="password" id="parola" name="parola" placeholder="Introduceți parola" required>
        
        <div class="g-recaptcha" data-sitekey="6Lf9mbYqAAAAAOpW99Otfk9cki67_34G6wco-Ykn"></div><!-- reCAPTCHA v2 widget --> 
        <button type="submit">Înregistrează-te</button>
    </form>
</body>
</html>
