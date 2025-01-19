<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin']) || !isset($_SESSION['session_id']) || $_SESSION['session_id'] !== session_id()) {
    header("Location: autentificare_administrator.php");
    exit();
}

$host = 'sql107.iceiy.com';
$user = 'icei_37820574';
$password = 'dutelaspital';
$dbname = 'icei_37820574_DU_TE_LA_SPITAL';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Conexiune eșuată: " . $conn->connect_error);
}

$sql1 = "SELECT `ADRESA IP`, `MOMENTUL VIZITARII`
         FROM `Analytics`
         ORDER BY `MOMENTUL VIZITARII` DESC";

$sql2 = "SELECT `ADRESA IP`, MAX(`MOMENTUL VIZITARII`) as `Ultima Accesare`, COUNT(`ADRESA IP`) as `Numar Accesari`
         FROM `Analytics`
         GROUP BY `ADRESA IP`
         ORDER BY `Numar Accesari` DESC";

$result1 = $conn->query($sql1);
$result2 = $conn->query($sql2);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: autentificare_administrator.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina Admin</title>
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f7f7f7;
        color: #333;
        margin: 0;
        padding: 0;
    }

    h1 {
        text-align: center;
        color: #4CAF50;
        padding: 20px 0;
    }

    .tables-container {
        display: flex;
        justify-content: space-around;
        padding: 20px;
    }

    table {
        width: 120%;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }

    th, td {
        padding: 12px;
        text-align: center;
        border: 1px solid #ddd;
    }

    th {
        background-color: #4CAF50;
        color: white;
    }

    th:nth-child(1), td:nth-child(1) {
        width: 160px;
    }

    th:nth-child(2), td:nth-child(2) {
        width: 160px;
    }

    th:nth-child(3), td:nth-child(3) {
        width: 160px;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    form {
        text-align: center;
    }

    .logout-btn {
        display: block;
        margin: 20px auto;
        padding: 10px 20px;
        font-size: 16px;
        color: #fff;
        background-color: #4CAF50;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .table-title {
        text-align: center;
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

</style>

</head>
<body>

    <h1>Cont Administrator</h1>

    <form action="" method="POST">
        <button type="submit" name="logout" class="logout-btn">Deconectează-te</button>
    </form>

    <div class="tables-container">
        <!-- Tabel pentru toate accesările (IP și Momentul Accesării) -->
        <div>
            <div class="table-title">Toate Accesările (ordonate după momentul accesării)</div>
            <table>
                <thead>
                    <tr>
                        <th>Adresa IP</th>
                        <th>Momentul Accesării</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result1->num_rows > 0) {
                        while ($row = $result1->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['ADRESA IP']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['MOMENTUL VIZITARII']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2">Nu există date.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel pentru accesările grupate pe IP (Număr și Ultima Accesare) -->
        <div>
            <div class="table-title">Accesări Grupate pe IP (ordonate după numărul de accesări)</div>
            <table>
                <thead>
                    <tr>
                        <th>Adresa IP</th>
                        <th>Ultima Accesare</th>
                        <th>Număr Accesări</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result2->num_rows > 0) {
                        while ($row = $result2->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['ADRESA IP']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['Ultima Accesare']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['Numar Accesari']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3">Nu există date.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
