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
    <title>Ana Depo Siparişi Oluştur - CREMMAVERSE</title>
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
                <h2>Ana Depo Siparişi Oluştur</h2>
                <button class="btn-exit" onclick="window.location.href='AnaDepo.php'">Çıkış Yap ↗</button>
            </header>

            <div class="content-wrapper">
                <section class="card">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Kalem Tanımı Filtresi:</label>
                            <input type="text" class="filter-input" placeholder="Kalem Tanımı Seçin">
                        </div>
                        <div class="filter-group">
                            <label>Kalem Grubu Filtresi:</label>
                            <input type="text" class="filter-input" placeholder="Kalem Grubu Seçin">
                        </div>
                        <div class="filter-group">
                            <label>Stokta Filtresi:</label>
                            <select class="filter-select">
                                <option>Tümü</option>
                                <option>Var</option>
                                <option>Yok</option>
                            </select>
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

                    <table class="data-table order-table">
                        <thead>
                            <tr>
                                <th>Kalem Kodu ▲</th>
                                <th>Kalem Tanımı</th>
                                <th>Kalem Grubu</th>
                                <th>Stokta</th>
                                <th>Stoktaki Miktar</th>
                                <th>Minimum Miktar</th>
                                <th>Sipariş Miktarı</th>
                                <th>Ölçü Birimi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10010</td>
                                <td>MOM'S GRANOLA</td>
                                <td>KURU GIDA</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>GR</td>
                            </tr>
                            <tr>
                                <td>10011</td>
                                <td>KREP KIRIĞI</td>
                                <td>KURU GIDA</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>GR</td>
                            </tr>
                            <tr>
                                <td>10021</td>
                                <td>KURU PORTAKAL</td>
                                <td>KURUTULMUŞ GIDALAR</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>PK</td>
                            </tr>
                            <tr>
                                <td>10023</td>
                                <td>KURU LİMON</td>
                                <td>KURUTULMUŞ GIDALAR</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>PK</td>
                            </tr>
                            <tr>
                                <td>10150</td>
                                <td>SPECULOOS KREMASI (LOTUS)</td>
                                <td>ÇİKOLATA</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>AD</td>
                            </tr>
                            <tr>
                                <td>10183</td>
                                <td>SÜT REÇELİ</td>
                                <td>PÜRELER</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>GR</td>
                            </tr>
                            <tr>
                                <td>10185</td>
                                <td>BAL</td>
                                <td>SOSLAR</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>AD</td>
                            </tr>
                            <tr>
                                <td>10186</td>
                                <td>YULAF EZMESİ</td>
                                <td>SÜRÜLEBİLİR</td>
                                <td>0</td>
                                <td>0</td>
                                <td>0</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="qty-btn minus">-</button>
                                        <input type="number" value="0" class="qty-input">
                                        <button class="qty-btn plus">+</button>
                                    </div>
                                </td>
                                <td>PK</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='ana-depo.html'">İptal</button>
                        <button class="btn btn-primary">Sipariş Oluştur</button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>