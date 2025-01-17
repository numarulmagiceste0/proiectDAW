<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$recaptchaSecret = '6Lf9mbYqAAAAAEH9mYLyItaHQL3ptM-5DcvMCEIQ';

define('ADMIN_PASSWORD', 'dutelaspital');

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

    if (empty($_POST["parola"])) {
        die("Eroare: Parola este obligatorie!");
    }

    $parola = trim($_POST["parola"]);

    if ($parola !== ADMIN_PASSWORD) {
        die("Eroare: Parola este incorectă.");
    }

    $_SESSION['admin'] = true;
    $_SESSION['session_id'] = session_id();

    echo "<script type='text/javascript'>
            window.location.href = 'cont_administrator.php';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare Administrator</title>
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
    <h2>Autentificare Administrator</h2>
    <form action="" method="POST">
        <label for="parola">Parolă:</label>
        <input type="password" id="parola" name="parola" placeholder="Introduceți parola" required>
        
        <div class="g-recaptcha" data-sitekey="6Lf9mbYqAAAAAOpW99Otfk9cki67_34G6wco-Ykn"></div>
        <button type="submit">Autentificare</button>
    </form>
</body>
</html>
