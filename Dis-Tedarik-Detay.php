<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';

$sap = new SAPConnect();
$whsCode = $_SESSION["WhsCode"] ?? '';
$docNum = $_GET['DocNum'] ?? '';

if (empty($docNum)) {
    header("Location: Dis-Tedarik.php");
    exit;
}

// SAP'den sipariş detayını çek (OPOR_DETAIL)
$data = $sap->get("SQLQueries('OPOR_DETAIL')/List?value1='SUPPLY'&value2='{$docNum}'"); 
$orderData = $data['response']['value'][0] ?? null;
$isRealOPOR = !empty($data['response']['value']); // Gerçek OPOR mu kontrolü

// Eğer OPOR_DETAIL'de yoksa, OPOR_LIST'ten al (UDO datası)
if (!$orderData) {
    $listQuery = "SQLQueries('OPOR_LIST')/List?value1='SUPPLY'&value2='{$whsCode}'";
    $listData = $sap->get($listQuery);
    $listRows = $listData['response']['value'] ?? [];
    
    foreach ($listRows as $row) {
        if (($row['DocNum'] ?? '') == $docNum) {
            $orderData = $row;
            break;
        }
    }
    
    if (!$orderData) {
        echo "Sipariş bulunamadı!";
        exit;
    }
}


$docEntry = $docNum;

error_log("DocEntry (DocNum): " . $docEntry);

// Handle POST request for delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deliver') {
    header('Content-Type: application/json');
    
    try {
        error_log("=== DELIVERY REQUEST START ===");
        error_log("DocEntry kullanılacak: " . $docEntry);
        
        // 2. Önce ASUDO_B2B_OPDN kaydını oluştur (teslim alma kaydı)
        $guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $userId = $_SESSION["BranchCode"] ?? $_SESSION["WhsCode"] ?? 
                 (preg_match('/(\d+)/', $_SESSION["UserName"] ?? '', $matches) ? $matches[1] : $_SESSION["UserName"] ?? "1000");
        
        $sessionId = $_SESSION["sapSession"] ?? "1221";
        
        $sapPayload = [
            "U_Type" => "SUPPLY",
            "U_DocNum" => $docNum,
            "U_WhsCode" => $whsCode,
            "U_GUID" => $guid,
            "U_User" => $userId,
            "U_SessionID" => $sessionId
        ];
        
        error_log("OPDN Payload: " . json_encode($sapPayload));
        $opdnResponse = $sap->post("ASUDO_B2B_OPDN", $sapPayload);
        error_log("OPDN Response: " . json_encode($opdnResponse));
        
        // 3. OPOR status'unu güncelle
        $today = date('Ymd');
        $patchPayload = [
            "U_ASB2B_STATUS" => "4",  // Tamamlandı
            "DocDueDate" => $today    // Teslim tarihini güncelle
        ];
        
        error_log("PATCH Payload: " . json_encode($patchPayload));
        $patchResponse = $sap->patch("PurchaseOrders({$docEntry})", $patchPayload);
        error_log("PATCH Response: " . json_encode($patchResponse));
        
        // OPDN başarılı olursa işlem tamamlandı kabul et
        // PATCH başarısız olsa bile (zaten SAP backend status'u güncelleyebilir)
        if ($opdnResponse['status'] == 200 || $opdnResponse['status'] == 201) {
            
            $patchSuccess = ($patchResponse['status'] == 200 || $patchResponse['status'] == 204);
            
            if ($patchSuccess) {
                error_log("✅ Her iki işlem de başarılı!");
                $message = 'Sipariş başarıyla teslim alındı ve Tamamlandı durumuna güncellendi!';
            } else {
                error_log("⚠️ OPDN başarılı ama PATCH başarısız. SAP backend status'u güncellemeli.");
                $message = 'Sipariş başarıyla teslim alındı! (Status güncellemesi SAP backend tarafından yapılacak)';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'newStatus' => '4',
                'deliveryDate' => date('d.m.Y')
            ]);
        } else {
            // Detailed error extraction
            $errorMsg = 'İşlem başarısız: ';
            
            if (($opdnResponse['status'] ?? 0) < 200 || ($opdnResponse['status'] ?? 0) >= 300) {
                $errorMsg .= 'OPDN kaydı oluşturulamadı. ';
                if (isset($opdnResponse['response']['error']['message']['value'])) {
                    $errorMsg .= $opdnResponse['response']['error']['message']['value'];
                }
            }
            
            if (($patchResponse['status'] ?? 0) < 200 || ($patchResponse['status'] ?? 0) >= 300) {
                $errorMsg .= 'Status güncellemesi yapılamadı. ';
                if (isset($patchResponse['response']['error']['message']['value'])) {
                    $errorMsg .= $patchResponse['response']['error']['message']['value'];
                }
            }
            
            error_log("ERROR: " . $errorMsg);
            echo json_encode([
                'success' => false, 
                'message' => $errorMsg,
                'details' => ['opdn' => $opdnResponse, 'patch' => $patchResponse]
            ]);
        }
        error_log("=== DELIVERY REQUEST END ===");
    } catch (Exception $e) {
        error_log("Exception in delivery: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dış Tedarik Siparişi Detay - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .detail-title h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approval {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-preparing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
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
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .info-card h3 {
            margin: 0 0 1rem 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .items-table th,
        .items-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="logo"><h1>CREMMA<span>VERSE</span></h1></div>
        <?php include 'navbar.php'; ?>
        <div class="user-info">
            <div class="user-avatar"><?= htmlspecialchars($whsCode) ?></div>
            <div class="user-details">
                <div class="user-name">Depo <?= htmlspecialchars($whsCode) ?></div> 
                <div class="version">v1.0.43</div> 
            </div> 
        </div>
    </aside>

    <main class="main-content">
        <div class="detail-header">
            <div class="detail-title">
                <h2>Dış Tedarik Siparişi: <?= htmlspecialchars($docNum) ?></h2>
                <?php
                $statusText = 'Bilinmiyor';
                $statusClass = 'status-unknown';
                
                // Status mapping: tüm kullanılan status'lar
                switch ($orderData['DocStatus'] ?? '') {
                    case '1': $statusText = 'Onay Bekliyor'; $statusClass = 'status-approval'; break;
                    case '2': $statusText = 'Hazırlanıyor'; $statusClass = 'status-preparing'; break;
                    case '3': $statusText = 'Sevk Edildi'; $statusClass = 'status-shipped'; break;
                    case '4': $statusText = 'Tamamlandı'; $statusClass = 'status-completed'; break;
                    case '5': $statusText = 'İptal Edildi'; $statusClass = 'status-cancelled'; break;
                    default: $statusText = 'İşlemde'; $statusClass = 'status-processing';
                }
                ?>
                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
            <a href="Dis-Tedarik.php" class="btn btn-secondary">← Geri Dön</a>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Sipariş Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">Sipariş No:</span>
                    <span class="info-value"><?= htmlspecialchars($orderData['DocNum'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tedarikçi:</span>
                    <span class="info-value"><?= htmlspecialchars($orderData['CardName'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sipariş Tarihi:</span>
                    <span class="info-value">
                        <?php
                        if (!empty($orderData['DocDate'])) {
                            $date = $orderData['DocDate'];
                            echo date('d.m.Y', strtotime(substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tahmini Teslimat:</span>
                    <span class="info-value">
                        <?php
                        if (!empty($orderData['DocDueDate']) && $orderData['DocDueDate'] !== '19000101') {
                            $date = $orderData['DocDueDate'];
                            echo date('d.m.Y', strtotime(substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)));
                        } else {
                            echo 'Beklemede';
                        }
                        ?>
                    </span>
                </div>
            </div>

            <div class="info-card">
                <h3>Belge Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">Teslimat Belge No:</span>
                    <span class="info-value"><?= htmlspecialchars($orderData['NumAtCard'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Durum:</span>
                    <span class="info-value">
                        <?= $statusText ?>
                        <?php if (($orderData['DocStatus'] ?? '') === '1'): ?>
                            <br><small style="color:#856404;">⚠️ Bu sipariş henüz SAP tarafından onaylanmamış</small>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sipariş Notu:</span>
                    <span class="info-value"><?= htmlspecialchars($orderData['Comments'] ?? 'Yok') ?></span>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h3>Sipariş Kalemleri</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Kalem Kodu</th>
                        <th>Kalem Tanımı</th>
                        <th>Sipariş Miktarı</th>
                        <th>Teslimat Miktarı</th>
                        <th>Ölçü Birimi</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items = $orderData['Items'] ?? [];
                    if (empty($items)) {
                        echo '<tr><td colspan="6" style="text-align:center;color:#888;">Kalem bulunamadı.</td></tr>';
                    } else {
                        foreach ($items as $item) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($item['ItemCode'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($item['ItemName'] ?? '') . '</td>';
                            echo '<td>' . number_format($item['Quantity'] ?? 0, 2) . '</td>';
                            $deliveredQty = $item['DeliveredQty'] ?? 0;
                            echo '<td>' . ($deliveredQty > 0 ? number_format($deliveredQty, 2) : '-') . '</td>';
                            echo '<td>' . htmlspecialchars($item['UoM'] ?? '') . '</td>';
                            $itemStatus = $item['Status'] ?? '';
                            echo '<td>' . ($itemStatus ? htmlspecialchars($itemStatus) : '-') . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <?php 
            // Sadece Status 3 (Sevk Edildi) ve gerçek OPOR olan siparişler için Teslim Al butonu
            // Status 1 (Onay Bekliyor) kayıtları henüz gerçek OPOR'a dönüşmemiş, teslim alınamaz
            if (($orderData['DocStatus'] ?? '') === '3'): 
                if ($isRealOPOR):
            ?>
                <button class="btn btn-success" onclick="deliverOrder()">✓ Teslim Al</button>
            <?php 
                else: 
            ?>
                <button class="btn btn-primary" disabled>Sipariş henüz onaylanmamış, teslim alınamaz</button>
            <?php 
                endif;
            endif; 
            ?>
            <a href="Dis-Tedarik.php" class="btn btn-secondary">← Geri Dön</a>
        </div>
    </main>
</div>

<script>
function deliverOrder() {
    if (!confirm('Bu siparişi teslim almak istediğinizden emin misiniz?\n\nSipariş "Tamamlandı" durumuna geçecek ve teslimat tarihi güncellenecektir.')) {
        return;
    }
    
    // Buton'u disable et ve loading göster
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ İşleniyor...';
    
    const formData = new FormData();
    formData.append('action', 'deliver');
    
    fetch('', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        console.log('Response Data:', data);
        if (data.success) {
                // Başarılı mesaj göster ve bilgilendir
            alert('✅ ' + data.message + '\n\nNot: Sipariş durumu SAP tarafından güncellenecektir. Sayfa yenileniyor...');
            
            // 2 saniye bekle (SAP'in işlemini tamamlaması için)
            setTimeout(() => {
                window.location.href = 'Dis-Tedarik.php?t=' + Date.now();
            }, 2000);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            // Detaylı hata mesajı göster
            let errorMsg = data.message;
            if (data.details) {
                console.error('Error Details:', data.details);
                errorMsg += '\n\nDetaylar: ' + JSON.stringify(data.details, null, 2);
            }
            alert('❌ ' + errorMsg);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Teslim alma işlemi sırasında hata oluştu!');
    });
}
</script>

</body>
</html>