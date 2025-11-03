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
    <title>Stok Sayımları - CREMMAVERSE</title>
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
                <h2>Stok Sayımları</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary" onclick="window.location.href='StokSO.php'">+ Yeni Sayım Oluştur</button>
                    <button class="btn btn-outline">⟳ Yenile</button>
                </div>
            </header>

            <div class="content-card">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Sipariş Durumu</label>
                        <select class="form-select">
                            <option>Tüm Durumlar</option>
                            <option>Beklemede</option>
                            <option>Tamamlandı</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" class="form-input" placeholder="gg.aa.yyyy">
                    </div>
                    <div class="filter-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" class="form-input" placeholder="gg.aa.yyyy">
                    </div>
                </div>

                <div class="table-controls">
                    <div class="show-entries">
                        <span>Sayfada</span>
                        <select class="form-select">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <span>kayıt göster</span>
                    </div>
                    <div class="search-box">
                        <span>Ara:</span>
                        <input type="text" class="form-input">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Şube Kodu</th>
                                <th>Sayım No</th>
                                <th>Sayım Tarihi</th>
                                <th>Giriş Tarihi</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1000</td>
                                <td>88</td>
                                <td>3 Ekim 2025</td>
                                <td>3 Ekim 2025</td>
                                <td><span class="badge badge-warning">Beklemede</span></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="Stok-Detay.php"><button class="btn-icon btn-view">👁️ Detay</button></a>
                                        <button class="btn btn-sm btn-success">✏ Düzenle</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>1000</td>
                                <td>83</td>
                                <td>1 Eylül 2025</td>
                                <td>1 Eylül 2025</td>
                                <td><span class="badge badge-success">Tamamlandı</span></td>
                                <td>
                                    <a href="Stok-Detay.php"><button class="btn-icon btn-view">👁️ Detay</button></a>
                                </td>
                            </tr>
                            <tr>
                                <td>1000</td>
                                <td>72</td>
                                <td>1 Ağustos 2025</td>
                                <td>7 Ağustos 2025</td>
                                <td><span class="badge badge-success">Tamamlandı</span></td>
                                <td>
                                    <a href="Stok-Detay.php"><button class="btn-icon btn-view">👁️ Detay</button></a>
                                </td>
                            </tr>
                            <tr>
                                <td>1000</td>
                                <td>62</td>
                                <td>1 Temmuz 2025</td>
                                <td>4 Temmuz 2025</td>
                                <td><span class="badge badge-success">Tamamlandı</span></td>
                                <td>
                                    <a href="Stok-Detay.php"><button class="btn-icon btn-view">👁️ Detay</button></a>
                                </td>
                            </tr>
                            <tr>
                                <td>1000</td>
                                <td>56</td>
                                <td>1 Haziran 2025</td>
                                <td>1 Haziran 2025</td>
                                <td><span class="badge badge-success">Tamamlandı</span></td>
                                <td>
                                    <a href="Stok-Detay.php"><button class="btn-icon btn-view">👁️ Detay</button></a>    
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span>5 kayıttan 1 - 5 arası gösteriliyor</span>
                </div>

                <div class="pagination">
                    <button class="btn-pagination">Önceki</button>
                    <button class="btn-pagination active">1</button>
                    <button class="btn-pagination">Sonraki</button>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>
