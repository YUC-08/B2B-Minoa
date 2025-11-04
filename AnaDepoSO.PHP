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
$branch = $_SESSION["WhsCode"] ?? '';

if (empty($uAsOwnr) || empty($branch)) {
    die("Session bilgileri eksik. Lütfen tekrar giriş yapın.");
}

// FromWarehouse ve ToWarehouse sorguları
$fromWarehouseFilter = "U_ASB2B_FATH eq 'Y' and U_AS_OWNR eq '{$uAsOwnr}'";
$fromWarehouseQuery = "Warehouses?\$select=WarehouseCode&\$filter=" . urlencode($fromWarehouseFilter);
$fromWarehouseData = $sap->get($fromWarehouseQuery);
$fromWarehouses = $fromWarehouseData['response']['value'] ?? [];
$fromWarehouse = !empty($fromWarehouses) ? $fromWarehouses[0]['WarehouseCode'] : null;

$toWarehouseFilter = "U_AS_OWNR eq '{$uAsOwnr}' and U_ASB2B_BRAN eq '{$branch}' and U_ASB2B_MAIN eq '2'";
$toWarehouseQuery = "Warehouses?\$select=WarehouseCode&\$filter=" . urlencode($toWarehouseFilter);
$toWarehouseData = $sap->get($toWarehouseQuery);
$toWarehouses = $toWarehouseData['response']['value'] ?? [];
$toWarehouse = !empty($toWarehouses) ? $toWarehouses[0]['WarehouseCode'] : null;

// POST işlemi: InventoryTransferRequests oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_request') {
    header('Content-Type: application/json');
    
    $selectedItems = json_decode($_POST['items'] ?? '[]', true);
    
    if (empty($selectedItems)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen en az bir kalem seçin!']);
        exit;
    }
    
    if (empty($fromWarehouse) || empty($toWarehouse)) {
        echo json_encode(['success' => false, 'message' => 'Depo bilgileri bulunamadı!']);
        exit;
    }
    
    $stockTransferLines = [];
    foreach ($selectedItems as $item) {
        if (floatval($item['quantity'] ?? 0) > 0) {
            $stockTransferLines[] = [
                'ItemCode' => $item['itemCode'] ?? '',
                'Quantity' => floatval($item['quantity'] ?? 0),
                'FromWarehouseCode' => $fromWarehouse,
                'WarehouseCode' => $toWarehouse
            ];
        }
    }
    
    if (empty($stockTransferLines)) {
        echo json_encode(['success' => false, 'message' => 'Miktarı girilen kalem bulunamadı!']);
        exit;
    }
    
    $payload = [
        'DocDate' => date('Y-m-d'),
        'FromWarehouse' => $fromWarehouse,
        'ToWarehouse' => $toWarehouse,
        'Comments' => 'Stok nakil talebi',
        'StockTransferLines' => $stockTransferLines
    ];
    
    $result = $sap->post('InventoryTransferRequests', $payload);
    
    if ($result['status'] == 200 || $result['status'] == 201) {
        echo json_encode(['success' => true, 'message' => 'Talep başarıyla oluşturuldu!', 'data' => $result]);
    } else {
        $errorMsg = 'Talep oluşturulamadı: HTTP ' . ($result['status'] ?? 'NO STATUS');
        if (isset($result['response']['error'])) {
            $errorMsg .= ' - ' . json_encode($result['response']['error']);
        }
        echo json_encode(['success' => false, 'message' => $errorMsg, 'response' => $result]);
    }
    exit;
}

// AJAX: Items listesi getir
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'items') {
    header('Content-Type: application/json');
    
    $skip = intval($_GET['skip'] ?? 0);
    $top = intval($_GET['top'] ?? 10);
    $search = trim($_GET['search'] ?? '');
    $itemGroup = trim($_GET['item_group'] ?? '');
    $stockStatus = trim($_GET['stock_status'] ?? '');
    
    $filter = "U_AS_OWNR eq '{$uAsOwnr}'";
    
    // Arama filtresi
    if (!empty($search)) {
        $searchEscaped = str_replace("'", "''", $search);
        $filter .= " and (ItemCode eq '{$searchEscaped}' or ItemName eq '{$searchEscaped}' or startswith(ItemName, '{$searchEscaped}'))";
    }
    
    // Kalem Grubu filtresi
    if (!empty($itemGroup)) {
        $itemGroupEscaped = str_replace("'", "''", $itemGroup);
        $filter .= " and ItemGroups/ItemsGroupCode eq '{$itemGroupEscaped}'";
    }
    
    // Stok durumu filtresi (ItemWarehouseInfoCollection'dan InStock kontrolü yapılabilir ama şimdilik basit tutuyoruz)
    // Not: Stok durumu için ItemWarehouseInfoCollection expand edilmesi gerekir, bu yavaş olabilir
    
    $expand = "";
    if (!empty($stockStatus)) {
        $expand = "?\$expand=ItemWarehouseInfoCollection";
    }
    
    $itemsQuery = "Items{$expand}?\$filter=" . urlencode($filter) . "&\$orderby=ItemCode&\$top={$top}&\$skip={$skip}";
    $itemsData = $sap->get($itemsQuery);
    $items = $itemsData['response']['value'] ?? [];
    
    // Stok durumu filtresi varsa, PHP'de filtrele
    if (!empty($stockStatus) && !empty($items)) {
        $filteredItems = [];
        foreach ($items as $item) {
            $warehouseInfos = $item['ItemWarehouseInfoCollection'] ?? [];
            $hasStock = false;
            
            foreach ($warehouseInfos as $whInfo) {
                $inStock = floatval($whInfo['InStock'] ?? 0);
                if ($stockStatus === 'var' && $inStock > 0) {
                    $hasStock = true;
                    break;
                } else if ($stockStatus === 'yok' && $inStock == 0) {
                    $hasStock = true;
                    break;
                }
            }
            
            if ($hasStock || ($stockStatus === 'var' && empty($warehouseInfos))) {
                $filteredItems[] = $item;
            }
        }
        $items = $filteredItems;
    }
    
    echo json_encode([
        'data' => $items,
        'count' => count($items),
        'hasMore' => count($items) >= $top
    ]);
    exit;
}

// AJAX: ItemGroups listesi getir (dropdown için)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'itemgroups') {
    header('Content-Type: application/json');
    
    $itemsQuery = "Items?\$filter=U_AS_OWNR eq '{$uAsOwnr}'&\$select=ItemGroups/ItemsGroupCode&\$expand=ItemGroups&\$top=1000";
    $itemsData = $sap->get($itemsQuery);
    $items = $itemsData['response']['value'] ?? [];
    
    $itemGroups = [];
    foreach ($items as $item) {
        $groups = $item['ItemGroups'] ?? [];
        foreach ($groups as $group) {
            $groupCode = $group['ItemsGroupCode'] ?? '';
            if (!empty($groupCode) && !in_array($groupCode, $itemGroups)) {
                $itemGroups[] = $groupCode;
            }
        }
    }
    sort($itemGroups);
    
    echo json_encode(['data' => $itemGroups]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Talep Oluştur - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
    <style>
.selected-items {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.selected-items h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: #2c3e50;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.selected-item:last-child {
    border-bottom: none;
}

.selected-item-name {
    flex: 1;
    margin-right: 1rem;
}

.selected-item-qty {
    width: 100px;
    text-align: center;
}

.remove-item-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
}

.remove-item-btn:hover {
    background: #c82333;
}

.quantity-controls {
    display: flex;
    gap: 5px;
    align-items: center;
    justify-content: center;
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
    width: 80px;
    text-align: center;
    padding: 0.25rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.search-controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: center;
}

.search-input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: center;
    margin-top: 1rem;
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
                <h2>Yeni Talep Oluştur</h2>
                <button class="btn btn-secondary" onclick="window.location.href='AnaDepo.php'">← Geri Dön</button>
            </header>

            <div class="content-wrapper">
                <?php if (empty($fromWarehouse) || empty($toWarehouse)): ?>
                    <div class="alert alert-warning" style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 20px; color: #856404;">
                        <strong>Uyarı:</strong> Depo bilgileri bulunamadı!
                    </div>
                <?php endif; ?>

                <div class="selected-items card" id="selectedItems" style="display: none;">
                    <h3>Seçilen Kalemler</h3>
                    <div id="selectedItemsList"></div>
                    <div style="margin-top: 1rem; text-align: right;">
                        <button class="btn btn-primary" onclick="saveRequest()">Kaydet</button>
                        </div>
                        </div>

                <div class="card">
                    <div class="search-controls">
                        <input type="text" 
                               id="searchInput" 
                               class="search-input" 
                               placeholder="Kalem kodu veya tanımı ile ara..."
                               onkeyup="if(event.key==='Enter') loadItems()">
                        <button class="btn btn-primary" onclick="loadItems()">Ara</button>
                        <button class="btn btn-secondary" onclick="clearSearch()">Temizle</button>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kalem Kodu</th>
                                <th>Kalem Tanımı</th>
                                <th>Miktar</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr>
                                <td colspan="4" style="text-align:center;color:#888;padding:20px;">
                                    Arama yapmak için yukarıdaki arama kutusunu kullanın.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; align-items: center; margin-top: 1rem;">
                        <button class="btn btn-secondary" id="prevBtn" onclick="changePage(-1)" disabled>← Önceki</button>
                        <span id="pageInfo">Sayfa 1</span>
                        <button class="btn btn-secondary" id="nextBtn" onclick="changePage(1)" disabled>Sonraki →</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
let currentPage = 0;
let pageSize = 25;
let selectedItems = {};
let hasMore = false;
let selectedItemGroup = '';
let selectedStockStatus = '';
let itemGroups = [];

// Sayfa yüklendiğinde item groups'u yükle
document.addEventListener('DOMContentLoaded', function() {
    loadItemGroups();
});

function loadItemGroups() {
    fetch('AnaDepoSO.PHP?ajax=itemgroups')
        .then(res => res.json())
        .then(data => {
            itemGroups = data.data || [];
            const dropdown = document.getElementById('itemGroupDropdown');
            const defaultOption = dropdown.querySelector('.single-select-option[data-value=""]');
            dropdown.innerHTML = '';
            dropdown.appendChild(defaultOption);
            
            itemGroups.forEach(group => {
                const option = document.createElement('div');
                option.className = 'single-select-option';
                option.setAttribute('data-value', group);
                option.textContent = group;
                option.onclick = () => selectItemGroup(group);
                dropdown.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Item Groups yüklenirken hata:', err);
        });
}

function toggleDropdown(type) {
    const dropdown = document.getElementById(type + 'Dropdown');
    const input = document.querySelector(`#filter${type.charAt(0).toUpperCase() + type.slice(1)}`).parentElement;
    const isOpen = dropdown.style.display === 'block';
    
    // Close all dropdowns
    document.querySelectorAll('.single-select-dropdown').forEach(d => d.style.display = 'none');
    document.querySelectorAll('.single-select-input').forEach(d => d.classList.remove('active'));
    
    if (!isOpen) {
        dropdown.style.display = 'block';
        input.classList.add('active');
    }
}

function selectItemGroup(value) {
    selectedItemGroup = value;
    const text = value || 'Tümü';
    document.getElementById('filterItemGroup').value = text;
    document.querySelectorAll('#itemGroupDropdown .single-select-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`#itemGroupDropdown .single-select-option[data-value="${value}"]`).classList.add('selected');
    currentPage = 0;
    loadItems();
}

function selectStockStatus(value) {
    selectedStockStatus = value;
    const text = value === 'var' ? 'Stokta Var' : (value === 'yok' ? 'Stokta Yok' : 'Tümü');
    document.getElementById('filterStockStatus').value = text;
    document.querySelectorAll('#stockStatusDropdown .single-select-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`#stockStatusDropdown .single-select-option[data-value="${value}"]`).classList.add('selected');
    currentPage = 0;
    loadItems();
}

function updatePageSize() {
    pageSize = parseInt(document.getElementById('entriesPerPage').value);
    currentPage = 0;
    loadItems();
}

function loadItems() {
    const search = document.getElementById('searchInput').value.trim();
    const itemName = document.getElementById('filterItemName').value.trim();
    const skip = currentPage * pageSize;
    
    // Arama için hem searchInput hem de filterItemName kullan
    const searchTerm = itemName || search;
    
    let url = `AnaDepoSO.PHP?ajax=items&skip=${skip}&top=${pageSize}`;
    if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
    if (selectedItemGroup) url += `&item_group=${encodeURIComponent(selectedItemGroup)}`;
    if (selectedStockStatus) url += `&stock_status=${encodeURIComponent(selectedStockStatus)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            hasMore = data.hasMore || false;
            renderItems(data.data || []);
            updatePagination();
        })
        .catch(err => {
            console.error('Hata:', err);
            document.getElementById('itemsTableBody').innerHTML = 
                '<tr><td colspan="4" style="text-align:center;color:#dc3545;">Veri yüklenirken hata oluştu.</td></tr>';
        });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.single-select-container')) {
        document.querySelectorAll('.single-select-dropdown').forEach(d => d.style.display = 'none');
        document.querySelectorAll('.single-select-input').forEach(d => d.classList.remove('active'));
    }
});

function renderItems(items) {
    const tbody = document.getElementById('itemsTableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888;">Kayıt bulunamadı.</td></tr>';
        return;
    }
    
    tbody.innerHTML = items.map(item => {
        const itemCode = item.ItemCode || '';
        const itemName = item.ItemName || item.ItemDescription || '';
        const isSelected = selectedItems.hasOwnProperty(itemCode);
        
        return `
            <tr>
                <td>${itemCode}</td>
                <td>${itemName}</td>
                <td>
                    <div class="quantity-controls">
                        <button class="qty-btn minus" onclick="changeQuantity('${itemCode}', -1)">-</button>
                        <input type="number" 
                               id="qty_${itemCode}"
                               value="${isSelected ? selectedItems[itemCode].quantity : 0}" 
                               min="0" 
                               step="0.01"
                               class="qty-input"
                               onchange="updateQuantity('${itemCode}', this.value)">
                        <button class="qty-btn plus" onclick="changeQuantity('${itemCode}', 1)">+</button>
                    </div>
                </td>
                <td>
                    ${isSelected 
                        ? `<button class="btn btn-danger" onclick="removeItem('${itemCode}')">Kaldır</button>`
                        : `<button class="btn btn-primary" onclick="addItem('${itemCode}', '${itemName.replace(/'/g, "\\'")}')">Ekle</button>`
                    }
                </td>
            </tr>
        `;
    }).join('');
}

function changeQuantity(itemCode, delta) {
    const input = document.getElementById('qty_' + itemCode);
    let value = parseFloat(input.value) || 0;
    value += delta;
    if (value < 0) value = 0;
    input.value = value;
    updateQuantity(itemCode, value);
}

function updateQuantity(itemCode, quantity) {
    if (selectedItems.hasOwnProperty(itemCode)) {
        selectedItems[itemCode].quantity = parseFloat(quantity) || 0;
        if (selectedItems[itemCode].quantity <= 0) {
            delete selectedItems[itemCode];
        }
    }
    updateSelectedItemsList();
}

function addItem(itemCode, itemName) {
    const quantity = parseFloat(document.getElementById('qty_' + itemCode).value) || 0;
    if (quantity <= 0) {
        alert('Lütfen miktar girin!');
        return;
    }
    
    selectedItems[itemCode] = {
        itemCode: itemCode,
        itemName: itemName,
        quantity: quantity
    };
    
    updateSelectedItemsList();
    loadItems(); // Refresh to show "Kaldır" button
}

function removeItem(itemCode) {
    delete selectedItems[itemCode];
    document.getElementById('qty_' + itemCode).value = 0;
    updateSelectedItemsList();
    loadItems(); // Refresh to show "Ekle" button
}

function updateSelectedItemsList() {
    const container = document.getElementById('selectedItems');
    const list = document.getElementById('selectedItemsList');
    
    if (Object.keys(selectedItems).length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    list.innerHTML = Object.values(selectedItems).map(item => `
        <div class="selected-item">
            <span class="selected-item-name">${item.itemCode} - ${item.itemName}</span>
            <span class="selected-item-qty">Miktar: ${item.quantity}</span>
            <button class="remove-item-btn" onclick="removeItem('${item.itemCode}')">Kaldır</button>
        </div>
    `).join('');
}

function changePage(delta) {
    currentPage += delta;
    if (currentPage < 0) currentPage = 0;
    loadItems();
}

function updatePagination() {
    document.getElementById('pageInfo').textContent = `Sayfa ${currentPage + 1}`;
    document.getElementById('prevBtn').disabled = currentPage === 0;
    document.getElementById('nextBtn').disabled = !hasMore;
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterItemName').value = '';
    selectedItemGroup = '';
    selectedStockStatus = '';
    document.getElementById('filterItemGroup').value = 'Tümü';
    document.getElementById('filterStockStatus').value = 'Tümü';
    document.querySelectorAll('.single-select-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelectorAll('.single-select-option[data-value=""]').forEach(opt => opt.classList.add('selected'));
    currentPage = 0;
    loadItems();
}

function saveRequest() {
    const items = Object.values(selectedItems).filter(item => item.quantity > 0);
    
    if (items.length === 0) {
        alert('Lütfen en az bir kalem seçin!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_request');
    formData.append('items', JSON.stringify(items));
    
    if (!confirm('Talebi oluşturmak istediğinize emin misiniz?')) {
        return;
    }
    
    fetch('AnaDepoSO.PHP', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Talep başarıyla oluşturuldu!');
            window.location.href = 'AnaDepo.php';
        } else {
            alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
        }
    })
    .catch(err => {
        console.error('Hata:', err);
        alert('Talep oluşturulurken hata oluştu!');
    });
}
    </script>
</body>
</html>
