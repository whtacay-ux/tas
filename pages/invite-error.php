<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Davet Hatası - Discord Clone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .invite-error {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .invite-error i {
            font-size: 80px;
            color: var(--danger);
            margin-bottom: 20px;
        }
        .invite-error h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .invite-error p {
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="invite-error">
        <i class="fas fa-exclamation-circle"></i>
        <h1>Davet Geçersiz</h1>
        <p><?php echo htmlspecialchars($error ?? 'Bu davet kodu kullanılamaz.'); ?></p>
        <a href="../index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Ana Sayfaya Dön
        </a>
    </div>
</body>
</html>