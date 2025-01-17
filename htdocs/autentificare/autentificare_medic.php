<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$recaptchaSecret = '6Lf9mbYqAAAAAEH9mYLyItaHQL3ptM-5DcvMCEIQ';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['g-recaptcha-response'])) {
        die("Eroare: Verificarea CAPTCHA a eșuat. Încercați din nou.");
    }

    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        die("Eroare: Verificarea CAPTCHA a eșuat. Încercați din nou.");
    }

    $host = 'sql107.iceiy.com';
    $user = 'icei_37820574';
    $password = 'dutelaspital';
    $dbname = 'icei_37820574_DU_TE_LA_SPITAL';

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        die("Eroare de conexiune la baza de date: " . $conn->connect_error);
    }

    if (empty($_POST["cod_medic"]) || empty($_POST["parola"])) {
        die("Eroare: Toate câmpurile sunt obligatorii!");
    }

    $cod_medic = trim($_POST["cod_medic"]);
    $parola = trim($_POST["parola"]);

    $sql = "SELECT * FROM Medic WHERE `COD MEDIC` = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Eroare la pregătirea interogării: " . $conn->error);
    }

    $stmt->bind_param("s", $cod_medic);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Eroare: Codul de medic nu există în baza de date.");
    }

    $row = $result->fetch_assoc();

    if ($parola !== $row['PAROLA']) {
        die("Eroare: Parola este incorectă.");
    }

    $_SESSION['cod_medic'] = $cod_medic;
    $_SESSION['nume_medic'] = $row['NUME'];

    $stmt->close();
    $conn->close();

    header("Location: cont_medic.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare Medic</title>
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
    <h2>Autentificare Medic</h2>
    <form action="" method="POST">
        <label for="cod_medic">Cod Medic:</label>
        <input type="text" id="cod_medic" name="cod_medic" placeholder="Introduceți codul de medic" required>
        
        <label for="parola">Parolă:</label>
        <input type="password" id="parola" name="parola" placeholder="Introduceți parola" required>
        
        <div class="g-recaptcha" data-sitekey="6Lf9mbYqAAAAAOpW99Otfk9cki67_34G6wco-Ykn"></div>
        <button type="submit">Autentificare</button>
    </form>
</body>
</html>
