<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ticket Oluştur - CREMMAVERSE</title>
    <link rel="stylesheet" href="styles.css">
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
                <h2>Yeni Ticket Oluştur</h2>
            </header>

            <div class="content-card" style="max-width: 900px; margin: 0 auto;">
                <form class="ticket-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Şube</label>
                            <select class="form-select">
                                <option>Şube Seçiniz</option>
                                <option>Koşuyolu 1000</option>
                                <option>Caddebostan</option>
                                <option>Suadiye</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ticket Alıcı</label>
                            <select class="form-select">
                                <option>Kullanıcı Seçiniz</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ticket Öncelik</label>
                        <div class="priority-buttons">
                            <button type="button" class="priority-btn priority-low">Düşük</button>
                            <button type="button" class="priority-btn priority-medium">Orta</button>
                            <button type="button" class="priority-btn priority-high active">Yüksek</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea class="form-textarea" rows="8" placeholder="Ticket açıklamasını buraya yazın..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Görsel</label>
                        <div class="file-upload-area">
                            <button type="button" class="btn btn-outline">Dosya Seç</button>
                            <button type="button" class="btn btn-outline">Dosya seçilmedi</button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='ticket.html'">İptal</button>
                        <button type="submit" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script>
        // Priority button toggle
        document.querySelectorAll('.priority-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.priority-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
