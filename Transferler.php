<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}

include 'sap_connect.php';
$sap = new SAPConnect();

// Kullanıcının mağaza kodu
$whsCode = $_SESSION["WhsCode"] ?? '1000';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// Filtre değişkenleri
$itemName  = trim($_GET['item'] ?? '');
$status    = trim($_GET['status'] ?? '');
$fromWhs   = trim($_GET['from'] ?? '');
$toWhs     = trim($_GET['to'] ?? '');
$startDate = trim($_GET['start'] ?? '');
$endDate   = trim($_GET['end'] ?? '');
$pageSize  = (int)($_GET['pageSize'] ?? 25);

// 🔹 SAP’ten sadece temel veri alınır
$query = "SQLQueries('OWTQ_T_LIST')/List?value1='TRANSFER'&value2='{$whsCode}'";
$data = $sap->get($query);
$rows = $data['response']['value'] ?? [];

// 🔧 SAP tarihlerini normalize eden fonksiyon
function normalizeSapDate($date)
{
    if (!$date) return null;

    $date = trim($date);

    // Format: 20250106 (Ymd)
    if (preg_match('/^\d{8}$/', $date)) {
        $y = substr($date, 0, 4);
        $m = substr($date, 4, 2);
        $d = substr($date, 6, 2);
        return "{$y}-{$m}-{$d}";
    }

    // Format: 2025-01-06T00:00:00
    if (strpos($date, 'T') !== false) {
        return substr($date, 0, 10);
    }

    // Format: 2025.01.06
    if (preg_match('/^\d{4}\.\d{2}\.\d{2}$/', $date)) {
        return str_replace('.', '-', $date);
    }

    return $date;
}

foreach ($rows as &$r) {
    $r['DocDate'] = normalizeSapDate($r['DocDate'] ?? '');
    $r['DeliveryDate'] = normalizeSapDate($r['DeliveryDate'] ?? '');
}
unset($r);



// 🔹 PHP tarafında filtreleme (SAP'e dokunmadan)
$filteredRows = array_filter($rows, function ($r) use ($itemName, $status, $fromWhs, $toWhs, $startDate, $endDate) {
    $match = true;

    if ($itemName !== '') {
        $match = $match && stripos($r['ItemName'] ?? '', $itemName) !== false;
    }

    if ($status !== '') {
        // DocStatus numarasını metinle eşleştir
        $docStatusNum = (int)($r['DocStatus'] ?? 0);
        $statusMap = [
            1 => 'Onay Bekliyor',
            2 => 'Hazırlanıyor',
            3 => 'Sevk Edildi',
            4 => 'Tamamlandı',
            5 => 'İptal Edildi'
        ];
        $docStatusText = $statusMap[$docStatusNum] ?? '';

        $match = $match && stripos($docStatusText, $status) !== false;
    }

    if ($fromWhs !== '') {
        $match = $match && stripos($r['FromWhsName'] ?? '', $fromWhs) !== false;
    }

    if ($toWhs !== '') {
        $match = $match && stripos($r['WhsName'] ?? '', $toWhs) !== false;
    }

    if ($startDate !== '' && !empty($r['DocDate'])) {
        $docDate = strtotime($r['DocDate']);
        $match = $match && $docDate >= strtotime($startDate);
    }

    if ($endDate !== '' && !empty($r['DocDate'])) {
        $docDate = strtotime($r['DocDate']);
        $match = $match && $docDate <= strtotime($endDate);
    }

    return $match;
});

$rows = array_values($filteredRows);

// 🔹 AJAX istekleri için JSON dönüş
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $rows,
        'count' => count($rows)
    ]);
    exit;
}

// Dropdown verileri (Items ve Warehouses)
$itemsData = $sap->get("Items?\$select=ItemCode,ItemName&\$top=200");
$items = $itemsData['response']['value'] ?? [];

$warehousesData = $sap->get("Warehouses?\$select=WarehouseCode,WarehouseName");
$warehouses = $warehousesData['response']['value'] ?? [];

/* 🔹 Debug bilgisi
$debugMode = true;
if ($debugMode) {
    echo "<pre style='background:#f5f5f5;padding:10px;margin:10px;border:1px solid #ccc;'>";
    echo "DEBUG >> WhsCode: {$whsCode}\n";
    echo "DEBUG >> SAP Status: " . ($data['status'] ?? 'N/A') . "\n";
    echo "DEBUG >> Satır Sayısı: " . count($rows) . "\n";
    echo "DEBUG >> Filtreler:\n";
    echo "  - Kalem: {$itemName}\n  - Durum: {$status}\n  - Gönderen: {$fromWhs}\n  - Alıcı: {$toWhs}\n  - Tarih Aralığı: {$startDate} - {$endDate}\n";
    if (!empty($rows)) {
        echo "\nİlk 2 kayıt:\n";
        print_r(array_slice($rows, 0, 2));
    }
    echo "</pre>";
}
    */ 
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Transferler - CREMMAVERSE</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
            flex: 1;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .filter-input,
        .filter-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }

        .btn-reset,
        .btn-filter {
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-filter {
            background: #ff5722;
            color: #fff;
            border: none;
        }

        .btn-filter:hover {
            background: #e64a19;
        }

        .btn-reset {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-reset:hover {
            background: #e0e0e0;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            border: 3px solid #eee;
            border-top: 3px solid #ff5722;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .table-wrapper {
            position: relative;
        } 

        #searchInput {
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    width: 100%;
    max-width: 350px;
    transition: all 0.2s ease;
}
#searchInput:focus {
    border-color: #ff5722;
    box-shadow: 0 0 0 2px rgba(255,87,34,0.2);
    outline: none;
}

    </style>
</head>



<body> 


    <div class="app-container">
        <?php include 'navbar.php'; ?>
        <div class="main-content">
            <div class="page-header">
                <h2>Transferler</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='TransferlerSO.php'">+ Yeni Transfer Oluştur</button>
                    <button class="btn-exit" onclick="window.location.href='config/logout.php'">Çıkış Yap ↗</button>
                </div>
            </div>

            <div class="card">
                <div id="filterForm">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Kalem Tanımı</label>
                            <select name="item" class="filter-select auto-filter">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= htmlspecialchars($item['ItemName']) ?>"><?= htmlspecialchars($item['ItemName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        <div class="filter-group">
                            <label>Transfer Durumu</label>
                            <select name="status" class="filter-select auto-filter">
                                <option value="">Tümü</option>
                                <option value="Onay Bekliyor">Onay Bekliyor</option>
                                <option value="Tamamlandı">Tamamlandı</option>
                                <option value="Sevk Edildi">Sevk Edildi</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Gönderen Şube</label>
                            <select name="from" class="filter-select auto-filter">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($warehouses as $whs): ?>
                                    <option value="<?= htmlspecialchars($whs['WarehouseName']) ?>"><?= htmlspecialchars($whs['WarehouseName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Alıcı Şube</label>
                            <select name="to" class="filter-select auto-filter">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($warehouses as $whs): ?>
                                    <option value="<?= htmlspecialchars($whs['WarehouseName']) ?>"><?= htmlspecialchars($whs['WarehouseName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" name="start" class="filter-input auto-filter">
                        </div>
                        <div class="filter-group">
                            <label>Bitiş Tarihi</label>
                            <input type="date" name="end" class="filter-input auto-filter">
                        </div>
                        <div class="filter-actions">
                            <div class="filter-actions">

                                
                                <input type="text" id="searchInput" class="filter-input" placeholder="Ara... (örnek: transfer no, kalem, şube)" >
                                <button type="button" class="btn-reset" id="resetBtn">Sıfırla</button>
                                <button type="button" class="btn-reset" onclick="location.reload()">🔄 Yenile</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <div class="loading-overlay" id="loadingOverlay">
                            <div class="spinner"></div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Transfer No</th>
                                    <th>Kalem Kodu</th>
                                    <th>Kalem Tanımı</th>
                                    <th>Tarihler</th>
                                    <th>Miktar</th>
                                    <th>Gönderen Şube</th>
                                    <th>Alıcı Şube</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if (!empty($rows)): foreach ($rows as $r):
                                        $statusMap = [1 => "Onay Bekliyor", 2 => "Hazırlanıyor", 3 => "Sevk Edildi", 4 => "Tamamlandı", 5 => "İptal Edildi"];
                                        $statusText = $statusMap[(int)($r["DocStatus"] ?? 0)] ?? "Bilinmiyor";
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r["DocNum"] ?? "-", ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($r["ItemCode"] ?? "-", ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($r["ItemName"] ?? "-", ENT_QUOTES) ?></td>
                                            <td>
                                                Talep: <?= !empty($r["DocDate"]) ? date("d.m.Y", strtotime($r["DocDate"])) : "-" ?><br>
                                                Teslim: <?= !empty($r["DeliveryDate"]) ? date("d.m.Y", strtotime($r["DeliveryDate"])) : "-" ?>
                                            </td>

                                            <td><?= htmlspecialchars($r["Quantity"] ?? "0") ?> <?= htmlspecialchars($r["UomCode"] ?? "AD") ?></td>
                                            <td><?= htmlspecialchars($r["FromWhsName"] ?? "-", ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($r["WhsName"] ?? "-", ENT_QUOTES) ?></td>
                                            <td><span class="status-badge"><?= $statusText ?></span></td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;padding:40px;color:#999;">SAP'ten veri alınamadı veya sonuç boş.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span id="recordCount">Toplam <?= count($rows) ?> kayıt gösteriliyor</span>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let filterTimeout;
            const loadingOverlay = document.getElementById('loadingOverlay');
            const tableBody = document.getElementById('tableBody');
            const recordCount = document.getElementById('recordCount');

            function getFilters() {
                const filters = {};
                document.querySelectorAll('.auto-filter').forEach(el => {
                    if (el.value) filters[el.name] = el.value;
                });
                return filters;
            }

            async function fetchData() {
                const params = new URLSearchParams(getFilters());
                params.append('ajax', '1');
                loadingOverlay.classList.add('active');
                try {
                    const res = await fetch(`Transferler.php?${params.toString()}`);
                    const result = await res.json();
                    if (result.success) updateTable(result.data, result.count);
                } catch (e) {
                    tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:red;">Veri alınamadı.</td></tr>';
                } finally {
                    loadingOverlay.classList.remove('active');
                }
            }

            // 🔍 Genel arama kutusu
            const searchInput = document.getElementById('searchInput'); 

            searchInput.addEventListener('input', () => {
                const term = searchInput.value.trim().toLowerCase();
                const rows = tableBody.querySelectorAll('tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = text.includes(term);
                    row.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });

                recordCount.textContent = `Toplam ${visibleCount} kayıt gösteriliyor`;
            });


            function updateTable(data, count) {
                if (!data.length) {
                    tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#999;">Sonuç bulunamadı.</td></tr>';
                    recordCount.textContent = '0 kayıt';
                    return;
                }
                recordCount.textContent = `Toplam ${count} kayıt`;
                tableBody.innerHTML = data.map(r => `
        <tr>
            <td>${r.DocNum || '-'}</td>
            <td>${r.ItemCode || '-'}</td>
            <td>${r.ItemName || '-'}</td>
            <td>
            Talep: ${r.DocDate ? new Date(r.DocDate.replace(/\./g,'-')).toLocaleDateString('tr-TR') : '-'}<br>
            Teslim: ${r.DeliveryDate ? new Date(r.DeliveryDate.replace(/\./g,'-')).toLocaleDateString('tr-TR') : '-'} 
            </td>

            <td>${r.Quantity || 0} ${r.UomCode || 'AD'}</td>
            <td>${r.FromWhsName || '-'}</td>
            <td>${r.WhsName || '-'}</td>
            <td>${r.DocStatus || 'Bilinmiyor'}</td>
        </tr>
    `).join('');
            }

            document.querySelectorAll('.auto-filter').forEach(el => {
                el.addEventListener('change', () => {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(fetchData, 400);
                });
            });

            document.getElementById('resetBtn').addEventListener('click', () => {
                document.querySelectorAll('.auto-filter').forEach(el => el.value = '');
                fetchData();
            });
        </script>
</body>

</html>