<?php
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

$sql = "SELECT `ADRESA IP`, `MOMENTUL VIZITARII`, COUNT(`ADRESA IP`) as `Numar Accesari`
        FROM `Analytics`
        GROUP BY `ADRESA IP`
        ORDER BY `MOMENTUL VIZITARII` DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo '<h1>Cont Administrator</h1>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Adresa IP</th>';
    echo '<th>Ultima Accesare</th>';
    echo '<th>Număr Accesări</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['ADRESA IP']) . '</td>';
        echo '<td>' . htmlspecialchars($row['MOMENTUL VIZITARII']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Numar Accesari']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
} else {
    echo 'Nu există date în tabelul Analytics.';
}

$conn->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: autentificare_administrator.php");
    exit();
}

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

        table {
            width: 80%;
            margin: 20px auto;
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

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .logout-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #f44336;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #d32f2f;
        }

        form {
            text-align: center;
        }

        label, input, button {
            display: block;
            margin: 10px auto;
        }

        input {
            padding: 10px;
            width: 250px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

    <form action="" method="POST">
        <button type="submit" name="logout" class="logout-btn">Deconectează-te</button>
    </form>

</body>
</html>
