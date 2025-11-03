<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();

// Kullanıcının deposu
$whsCode = $_SESSION["WhsCode"] ?? ''; 


$data = $sap->get("SQLQueries('OWTQ_LIST')/List?value1='MAIN'&value2='{$whsCode}'"); 
?> 


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Depo Siparişleri - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
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
                    <div class="user-name">Koşuyolu 1000 - Koşuyolu</div>
                    <div class="version">v1.0.43</div>
                </div>
            </div>
        </aside>


         Main Content 
        <main class="main-content">
            <header class="page-header">
                <h2>Ana Depo Siparişleri</h2>
                <button class="btn btn-primary" onclick="window.location.href='AnaDepoSO.php'">+ Yeni Sipariş Oluştur</button>
            </header>

            <div class="content-wrapper">
                <section class="card">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Sipariş Durumu</label>
                            <select class="filter-select">
                                <option>Tümü</option>
                                <option>Tamamlandı</option>
                                <option>Sevk Edildi</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" class="filter-input" placeholder="gg.aa.yyyy">
                        </div>
                        <div class="filter-group">
                            <label>Bitiş Tarihi</label>
                            <input type="date" class="filter-input" placeholder="gg.aa.yyyy">
                        </div>
                    </div>

                    <div class="table-controls">
                        <div class="show-entries">
                            Sayfada 
                            <select class="entries-select">
                                <option>25</option>
                                <option>50</option>
                                <option>100</option>
                            </select>
                            kayıt göster
                        </div>
                        <div class="search-box">
                            <label>Ara:</label>
                            <input type="text" class="search-input">
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sipariş No ▼</th>
                                <th>Sipariş Tarihi</th>
                                <th>Tahmini Teslimat Tarihi</th>
                                <th>Teslimat Belge No</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                         <?php
if (
    is_array($data) &&
    isset($data['response']['value']) &&
    count($data['response']['value']) > 0
) {
    foreach ($data['response']['value'] as $row) {
        switch ($row['DocStatus']) {
            case '4': $statusText = 'Tamamlandı'; $statusClass = 'status-completed'; break;
            case '3': $statusText = 'Beklemede';  $statusClass = 'status-pending';   break;
            default:  $statusText = 'Bilinmiyor'; $statusClass = 'status-unknown';
        }

        $data = 

        $docDate = date('d.m.Y', strtotime($row['DocDate']));
        $dueDate = date('d.m.Y', strtotime($row['DocDueDate']));

        echo "<tr>
                <td>{$row['DocNum']}</td>
                <td>{$docDate} / {$dueDate}</td>
                <td>{$row['NumAtCard']}</td>
                <td><span class='status-badge {$statusClass}'>{$statusText}</span></td>
                <td>
                    <a href='Dis-Tedarik-Detay.php?DocNum={$row['DocNum']}'>
                        <button class='btn-icon btn-view'>👁️ Detay</button>
                    </a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;color:#888;'>SAP'den kayıt bulunamadı.</td></tr>"; 
}
?>
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>