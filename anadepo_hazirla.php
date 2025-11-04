<?php
session_start();
if (!isset($_SESSION["UserName"]) || !isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();

// Session'dan bilgileri al
$uAsOwnr = $_SESSION["U_AS_OWNR"] ?? '';

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

// TEST MODU: Canlı stok sorgusu kaldırıldı - RemainingOpenQuantity kullanılıyor
// Gerçek stok bilgisi için Items API'den çekilmesi gerekir, ama test için şimdilik RemainingOpenQuantity yeterli

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transferLines = [];
    
    foreach ($lines as $index => $line) {
        $quantity = floatval($_POST['quantity'][$index] ?? 0);
        if ($quantity > 0) {
            // BaseType/BaseEntry/BaseLine kaldırıldı - çünkü aynı InventoryTransferRequest için 
            // zaten bir StockTransfer varsa "already closed" hatası veriyor
            // Bunun yerine bağımsız bir StockTransfer oluşturuyoruz
            $transferLines[] = [
                'ItemCode' => $line['ItemCode'] ?? '',
                'Quantity' => $quantity,
                'FromWarehouseCode' => $fromWarehouse,
                'WarehouseCode' => $toWarehouse
            ];
        }
    }
    
    if (empty($transferLines)) {
        $errorMsg = "Hazırlanacak kalem bulunamadı!";
    } else {
        // StockTransfers POST
        $stockTransferPayload = [
            'FromWarehouse' => $fromWarehouse,
            'ToWarehouse' => $toWarehouse,
            'DocDate' => date('Y-m-d'),
            'StockTransferLines' => $transferLines
        ];
        
        $result = $sap->post('StockTransfers', $stockTransferPayload);
        
        if ($result['status'] == 200 || $result['status'] == 201) {
            // StockTransfer başarılı, şimdi InventoryTransferRequest'in durumunu güncelle (U_ASB2B_STATUS = '3' = Sevk Edildi)
            $updatePayload = [
                'U_ASB2B_STATUS' => '3' // Sevk Edildi
            ];
            $updateResult = $sap->patch("InventoryTransferRequests({$doc})", $updatePayload);
            
            // Update başarısız olsa bile StockTransfer oluştu, yönlendir
            if ($updateResult['status'] == 200 || $updateResult['status'] == 204) {
                header("Location: AnaDepo.php?msg=ok");
            } else {
                // StockTransfer başarılı ama status güncellenemedi, yine de yönlendir
                error_log("[ANADEPO HAZIRLA] StockTransfer başarılı ama status güncellenemedi: " . ($updateResult['status'] ?? 'NO STATUS'));
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
    <title>Hazırla - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
.form-group {
    margin-bottom: 1rem;
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

.qty-btn {
    padding: 4px 10px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.qty-btn:hover {
    background: #e9ecef;
}

.qty-input {
    width: 100px;
    text-align: center;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.95rem;
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
                <h2>Hazırla - DocEntry: <?= htmlspecialchars($doc) ?></h2>
                <button class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">← Geri Dön</button>
            </header>

            <div class="content-wrapper">
                <?php if (isset($errorMsg)): ?>
                    <div class="alert alert-danger" style="padding: 15px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 4px; margin-bottom: 20px; color: #721c24;">
                        <strong>Hata:</strong> <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>Bilgi:</strong> FromWarehouse: <?= htmlspecialchars($fromWarehouse) ?> → ToWarehouse: <?= htmlspecialchars($toWarehouse) ?>
                    <div style="margin-top: 8px; font-size: 0.85em; color: #666;">
                        <em>TEST MODU: "Sipariş Miktarı" = RemainingOpenQuantity (talepteki kalan miktar)</em>
                    </div>
                </div>

                <form method="POST" class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Sipariş Miktarı</th>
                                <th>Teslimat Miktarı</th>
                                <th>Miktar +/-</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $index => $line): 
                                $itemCode = $line['ItemCode'] ?? '';
                                $quantity = floatval($line['Quantity'] ?? 0); // AHMET'in istediği miktar (orijinal talep)
                                $remaining = floatval($line['RemainingOpenQuantity'] ?? 0); // Bu talepteki kalan miktar
                                $delivered = $quantity - $remaining; // Şu ana kadar bu talepten sevk edilen
                                
                                // TEST MODU: Sipariş Miktarı = RemainingOpenQuantity (talepteki kalan miktar)
                                // Gerçek stok için Items API'den çekilmesi gerekir, ama test için şimdilik bu yeterli
                                $currentStock = $remaining; // TEST: RemainingOpenQuantity kullan
                                $maxShipable = $remaining; // TEST: RemainingOpenQuantity kadar sevk edilebilir
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($line['ItemCode'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($line['ItemDescription'] ?? '-') ?></td>
                                    <td>
                                        <input type="number" 
                                               id="order_qty_<?= $index ?>"
                                               data-original="<?= htmlspecialchars($quantity) ?>"
                                               value="<?= htmlspecialchars($currentStock) ?>" 
                                               readonly 
                                               step="0.01"
                                               class="qty-input">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               id="delivered_qty_<?= $index ?>"
                                               value="<?= htmlspecialchars($delivered) ?>" 
                                               readonly 
                                               step="0.01"
                                               class="qty-input">
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                                            <button type="button" class="qty-btn minus" onclick="changeQuantity(<?= $index ?>, -1, <?= htmlspecialchars($maxShipable) ?>, <?= htmlspecialchars($delivered) ?>)">-</button>
                                            <input type="number" 
                                                   name="quantity[<?= $index ?>]" 
                                                   id="quantity_<?= $index ?>"
                                                   value="<?= htmlspecialchars($remaining) ?>" 
                                                   min="0" 
                                                   max="<?= htmlspecialchars($maxShipable) ?>"
                                                   step="0.01"
                                                   class="qty-input"
                                                   data-original-qty="<?= htmlspecialchars($quantity) ?>"
                                                   data-current-stock="<?= htmlspecialchars($currentStock) ?>"
                                                   data-max-shipable="<?= htmlspecialchars($maxShipable) ?>"
                                                   onchange="updateRelatedFields(<?= $index ?>, <?= htmlspecialchars($currentStock) ?>, <?= htmlspecialchars($delivered) ?>)"
                                                   oninput="updateRelatedFields(<?= $index ?>, <?= htmlspecialchars($currentStock) ?>, <?= htmlspecialchars($delivered) ?>)"
                                                   required>
                                            <button type="button" class="qty-btn plus" onclick="changeQuantity(<?= $index ?>, 1, <?= htmlspecialchars($maxShipable) ?>, <?= htmlspecialchars($delivered) ?>)">+</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 1.5rem; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">İptal</button>
                        <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Hazırla / Gönder</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
function changeQuantity(index, delta, maxShipable, deliveredQty) {
    const input = document.getElementById('quantity_' + index);
    let value = parseFloat(input.value) || 0;
    value += delta;
    if (value < 0) value = 0;
    if (value > parseFloat(maxShipable)) {
        value = parseFloat(maxShipable);
        alert('Sevk miktarı ana depodaki mevcut stoku (' + maxShipable + ') aşamaz!');
    }
    input.value = value;
    
    // Bağlı alanları güncelle
    const currentStock = parseFloat(document.getElementById('quantity_' + index).getAttribute('data-current-stock')) || 0;
    updateRelatedFields(index, currentStock, deliveredQty);
}

function updateRelatedFields(index, currentStock, deliveredQty) {
    const quantityInput = document.getElementById('quantity_' + index);
    const newQuantity = parseFloat(quantityInput.value) || 0;
    const maxShipable = parseFloat(quantityInput.getAttribute('data-max-shipable')) || 0;
    
    // Maksimum kontrol: Sevk miktarı maxShipable'ı aşamaz
    if (newQuantity > maxShipable) {
        quantityInput.value = maxShipable;
        alert('Sevk miktarı ana depodaki mevcut stoku (' + maxShipable + ') aşamaz!');
        updateRelatedFields(index, currentStock, deliveredQty); // Tekrar güncelle
        return;
    }
    
    // Minimum kontrol
    if (newQuantity < 0) {
        quantityInput.value = 0;
        newQuantity = 0;
    }
    
    // Teslimat Miktarı = Yeni sevk miktarı (Miktar +/- değeri)
    const deliveredInput = document.getElementById('delivered_qty_' + index);
    if (deliveredInput) {
        deliveredInput.value = newQuantity;
    }
    
    // Sipariş Miktarı = Ana depodaki mevcut stok - Sevk edilecek miktar
    // Örnek: Ana depoda 3 adet var, 3 adet sevk edilirse → 0 kalır
    const orderInput = document.getElementById('order_qty_' + index);
    if (orderInput) {
        const remainingStock = currentStock - newQuantity;
        orderInput.value = remainingStock >= 0 ? remainingStock : 0;
    }
}
    </script>
</body>
</html>

