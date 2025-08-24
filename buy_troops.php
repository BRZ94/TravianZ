/*****************************************************************************
*  © Copyright 2025 - Creato da pier94                                       *
*  Modulo: Acquisto Truppe                                                   *
*  Descrizione: Permette ai giocatori di acquistare truppe                   *
*  Per rendere visibile il modulo, aggiungi un link nel menu.tpl            *
*****************************************************************************/
<?php
include("GameEngine/Village.php");

if (!$session->logged_in) {
    die("Accesso negato.");
}

$uid = (int)$session->uid;
$wid = (int)$village->wid;
$tribe = (int)$session->tribe;
$message = "";

// Mappa quantità → costo in gold
$packages = [
    10000 => 5,
    50000 => 10,
    100000 => 20,
    300000 => 30,
    500000 => 50,
    5000000 => 100
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
    $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

    // Validazioni base
    if (!in_array($amount, array_keys($packages))) {
        $message = "Quantità non valida.";
    } elseif ($unitId < 1 || $unitId > 10) {
        $message = "Truppa non valida.";
    } else {
        $goldCost = $packages[$amount];

        // Nome del campo truppa es. u1-u10 / u11-u20 / u21-u30
        $tribeOffset = ($tribe - 1) * 10;
        $unitField = 'u' . ($tribeOffset + $unitId);

        // Recupero gold attuale
        $query = $database->query("SELECT gold FROM " . TB_PREFIX . "users WHERE id = $uid");
        $result = mysqli_fetch_assoc($query);

        if (!$result) {
            $message = "Errore nel recupero gold.";
        } else {
            $currentGold = (int)$result['gold'];

            if ($currentGold < $goldCost) {
                $message = "Gold insufficiente.";
            } else {
                // Deduce gold
                $database->query("UPDATE " . TB_PREFIX . "users SET gold = gold - $goldCost WHERE id = $uid");

                // Aggiunge truppe
                $checkUnits = $database->query("SELECT * FROM " . TB_PREFIX . "units WHERE vref = $wid");
                if (mysqli_num_rows($checkUnits) == 0) {
                    $database->query("INSERT INTO " . TB_PREFIX . "units (vref, `$unitField`) VALUES ($wid, $amount)");
                } else {
                    $database->query("UPDATE " . TB_PREFIX . "units SET `$unitField` = `$unitField` + $amount WHERE vref = $wid");
                }

                // Redirect su dorf1.php
                header("Location: dorf1.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Acquista Truppe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            text-align: center;
            padding-top: 50px;
        }
        .form-box {
            background: #fff;
            padding: 20px;
            display: inline-block;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        input[type="number"], select {
            padding: 8px;
            width: 200px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background: #0066cc;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Acquista Truppe</h2>
    <form method="POST">
        <label for="unit_id">Tipo Truppa:</label><br>
        <select name="unit_id" required>
            <?php
            for ($i = 1; $i <= 10; $i++) {
                echo "<option value=\"$i\">Truppa $i</option>";
            }
            ?>
        </select><br><br>

        <label for="amount">Pacchetto:</label><br>
        <select name="amount" required>
            <?php
            foreach ($packages as $qty => $cost) {
                echo "<option value=\"$qty\">$qty truppe - $cost gold</option>";
            }
            ?>
        </select><br><br>

        <input type="submit" value="Acquista">
    </form>

    <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
</div>

</body>
</html>
