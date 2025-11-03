<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fire/Zayi Kaydı Oluştur - CREMMAVERSE</title>
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
                <h2>Fire/Zayi Kaydı Oluştur</h2>
                <button class="btn-secondary" onclick="window.location.href='fire-zayi.html'">← Geri</button>
            </header>

            <div class="content-wrapper">
                <section class="card">
                    <div class="tab-container">
                        <button class="tab-btn active" data-tab="fire">Fire</button>
                        <button class="tab-btn" data-tab="zayi">Zayi</button>
                    </div>

                    <div class="tab-content active" id="fire">
                        <div class="filter-section">
                            <div class="filter-group">
                                <label>Kalem Tanımı</label>
                                <input type="text" class="filter-input" placeholder="Kalem Tanımı Seçin">
                            </div>
                            <div class="filter-group">
                                <label>Kalem Grubu</label>
                                <input type="text" class="filter-input" placeholder="Kalem Grubu Seçin">
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

                        <table class="data-table order-table">
                            <thead>
                                <tr>
                                    <th>Kalem Kodu ▲</th>
                                    <th>Kalem Tanımı</th>
                                    <th>Kalem Grubu</th>
                                    <th>Ölçü Birimi</th>
                                    <th>Miktar</th>
                                    <th>Açıklama</th>
                                    <th>Görsel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>10002</td>
                                    <td>EKŞİ MAYALI TAM BUĞDAY EKMEK</td>
                                    <td>EKMEKLER</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10003</td>
                                    <td>ÜN</td>
                                    <td>ÜN</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10010</td>
                                    <td>MOM'S GRANOLA</td>
                                    <td>KURU GIDA</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10011</td>
                                    <td>KREP KIRIĞI</td>
                                    <td>KURU GIDA</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10014</td>
                                    <td>CİCİBEBE</td>
                                    <td>BİSKÜVİ</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10017</td>
                                    <td>ETİ FİT TOZ BURÇAK</td>
                                    <td>BİSKÜVİ</td>
                                    <td>GR</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>10018</td>
                                    <td>WAFFLE</td>
                                    <td>BİSKÜVİ</td>
                                    <td>AD</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn minus">-</button>
                                            <input type="number" value="0" class="qty-input">
                                            <button class="qty-btn plus">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="file-upload-group">
                                            <button class="btn-file">Dosya Seç</button>
                                            <button class="btn-file-selected">Dosya seçilmedi</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="form-actions">
                            <button class="btn btn-secondary" onclick="window.location.href='fire-zayi.html'">İptal</button>
                            <button class="btn btn-primary">Kaydet</button>
                        </div>
                    </div>

                    <div class="tab-content" id="zayi">
                        <p style="text-align: center; padding: 40px; color: #666;">Zayi kaydı formu burada görüntülenecek</p>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>