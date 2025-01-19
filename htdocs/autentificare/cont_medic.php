<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['cod_medic'])) {
    header("Location: autentificare_medic.php");
    exit;
}

$host = 'sql107.iceiy.com';
$user = 'icei_37820574';
$password = 'dutelaspital';
$dbname = 'icei_37820574_DU_TE_LA_SPITAL';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Eroare de conexiune la baza de date: " . $conn->connect_error);
}

$cod_medic = $_SESSION['cod_medic'];

$sql_check_medic = "SELECT COUNT(*) AS count, `NUME` FROM Medic WHERE `COD MEDIC` = ?";
$stmt_check = $conn->prepare($sql_check_medic);
$stmt_check->bind_param("s", $cod_medic);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();

if ($row_check['count'] == 0) {
    die("Eroare: Cod Medic din sesiune nu există în baza de date.");
}

$nume_medic = $row_check['NUME'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['propune_programare'])) {
        $data_si_ora = $_POST['data_si_ora'] ?? null;
        $pret = $_POST['pret'] ?? null;

        if (empty($data_si_ora) || empty($pret)) {
            echo "<p style='color: red;'>Eroare: Toate câmpurile sunt obligatorii!</p>";
        } else {
            $sql_insert = "INSERT INTO `Programare Propusa` (`MEDIC ID`, `DATA SI ORA`, `PRET`) VALUES ((SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?), ?, ?)";
            $stmt = $conn->prepare($sql_insert);

            if (!$stmt) {
                die("Eroare la pregătirea interogării: " . $conn->error);
            }

            $stmt->bind_param("ssi", $cod_medic, $data_si_ora, $pret);

            if ($stmt->execute()) {
                echo "<p style='color: green;'>Programare propusă adăugată cu succes!</p>";
            } else {
                echo "<p style='color: red;'>Eroare la adăugarea programării propuse: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }
    }

    if (isset($_POST['anuleaza_programare'])) {
        $data_si_ora = $_POST['data_si_ora'];

        $sql_delete = "DELETE FROM `Programare Propusa` WHERE `MEDIC ID` = (SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?) AND `DATA SI ORA` = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ss", $cod_medic, $data_si_ora);

        if ($stmt_delete->execute()) {
            echo "<p style='color: green;'>Programarea propusă a fost anulată!</p>";
        } else {
            echo "<p style='color: red;'>Eroare la anularea programării!</p>";
        }

        $stmt_delete->close();
    }

    if (isset($_POST['sterge_cont'])) {
        // Ștergere programări acceptate
        $sql_delete_acceptate = "DELETE FROM `Programare` WHERE `MEDIC ID` = (SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?)";
        $stmt_delete_acceptate = $conn->prepare($sql_delete_acceptate);
        $stmt_delete_acceptate->bind_param("s", $cod_medic);
        $stmt_delete_acceptate->execute();
        $stmt_delete_acceptate->close();

        // Ștergere programări propuse
        $sql_delete_propuse = "DELETE FROM `Programare Propusa` WHERE `MEDIC ID` = (SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?)";
        $stmt_delete_propuse = $conn->prepare($sql_delete_propuse);
        $stmt_delete_propuse->bind_param("s", $cod_medic);
        $stmt_delete_propuse->execute();
        $stmt_delete_propuse->close();

        // Ștergere medic
        $sql_delete_medic = "DELETE FROM `Medic` WHERE `COD MEDIC` = ?";
        $stmt_delete_medic = $conn->prepare($sql_delete_medic);
        $stmt_delete_medic->bind_param("s", $cod_medic);
        $stmt_delete_medic->execute();
        $stmt_delete_medic->close();

        // Deconectare și redirecționare
        session_unset();
        session_destroy();
        header("Location: autentificare_medic.php");
        exit;
    }
}

$sql_select = "SELECT `DATA SI ORA`, `PRET` FROM `Programare Propusa` WHERE `MEDIC ID` = (SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?)";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("s", $cod_medic);
$stmt_select->execute();
$programari_propuse = $stmt_select->get_result();

$sql_acceptate = "SELECT pr.`DATA SI ORA`, pr.`PRET`, p.`NUME` AS `NUME_PACIENT`
                  FROM `Programare` pr
                  JOIN `Pacient` p ON pr.`PACIENT ID` = p.`PACIENT ID`
                  WHERE pr.`MEDIC ID` = (SELECT `MEDIC ID` FROM Medic WHERE `COD MEDIC` = ?)";
$stmt_acceptate = $conn->prepare($sql_acceptate);
$stmt_acceptate->bind_param("s", $cod_medic);
$stmt_acceptate->execute();
$programari_acceptate = $stmt_acceptate->get_result();

$conn->close();

if (isset($_POST['deconectare'])) {
    session_unset();
    session_destroy();
    header("Location: autentificare_medic.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cont Medic - Programări Propuse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 50px;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        form {
            display: inline-block;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        input, button {
            display: block;
            margin: 10px auto;
            padding: 10px;
            width: 80%;
            max-width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Bun venit, <?php echo htmlspecialchars($nume_medic); ?>!</h1>

    <form action="" method="POST">
        <button type="submit" name="deconectare">Deconectare</button>
    </form>

    <h2>Creare Programare Propusă</h2>
    <form action="" method="POST">
        <label for="data_si_ora">Data și Ora:</label>
        <input type="datetime-local" id="data_si_ora" name="data_si_ora" required>
        
        <label for="pret">Preț:</label>
        <input type="number" step="0.01" id="pret" name="pret" required>
        
        <button type="submit" name="propune_programare">Propune Programare</button>
    </form>

    <h2>Programările Tale Propuse</h2>
    <table>
        <tr>
            <th>Data și Ora</th>
            <th>Preț</th>
            <th>Acțiune</th>
        </tr>
        <?php while ($row = $programari_propuse->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['DATA SI ORA']); ?></td>
            <td><?php echo htmlspecialchars($row['PRET']); ?></td>
            <td>
                <form action="" method="POST">
                    <input type="hidden" name="data_si_ora" value="<?php echo htmlspecialchars($row['DATA SI ORA']); ?>">
                    <button type="submit" name="anuleaza_programare">Anulează</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>Programările Tale Acceptate</h2>
    <table>
        <tr>
            <th>Data și Ora</th>
            <th>Preț</th>
            <th>Pacient</th>
        </tr>
        <?php while ($row = $programari_acceptate->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['DATA SI ORA']); ?></td>
            <td><?php echo htmlspecialchars($row['PRET']); ?></td>
            <td><?php echo htmlspecialchars($row['NUME_PACIENT']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>Ștergere Cont Medic</h2>
    <form action="" method="POST">
        <button type="submit" name="sterge_cont" style="background-color: red;">Șterge Contul</button>
    </form>
</body>
</html>
