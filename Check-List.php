<?php
session_start();
include 'sap_connect.php';
$sap = new SAPConnect();

// Kullanıcının mağaza/depo kodu
$whsCode = $_SESSION["WhsCode"] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $guid = uniqid(); // benzersiz kod

    $payload = [
        "Code"           => $guid,                       
        "Name"           => "CheckList_" . $guid,        
        "U_WhsCode"      => $_SESSION["WhsCode"],
        "U_CheckListCode"=> $_POST["CheckListCode"] ?? "",
        "U_DocDate"      => date("Y-m-d"),
        "U_WorkCode"     => $_POST["WorkCode"] ?? "01",
        "U_WorkName"     => $_POST["WorkName"] ?? "tanım",
        "U_WorkStatus"   => ($_POST["WorkStatus"] === "true") ? "tYES" : "tNO",  
        "U_FreeText"     => $_POST["FreeText"] ?? "",
        "U_Frequency"    => "1",
        "U_SessionID"    => $_SESSION["sapSession"] ?? "1221",
        "U_GUID"         => $guid,
        "U_User"         => $_SESSION["UserName"] ?? "manager"
    ];

    // 🔹 ASIL SAP POST BURADA YAPILACAK
    // Eğer SAP’de “User Defined Object” (UDO) olarak tanımlandıysa:
    $response = $sap->post("ASUDO_B2B_CheckList", $payload);
    // Eğer sadece “User Table” olarak varsa yukarıyı değiştir:
    // $response = $sap->post("U_ASUDO_B2B_CheckList", $payload);

    // Yanıtı kontrol et
    if (!empty($response) && isset($response['status'])) {
        if ($response['status'] == 200 || $response['status'] == 201) {
            $_SESSION["sap_message"] = "✅ Kayıt SAP'ye başarıyla gönderildi.";
        } else {
            $error = $response['response']['error']['message']['value'] ?? 'Bilinmeyen hata';
            $_SESSION["sap_message"] = "⚠️ SAP hatası: {$error}";
        }
    } else {
        $_SESSION["sap_message"] = "⚠️ SAP'den yanıt alınamadı.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 🔹 Sayfa yüklenirken SAP’ten mevcut checklist verilerini çek
$data = $sap->get("SQLQueries('Check_List')/List?value1='{$whsCode}'");
$rows = $data['response']['value'] ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check List - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .success { color: #28a745; font-weight: 600; }
        .error { color: #dc3545; font-weight: 600; }
        .form-inline input, .form-inline select {
            padding: 4px 6px; border: 1px solid #ccc; border-radius: 4px;
        }
        .form-inline button { padding: 6px 10px; border: none; background: #007bff; color: #fff; border-radius: 4px; cursor: pointer; }
        .form-inline button:hover { background: #0056b3; }
    </style>
</head>

<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="logo">
            <h1>CREMMA<span>VERSE</span></h1>
        </div>
        <?php include 'navbar.php'; ?>
        <div class="user-info">
            <div class="user-avatar">K1</div>
            <div class="user-details">
                <div class="user-name">Koşuyolu <?= htmlspecialchars($whsCode) ?></div>
                <div class="version">v1.0.43</div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h2>Check List</h2>
            <div class="header-actions">
                <form method="post" class="form-inline">
                    <input type="text" name="CheckListCode" placeholder="Checklist Kodu" required>
                    <input type="text" name="WorkCode" placeholder="İş Kodu" required>
                    <input type="text" name="WorkName" placeholder="İş Tanımı" required>
                    <select name="WorkStatus">
                        <option value="true">Aktif</option>
                        <option value="false">Pasif</option>
                    </select>
                    <input type="text" name="FreeText" placeholder="Açıklama">
                    <button type="submit">Kaydet</button>
                </form>
            </div>
        </header>

        <div class="content-wrapper">
            <section class="card">
                <h3 class="section-title">Check List</h3>

                <?php if (isset($_SESSION["sap_message"])): ?>
                    <p class="<?= strpos($_SESSION["sap_message"], '✅') !== false ? 'success' : 'error' ?>">
                        <?= $_SESSION["sap_message"]; unset($_SESSION["sap_message"]); ?>
                    </p>
                <?php endif; ?>

                <table class="data-table checklist-table">
                    <thead>
                    <tr>
                        <th>Kod</th>
                        <th>İş Kodu</th>
                        <th>İş Tanımı</th>
                        <th>İşlem</th>
                        <th>Açıklama</th>
                        <th>Sorumlu Personel</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $CheckListCode = htmlspecialchars($r["CheckListCode"] ?? "-", ENT_QUOTES, 'UTF-8');
                            $WorkCode      = htmlspecialchars($r["WorkCode"] ?? "-", ENT_QUOTES, 'UTF-8');
                            $WorkName      = htmlspecialchars($r["WorkName"] ?? "-", ENT_QUOTES, 'UTF-8');
                            $FreeText      = htmlspecialchars($r["FreeText"] ?? "-", ENT_QUOTES, 'UTF-8');
                            $Responsible   = htmlspecialchars($r["Responsible"] ?? "-", ENT_QUOTES, 'UTF-8');
                            $WorkStatusRaw = $r["WorkStatus"] ?? null;
                            $WorkStatusBool = filter_var($WorkStatusRaw, FILTER_VALIDATE_BOOLEAN);
                            $WorkStatus = $WorkStatusBool
                                ? '<span style="color:#28a745;font-weight:600;">✔️ Aktif</span>'
                                : '<span style="color:#dc3545;font-weight:600;">❌ Pasif</span>';
                            ?>
                            <tr>
                                <td><?= $CheckListCode ?></td>
                                <td><?= $WorkCode ?></td>
                                <td><?= $WorkName ?></td>
                                <td><?= $WorkStatus ?></td>
                                <td><?= $FreeText ?></td>
                                <td><?= $Responsible ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">SAP'ten veri alınamadı veya sonuç boş.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</div>
</body>
</html>
