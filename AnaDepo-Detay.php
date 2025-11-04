<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();

// URL'den doc parametresi al
$doc = $_GET['doc'] ?? '';

if (empty($doc)) {
    header("Location: AnaDepo.php");
    exit;
}

// InventoryTransferRequests({doc}) çağır
$docQuery = "InventoryTransferRequests({$doc})";
$docData = $sap->get($docQuery);
$requestData = $docData['response'] ?? null;

if (!$requestData) {
    echo "Belge bulunamadı!";
    exit;
}

// Status mapping
function getStatusText($status) {
    $statusMap = [
        '1' => 'Onay Bekliyor',
        '2' => 'Hazırlanıyor',
        '3' => 'Sevk Edildi',
        '4' => 'Tamamlandı',
        '5' => 'İptal Edildi'
    ];
    return $statusMap[$status] ?? 'Bilinmiyor';
}

function getStatusClass($status) {
    $classMap = [
        '1' => 'status-pending',
        '2' => 'status-processing',
        '3' => 'status-shipped',
        '4' => 'status-completed',
        '5' => 'status-cancelled'
    ];
    return $classMap[$status] ?? 'status-unknown';
}

function formatDate($date) {
    if (empty($date)) return '-';
    if (strpos($date, 'T') !== false) {
        return date('d.m.Y', strtotime(substr($date, 0, 10)));
    }
    return date('d.m.Y', strtotime($date));
}

$docEntry = $requestData['DocEntry'] ?? '';
$docDate = formatDate($requestData['DocDate'] ?? '');
$dueDate = formatDate($requestData['DueDate'] ?? '');
$status = $requestData['U_ASB2B_STATUS'] ?? '1';
$statusText = getStatusText($status);
$numAtCard = $requestData['U_ASB2B_NumAtCard'] ?? '-';
$ordSum = $requestData['U_ASB2B_ORDSUM'] ?? '-';
$branchCode = $requestData['U_ASB2B_BRAN'] ?? '-';
$journalMemo = $requestData['JournalMemo'] ?? '-';
$fromWarehouse = $requestData['FromWarehouse'] ?? '';
$toWarehouse = $requestData['ToWarehouse'] ?? '';
$lines = $requestData['StockTransferLines'] ?? [];

// TEST: Durumu Onay Bekliyor'a döndür (GEÇİCİ - SONRA KALDIRILACAK)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_status'])) {
    $resetPayload = [
        'U_ASB2B_STATUS' => '1' // Onay Bekliyor
    ];
    $resetResult = $sap->patch("InventoryTransferRequests({$doc})", $resetPayload);
    
    if ($resetResult['status'] == 200 || $resetResult['status'] == 204) {
        // Başarılı, sayfayı yenile
        header("Location: AnaDepo-Detay.php?doc={$doc}");
        exit;
    } else {
        error_log("[TEST RESET] Status reset başarısız: " . ($resetResult['status'] ?? 'NO STATUS'));
    }
}

// Sevk Edildi ise StockTransfers kaydını çek (SAP'de hangi tabloya yazıldığını görmek için)
$stockTransfers = [];
$stockTransferInfo = null;
if ($status == '3' || $status == '4') {
    // StockTransfers sorgusu: BaseType=1250000001 (InventoryTransferRequest), BaseEntry=docEntry
    $stockTransferFilter = "BaseType eq 1250000001 and BaseEntry eq {$docEntry}";
    $stockTransferQuery = "StockTransfers?\$filter=" . urlencode($stockTransferFilter) . "&\$orderby=DocEntry desc";
    $stockTransferData = $sap->get($stockTransferQuery);
    $stockTransfers = $stockTransferData['response']['value'] ?? [];
    
    if (!empty($stockTransfers)) {
        $stockTransferInfo = $stockTransfers[0]; // En son sevk kaydı
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Depo Sipariş Detayı - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #d1ecf1;
    color: #0c5460;
}

.status-shipped {
    background: #d4edda;
    color: #155724;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status-unknown {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .detail-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
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

        <main class="main-content">
            <header class="page-header">
                <h2>Ana Depo Sipariş Detayı</h2>
                <div>
                    <?php if ($status == '1' || $status == '2'): ?>
                        <!-- TEST: Onay Bekliyor veya Hazırlanıyor durumunda Hazırla butonu -->
                        <button class="btn btn-primary" onclick="window.location.href='anadepo_hazirla.php?doc=<?= $docEntry ?>'" style="margin-right: 10px;">
                            📦 Hazırla (Test)
                        </button>
                    <?php endif; ?>
                    <?php if ($status == '3' || $status == '4'): ?>
                        <!-- TEST: Sevk Edildi veya Tamamlandı durumunda Onay Bekliyor'a döndür butonu (GEÇİCİ - SONRA KALDIRILACAK) -->
                        <form method="POST" action="AnaDepo-Detay.php?doc=<?= $docEntry ?>" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="reset_status" value="1">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Durumu Onay Bekliyor olarak sıfırlamak istediğinize emin misiniz? (Test amaçlı)');">
                                🔄 Onay Bekliyor'a Döndür (Test)
                            </button>
                        </form>
                    <?php endif; ?>
                    <button class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">← Geri Dön</button>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="detail-header">
                    <div class="detail-title">
                        <h3>Ana Depo Siparişi: <strong><?= htmlspecialchars($docEntry) ?></strong></h3>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Sipariş No:</label>
                            <div class="detail-value"><?= htmlspecialchars($docEntry) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Tarihi:</label>
                            <div class="detail-value"><?= htmlspecialchars($docDate) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Özeti:</label>
                            <div class="detail-value"><?= htmlspecialchars($ordSum) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Şube Kodu:</label>
                            <div class="detail-value"><?= htmlspecialchars($branchCode) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Tahmini Teslimat Tarihi:</label>
                            <div class="detail-value"><?= htmlspecialchars($dueDate) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Durumu:</label>
                            <div class="detail-value">
                                <span class="status-badge <?= getStatusClass($status) ?>"><?= htmlspecialchars($statusText) ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Teslimat Belge No:</label>
                            <div class="detail-value"><?= htmlspecialchars($numAtCard) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Sipariş Notu:</label>
                            <div class="detail-value"><?= htmlspecialchars($journalMemo) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Gönderen Depo:</label>
                            <div class="detail-value"><?= htmlspecialchars($fromWarehouse) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Alıcı Depo (Hedef):</label>
                            <div class="detail-value"><?= htmlspecialchars($toWarehouse) ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($stockTransferInfo): ?>
                    <div class="section-title">Sevk Bilgileri (SAP StockTransfers Tablosu)</div>
                    <div class="detail-card">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>StockTransfer DocEntry:</label>
                                <div class="detail-value"><?= htmlspecialchars($stockTransferInfo['DocEntry'] ?? '-') ?></div>
                            </div>
                            <div class="detail-item">
                                <label>StockTransfer DocNum:</label>
                                <div class="detail-value"><?= htmlspecialchars($stockTransferInfo['DocNum'] ?? '-') ?></div>
                            </div>
                            <div class="detail-item">
                                <label>Sevk Tarihi:</label>
                                <div class="detail-value"><?= formatDate($stockTransferInfo['DocDate'] ?? '') ?></div>
                            </div>
                            <div class="detail-item">
                                <label>Gönderen Depo (Sevk):</label>
                                <div class="detail-value"><?= htmlspecialchars($stockTransferInfo['FromWarehouse'] ?? '-') ?></div>
                            </div>
                            <div class="detail-item">
                                <label>Gittiği Depo (Sevk):</label>
                                <div class="detail-value"><strong><?= htmlspecialchars($stockTransferInfo['ToWarehouse'] ?? '-') ?></strong></div>
                            </div>
                            <div class="detail-item">
                                <label>Durum:</label>
                                <div class="detail-value">
                                    <?php
                                    $stStatus = $stockTransferInfo['DocumentStatus'] ?? '';
                                    $stStatusText = $stStatus == 'bost_Closed' ? 'Kapalı (Sevk Edildi)' : ($stStatus == 'bost_Open' ? 'Açık' : $stStatus);
                                    ?>
                                    <?= htmlspecialchars($stStatusText) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="section-title">Sipariş Kalemleri</div>

                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kalem Numarası</th>
                                <th>Kalem Tanımı</th>
                                <th>Talep Miktarı</th>
                                <th>Teslimat Miktarı</th>
                                <th>Birim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lines)): ?>
                                <?php foreach ($lines as $line): ?>
                                    <?php 
                                        $quantity = $line['Quantity'] ?? 0;
                                        $remaining = $line['RemainingOpenQuantity'] ?? 0;
                                        $delivered = $quantity - $remaining;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($line['ItemCode'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($line['ItemDescription'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($quantity) ?></td>
                                        <td><?= htmlspecialchars($delivered) ?></td>
                                        <td><?= htmlspecialchars($line['UoMCode'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;color:#888;">Kalem bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>

