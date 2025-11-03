<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();

// SAP'den veri çek (her sayfada sorguyu değiştir)
$data = $sap->get("SQLQueries('OWTQ_LIST')/List?value1='PROD'&value2='WhsCode'");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - CREMMAVERSE</title>
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

        <main class="main-content">
            <header class="page-header">
                <h2>Ticket</h2>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span class="user-badge">👤 Yükleniyor...</span>
                    <button class="btn btn-primary" onclick="window.location.href='TicketSO.php'">+ Yeni Ticket Oluştur</button>
                </div>
            </header>

            <div class="content-card">
                <div class="tabs">
                    <button class="tab-btn active">📥 Gelen Ticket</button>
                    <button class="tab-btn">📤 Giden Ticket</button>
                </div>

                <div class="table-controls">
                    <div class="show-entries">
                        <span>Show</span>
                        <select class="form-select">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="search-box">
                        <span>Search:</span>
                        <input type="text" class="form-input">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Şube</th>
                                <th>Açılış Tarihi</th>
                                <th>Kullanıcı</th>
                                <th>Öncelik</th>
                                <th>Durum</th>
                                <th>Konu</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem;">
                                    No data available in table
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span>Showing 0 to 0 of 0 entries</span>
                </div>

                <div class="pagination">
                    <button class="btn-pagination" disabled>Previous</button>
                    <button class="btn-pagination" disabled>Next</button>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>
