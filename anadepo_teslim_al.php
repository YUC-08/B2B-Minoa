<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();

// Session'dan bilgileri al
$uAsOwnr = $_SESSION["U_AS_OWNR"] ?? '';
$branch = $_SESSION["WhsCode"] ?? '';

if (empty($uAsOwnr) || empty($branch)) {
    die("Session bilgileri eksik. Lütfen tekrar giriş yapın.");
}

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

$lines = $requestData['StockTransferLines'] ?? [];
$fromWarehouse = $requestData['FromWarehouse'] ?? '';
$toWarehouse = $requestData['ToWarehouse'] ?? '';

// Hedef depo sorgusu (U_ASB2B_MAIN=1)
$targetWarehouseFilter = "U_AS_OWNR eq '{$uAsOwnr}' and U_ASB2B_BRAN eq '{$branch}' and U_ASB2B_MAIN eq '1'";
$targetWarehouseQuery = "Warehouses?\$filter=" . urlencode($targetWarehouseFilter);
$targetWarehouseData = $sap->get($targetWarehouseQuery);
$targetWarehouses = $targetWarehouseData['response']['value'] ?? [];
$targetWarehouse = !empty($targetWarehouses) ? $targetWarehouses[0]['WarehouseCode'] : null;

if (empty($targetWarehouse)) {
    die("Hedef depo (U_ASB2B_MAIN=1) bulunamadı!");
}

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transferLines = [];
    
    foreach ($lines as $index => $line) {
        $deliveredQty = floatval($_POST['delivered_qty'][$index] ?? 0);
        if ($deliveredQty > 0) {
            $transferLines[] = [
                'ItemCode' => $line['ItemCode'] ?? '',
                'Quantity' => $deliveredQty,
                'FromWarehouseCode' => $toWarehouse,
                'WarehouseCode' => $targetWarehouse
            ];
        }
    }
    
    if (empty($transferLines)) {
        $errorMsg = "Teslim miktarı girilen kalem bulunamadı!";
    } else {
        // StockTransfers POST
        $stockTransferPayload = [
            'FromWarehouse' => $toWarehouse,
            'ToWarehouse' => $targetWarehouse,
            'DocDate' => date('Y-m-d'),
            'StockTransferLines' => $transferLines
        ];
        
        $result = $sap->post('StockTransfers', $stockTransferPayload);
        
        if ($result['status'] == 200 || $result['status'] == 201) {
            // StockTransfer başarılı, şimdi InventoryTransferRequest'in durumunu güncelle (U_ASB2B_STATUS = '4' = Tamamlandı)
            $updatePayload = [
                'U_ASB2B_STATUS' => '4' // Tamamlandı
            ];
            $updateResult = $sap->patch("InventoryTransferRequests({$doc})", $updatePayload);
            
            // Update başarısız olsa bile StockTransfer oluştu, yönlendir
            if ($updateResult['status'] == 200 || $updateResult['status'] == 204) {
                header("Location: AnaDepo.php?msg=ok");
            } else {
                // StockTransfer başarılı ama status güncellenemedi, yine de yönlendir
                error_log("[TESLIM AL] StockTransfer başarılı ama status güncellenemedi: " . ($updateResult['status'] ?? 'NO STATUS'));
                if (isset($updateResult['response']['error'])) {
                    error_log("[TESLIM AL] Update Error: " . json_encode($updateResult['response']['error']));
                }
                header("Location: AnaDepo.php?msg=ok");
            }
            exit;
        } else {
            $errorMsg = "StockTransfer oluşturulamadı! HTTP " . ($result['status'] ?? 'NO STATUS');
            if (isset($result['response']['error'])) {
                $errorMsg .= " - " . json_encode($result['response']['error']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teslim Al - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #2c3e50;
}

.form-group input[type="number"],
.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.95rem;
}

.form-group input[readonly] {
    background-color: #e9ecef;
}

.info-box {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.info-box strong {
    color: #0056b3;
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
                <h2>Teslim Al - DocEntry: <?= htmlspecialchars($doc) ?></h2>
                <button class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">← Geri Dön</button>
            </header>

            <div class="content-wrapper">
                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger" style="padding: 15px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 4px; margin-bottom: 20px; color: #721c24;">
                        <strong>Hata:</strong> <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>Bilgi:</strong> FromWarehouse: <?= htmlspecialchars($toWarehouse) ?> → ToWarehouse: <?= htmlspecialchars($targetWarehouse) ?>
                </div>

                <form method="POST" class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Sipariş Miktarı</th>
                                <th>Teslimat Miktarı</th>
                                <th>Eksik Miktar</th>
                                <th>Kusurlu Miktar</th>
                                <th>Not</th>
                                <th>Görsel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $index => $line): 
                                $quantity = $line['Quantity'] ?? 0;
                                $remaining = $line['RemainingOpenQuantity'] ?? 0;
                                $delivered = $quantity - $remaining;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($line['ItemCode'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($line['ItemDescription'] ?? '-') ?></td>
                                    <td class="table-cell-center">
                                        <input type="number" 
                                               value="<?= htmlspecialchars($quantity) ?>" 
                                               readonly 
                                               step="0.01"
                                               class="qty-input"
                                               style="background-color: #e9ecef;">
                                    </td>
                                    <td class="table-cell-center">
                                        <div class="quantity-controls">
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'delivered', -1, <?= htmlspecialchars($quantity) ?>)">-</button>
                                            <input type="number" 
                                                   name="delivered_qty[<?= $index ?>]" 
                                                   id="delivered_qty_<?= $index ?>"
                                                   value="<?= htmlspecialchars($delivered) ?>" 
                                                   min="0" 
                                                   max="<?= htmlspecialchars($quantity) ?>"
                                                   step="0.01"
                                                   class="qty-input"
                                                   required>
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'delivered', 1, <?= htmlspecialchars($quantity) ?>)">+</button>
                                        </div>
                                    </td>
                                    <td class="table-cell-center">
                                        <div class="quantity-controls">
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'missing', -1, <?= htmlspecialchars($quantity) ?>)">-</button>
                                            <input type="number" 
                                                   name="missing_qty[<?= $index ?>]" 
                                                   id="missing_qty_<?= $index ?>"
                                                   value="0" 
                                                   min="0" 
                                                   max="<?= htmlspecialchars($quantity) ?>"
                                                   step="0.01"
                                                   class="qty-input qty-input-small">
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'missing', 1, <?= htmlspecialchars($quantity) ?>)">+</button>
                                        </div>
                                    </td>
                                    <td class="table-cell-center">
                                        <div class="quantity-controls">
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'damaged', -1, <?= htmlspecialchars($quantity) ?>)">-</button>
                                            <input type="number" 
                                                   name="damaged_qty[<?= $index ?>]" 
                                                   id="damaged_qty_<?= $index ?>"
                                                   value="0" 
                                                   min="0" 
                                                   max="<?= htmlspecialchars($quantity) ?>"
                                                   step="0.01"
                                                   class="qty-input qty-input-small">
                                            <button type="button" class="qty-btn" onclick="changeQuantity(<?= $index ?>, 'damaged', 1, <?= htmlspecialchars($quantity) ?>)">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <textarea name="notes[<?= $index ?>]" rows="2" class="notes-textarea" placeholder="Not..."></textarea>
                                    </td>
                                    <td>
                                        <input type="file" name="image[<?= $index ?>]" accept="image/*" class="file-input">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 1.5rem; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">İptal</button>
                        <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Teslim Al / Onayla</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <style>
.quantity-controls {
    display: flex;
    gap: 5px;
    align-items: center;
    justify-content: center;
}

.qty-btn {
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    font-size: 0.95rem;
    min-width: 35px;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.qty-btn:active {
    background: #dee2e6;
}

.qty-input {
    width: 100px;
    text-align: center;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.95rem;
}

.qty-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.qty-input-small {
    width: 80px;
}

.notes-textarea {
    width: 100%;
    min-width: 150px;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.85rem;
    resize: vertical;
}

.file-input {
    font-size: 0.85rem;
    padding: 0.25rem;
}

.table-cell-center {
    text-align: center;
}
    </style>
    <script>
function changeQuantity(index, type, delta, maxValue = null) {
    const inputId = type === 'delivered' ? 'delivered_qty_' + index : type + '_qty_' + index;
    const input = document.getElementById(inputId);
    if (!input) return;
    
    let value = parseFloat(input.value) || 0;
    value += delta;
    if (value < 0) value = 0;
    if (maxValue !== null && value > maxValue) {
        value = maxValue;
        alert('Maksimum değer: ' + maxValue);
    }
    
    input.value = value.toFixed(2);
    
    // Bağımlılıkları güncelle (delivered dışında)
    if (type !== 'delivered') {
        updateRelatedFields(index);
    }
}

function updateRelatedFields(index) {
    // Sipariş miktarı (readonly - ilk input)
    const row = document.getElementById('missing_qty_' + index).closest('tr');
    const orderQtyInput = row.querySelector('input[readonly]');
    const orderQty = parseFloat(orderQtyInput.value) || 0;
    
    // Teslimat miktarı
    const deliveredInput = document.getElementById('delivered_qty_' + index);
    
    // Eksik miktar
    const missingInput = document.getElementById('missing_qty_' + index);
    const missingQty = parseFloat(missingInput.value) || 0;
    
    // Kusurlu miktar
    const damagedInput = document.getElementById('damaged_qty_' + index);
    const damagedQty = parseFloat(damagedInput.value) || 0;
    
    // Maksimum kontrol: Eksik + Kusurlu, Sipariş Miktarını aşamaz
    const totalDeficit = missingQty + damagedQty;
    if (totalDeficit > orderQty) {
        const excess = totalDeficit - orderQty;
        // Eksik miktarı düşür
        if (missingQty > 0) {
            const newMissing = Math.max(0, missingQty - excess);
            missingInput.value = newMissing.toFixed(2);
            // Tekrar güncelle
            updateRelatedFields(index);
            return;
        }
        // Kusurlu miktarı düşür
        if (damagedQty > 0) {
            const newDamaged = Math.max(0, damagedQty - excess);
            damagedInput.value = newDamaged.toFixed(2);
            // Tekrar güncelle
            updateRelatedFields(index);
            return;
        }
    }
    
    // Teslimat Miktarı = Sipariş Miktarı - Eksik Miktar - Kusurlu Miktar
    const calculatedDelivered = orderQty - missingQty - damagedQty;
    const newDelivered = calculatedDelivered >= 0 ? calculatedDelivered : 0;
    
    if (deliveredInput) {
        deliveredInput.value = newDelivered.toFixed(2);
    }
}

// Sayfa yüklendiğinde her satır için bağımlılıkları başlat
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        const missingInput = document.getElementById('missing_qty_' + index);
        const damagedInput = document.getElementById('damaged_qty_' + index);
        const deliveredInput = document.getElementById('delivered_qty_' + index);
        
        if (missingInput) {
            missingInput.addEventListener('input', () => updateRelatedFields(index));
            missingInput.addEventListener('change', () => updateRelatedFields(index));
        }
        if (damagedInput) {
            damagedInput.addEventListener('input', () => updateRelatedFields(index));
            damagedInput.addEventListener('change', () => updateRelatedFields(index));
        }
        if (deliveredInput) {
            deliveredInput.addEventListener('input', function() {
                // Teslimat miktarı manuel değiştirilirse kontrol et
                const orderQtyInput = row.querySelector('input[readonly]');
                const orderQty = parseFloat(orderQtyInput.value) || 0;
                const deliveredQty = parseFloat(this.value) || 0;
                
                if (deliveredQty > orderQty) {
                    this.value = orderQty.toFixed(2);
                    alert('Teslimat miktarı sipariş miktarını aşamaz!');
                }
            });
        }
    });
});
    </script>
</body>
</html>

