<?php
$host = 'sql107.iceiy.com';
$user = 'icei_37820574';
$password = 'dutelaspital';
$dbname = 'icei_37820574_DU_TE_LA_SPITAL';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT * FROM Pacient WHERE token = ? AND confirmat = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql_update = "UPDATE Pacient SET confirmat = 1 WHERE token = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("s", $token);

        if ($stmt_update->execute()) {
            echo "Cont confirmat cu succes! Acum te poți autentifica.";
        } else {
            echo "Eroare la confirmarea contului. Încearcă din nou.";
        }

        $stmt_update->close();
    } else {
        echo "Token invalid sau cont deja confirmat.";
    }

    $stmt->close();
} else {
    echo "Token lipsă. Verifică linkul primit în e-mail.";
}

$conn->close();
?>
