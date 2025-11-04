<?php //deneme git
session_start();
if (!isset($_SESSION["UserName"]) || !isset($_SESSION["sapSession"])) {
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

// Herkes normal kullanıcı gibi çalışacak (anadepo kullanıcı mantığı kaldırıldı)
$isAnadepoUser = false;

// FromWarehouse sorgusu (ana depo)
$fromWarehouseFilter = "U_ASB2B_FATH eq 'Y' and U_AS_OWNR eq '{$uAsOwnr}'";
$fromWarehouseQuery = "Warehouses?\$select=WarehouseCode&\$filter=" . urlencode($fromWarehouseFilter);
$fromWarehouseData = $sap->get($fromWarehouseQuery);
$fromWarehouses = $fromWarehouseData['response']['value'] ?? [];
$fromWarehouse = !empty($fromWarehouses) ? $fromWarehouses[0]['WarehouseCode'] : null;
$fromWarehouseNotFound = ($fromWarehouseData['status'] ?? 0) == 200 && empty($fromWarehouses);

// ToWarehouse sorgusu (kullanıcının şube sevkiyat deposu)
$toWarehouseFilter = "U_AS_OWNR eq '{$uAsOwnr}' and U_ASB2B_BRAN eq '{$branch}' and U_ASB2B_MAIN eq '2'";
$toWarehouseQuery = "Warehouses?\$select=WarehouseCode&\$filter=" . urlencode($toWarehouseFilter);
$toWarehouseData = $sap->get($toWarehouseQuery);
$toWarehouses = $toWarehouseData['response']['value'] ?? [];
$toWarehouse = !empty($toWarehouses) ? $toWarehouses[0]['WarehouseCode'] : null;
$toWarehouseNotFound = ($toWarehouseData['status'] ?? 0) == 200 && empty($toWarehouses);

// Debug: Session ve sorgu bilgileri
if (isset($_GET['debug'])) {
    error_log("[ANADEPO DEBUG] U_AS_OWNR: " . $uAsOwnr);
    error_log("[ANADEPO DEBUG] Branch (WhsCode): " . ($branch ?: 'EMPTY'));
    error_log("[ANADEPO DEBUG] FromWarehouse: " . ($fromWarehouse ?: 'NOT FOUND'));
    error_log("[ANADEPO DEBUG] ToWarehouse: " . ($toWarehouse ?: 'NOT FOUND'));
}

// Hata kontrolü ve bilgilendirme
$errorMsg = '';
$infoMsg = '';

if (empty($fromWarehouse)) {
    if ($fromWarehouseNotFound) {
        // SAP'de yok - sessizce geç, sadece bilgi mesajı göster
        $infoMsg = "<strong>Bilgi:</strong> {$uAsOwnr} sektörü için ana depo (FromWarehouse) SAP'de tanımlı değil. Bu sektör için Ana Depo Tedarik listesi boş görünecektir.";
    } else {
        // Bağlantı hatası veya başka bir sorun
        $errorMsg = "FromWarehouse sorgusu başarısız!";
        if (isset($fromWarehouseData['status']) && $fromWarehouseData['status'] != 200) {
            $errorMsg .= " (HTTP " . $fromWarehouseData['status'] . ")";
        }
    }
}

if (empty($toWarehouse)) {
    if ($toWarehouseNotFound) {
        // SAP'de yok - bilgi mesajına ekle
        if (empty($infoMsg)) {
            $infoMsg = "<strong>Bilgi:</strong> {$uAsOwnr} sektörü ve {$branch} şubesi için sevkiyat deposu (ToWarehouse) SAP'de tanımlı değil.";
        } else {
            $infoMsg .= "<br><strong>Bilgi:</strong> {$uAsOwnr} sektörü ve {$branch} şubesi için sevkiyat deposu (ToWarehouse) SAP'de tanımlı değil.";
        }
    } else {
        // Bağlantı hatası veya başka bir sorun
        $errorMsg .= ($errorMsg ? '<br>' : '') . "ToWarehouse sorgusu başarısız!";
        if (isset($toWarehouseData['status']) && $toWarehouseData['status'] != 200) {
            $errorMsg .= " (HTTP " . $toWarehouseData['status'] . ")";
        }
        if (empty($branch)) {
            $errorMsg .= " (Branch/WhsCode session'da yok!)";
        }
    }
}

// Filtreler (GET parametrelerinden)
$filterStatus = $_GET['status'] ?? '';
$filterStartDate = $_GET['start_date'] ?? '';
$filterEndDate = $_GET['end_date'] ?? '';

// InventoryTransferRequests sorgusu (herkes için aynı: FromWarehouse + ToWarehouse)
$data = ['response' => ['value' => []]];
// Query çalıştırma koşulu: Hata yoksa VE FromWarehouse VE ToWarehouse varsa
if (!$errorMsg && $fromWarehouse && $toWarehouse) {
    // Herkes için: FromWarehouse ve ToWarehouse ile filter
    $transferFilter = "U_AS_OWNR eq '{$uAsOwnr}' and FromWarehouse eq '{$fromWarehouse}' and ToWarehouse eq '{$toWarehouse}'";
    
    // Status filtresi
    if (!empty($filterStatus)) {
        $transferFilter .= " and U_ASB2B_STATUS eq '{$filterStatus}'";
    }
    
    // Tarih filtreleri
    if (!empty($filterStartDate)) {
        $startDateFormatted = date('Y-m-d', strtotime($filterStartDate));
        $transferFilter .= " and DocDate ge '{$startDateFormatted}'";
    }
    if (!empty($filterEndDate)) {
        $endDateFormatted = date('Y-m-d', strtotime($filterEndDate));
        $transferFilter .= " and DocDate le '{$endDateFormatted}'";
    }
    
    $filterEncoded = urlencode($transferFilter);
    $orderByEncoded = urlencode("DocEntry desc");
    $transferQuery = "InventoryTransferRequests?\$filter=" . $filterEncoded . "&\$orderby=" . $orderByEncoded . "&\$top=100";
    
    // Debug
    error_log("[ANADEPO] U_AS_OWNR: " . $uAsOwnr);
    error_log("[ANADEPO] Branch: " . $branch);
    error_log("[ANADEPO] FromWarehouse: " . $fromWarehouse);
    error_log("[ANADEPO] ToWarehouse: " . $toWarehouse);
    error_log("[ANADEPO] Transfer Query: " . $transferQuery);
    
    $data = $sap->get($transferQuery);
    
    error_log("[ANADEPO] Response Status: " . ($data['status'] ?? 'NO STATUS'));
    error_log("[ANADEPO] Response Count: " . (count($data['response']['value'] ?? [])));
    
    // Response Status 0 ise, cURL hatası veya timeout olabilir
    if (($data['status'] ?? 0) == 0) {
        error_log("[ANADEPO] ERROR: Response status is 0 - Possible cURL error or timeout");
        if (isset($data['response']['raw'])) {
            error_log("[ANADEPO] Raw Response: " . substr($data['response']['raw'], 0, 500));
        }
    }
    
    if (!empty($data['response']['value'])) {
        error_log("[ANADEPO] First Record: " . json_encode($data['response']['value'][0] ?? []));
    }
} else {
    error_log("[ANADEPO] Query not executed - errorMsg: " . ($errorMsg ?: 'none') . ", fromWarehouse: " . ($fromWarehouse ?: 'empty') . ", toWarehouse: " . ($toWarehouse ?: 'empty'));
}

$allRows = $data['response']['value'] ?? [];

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

// Tarih formatlama
function formatDate($date) {
    if (empty($date)) return '-';
    if (strpos($date, 'T') !== false) {
        return date('d.m.Y', strtotime(substr($date, 0, 10)));
    }
    return date('d.m.Y', strtotime($date));
}
?> 


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Depo Siparişleri - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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

.filter-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.9rem;
}

.single-select-container {
    position: relative;
    width: 100%;
}

.single-select-input {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    cursor: pointer;
    min-height: 38px;
}

.single-select-input:hover {
    border-color: #007bff;
}

.single-select-input.active {
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.single-select-input input {
    border: none;
    outline: none;
    flex: 1;
    background: transparent;
    cursor: pointer;
}

.dropdown-arrow {
    transition: transform 0.2s;
}

.single-select-input.active .dropdown-arrow {
    transform: rotate(180deg);
}

.single-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.single-select-dropdown.show {
    display: block;
} 

.single-select-option {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.single-select-option:hover {
    background: #f8f9fa;
}

.single-select-option.selected {
    background: #007bff;
    color: white;
}

.date-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.date-range input {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    flex: 1;
}

.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.show-entries {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.entries-select {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
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
                <button class="btn btn-primary" onclick="window.location.href='AnaDepoSO.php'">+ Yeni Sipariş Oluştur</button>
            </header>

            <div class="content-wrapper">
                <?php if ($errorMsg): ?>
                    <div class="alert alert-warning" style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 20px; color: #856404;">
                        <strong>Uyarı:</strong> <?= $errorMsg ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($infoMsg): ?>
                    <div class="alert alert-info" style="padding: 15px; background: #d1ecf1; border: 1px solid #17a2b8; border-radius: 4px; margin-bottom: 20px; color: #0c5460;">
                        <?= $infoMsg ?>
                    </div>
                <?php endif; ?>
                
                <!-- Debug Info -->
                <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info" style="padding: 15px; background: #d1ecf1; border: 1px solid #17a2b8; border-radius: 4px; margin-bottom: 20px; color: #0c5460; font-family: monospace; font-size: 12px;">
                        <strong>Debug Bilgileri:</strong><br>
                        U_AS_OWNR: <?= htmlspecialchars($uAsOwnr) ?><br>
                        Branch (WhsCode): <?= htmlspecialchars($branch ?: 'BOŞ') ?><br>
                        FromWarehouse: <?= htmlspecialchars($fromWarehouse ?? 'BULUNAMADI') ?><br>
                        ToWarehouse: <?= htmlspecialchars($toWarehouse ?? 'BULUNAMADI') ?><br>
                        ErrorMsg: <?= htmlspecialchars($errorMsg ?: 'Yok') ?><br>
                        <br>
                        <strong>FromWarehouse Query:</strong><br>
                        <?= htmlspecialchars($fromWarehouseQuery ?? 'Query oluşturulmadı') ?><br>
                        FromWarehouse Status: <?= isset($fromWarehouseData['status']) ? $fromWarehouseData['status'] : 'YOK' ?><br>
                        <br>
                        <strong>ToWarehouse Query:</strong><br>
                        <?= htmlspecialchars($toWarehouseQuery ?? 'Query oluşturulmadı') ?><br>
                        ToWarehouse Status: <?= isset($toWarehouseData['status']) ? $toWarehouseData['status'] : 'YOK' ?><br>
                        <br>
                        Query Çalıştırıldı: <?= (!$errorMsg && $fromWarehouse) ? 'EVET' : 'HAYIR' ?><br>
                        Response Status: <?= isset($data['status']) ? $data['status'] : 'YOK' ?><br>
                        <?php if (isset($data['response']['error'])): ?>
                            Response Error: <?= htmlspecialchars($data['response']['error']) ?><br>
                        <?php endif; ?>
                        Kayıt Sayısı: <?= count($rows) ?><br>
                        <br>
                        <strong>Full Query:</strong><br>
                        <?= htmlspecialchars($transferQuery ?? 'Query oluşturulmadı') ?><br>
                        <br>
                        <strong>Full URL (baseUrl + endpoint):</strong><br>
                        <?= htmlspecialchars('https://192.168.54.185:50000/b1s/v2/' . ($transferQuery ?? '')) ?><br>
                        <br>
                        <strong>Test için Insomnia'da kullanın:</strong><br>
                        <code style="font-size:11px; word-break:break-all;"><?= htmlspecialchars('https://192.168.54.185:50000/b1s/v2/InventoryTransferRequests?$filter=U_AS_OWNR eq \'KT\' and FromWarehouse eq \'KT-00\' and ToWarehouse eq \'100-KT-1\'&$orderby=DocEntry desc&$top=25') ?></code>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
                    <div id="successMsg" class="alert alert-success" style="padding: 15px; background: #d4edda; border: 1px solid #28a745; border-radius: 4px; margin-bottom: 20px; color: #155724; position: relative;">
                        <strong>Başarılı:</strong> Teslim alma işlemi tamamlandı.
                        <button onclick="document.getElementById('successMsg').style.display='none';" style="position: absolute; right: 10px; top: 10px; background: none; border: none; color: #155724; font-size: 18px; cursor: pointer; font-weight: bold;">×</button>
                    </div>
                    <script>
                        // 5 saniye sonra otomatik kapat
                        setTimeout(function() {
                            const msg = document.getElementById('successMsg');
                            if (msg) {
                                msg.style.transition = 'opacity 0.5s';
                                msg.style.opacity = '0';
                                setTimeout(function() {
                                    msg.style.display = 'none';
                                }, 500);
                            }
                        }, 5000);
                    </script>
                <?php endif; ?>

                <!-- Filtreler -->
                <section class="card">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Sipariş Durumu</label>
                            <div class="single-select-container">
                                <div class="single-select-input" onclick="toggleDropdown('status')">
                                    <input type="text" id="filterStatus" value="<?= $filterStatus ? getStatusText($filterStatus) : 'Tümü' ?>" placeholder="Seçiniz..." readonly>
                                    <span class="dropdown-arrow">▼</span>
                                </div>
                                <div class="single-select-dropdown" id="statusDropdown">
                                    <div class="single-select-option <?= empty($filterStatus) ? 'selected' : '' ?>" data-value="" onclick="selectStatus('')">Tümü</div>
                                    <div class="single-select-option <?= $filterStatus === '1' ? 'selected' : '' ?>" data-value="1" onclick="selectStatus('1')">Onay Bekliyor</div>
                                    <div class="single-select-option <?= $filterStatus === '2' ? 'selected' : '' ?>" data-value="2" onclick="selectStatus('2')">Hazırlanıyor</div>
                                    <div class="single-select-option <?= $filterStatus === '3' ? 'selected' : '' ?>" data-value="3" onclick="selectStatus('3')">Sevk Edildi</div>
                                    <div class="single-select-option <?= $filterStatus === '4' ? 'selected' : '' ?>" data-value="4" onclick="selectStatus('4')">Tamamlandı</div>
                                    <div class="single-select-option <?= $filterStatus === '5' ? 'selected' : '' ?>" data-value="5" onclick="selectStatus('5')">İptal Edildi</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" id="start-date" value="<?= htmlspecialchars($filterStartDate) ?>" onblur="applyFilters()">
                        </div>
                        
                        <div class="filter-group">
                            <label>Bitiş Tarihi</label>
                            <input type="date" id="end-date" value="<?= htmlspecialchars($filterEndDate) ?>" onblur="applyFilters()">
                        </div>
                    </div>
                </section>

                <!-- Tablo Kontrolleri ve Tablo -->
                <section class="card">
                    <div class="table-controls">
                        <div class="show-entries">
                            Sayfada 
                            <select class="entries-select" id="entriesPerPage" onchange="applyFilters()">
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="75">75</option>
                            </select>
                            kayıt göster
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Transfer No</th>
                                <th>Talep Tarihi</th>
                                <th>Vade Tarihi</th>
                                <th>Teslimat Belge No</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                         <?php
// Sayfalama
$entriesPerPage = intval($_GET['entries'] ?? 25);
$currentPage = intval($_GET['page'] ?? 1);
$totalRows = count($allRows);
$totalPages = ceil($totalRows / $entriesPerPage);
$startIndex = ($currentPage - 1) * $entriesPerPage;
$rows = array_slice($allRows, $startIndex, $entriesPerPage);

if (!empty($rows)) {
    foreach ($rows as $row) {
        $status = $row['U_ASB2B_STATUS'] ?? '1';
        $statusText = getStatusText($status);
        $statusClass = getStatusClass($status);
        $docEntry = $row['DocEntry'] ?? '';
        $docDate = formatDate($row['DocDate'] ?? '');
        $dueDate = formatDate($row['DueDate'] ?? '');
        $numAtCard = $row['U_ASB2B_NumAtCard'] ?? '-';

        echo "<tr>
                <td>{$docEntry}</td>
                <td>{$docDate}</td>
                <td>{$dueDate}</td>
                <td>{$numAtCard}</td>
                <td><span class='status-badge {$statusClass}'>{$statusText}</span></td>
                <td>
                    <a href='AnaDepo-Detay.php?doc={$docEntry}'>
                        <button class='btn-icon btn-view'>👁️ Detay</button>
                    </a>";
        
        // Teslim Al butonu (sadece status=3 için, herkes için)
        if ($status === '3') {
            echo "    <a href='anadepo_teslim_al.php?doc={$docEntry}' style='margin-left:5px;'>
                        <button class='btn-icon btn-primary'>✓ Teslim Al</button>
                    </a>";
        }
        
        echo "    </td>
              </tr>";
    }
} else {
    $colspan = 6;
    
    // Boş liste mesajı
    if ($errorMsg) {
        // Sistem hatası varsa
        $emptyMsg = 'Depo bilgileri eksik olduğu için kayıt bulunamadı.';
    } else if ($infoMsg) {
        // SAP'de depo tanımlı değilse
        $emptyMsg = 'Bu sektör/şube için kayıt bulunamadı. SAP\'de uygun depo tanımlı değil olabilir.';
    } else if (!isset($data['status'])) {
        // Query çalıştırılmadıysa
        $emptyMsg = 'Sorgu çalıştırılamadı. Depo bilgileri kontrol edilmelidir.';
    } else {
        // Query çalıştı ama boş döndü
        $emptyMsg = 'Kayıt bulunamadı.';
        if (isset($data['status'])) {
            $emptyMsg .= ' (HTTP Status: ' . $data['status'] . ')';
        }
    }
    
    echo "<tr><td colspan='{$colspan}' style='text-align:center;color:#888;padding:20px;'>" . $emptyMsg . "</td></tr>"; 
}
?>
                        </tbody>
                    </table>
                    
                    <!-- Sayfalama -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; align-items: center;">
                            <button class="btn btn-secondary" onclick="changePage(<?= $currentPage - 1 ?>)" <?= $currentPage <= 1 ? 'disabled' : '' ?>>← Önceki</button>
                            <span>Sayfa <?= $currentPage ?> / <?= $totalPages ?> (Toplam <?= $totalRows ?> kayıt)</span>
                            <button class="btn btn-secondary" onclick="changePage(<?= $currentPage + 1 ?>)" <?= $currentPage >= $totalPages ? 'disabled' : '' ?>>Sonraki →</button>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
let selectedStatus = '<?= htmlspecialchars($filterStatus) ?>';

function toggleDropdown(type) {
    const dropdown = document.getElementById(type + 'Dropdown');
    const input = document.querySelector(`#filter${type.charAt(0).toUpperCase() + type.slice(1)}`).parentElement;
    const isOpen = dropdown.classList.contains('show');
    
    // Close all dropdowns
    document.querySelectorAll('.single-select-dropdown').forEach(d => d.classList.remove('show'));
    document.querySelectorAll('.single-select-input').forEach(d => d.classList.remove('active'));
    
    if (!isOpen) {
        dropdown.classList.add('show');
        input.classList.add('active');
    }
}

function selectStatus(value) {
    selectedStatus = value;
    const statusText = document.querySelector(`#statusDropdown .single-select-option[data-value="${value}"]`).textContent;
    document.getElementById('filterStatus').value = statusText;
    document.querySelectorAll('#statusDropdown .single-select-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`#statusDropdown .single-select-option[data-value="${value}"]`).classList.add('selected');
    applyFilters();
}

function applyFilters() {
    // Tarih input'larından önce değerleri al (input focus'ta olabilir)
    const status = selectedStatus;
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const startDate = startDateInput ? startDateInput.value : '';
    const endDate = endDateInput ? endDateInput.value : '';
    const entries = document.getElementById('entriesPerPage').value;
    
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (entries) params.append('entries', entries);
    
    window.location.href = 'AnaDepo.php' + (params.toString() ? '?' + params.toString() : '');
}

function changePage(page) {
    if (page < 1) return;
    
    const status = '<?= htmlspecialchars($filterStatus) ?>';
    const startDate = '<?= htmlspecialchars($filterStartDate) ?>';
    const endDate = '<?= htmlspecialchars($filterEndDate) ?>';
    const entries = document.getElementById('entriesPerPage').value;
    
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (entries) params.append('entries', entries);
    params.append('page', page);
    
    window.location.href = 'AnaDepo.php?' + params.toString();
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.single-select-container')) {
        document.querySelectorAll('.single-select-dropdown').forEach(d => d.classList.remove('show'));
        document.querySelectorAll('.single-select-input').forEach(d => d.classList.remove('active'));
    }
});
    </script>
    <script src="script.js"></script>
</body>
</html>