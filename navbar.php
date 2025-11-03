<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">
    <div class="logo">
        <h1>CREMMA<span>VERSE</span></h1>
    </div>

    <head><style>
        /* Navbar/Sidebar Specific Styles */
.sidebar {
  width: 200px;
  background: #2d2d2d;
  color: #fff;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
  overflow-y: auto;
  z-index: 1000;
}

.logo {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo h1 {
  font-size: 20px;
  font-weight: 600;
  color: #fff;
}

.logo span {
  font-weight: 300;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 20px;
  color: #fff;
  text-decoration: none;
  transition: background 0.2s;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.nav-item.active {
  background: rgba(255, 87, 34, 0.2);
  border-left: 3px solid #ff5722;
}

.nav-item .icon {
  font-size: 18px;
}

.user-info {
  padding: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: auto;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #00bcd4;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.user-details {
  flex: 1;
  font-size: 12px;
}

.user-name {
  font-weight: 500;
  margin-bottom: 2px;
}

.version {
  color: #999;
  font-size: 11px;
}

/* Responsive Navbar */
@media (max-width: 768px) {
  .sidebar {
    width: 60px;
  }

  .sidebar .logo h1 span,
  .sidebar .nav-item span:not(.icon),
  .sidebar .user-details {
    display: none;
  }
}

    </style></head>

    <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
        <span class="icon">🏠</span>
        <span>Anasayfa</span>
    </a>

    <a href="Dis-Tedarik.php" class="nav-item <?= ($currentPage == 'Dis-Tedarik.php') ? 'active' : '' ?>">
        <span class="icon">📦</span>
        <span>Dış Tedarik</span>
    </a>

    <a href="AnaDepo.php" class="nav-item <?= ($currentPage == 'AnaDepo.php') ? 'active' : '' ?>">
        <span class="icon">🏪</span>
        <span>Ana Depo</span>
    </a>

    <a href="Transferler.php" class="nav-item <?= ($currentPage == 'Transferler.php') ? 'active' : '' ?>">
        <span class="icon">🔄</span>
        <span>Transferler</span>
    </a>

    <a href="Check-List.php" class="nav-item <?= ($currentPage == 'Check-List.php') ? 'active' : '' ?>">
        <span class="icon">✓</span>
        <span>Check List</span>
    </a>

    <a href="Fire-Zayi.php" class="nav-item <?= ($currentPage == 'Fire-Zayi.php') ? 'active' : '' ?>">
        <span class="icon">⚠</span>
        <span>Fire ve Zayi</span>
    </a>

    <a href="Ticket.php" class="nav-item <?= ($currentPage == 'Ticket.php') ? 'active' : '' ?>">
        <span class="icon">🎫</span>
        <span>Ticket</span>
    </a>

    <a href="Stok.php" class="nav-item <?= ($currentPage == 'Stok.php') ? 'active' : '' ?>">
        <span class="icon">📊</span>
        <span>Stok Sayımı</span>
    </a>

    <div class="user-info">
        <div class="user-avatar">K1</div>
        <div class="user-details">
            <div class="user-name">Koşuyolu 1000 - Koşuyolu</div>
            <div class="version">v1.0.43</div>
        </div>
    </div>
</nav>
