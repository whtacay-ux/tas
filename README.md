# Discord Clone - Sesli, Görüntülü ve Metin Tabanlı Sohbet Platformu

Discord benzeri, modern bir sohbet platformu. PHP, MySQL, WebRTC ve JavaScript kullanılarak geliştirilmiştir.

## Özellikler

### Temel Özellikler
- Kullanıcı kayıt ve giriş sistemi
- Sunucu oluşturma ve yönetme
- Metin kanalları (gerçek zamanlı mesajlaşma)
- **Sesli ve görüntülü kanallar (WebRTC)**
- **Ekran paylaşımı**
- Rol ve izin sistemi (Admin, Moderator, Üye, Misafir)
- **Kanal kuran kişi rol atayabilir**
- Arkadaşlık sistemi
- Bildirim sistemi
- Koyu/Açık tema desteği
- Profil yönetimi
- Dosya paylaşımı

### Davet Sistemi
- **Sunucu davet linkleri** oluşturma
- **Kanal davet linkleri** oluşturma
- Davet linklerini yönetme (süre, kullanım limiti)
- Arkadaşları davet etme

### Rol Yönetimi
- Admin: Tüm yetkiler
- Moderator: Mesaj silme, kullanıcı atma
- Member: Sesli/görüntülü sohbet
- Guest: Sadece metin okuma

## Kurulum

### Gereksinimler
- XAMPP/WAMP/MAMP (PHP 7.4+, MySQL, Apache)
- Modern bir web tarayıcısı (Chrome, Firefox, Edge)

### Adımlar

1. **XAMPP Kurulumu**
   - XAMPP'ı bilgisayarınıza kurun
   - Apache ve MySQL servislerini başlatın

2. **Proje Dosyalarını Yükleme**
   - `discord-clone` klasörünü `C:\xampp\htdocs\` (Windows) veya `/Applications/XAMPP/htdocs/` (Mac) içine kopyalayın

3. **Veritabanı Kurulumu**
   - Tarayıcıdan `http://localhost/phpmyadmin` adresine gidin
   - Yeni bir veritabanı oluşturun: `discord_clone`
   - `discord_clone.sql` dosyasını içe aktarın

4. **Yapılandırma**
   - `includes/config.php` dosyasını açın
   - Gerekirse veritabanı bağlantı bilgilerini güncelleyin:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'discord_clone');
     ```

5. **Klasör İzinleri**
   - `assets/uploads/` ve `assets/uploads/avatars/` klasörlerine yazma izni verin:
     ```bash
     chmod -R 777 assets/uploads/
     ```

6. **Çalıştırma**
   - Tarayıcıdan `http://localhost/discord-clone/` adresine gidin

## Varsayılan Giriş Bilgileri

- **Kullanıcı adı:** admin
- **E-posta:** admin@discordclone.com
- **Şifre:** password

## Kullanım

### Sunucu Oluşturma
1. Sol taraftaki "+" butonuna tıklayın
2. Sunucu adı girin
3. "Oluştur" butonuna tıklayın

### Kanal Oluşturma
1. Sunucu sahibi veya admin olarak kanal listesinin altındaki "Kanal Ekle"ye tıklayın
2. Kanal adı ve türü seçin (Metin, Sesli, Görüntülü)
3. "Oluştur" butonuna tıklayın

### Davet Linki Oluşturma
1. Kanal üzerine gelin, sağdaki kullanıcı ikonuna tıklayın
2. "Davet Linki Oluştur" butonuna tıklayın
3. Linki kopyalayıp paylaşın

### Rol Atama
1. Sunucu sahibi veya admin olarak sağ üstteki ayarlar ikonuna tıklayın
2. "Sunucu Ayarları" > "Rol Yönetimi"
3. Üye üzerine tıklayarak rol atayın

### Sesli/Görüntülü Sohbet
1. Sesli veya görüntülü kanala tıklayın
2. Mikrofon ve kamera izinlerini verin
3. Sohbete katılın

### Ekran Paylaşımı
1. Sesli kanala katılın
2. Ekran paylaşımı butonuna tıklayın
3. Paylaşmak istediğiniz ekranı/sekmeyi seçin

## API Endpoints

### Mesajlaşma
- `POST /api/send-message.php` - Mesaj gönder
- `GET /api/messages.php?channel_id={id}` - Mesajları getir

### Sunucu/Kanal
- `POST /api/create-server.php` - Sunucu oluştur
- `POST /api/create-channel.php` - Kanal oluştur

### Arkadaşlık
- `POST /api/friend-request.php` - Arkadaşlık isteği gönder
- `POST /api/respond-friend.php` - İsteği kabul/reddet
- `GET /api/friends.php` - Arkadaşları getir

### Davet
- `POST /api/server-invite.php` - Sunucu daveti oluştur
- `POST /api/channel-invite.php` - Kanal daveti oluştur

### Rol
- `POST /api/assign-role.php` - Rol ata

### Sesli Sohbet
- `POST /api/voice-join.php` - Sesli kanala katıl
- `POST /api/voice-leave.php` - Sesli kanaldan ayrıl
- `POST /api/voice-status.php` - Ses durumunu güncelle

## Güvenlik

- CSRF koruması
- XSS koruması (htmlspecialchars)
- SQL Injection koruması (PDO prepared statements)
- Şifre hashleme (password_hash)
- Dosya yükleme kısıtlamaları

## Teknolojiler

- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Real-time:** WebRTC, AJAX Polling
- **Icons:** Font Awesome 6

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## Geliştirici

Geliştirilmiş Discord Clone - WebRTC Destekli
