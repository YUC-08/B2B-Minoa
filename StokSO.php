<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Sayımı Oluştur - CREMMAVERSE</title>
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
                <h2>Stok Sayımı Oluştur</h2>
                <button class="btn btn-outline" onclick="window.location.href='Stok.php'">← Geri</button>
            </header>

            <div class="content-card">
                <div class="info-section">
                    <div class="info-item">
                        <strong>Şube Kodu:</strong> 1000
                    </div>
                    <div class="info-item">
                        <strong>Kullanıcı:</strong> Koşuyolu 1000
                    </div>
                    <div class="info-item">
                        <strong>Sayım Tarihi:</strong>
                        <input type="date" class="form-input" value="2025-10-12">
                    </div>
                </div>

                <div class="filter-grid" style="margin-top: 1.5rem;">
                    <div class="filter-group">
                        <label>Kalem Tanımı:</label>
                        <input type="text" placeholder="Kalem Tanımı seçiniz" class="form-input">
                    </div>
                    <div class="filter-group">
                        <label>Kalem Grubu:</label>
                        <input type="text" placeholder="Kalem Grubu seçiniz" class="form-input">
                    </div>
                </div>

                <div class="table-controls">
                    <div class="show-entries">
                        <span>Sayfada</span>
                        <select class="form-select">
                            <option>25</option>
                            <option>50</option>
                            <option>100</option>
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
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Kalem Grubu</th>
                                <th>Ölçü Birimi</th>
                                <th>Sayım Miktarı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10002</td>
                                <td>EKŞİ MAYALI TAM BUĞDAY EKMEK</td>
                                <td>BAK. HAMMADDE</td>
                                <td>PK</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10003</td>
                                <td>UN</td>
                                <td>BAK. HAMMADDE</td>
                                <td>GR</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10010</td>
                                <td>MOM'S GRANOLA</td>
                                <td>BAK. HAMMADDE</td>
                                <td>GR</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10011</td>
                                <td>KREP KIRIĞI</td>
                                <td>BAK. HAMMADDE</td>
                                <td>GR</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10014</td>
                                <td>CİCİBEBE</td>
                                <td>BAR HAMMADDE</td>
                                <td>PK</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10017</td>
                                <td>ETİ FİT TOZ BURÇAK</td>
                                <td>BAK. HAMMADDE</td>
                                <td>GR</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10018</td>
                                <td>WAFFLE</td>
                                <td>DONUKLAR</td>
                                <td>AD</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10020</td>
                                <td>KURU DOMATES</td>
                                <td>BAK. HAMMADDE</td>
                                <td>GR</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10021</td>
                                <td>KURU PORTAKAL</td>
                                <td>BAR HAMMADDE</td>
                                <td>PK</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>10023</td>
                                <td>KURU LİMON</td>
                                <td>BAR HAMMADDE</td>
                                <td>AD</td>
                                <td>
                                    <div class="quantity-control">
                                        <button class="btn-quantity">-</button>
                                        <input type="number" value="0" class="quantity-input">
                                        <button class="btn-quantity">+</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>
