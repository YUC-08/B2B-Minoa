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
    <title>Fire ve Zayi Listesi - CREMMAVERSE</title>
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
                <h2>Fire ve Zayi Listesi</h2>
                <button class="btn btn-primary" onclick="window.location.href='Fire-ZayiKO.php'">+ Yeni Fire/Zayi Kaydı</button>
            </header>

            <div class="content-wrapper">
                <section class="card">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Durum</label>
                            <select class="filter-select">
                                <option>Tümü</option>
                                <option>Fire</option>
                                <option>Zayi</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Kalem Kodu</label>
                            <input type="text" class="filter-input" placeholder="Kalem kodu girin...">
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
                            Show 
                            <select class="entries-select">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                            entries
                        </div>
                        <div class="search-box">
                            <label>Search:</label>
                            <input type="text" class="search-input">
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Belge No ▼</th>
                                <th>İşlem Tarihi</th>
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Miktar</th>
                                <th>Durum</th>
                                <th>Açıklama</th>
                                <th>Görsel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>9287</td>
                                <td>11.10.2025</td>
                                <td>10317</td>
                                <td>MENEMEN HARCI</td>
                                <td>400 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>BOZULMA</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9286</td>
                                <td>11.10.2025</td>
                                <td>20062</td>
                                <td>SU MUHALLEBİSİ</td>
                                <td>24 AD</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>SKT</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9278</td>
                                <td>10.10.2025</td>
                                <td>10310</td>
                                <td>KEMİKSİZ TAVUK BUT</td>
                                <td>1000 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>KOKU</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9273</td>
                                <td>09.10.2025</td>
                                <td>10300</td>
                                <td>BRATWURST SOSİS</td>
                                <td>250 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9272</td>
                                <td>09.10.2025</td>
                                <td>10305</td>
                                <td>AVOKADO</td>
                                <td>70 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9271</td>
                                <td>09.10.2025</td>
                                <td>10100</td>
                                <td>LABNE PEYNİR</td>
                                <td>380 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9270</td>
                                <td>09.10.2025</td>
                                <td>10317</td>
                                <td>MENEMEN HARCI</td>
                                <td>200 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9269</td>
                                <td>09.10.2025</td>
                                <td>20062</td>
                                <td>SU MUHALLEBİSİ</td>
                                <td>20 AD</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9248</td>
                                <td>07.10.2025</td>
                                <td>10305</td>
                                <td>AVOKADO</td>
                                <td>180 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>9247</td>
                                <td>07.10.2025</td>
                                <td>20064</td>
                                <td>MAGNOLIA KREMASI</td>
                                <td>900 GR</td>
                                <td><span class="status-badge status-fire">Fire</span></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="table-footer">
                        <div>Showing 1 to 10 of 258 entries</div>
                        <div class="pagination">
                            <button class="page-btn">Previous</button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">4</button>
                            <button class="page-btn">5</button>
                            <span>...</span>
                            <button class="page-btn">26</button>
                            <button class="page-btn">Next</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>