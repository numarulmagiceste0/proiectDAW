<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['cod_pacient'])) {
    header("Location: autentificare_pacient.php");
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

$cod_pacient = $_SESSION['cod_pacient'];

$sql_check_pacient = "SELECT COUNT(*) AS count, `NUME` FROM Pacient WHERE `EMAIL` = ?";
$stmt_check = $conn->prepare($sql_check_pacient);
$stmt_check->bind_param("s", $cod_pacient);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();

if ($row_check['count'] == 0) {
    die("Eroare: Codul pacientului din sesiune nu există în baza de date.");
}

$nume_pacient = $row_check['NUME'];

$sql_propuse = "SELECT pp.`MEDIC ID`, pp.`DATA SI ORA`, pp.`PRET`, m.`NUME` AS `NUME_MEDIC`, m.`SPECIALITATE` 
                FROM `Programare Propusa` pp
                JOIN `Medic` m ON pp.`MEDIC ID` = m.`MEDIC ID`";
$stmt_propuse = $conn->prepare($sql_propuse);
$stmt_propuse->execute();
$programari_propuse = $stmt_propuse->get_result();

$sql_acceptate = "SELECT pr.`DATA SI ORA`, pr.`PRET`, m.`NUME` AS `NUME_MEDIC`, m.`SPECIALITATE`, pr.`MEDIC ID`
                  FROM `Programare` pr
                  JOIN `Medic` m ON pr.`MEDIC ID` = m.`MEDIC ID`
                  WHERE pr.`PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?)";
$stmt_acceptate = $conn->prepare($sql_acceptate);
$stmt_acceptate->bind_param("s", $cod_pacient);
$stmt_acceptate->execute();
$programari_acceptate = $stmt_acceptate->get_result();

$conn->close();

if (isset($_POST['deconectare'])) {
    session_unset();
    session_destroy();
    header("Location: autentificare_pacient.php");
    exit;
}

if (isset($_POST['accepta_programarea'])) {
    $medic_id = $_POST['medic_id'];
    $data_si_ora = $_POST['data_si_ora'];

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        die("Eroare de conexiune la baza de date: " . $conn->connect_error);
    }

    $sql_check_existing = "SELECT COUNT(*) AS count FROM `Programare` 
                           WHERE `MEDIC ID` = ? AND `PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?) 
                           AND `DATA SI ORA` = ?";
    $stmt_check_existing = $conn->prepare($sql_check_existing);
    $stmt_check_existing->bind_param("iss", $medic_id, $cod_pacient, $data_si_ora);
    $stmt_check_existing->execute();
    $result_check_existing = $stmt_check_existing->get_result();
    $row_check_existing = $result_check_existing->fetch_assoc();

    if ($row_check_existing['count'] > 0) {
        echo "<p>Programarea a fost deja făcută!</p>";
    } else {
        $sql_insert = "INSERT INTO `Programare` (`PACIENT ID`, `MEDIC ID`, `DATA SI ORA`, `PRET`) 
                       SELECT (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?), ?, ?, 
                              (SELECT `PRET` FROM `Programare Propusa` WHERE `MEDIC ID` = ? AND `DATA SI ORA` = ?)";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sisis", $cod_pacient, $medic_id, $data_si_ora, $medic_id, $data_si_ora);
        
        if ($stmt_insert->execute()) {
            $sql_delete = "DELETE FROM `Programare Propusa` WHERE `MEDIC ID` = ? AND `DATA SI ORA` = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("is", $medic_id, $data_si_ora);
            $stmt_delete->execute();

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "<p>Eroare la acceptarea programării!</p>";
        }
    }

    $conn->close();
}

if (isset($_POST['anuleaza_programarea'])) {
    $medic_id = $_POST['medic_id'];
    $data_si_ora = $_POST['data_si_ora'];

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        die("Eroare de conexiune la baza de date: " . $conn->connect_error);
    }

    $sql_insert_back = "INSERT INTO `Programare Propusa` (`MEDIC ID`, `DATA SI ORA`, `PRET`) 
                        SELECT `MEDIC ID`, `DATA SI ORA`, `PRET` 
                        FROM `Programare` 
                        WHERE `MEDIC ID` = ? AND `PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?) 
                        AND `DATA SI ORA` = ?";

    $stmt_insert_back = $conn->prepare($sql_insert_back);
    $stmt_insert_back->bind_param("iss", $medic_id, $cod_pacient, $data_si_ora);

    $sql_delete_programare = "DELETE FROM `Programare` WHERE `MEDIC ID` = ? AND `PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?) AND `DATA SI ORA` = ?";
    $stmt_delete_programare = $conn->prepare($sql_delete_programare);
    $stmt_delete_programare->bind_param("iss", $medic_id, $cod_pacient, $data_si_ora);

    if ($stmt_insert_back->execute() && $stmt_delete_programare->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p>Eroare la anularea programării!</p>";
    }

    $conn->close();
}

// Cod pentru ștergerea contului pacientului
if (isset($_POST['sterge_cont'])) {
    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        die("Eroare de conexiune la baza de date: " . $conn->connect_error);
    }

    // Mutăm programările acceptate în Programare Propusa
    $sql_move_programari = "INSERT INTO `Programare Propusa` (`MEDIC ID`, `DATA SI ORA`, `PRET`)
                            SELECT `MEDIC ID`, `DATA SI ORA`, `PRET`
                            FROM `Programare` 
                            WHERE `PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?)";
    $stmt_move = $conn->prepare($sql_move_programari);
    $stmt_move->bind_param("s", $cod_pacient);
    $stmt_move->execute();

    // Ștergem programările acceptate
    $sql_delete_programari = "DELETE FROM `Programare` WHERE `PACIENT ID` = (SELECT `PACIENT ID` FROM Pacient WHERE `EMAIL` = ?)";
    $stmt_delete = $conn->prepare($sql_delete_programari);
    $stmt_delete->bind_param("s", $cod_pacient);
    $stmt_delete->execute();

    // Ștergem contul pacientului
    $sql_delete_pacient = "DELETE FROM `Pacient` WHERE `EMAIL` = ?";
    $stmt_delete_pacient = $conn->prepare($sql_delete_pacient);
    $stmt_delete_pacient->bind_param("s", $cod_pacient);
    $stmt_delete_pacient->execute();

    session_unset();
    session_destroy();
    header("Location: autentificare_pacient.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina pacientului</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 80%;
            max-width: 1200px;
            margin: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        table th {
            background-color: #4CAF50;
            color: white;
        }
        .button {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .button-danger {
            background-color: #f44336;
        }
        .button-danger:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2 class="header">Bun venit, <?php echo $nume_pacient; ?>!</h2>
        <form method="post">
            <button type="submit" name="deconectare" class="button">Deconectare</button>
        </form>

        <h3>Programări Propuse</h3>
        <table>
            <tr>
                <th>Medic</th>
                <th>Specialitate</th>
                <th>Data și Ora</th>
                <th>Preț</th>
                <th>Acțiune</th>
            </tr>
            <?php while ($row = $programari_propuse->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['NUME_MEDIC']; ?></td>
                    <td><?php echo $row['SPECIALITATE']; ?></td>
                    <td><?php echo $row['DATA SI ORA']; ?></td>
                    <td><?php echo $row['PRET']; ?> RON</td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="medic_id" value="<?php echo $row['MEDIC ID']; ?>">
                            <input type="hidden" name="data_si_ora" value="<?php echo $row['DATA SI ORA']; ?>">
                            <button type="submit" name="accepta_programarea" class="button">Acceptă programarea</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <h3>Programări Acceptate</h3>
        <table>
            <tr>
                <th>Medic</th>
                <th>Specialitate</th>
                <th>Data și Ora</th>
                <th>Preț</th>
                <th>Acțiune</th>
            </tr>
            <?php while ($row = $programari_acceptate->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['NUME_MEDIC']; ?></td>
                    <td><?php echo $row['SPECIALITATE']; ?></td>
                    <td><?php echo $row['DATA SI ORA']; ?></td>
                    <td><?php echo $row['PRET']; ?> RON</td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="medic_id" value="<?php echo $row['MEDIC ID']; ?>">
                            <input type="hidden" name="data_si_ora" value="<?php echo $row['DATA SI ORA']; ?>">
                            <button type="submit" name="anuleaza_programarea" class="button button-danger">Anulează programarea</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <h3>Șterge contul</h3>
        <form method="post">
            <button type="submit" name="sterge_cont" class="button button-danger">Șterge contul</button>
        </form>
    </div>

</body>
</html>
