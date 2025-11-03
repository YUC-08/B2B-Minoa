<?php
session_start();
if (!isset($_SESSION["sapSession"])) {
    header("Location: config/login.php");
    exit;
}
include 'sap_connect.php';
$sap = new SAPConnect();  

// 🔹 Kullanıcının deposu
$whsCode = $_SESSION["WhsCode"] ?? ''; 

// 🔹 SAP çağrısı
$data = $sap->get("SQLQueries('OPOR_LIST')/List?value1='SUPPLY'&value2='{$whsCode}'");  

// 🔹 Gelen JSON’dan verileri al
$rows = $data['response']['value'] ?? [];  


/*echo "<pre>";
var_dump($whsCode);
print_r($data);
echo "</pre>";
exit; */  
 
?>
<!DOCTYPE html>
<html lang="tr"> 
<head>
    <meta charset="UTF-8">
    <title>Dış Tedarik Siparişleri - CREMMAVERSE</title> 
    <link rel="stylesheet" href="styles.css">
    <style>
        .status-processing {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-approval {
            background: #cce5ff;
            color: #004085;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-preparing {
            background: #d1ecf1;
            color: #0c5460;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-unknown {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Filter Styles */
        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 1rem;
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
        
        .multi-select-container {
            position: relative;
            width: 100%;
        }
        
        .multi-select-input {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            min-height: 38px;
        }
        
        .multi-select-input:hover {
            border-color: #007bff;
        }
        
        .multi-select-input.active {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .multi-select-input input {
            border: none;
            outline: none;
            flex: 1;
            background: transparent;
        }
        
        .multi-select-dropdown {
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
        
        .multi-select-dropdown.show {
            display: block;
        }
        
        .multi-select-option {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .multi-select-option:hover {
            background: #f8f9fa;
        }
        
        .multi-select-option.selected {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .multi-select-tag {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin: 2px;
        }
        
        .multi-select-tag .remove {
            margin-left: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .multi-select-tag .remove:hover {
            color: #ffcccb;
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
            gap: 5px;
        }
        
        .entries-select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-box {
            display: flex;
            gap: 5px;
        }
        
        .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        
        .search-btn {
            padding: 8px 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            cursor: pointer;
        }
        
        .data-table th:hover {
            background: #e9ecef;
        }
        
        .sort-icon {
            margin-left: 5px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .page-btn:hover {
            background: #f8f9fa;
        }
        
        .page-btn.active {
            background: #007bff;
            color: white;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-icon {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-view {
            background: #e9ecef;
            color: #2c3e50;
        }
        
        .btn-view:hover {
            background: #dee2e6;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
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
        <header class="page-header">
            <h2>Dış Tedarik Siparişleri (<?= htmlspecialchars($whsCode) ?>)</h2>
            <button class="btn btn-primary" onclick="window.location.href='Dis-TedarikSO.php'">+ Yeni Sipariş Oluştur</button> 
        </header>

        <div class="content-wrapper">
            <!-- Filtreler -->
            <section class="card" style="margin-bottom: 1rem;">
                <div class="filter-section">
                    <div class="filter-group">
                        <label>Sipariş Durumu</label>
                        <div class="single-select-container">
                            <div class="single-select-input" onclick="toggleDropdown('status')">
                                <input type="text" id="filterStatus" value="Tümü" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="single-select-dropdown" id="statusDropdown">
                                <div class="single-select-option selected" data-value="" onclick="selectSingleOption('status', '', 'Tümü')">Tümü</div>
                                <div class="single-select-option" data-value="1" onclick="selectSingleOption('status', '1', 'Onay Bekliyor')">Onay Bekliyor</div>
                                <div class="single-select-option" data-value="2" onclick="selectSingleOption('status', '2', 'Hazırlanıyor')">Hazırlanıyor</div>
                                <div class="single-select-option" data-value="3" onclick="selectSingleOption('status', '3', 'Sevk Edildi')">Sevk Edildi</div>
                                <div class="single-select-option" data-value="4" onclick="selectSingleOption('status', '4', 'Tamamlandı')">Tamamlandı</div>
                                <div class="single-select-option" data-value="5" onclick="selectSingleOption('status', '5', 'İptal Edildi')">İptal Edildi</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label>Tedarikçi</label>
                        <div class="multi-select-container">
                            <div class="multi-select-input" onclick="toggleDropdown('supplier')">
                                <div id="supplierTags"></div>
                                <input type="text" id="filterSupplier" placeholder="Seçiniz..." readonly>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multi-select-dropdown" id="supplierDropdown">
                                <div class="multi-select-option" data-value="" onclick="selectOption('supplier', '', 'Tümü')">Tümü</div>
                                <?php
                                $suppliers = [];
                                foreach ($data['response']['value'] as $row) {
                                    if (!in_array($row['CardName'], $suppliers)) {
                                        $suppliers[] = $row['CardName'];
                                    }
                                }
                                sort($suppliers);
                                foreach ($suppliers as $supplier) {
                                    echo "<div class='multi-select-option' data-value='" . htmlspecialchars($supplier) . "' onclick=\"selectOption('supplier', '" . htmlspecialchars($supplier) . "', '" . htmlspecialchars($supplier) . "')\">" . htmlspecialchars($supplier) . "</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label>Başlangıç - Bitiş Tarihi</label>
                        <div class="date-range">
                            <input type="date" id="start-date" placeholder="Başlangıç">
                            <span>-</span>
                            <input type="date" id="end-date" placeholder="Bitiş">
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="card">
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
                        <button class="search-btn" onclick="applySearch()">🔍</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table" id="orders-table">
                        <thead>
                            <tr>
                                <th onclick="sortTable('DocNum')">Sipariş No <span class="sort-icon">↕️</span></th>
                                <th onclick="sortTable('CardName')">Tedarikçi <span class="sort-icon">↕️</span></th>
                                <th onclick="sortTable('DocDate')">Sipariş / Teslimat Tarihi <span class="sort-icon">↕️</span></th>
                                <th onclick="sortTable('NumAtCard')">Teslimat Belge No <span class="sort-icon">↕️</span></th>
                                <th onclick="sortTable('DocStatus')">Durum <span class="sort-icon">↕️</span></th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JavaScript ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination" id="pagination">
                    <!-- JavaScript ile doldurulacak -->
                </div>
            </section>
        </div>
    </main>
</div>

<script>
// Global data storage - DocEntry ekle
let allOrders = <?php echo json_encode($data['response']['value'] ?? []); ?>;
let filteredOrders = [...allOrders];

// Debug: Find unique status values
const uniqueStatuses = [...new Set(allOrders.map(order => order.DocStatus))];
console.log('Available status values:', uniqueStatuses);
let currentPage = 1;
let sortColumn = '';
let sortDirection = 'asc';
let searchTerm = '';

// Filter state
let selectedStatus = [];
let selectedSuppliers = [];

// Dropdown functionality
function toggleDropdown(type) {
    const dropdown = document.getElementById(type + 'Dropdown');
    const input = document.getElementById('filter' + type.charAt(0).toUpperCase() + type.slice(1));
    
    // Close all dropdowns
    document.querySelectorAll('.single-select-dropdown, .multi-select-dropdown').forEach(dd => {
        dd.classList.remove('show');
    });
    document.querySelectorAll('.single-select-input, .multi-select-input').forEach(input => {
        input.classList.remove('active');
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
    input.classList.toggle('active');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.single-select-container') && !e.target.closest('.multi-select-container')) {
        document.querySelectorAll('.single-select-dropdown, .multi-select-dropdown').forEach(dd => {
            dd.classList.remove('show');
        });
        document.querySelectorAll('.single-select-input, .multi-select-input').forEach(input => {
            input.classList.remove('active');
        });
    }
});

function selectSingleOption(type, value, text) {
    const input = document.getElementById('filter' + type.charAt(0).toUpperCase() + type.slice(1));
    const dropdown = document.getElementById(type + 'Dropdown');
    
    // Update input value
    input.value = text;
    
    // Update selected option
    dropdown.querySelectorAll('.single-select-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.target.classList.add('selected');
    
    // Update filter state
    if (type === 'status') {
        selectedStatus = value ? [value] : [];
    }
    
    // Close dropdown
    dropdown.classList.remove('show');
    input.classList.remove('active');
    
    applyFilters();
}

function selectOption(type, value, text) {
    const tagsContainer = document.getElementById(type + 'Tags');
    const input = document.getElementById('filter' + type.charAt(0).toUpperCase() + type.slice(1));
    
    if (value === '') {
        // Clear all selections
        if (type === 'supplier') {
            selectedSuppliers = [];
        }
        tagsContainer.innerHTML = '';
        input.placeholder = 'Seçiniz...';
    } else {
        if (type === 'supplier') {
            if (!selectedSuppliers.includes(value)) {
                selectedSuppliers.push(value);
            }
        }
        
        updateTags(type);
    }
    
    applyFilters();
}

function updateTags(type) {
    const tagsContainer = document.getElementById(type + 'Tags');
    const input = document.getElementById('filter' + type.charAt(0).toUpperCase() + type.slice(1));
    
    tagsContainer.innerHTML = '';
    
    let selected = [];
    if (type === 'status') {
        selected = selectedStatus;
    } else if (type === 'supplier') {
        selected = selectedSuppliers;
    }
    
    selected.forEach(value => {
        const tag = document.createElement('span');
        tag.className = 'multi-select-tag';
        tag.innerHTML = `${value} <span class="remove" onclick="removeTag('${type}', '${value}')">×</span>`;
        tagsContainer.appendChild(tag);
    });
    
    input.placeholder = selected.length > 0 ? `${selected.length} seçili` : 'Seçiniz...';
}

function removeTag(type, value) {
    if (type === 'status') {
        selectedStatus = selectedStatus.filter(v => v !== value);
    } else if (type === 'supplier') {
        selectedSuppliers = selectedSuppliers.filter(v => v !== value);
    }
    
    updateTags(type);
    applyFilters();
}

function applyFilters() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    filteredOrders = allOrders.filter(order => {
        // Status filter
        if (selectedStatus.length > 0 && !selectedStatus.includes(order.DocStatus)) {
            return false;
        }
        
        // Supplier filter
        if (selectedSuppliers.length > 0 && !selectedSuppliers.includes(order.CardName)) {
            return false;
        }
        
        // Date filter
        if (startDate || endDate) {
            const orderDate = order.DocDate;
            if (orderDate) {
                const orderDateObj = new Date(
                    orderDate.substring(0, 4) + '-' + 
                    orderDate.substring(4, 6) + '-' + 
                    orderDate.substring(6, 8)
                );
                
                if (startDate && orderDateObj < new Date(startDate)) return false;
                if (endDate && orderDateObj > new Date(endDate)) return false;
            }
        }
        
        // Search filter
        if (searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            return (
                order.DocNum.toString().toLowerCase().includes(searchLower) ||
                order.CardName.toLowerCase().includes(searchLower) ||
                (order.NumAtCard && order.NumAtCard.toLowerCase().includes(searchLower))
            );
        }
        
        return true;
    });
    
    currentPage = 1;
    renderTable();
    updatePagination();
}

function applySearch() {
    searchTerm = document.getElementById('searchInput').value;
    applyFilters();
}

function sortTable(column) {
    if (sortColumn === column) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = column;
        sortDirection = 'asc';
    }
    
    filteredOrders.sort((a, b) => {
        let aVal = a[column];
        let bVal = b[column];
        
        if (column === 'DocDate' || column === 'DocDueDate') {
            aVal = aVal ? new Date(aVal.substring(0, 4) + '-' + aVal.substring(4, 6) + '-' + aVal.substring(6, 8)) : new Date(0);
            bVal = bVal ? new Date(bVal.substring(0, 4) + '-' + bVal.substring(4, 6) + '-' + bVal.substring(6, 8)) : new Date(0);
        }
        
        if (sortDirection === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
    
    renderTable();
}

function changePage(page) {
    currentPage = page; 
    renderTable();
    updatePagination();
}

function updatePagination() {
    const entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
    const totalPages = Math.ceil(filteredOrders.length / entriesPerPage);
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

function renderTable() {
    const tbody = document.querySelector('#orders-table tbody');
    tbody.innerHTML = '';
    
    if (filteredOrders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;">Filtreye uygun kayıt bulunamadı.</td></tr>';
        return;
    }
    
    const entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
    const startIndex = (currentPage - 1) * entriesPerPage;
    const endIndex = startIndex + entriesPerPage;
    const pageData = filteredOrders.slice(startIndex, endIndex);
    
    pageData.forEach(row => {
        let statusText, statusClass;
        switch (row.DocStatus) {
            case '1': statusText = 'Onay Bekliyor'; statusClass = 'status-approval'; break;
            case '2': statusText = 'Hazırlanıyor'; statusClass = 'status-preparing'; break;
            case '3': statusText = 'Sevk Edildi'; statusClass = 'status-shipped'; break;
            case '4': statusText = 'Tamamlandı'; statusClass = 'status-completed'; break;
            case '5': statusText = 'İptal Edildi'; statusClass = 'status-cancelled'; break;
            default: statusText = 'İşlemde'; statusClass = 'status-processing';
        }

        // SAP tarih formatı: 20250327 (YYYYMMDD) -> d.m.Y
        const docDate = row.DocDate ? 
            new Date(row.DocDate.substring(0, 4) + '-' + row.DocDate.substring(4, 6) + '-' + row.DocDate.substring(6, 8))
                .toLocaleDateString('tr-TR') : 'N/A';
        
        // DocDueDate için özel kontrol - 19000101 = default tarih
        let dueDate = 'Beklemede';
        if (row.DocDueDate && row.DocDueDate !== '19000101') {
            dueDate = new Date(row.DocDueDate.substring(0, 4) + '-' + row.DocDueDate.substring(4, 6) + '-' + row.DocDueDate.substring(6, 8))
                .toLocaleDateString('tr-TR');
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.DocNum}</td>
            <td>${row.CardName}</td>
            <td>${docDate} / ${dueDate}</td> 
            <td>${row.NumAtCard || '-'}</td>
            <td><span class='status-badge ${statusClass}'>${statusText}</span></td>
            <td>
                <a href='Dis-Tedarik-Detay.php?DocNum=${row.DocNum}'>
                    <button class='btn-icon btn-view'>👁️ Detay</button>
                </a>
                ${row.DocStatus === '3' ? `
                <button class='btn-icon btn-success' onclick="receiveOrder(event, ${row.DocNum})">✓ Teslim Al</button>
                ` : row.DocStatus === '1' ? `
                <span class='info-text' style='color:#856404;font-size:0.85rem;'>Onay bekleniyor</span>
                ` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Event listeners
document.getElementById('entriesPerPage').addEventListener('change', function() {
    currentPage = 1;
    renderTable();
    updatePagination();
});

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applySearch();
    }
});

// Receive order function
function receiveOrder(e, docNum) {
    if (!confirm('Bu siparişi teslim almak istediğinizden emin misiniz?\n\nSipariş "Tamamlandı" durumuna geçecek ve teslimat tarihi güncellenecektir.')) {
        return;
    }
    
    // Güvenli buton yakalama
    const btn = e?.currentTarget || e?.target;
    const originalText = btn ? btn.innerHTML : '';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '⏳ İşleniyor...';
    }
    
    const formData = new FormData();
    formData.append('action', 'deliver');
    
    fetch(`Dis-Tedarik-Detay.php?DocNum=${docNum}`, { 
        method: 'POST', 
        body: formData 
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response Data:', data);
        if (data.success) {
            // allOrders içindeki kaydı güncelle
            var today = new Date().toISOString().slice(0,10).replace(/-/g,'');
            allOrders = allOrders.map(function(x) {
                if (x.DocNum == docNum) {
                    var updated = {};
                    for (var key in x) {
                        if (x.hasOwnProperty(key)) {
                            updated[key] = x[key];
                        }
                    }
                    updated.DocStatus = '4';
                    updated.DocDueDate = today;
                    return updated;
                }
                return x;
            });
            
            // Filtreleri tekrar uygula
            applyFilters();
            
            alert('✅ ' + data.message);
        } else {
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
        alert('❌ Teslim alma işlemi sırasında hata oluştu!');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderTable();
    updatePagination();
});
</script>

</body>
</html>
