<?php
session_start();
include '../sap_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sap = new SAPConnect();

    
    $sapUser = 'manager';
    $sapPass = '1234';
    $company = 'CREMMA_CANLI_2209';

    
    $userWhs = trim($_POST['whs']);

    if ($sap->login($sapUser, $sapPass, $company)) {
        $_SESSION["WhsCode"] = $userWhs;
        header("Location: ../index.php");
        exit;
    } else {
        $error = "SAP B1S v2 oturumu başlatılamadı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>CREMMAVERSE Giriş</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;height:100vh;background:#f8f9fa;">
    <form method="POST" class="card" style="width:320px;">
        <h2 style="text-align:center;margin-bottom:20px;">Depo Girişi</h2>
        <?php if (isset($error)): ?>
            <div style="color:red;margin-bottom:10px;text-align:center;"><?= $error ?></div>
        <?php endif; ?>

        <label>Depo Kodu (ör: 1000)</label>
        <input type="text" name="whs" required class="filter-input" placeholder="Depo kodunuzu girin">

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:15px;" >Giriş Yap</button> 
    </form>
</body>
</html>
