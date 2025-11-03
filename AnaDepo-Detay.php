<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Depo Sipariş Detayı - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Info Section */
.info-section {
  display: flex;
  gap: 2rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-item strong {
  color: #2c3e50;
}

.info-item input {
  max-width: 200px;
}

/* Tabs */
.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e9ecef;
}

.tab-btn {
  padding: 0.75rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 0.95rem;
  color: #6c757d;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #2c3e50;
}

.tab-btn.active {
  color: #ff5722;
  border-bottom-color: #ff5722;
}

/* User Badge */
.user-badge {
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #6c757d;
}

/* Ticket Form */
.ticket-form {
  padding: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #2c3e50;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: #ff5722;
}

/* Priority Buttons */
.priority-buttons {
  display: flex;
  gap: 0.5rem;
}

.priority-btn {
  padding: 0.5rem 1.5rem;
  border: 2px solid;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.priority-low {
  border-color: #ffc107;
  color: #ffc107;
}

.priority-low.active {
  background: #ffc107;
  color: white;
}

.priority-medium {
  border-color: #17a2b8;
  color: #17a2b8;
}

.priority-medium.active {
  background: #17a2b8;
  color: white;
}

.priority-high {
  border-color: #dc3545;
  color: #dc3545;
}

.priority-high.active {
  background: #dc3545;
  color: white;
}

/* File Upload Area */
.file-upload-area {
  display: flex;
  gap: 0.5rem;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

/* Small Button */
.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.85rem;
}

/* Detail Pages */
.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.detail-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-title h3 {
  font-size: 1.5rem;
  color: #2c3e50;
  font-weight: 400;
}

.detail-title h3 strong {
  font-weight: 600;
  color: #ff5722;
}

.detail-icon {
  font-size: 2rem;
}

.detail-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item label {
  font-size: 0.85rem;
  color: #6c757d;
  font-weight: 500;
}

.detail-value {
  font-size: 1rem;
  color: #2c3e50;
  font-weight: 500;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .info-section {
    flex-direction: column;
    gap: 1rem;
  }

  .priority-buttons {
    flex-direction: column;
  }

  .file-upload-area {
    flex-direction: column;
  }

  .detail-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .detail-title h3 {
    font-size: 1.2rem;
  }
}

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
                    <div class="user-name">Koşuyolu 1000 - Koşuyolu</div>
                    <div class="version">v1.0.43</div>
                </div>
            </div>
        </aside>


         Main Content 
        <main class="main-content">
            <header class="page-header">
                <h2>Ana Depo Siparişleri</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='ana-depo-siparis-olustur.html'">+ Yeni Sipariş Oluştur</button>
                    <button class="btn btn-secondary">Çıkış Yap ↗</button>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="detail-header">
                    <div class="detail-title">
                        <span class="detail-icon">🏢</span>
                        <h3>Ana Depo Siparişi: <strong>3709</strong></h3>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='ana-depo.html'">← Geri Dön</button>
                </div>

                <div class="detail-card">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Sipariş No:</label>
                            <div class="detail-value">3709</div>
                        </div>
                        <div class="detail-item">
                            <label>Tahmini Teslimat Tarihi:</label>
                            <div class="detail-value">05.10.2025</div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Tarihi:</label>
                            <div class="detail-value">05.10.2025</div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Durumu:</label>
                            <div class="detail-value">
                                <span class="badge badge-success">Tamamlandı</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Özeti:</label>
                            <div class="detail-value">(10 çeşit kalem 43322 birim)</div>
                        </div>
                        <div class="detail-item">
                            <label>Teslimat Belge No:</label>
                            <div class="detail-value">-</div>
                        </div>
                        <div class="detail-item">
                            <label>Şube Kodu:</label>
                            <div class="detail-value">1000</div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Notu:</label>
                            <div class="detail-value">İlgili yöntem Stok nakli talebi 3709.</div>
                        </div>
                    </div>
                </div>

                <div class="section-title">Sipariş Kalemleri</div>

                <div class="table-card">
                    <div class="table-header">
                        <h4>Sipariş Kalemleri</h4>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Sipariş Miktarı</th>
                                <th>Teslimat Miktarı</th>
                                <th>Ölçü Birimi</th>
                                <th>Açıklama</th>
                                <th>Resim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>30445</td>
                                <td>CAM ŞİŞE</td>
                                <td>12</td>
                                <td>12</td>
                                <td>AD</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>10236</td>
                                <td>CHAI TEA</td>
                                <td>1000</td>
                                <td>1000</td>
                                <td>PK</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>10237</td>
                                <td>EARL GREY</td>
                                <td>500</td>
                                <td>500</td>
                                <td>PK</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>10238</td>
                                <td>GREEN TEA</td>
                                <td>750</td>
                                <td>750</td>
                                <td>PK</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>10239</td>
                                <td>JASMINE TEA</td>
                                <td>600</td>
                                <td>600</td>
                                <td>PK</td>
                                <td>-</td>
                                <td>-</td>
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
