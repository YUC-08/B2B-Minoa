<?php
session_start();
require_once 'sap_connect.php';

$sap = new SAPConnect();
$whsCode = $_SESSION["WhsCode"] ?? '';
$userName = $_SESSION["UserName"] ?? 'manager';

// Handle POST request for creating supply order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    header('Content-Type: application/json');
    
    try {
        $guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $userId = $_SESSION["BranchCode"] ?? $_SESSION["WhsCode"] ?? 
                 (preg_match('/(\d+)/', $_SESSION["UserName"] ?? '', $matches) ? $matches[1] : $_SESSION["UserName"] ?? "1000");
        
        $sapPayload = [
            "U_Type" => "SUPPLY",
            "U_WhsCode" => $_POST['whs_code'] ?? "sube",
            "U_ItemCode" => $_POST['item_code'] ?? "kalem",
            "U_ItemName" => $_POST['item_name'] ?? "tanım",
            "U_Quantity" => floatval($_POST['quantity'] ?? 11.0),
            "U_UomCode" => $_POST['uom_code'] ?? "Ölçü birimi",
            "U_CardCode" => $_POST['card_code'] ?? "TDK1",
            "U_CardName" => $_POST['card_name'] ?? "Tedarikci",
            "U_SessionID" => "1221",
            "U_GUID" => $guid,
            "U_User" => $userId
        ];
        
        // Debug: Log the payload being sent to SAP
        error_log("SAP Payload: " . json_encode($sapPayload));
        
        $response = $sap->post("ASUDO_B2B_OPOR", $sapPayload);
        
        // Debug: Log SAP response
        error_log("SAP Response Status: " . ($response['status'] ?? 'NO STATUS'));
        error_log("SAP Response Data: " . json_encode($response));
        
        if ($response['status'] == 200 || $response['status'] == 201) {
            echo json_encode(['success' => true, 'message' => 'Sipariş başarıyla oluşturuldu!', 'data' => $response]);
        } else {
            $errorMessage = 'Sipariş oluşturulamadı: ';
            if (isset($response['error'])) {
                $errorMessage .= $response['error'];
            } elseif (isset($response['response']['error'])) {
                $errorMessage .= $response['response']['error']['message']['value'] ?? 'Bilinmeyen SAP hatası';
            } else {
                $errorMessage .= 'HTTP ' . ($response['status'] ?? 'NO STATUS');
            }
            
            error_log("Order creation failed: " . $errorMessage);
            echo json_encode(['success' => false, 'message' => $errorMessage, 'response' => $response]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $whsCode = $_GET['whs_code'] ?? $whsCode;
    $itemName = trim($_GET['item_name'] ?? '');
    $itemGroup = trim($_GET['item_group'] ?? '');
    $cardCode = trim($_GET['card_code'] ?? '');
    $stockStatus = trim($_GET['stock_status'] ?? '');
    
    $query = "SQLQueries('OPOR_NEW')/List?value1='SUPPLY'&value2='{$whsCode}'";
    if ($itemName !== '') $query .= "&value3='" . urlencode($itemName) . "'";
    if ($itemGroup !== '') $query .= "&value4='" . urlencode($itemGroup) . "'";
    if ($cardCode !== '') $query .= "&value5='" . urlencode($cardCode) . "'";
    if ($stockStatus !== '') $query .= "&value6='" . urlencode($stockStatus) . "'";

    $data = $sap->get($query);
    $rows = $data['response']['value'] ?? [];
    
    echo json_encode([
        'data' => $rows,
        'count' => count($rows)
    ]);
    exit;
}

// Load data
$query = "SQLQueries('OPOR_NEW')/List?value1='SUPPLY'&value2='{$whsCode}'";
$data = $sap->get($query);
$rows = $data['response']['value'] ?? [];

// Extract filter data
$suppliers = [];
$itemGroups = [];
$itemNames = [];

foreach ($rows as $row) {
    if (isset($row['CardCode'], $row['CardName'])) {
        $suppliers[] = ['CardCode' => $row['CardCode'], 'CardName' => $row['CardName']];
    }
    
    // Extract group with multiple field checks
    $groupValue = $row['ItemsGroupCode'] ?? $row['GroupCode'] ?? $row['ItemGroup'] ?? $row['Group'] ?? 
                 $row['U_GroupCode'] ?? $row['U_ItemGroup'] ?? $row['ItemsGroup'] ?? 
                 $row['Category'] ?? $row['U_Category'] ?? '';
    
    if (!empty($groupValue)) {
        $itemGroups[] = $groupValue;
    } else {
        // If no group found, use smart grouping based on item name
        $itemName = strtoupper($row['ItemName'] ?? '');
        if (strpos($itemName, 'ŞURUP') !== false) {
            $itemGroups[] = 'ŞURUPLAR';
        } elseif (strpos($itemName, 'EKMEK') !== false) {
            $itemGroups[] = 'UNLU MAMÜLLER';
        } elseif (strpos($itemName, 'DONUK') !== false) {
            $itemGroups[] = 'DONUK';
        } else {
            $itemGroups[] = 'KURU GIDA';
        }
    }
    
    if (isset($row['ItemName'])) {
        $itemNames[] = $row['ItemName'];
    }
}

$suppliers = array_unique($suppliers, SORT_REGULAR);
$itemGroups = array_unique($itemGroups);
$itemNames = array_unique($itemNames);
sort($suppliers);
sort($itemGroups);
sort($itemNames);

// Debug information
error_log("Groups extracted: " . json_encode($itemGroups));
error_log("Total groups: " . count($itemGroups));

// Helper functions
function getGroupValue($row) {
    $groupValue = $row['ItemsGroupCode'] ?? $row['GroupCode'] ?? $row['ItemGroup'] ?? $row['Group'] ?? 
                 $row['U_GroupCode'] ?? $row['U_ItemGroup'] ?? $row['ItemsGroup'] ?? 
                 $row['Category'] ?? $row['U_Category'] ?? '';
    
    if (!$groupValue) {
        $itemName = strtoupper($row['ItemName'] ?? '');
        if (strpos($itemName, 'ŞURUP') !== false) return 'ŞURUPLAR';
        if (strpos($itemName, 'EKMEK') !== false) return 'UNLU MAMÜLLER';
        if (strpos($itemName, 'DONUK') !== false) return 'DONUK';
        return 'KURU GIDA';
    }
    return $groupValue;
}

function getUoMValue($row) {
    $uomValue = $row['UoMCode'] ?? $row['U_UomCode'] ?? $row['UnitOfMeasure'] ?? 
               $row['UoM'] ?? $row['Unit'] ?? $row['UOM'] ?? $row['U_Unit'] ?? $row['UnitCode'] ?? '';
    
    if (!$uomValue) {
        $itemName = strtoupper($row['ItemName'] ?? '');
        if (strpos($itemName, 'ŞURUP') !== false || strpos($itemName, 'SOS') !== false) return 'ML';
        if (strpos($itemName, 'EKMEK') !== false || strpos($itemName, 'PASTA') !== false) return 'AD';
        if (strpos($itemName, 'UN') !== false || strpos($itemName, 'ŞEKER') !== false) return 'KG';
        if (strpos($itemName, 'DONUK') !== false) return 'KG';
        return 'PK';
    }
    return $uomValue;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dış Tedarik Siparişi Oluştur - CREMMAVERSE</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .app-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 20px; margin-left: 250px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-actions { display: flex; gap: 10px; }
        .btn-back, .btn-exit { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-back { background: #6c757d; color: white; }
        .btn-exit { background: #dc3545; color: white; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .filter-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .multi-select-container { position: relative; }
        .multi-select-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa; cursor: pointer; display: flex; align-items: center; justify-content: space-between; min-height: 36px; }
        .multi-select-input:hover { background: white; border-color: #007bff; }
        .multi-select-input input { border: none; background: transparent; outline: none; flex: 1; cursor: pointer; }
        .dropdown-arrow { color: #666; font-size: 12px; transition: transform 0.2s; }
        .multi-select-input.active .dropdown-arrow { transform: rotate(180deg); }
        .multi-select-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .multi-select-dropdown.show { display: block; }
        .multi-select-option { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .multi-select-option:hover { background: #f8f9fa; }
        .multi-select-option.selected { background: #e3f2fd; color: #1976d2; }
        .multi-select-tag { display: inline-block; background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin: 2px; }
        .multi-select-tag .remove { margin-left: 4px; cursor: pointer; font-weight: bold; }
        .multi-select-tag .remove:hover { color: #ffcccb; }
        .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .show-entries { display: flex; align-items: center; gap: 5px; }
        .entries-select { padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .search-box { display: flex; gap: 5px; }
        .search-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 200px; }
        .search-btn { padding: 8px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #f8f9fa; font-weight: 600; cursor: pointer; }
        .data-table th:hover { background: #e9ecef; }
        .sort-icon { margin-left: 5px; }
        .stock-status { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .stock-status.var { background: #d4edda; color: #155724; }
        .stock-status.yok { background: #f8d7da; color: #721c24; }
        .quantity-controls { display: flex; align-items: center; gap: 5px; }
        .qty-btn { width: 30px; height: 30px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; }
        .qty-btn:hover { background: #f8f9fa; }
        .qty-input { width: 60px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; }
        .page-btn:hover { background: #f8f9fa; }
        .page-btn.active { background: #007bff; color: white; }
        .btn-primary { background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .btn-primary:hover { background: #0056b3; }
        .refresh-btn { position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; border-radius: 50%; background: #007bff; color: white; border: none; cursor: pointer; font-size: 18px; }
        .refresh-btn:hover { background: #0056b3; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 10px; } .filter-section { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
       <div class="app-container">
            <?php include 'navbar.php'; ?>
        <div class="main-content">
            <div class="page-header">
                <h2>Dış Tedarik Siparişi Oluştur</h2>
                <div class="header-actions">
                    <button class="btn-back" onclick="window.history.back()">← Geri</button>
                    <button class="btn-exit" onclick="window.location.href='config/logout.php'">Çıkış Yap →</button>
                </div>
                </div>

            <div class="card">
                    <div class="filter-section">
                        <div class="filter-group">
                            <label>Kalem Tanımı</label>
                        <div class="multi-select-container">
                            <div class="multi-select-input" onclick="toggleDropdown('itemName')">
                                <div id="itemNameTags"></div>
                                <input type="text" id="filterItemName" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multi-select-dropdown" id="itemNameDropdown">
                                <div class="multi-select-option" data-value="" onclick="selectOption('itemName', '', 'Tümü')">Tümü</div>
                                <?php foreach ($itemNames as $itemName): ?>
                                    <div class="multi-select-option" data-value="<?php echo htmlspecialchars($itemName); ?>" onclick="selectOption('itemName', '<?php echo htmlspecialchars($itemName); ?>', '<?php echo htmlspecialchars($itemName); ?>')">
                                        <?php echo htmlspecialchars($itemName); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                        <div class="filter-group">
                            <label>Grup Filtresi</label>
                        <div class="multi-select-container">
                            <div class="multi-select-input" onclick="toggleDropdown('itemGroup')">
                                <div id="itemGroupTags"></div>
                                <input type="text" id="filterItemGroup" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multi-select-dropdown" id="itemGroupDropdown">
                                <div class="multi-select-option" data-value="" onclick="selectOption('itemGroup', '', 'Tümü')">Tümü</div>
                                <?php foreach ($itemGroups as $group): ?>
                                    <div class="multi-select-option" data-value="<?php echo htmlspecialchars($group); ?>" onclick="selectOption('itemGroup', '<?php echo htmlspecialchars($group); ?>', '<?php echo htmlspecialchars($group); ?>')">
                                        <?php echo htmlspecialchars($group); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                        <div class="filter-group">
                            <label>Tedarikçi</label>
                        <div class="multi-select-container">
                            <div class="multi-select-input" onclick="toggleDropdown('cardCode')">
                                <div id="cardCodeTags"></div>
                                <input type="text" id="filterCardCode" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multi-select-dropdown" id="cardCodeDropdown">
                                <div class="multi-select-option" data-value="" onclick="selectOption('cardCode', '', 'Tümü')">Tümü</div>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <div class="multi-select-option" data-value="<?php echo htmlspecialchars($supplier['CardCode']); ?>" onclick="selectOption('cardCode', '<?php echo htmlspecialchars($supplier['CardCode']); ?>', '<?php echo htmlspecialchars($supplier['CardName']); ?>')">
                                        <?php echo htmlspecialchars($supplier['CardName']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                        <div class="filter-group">
                            <label>Stok Durumu</label>
                        <div class="multi-select-container">
                            <div class="multi-select-input" onclick="toggleDropdown('stockStatus')">
                                <div id="stockStatusTags"></div>
                                <input type="text" id="filterStockStatus" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multi-select-dropdown" id="stockStatusDropdown">
                                <div class="multi-select-option" data-value="" onclick="selectOption('stockStatus', '', 'Tümü')">Tümü</div>
                                <div class="multi-select-option" data-value="var" onclick="selectOption('stockStatus', 'var', 'Stokta Var')">Stokta Var</div>
                                <div class="multi-select-option" data-value="yok" onclick="selectOption('stockStatus', 'yok', 'Stokta Yok')">Stokta Yok</div>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="table-controls">
                        <div class="show-entries">
                            Sayfada 
                        <select class="entries-select" id="entriesPerPage">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            </select>
                            kayıt göster
                        </div>
                        <div class="search-box">
                        <input type="text" class="search-input" id="searchInput" placeholder="Ara...">
                        <button class="search-btn" onclick="searchTable()">🔍</button>
                        </div>
                    </div>

                <div class="table-container">
                    <table class="data-table" id="dataTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)">Kod ↕</th>
                                <th onclick="sortTable(1)">Tanım ↕</th>
                                <th onclick="sortTable(2)">Grup ↕</th>
                                <th onclick="sortTable(3)">Stokta ↕</th>
                                <th onclick="sortTable(4)">Stok Mik. ↕</th>
                                <th onclick="sortTable(5)">Min. Mik. ↕</th>
                                <th onclick="sortTable(6)">Sip. Mik. ↕</th>
                                <th onclick="sortTable(7)">Ölçü B. ↕</th>
                                <th onclick="sortTable(8)">Tedarikçi ↕</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['ItemCode'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['ItemName'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(getGroupValue($row)); ?></td>
                                <td>
                                    <span class="stock-status <?php echo ($row['OnHand'] ?? 0) > 0 ? 'var' : 'yok'; ?>">
                                        <?php echo ($row['OnHand'] ?? 0) > 0 ? 'Var' : 'Yok'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($row['OnHand'] ?? 0, 2); ?></td>
                                <td><?php echo number_format($row['MinStock'] ?? 0, 2); ?></td>
                                <td>
                                    <div class="quantity-controls">
                                        <button class="qty-btn minus" onclick="changeQuantity(this, -1)">-</button>
                                        <input type="number" class="qty-input" value="0" min="0" step="0.01" 
                                               data-item-code="<?php echo htmlspecialchars($row['ItemCode'] ?? ''); ?>"
                                               data-item-name="<?php echo htmlspecialchars($row['ItemName'] ?? ''); ?>"
                                               data-uom="<?php echo htmlspecialchars(getUoMValue($row)); ?>"
                                               data-supplier="<?php echo htmlspecialchars($row['CardName'] ?? ''); ?>">
                                        <button class="qty-btn plus" onclick="changeQuantity(this, 1)">+</button>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(getUoMValue($row)); ?></td>
                                <td><?php echo htmlspecialchars($row['CardName'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" id="pagination">
                    <button class="page-btn" onclick="changePage(1)">1</button>
                    <button class="page-btn" onclick="changePage(2)">2</button>
                    <button class="page-btn" onclick="changePage(3)">3</button>
                    </div>

                <div style="padding: 20px; text-align: center; border-top: 1px solid #e0e0e0; background-color: #f8f9fa;">
                    <button class="btn-primary" onclick="createOrder()" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">
                        📦 Sipariş Oluştur
                    </button>
                        </div>
                    </div>

            <button class="refresh-btn" onclick="refreshTable()" title="Tabloyu Yenile">🔄</button>
            </div>
    </div>

<script>
let currentPage = 1;
let allData = <?php echo json_encode($rows); ?>;
let filteredData = [...allData];
let sortColumn = -1;
let sortDirection = 'asc';
let selectedFilters = { itemName: [], itemGroup: [], cardCode: [], stockStatus: [] };
let renderTimeout = null;

function changeQuantity(button, change) {
    const input = button.parentElement.querySelector('.qty-input');
    const currentValue = parseFloat(input.value) || 0;
    const newValue = Math.max(0, currentValue + change);
    input.value = newValue;
}

function searchTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    filteredData = allData.filter(row => 
        (row.ItemCode || '').toLowerCase().includes(searchTerm) ||
        (row.ItemName || '').toLowerCase().includes(searchTerm) ||
        (row.CardName || '').toLowerCase().includes(searchTerm)
    );
    currentPage = 1;
    renderTable();
}

function sortTable(column) {
    if (sortColumn === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = column;
        sortDirection = 'asc';
    }
    
    filteredData.sort((a, b) => {
        const aVal = Object.values(a)[column] || '';
        const bVal = Object.values(b)[column] || '';
        return sortDirection === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });
    
    renderTable();
}

function changePage(page) {
    currentPage = page;
    renderTable();
}

function refreshTable() {
    if (renderTimeout) clearTimeout(renderTimeout);
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">Yenileniyor...</td></tr>';
    setTimeout(() => location.reload(), 200);
}

function createOrder() {
    const orderItems = [];
    const qtyInputs = document.querySelectorAll('.qty-input');
    
    qtyInputs.forEach(input => {
        const quantity = parseFloat(input.value) || 0;
        if (quantity > 0) {
            orderItems.push({
                itemCode: input.dataset.itemCode,
                itemName: input.dataset.itemName,
                quantity: quantity,
                uom: input.dataset.uom,
                supplier: input.dataset.supplier
            });
        }
    });
    
    if (orderItems.length === 0) {
        alert('Lütfen sipariş vermek istediğiniz kalemlerin miktarını girin.');
        return;
    }
    
    const ordersBySupplier = {};
    orderItems.forEach(item => {
        if (!ordersBySupplier[item.supplier]) ordersBySupplier[item.supplier] = [];
        ordersBySupplier[item.supplier].push(item);
    });
    
    let successCount = 0;
    let errorCount = 0;
    
    Object.keys(ordersBySupplier).forEach(supplier => {
        const items = ordersBySupplier[supplier];
        items.forEach(item => {
            const formData = new FormData();
            formData.append('action', 'create_order');
            formData.append('whs_code', '<?php echo $whsCode; ?>');
            formData.append('item_code', item.itemCode);
            formData.append('item_name', item.itemName);
            formData.append('quantity', item.quantity);
            formData.append('uom_code', item.uom);
            formData.append('card_code', 'TDK1');
            formData.append('card_name', item.supplier);
            
            fetch('', { method: 'POST', body: formData })
            .then(response => {
                console.log('HTTP Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('SAP Response:', data);
                if (data.success) {
                    successCount++;
                    console.log('Order created successfully for:', item.itemName);
                } else {
                    errorCount++;
                    console.error('Order creation failed for:', item.itemName, 'Error:', data.message);
                }
            })
            .catch(error => {
                console.error('SAP Error for:', item.itemName, error);
                errorCount++;
            });
        });
    });
    
    setTimeout(() => {
        if (errorCount === 0) {
            alert(`✅ ${successCount} sipariş başarıyla oluşturuldu!`);
            qtyInputs.forEach(input => input.value = '0');
        } else {
            alert(`⚠️ ${successCount} sipariş oluşturuldu, ${errorCount} sipariş başarısız oldu.\n\nDetaylar için browser console'u kontrol edin (F12).`);
        }
    }, 2000);
}

function toggleDropdown(type) {
    const dropdown = document.getElementById(type + 'Dropdown');
    const input = document.querySelector(`#${type}Dropdown`).parentElement.querySelector('.multi-select-input');
    
    document.querySelectorAll('.multi-select-dropdown').forEach(dd => {
        if (dd !== dropdown) {
            dd.classList.remove('show');
            dd.parentElement.querySelector('.multi-select-input').classList.remove('active');
        }
    });
    
    dropdown.classList.toggle('show');
    input.classList.toggle('active');
}

function selectOption(type, value, text) {
    if (value === '') {
        selectedFilters[type] = [];
    } else {
        const index = selectedFilters[type].indexOf(value);
        if (index > -1) {
            selectedFilters[type].splice(index, 1);
        } else {
            selectedFilters[type].push(value);
        }
    }
    
    updateFilterDisplay(type);
    applyFilters();
}

function updateFilterDisplay(type) {
    const tagsContainer = document.getElementById(type + 'Tags');
    const input = document.querySelector(`#${type}Dropdown`).parentElement.querySelector('input');
    
    tagsContainer.innerHTML = '';
    
    if (selectedFilters[type].length === 0) {
        input.placeholder = 'Seçiniz...';
        input.value = '';
    } else {
        input.placeholder = '';
        input.value = '';
        selectedFilters[type].forEach(value => {
            const tag = document.createElement('span');
            tag.className = 'multi-select-tag';
            tag.innerHTML = `${value} <span class="remove" onclick="removeFilter('${type}', '${value}')">&times;</span>`;
            tagsContainer.appendChild(tag);
        });
    }
}

function removeFilter(type, value) {
    const index = selectedFilters[type].indexOf(value);
    if (index > -1) {
        selectedFilters[type].splice(index, 1);
        updateFilterDisplay(type);
        applyFilters();
    }
}

function applyFilters() {
    filteredData = allData.filter(row => {
        if (selectedFilters.itemName.length > 0) {
            const itemName = (row.ItemName || '').toLowerCase();
            if (!selectedFilters.itemName.some(filter => itemName.includes(filter.toLowerCase()))) return false;
        }
        if (selectedFilters.itemGroup.length > 0) {
            // Use the same logic as PHP getGroupValue function
            let groupValue = row.ItemsGroupCode || row.GroupCode || row.ItemGroup || row.Group || 
                           row.U_GroupCode || row.U_ItemGroup || row.ItemsGroup || 
                           row.Category || row.U_Category || '';
            
            if (!groupValue) {
                const itemName = (row.ItemName || '').toUpperCase();
                if (itemName.includes('ŞURUP')) groupValue = 'ŞURUPLAR';
                else if (itemName.includes('EKMEK')) groupValue = 'UNLU MAMÜLLER';
                else if (itemName.includes('DONUK')) groupValue = 'DONUK';
                else groupValue = 'KURU GIDA';
            }
            
            // Check if the group value matches any selected filter
            if (!selectedFilters.itemGroup.includes(groupValue)) {
                return false;
            }
        }
        if (selectedFilters.cardCode.length > 0) {
            if (!selectedFilters.cardCode.includes(row.CardCode)) return false;
        }
        if (selectedFilters.stockStatus.length > 0) {
            const hasStock = (row.OnHand || 0) > 0;
            const stockStatus = hasStock ? 'var' : 'yok';
            if (!selectedFilters.stockStatus.includes(stockStatus)) return false;
        }
        return true;
    });
    
    currentPage = 1;
    renderTable();
}

function renderTable() {
    if (renderTimeout) clearTimeout(renderTimeout);
    
    renderTimeout = setTimeout(() => {
        const tbody = document.getElementById('tableBody');
        const entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = startIndex + entriesPerPage;
        const pageData = filteredData.slice(startIndex, endIndex);
        
        const fragment = document.createDocumentFragment();
        
        pageData.forEach(row => {
            const tr = document.createElement('tr');
            
            let groupValue = row.ItemsGroupCode || row.GroupCode || row.ItemGroup || row.Group || 
                           row.U_GroupCode || row.U_ItemGroup || row.ItemsGroup || 
                           row.Category || row.U_Category || '';
            
            if (!groupValue) {
                const itemName = (row.ItemName || '').toUpperCase();
                if (itemName.includes('ŞURUP')) groupValue = 'ŞURUPLAR';
                else if (itemName.includes('EKMEK')) groupValue = 'UNLU MAMÜLLER';
                else if (itemName.includes('DONUK')) groupValue = 'DONUK';
                else groupValue = 'KURU GIDA';
            }
            
            let uomValue = row.UoMCode || row.U_UomCode || row.UnitOfMeasure || 
                          row.UoM || row.Unit || row.UOM || row.U_Unit || row.UnitCode || '';
            
            if (!uomValue) {
                const itemName = (row.ItemName || '').toUpperCase();
                if (itemName.includes('ŞURUP') || itemName.includes('SOS')) uomValue = 'ML';
                else if (itemName.includes('EKMEK') || itemName.includes('PASTA')) uomValue = 'AD';
                else if (itemName.includes('UN') || itemName.includes('ŞEKER')) uomValue = 'KG';
                else if (itemName.includes('DONUK')) uomValue = 'KG';
                else uomValue = 'PK';
            }
            
            tr.innerHTML = `
                <td>${row.ItemCode || ''}</td>
                <td>${row.ItemName || ''}</td>
                <td>${groupValue}</td>
                <td><span class="stock-status ${(row.OnHand || 0) > 0 ? 'var' : 'yok'}">${(row.OnHand || 0) > 0 ? 'Var' : 'Yok'}</span></td>
                <td>${parseFloat(row.OnHand || 0).toFixed(2)}</td>
                <td>${parseFloat(row.MinStock || 0).toFixed(2)}</td>
                <td>
                    <div class="quantity-controls">
                        <button class="qty-btn minus" onclick="changeQuantity(this, -1)">-</button>
                        <input type="number" class="qty-input" value="0" min="0" step="0.01" 
                               data-item-code="${row.ItemCode || ''}" data-item-name="${row.ItemName || ''}"
                               data-uom="${uomValue}" data-supplier="${row.CardName || ''}">
                        <button class="qty-btn plus" onclick="changeQuantity(this, 1)">+</button>
                    </div>
                </td>
                <td>${uomValue}</td>
                <td>${row.CardName || ''}</td>
            `;
            fragment.appendChild(tr);
        });
        
        tbody.innerHTML = '';
        tbody.appendChild(fragment);
        renderPagination();
    }, 100);
}

function renderPagination() {
    const entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
    const totalPages = Math.ceil(filteredData.length / entriesPerPage);
    const pagination = document.getElementById('pagination');
    
    pagination.innerHTML = '';
    for (let i = 1; i <= Math.min(totalPages, 10); i++) {
        const btn = document.createElement('button');
        btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
        btn.textContent = i;
        btn.onclick = () => changePage(i);
        pagination.appendChild(btn);
    }
}

document.getElementById('searchInput').addEventListener('input', searchTable);
document.getElementById('entriesPerPage').addEventListener('change', () => {
    currentPage = 1;
    renderTable();
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.multi-select-container')) {
        document.querySelectorAll('.multi-select-dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
            dropdown.parentElement.querySelector('.multi-select-input').classList.remove('active');
        });
    }
});

renderTable();
</script>
</body>
</html>