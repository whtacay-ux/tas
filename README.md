# Discord Clone - PHP Sohbet Platformu

Discord benzeri bir sesli, görüntülü sohbet ve mesajlaşma platformu.

## Özellikler

- ✅ Kullanıcı kayıt ve giriş sistemi
- ✅ Sunucu oluşturma ve yönetme
- ✅ Metin kanalları (gerçek zamanlı mesajlaşma)
- ✅ Sesli ve görüntülü kanallar (WebRTC)
- ✅ Rol ve izin sistemi (Admin, Moderator, Üye, Misafir)
- ✅ Arkadaşlık sistemi
- ✅ Bildirim sistemi
- ✅ Koyu/Açık tema desteği
- ✅ Admin paneli
- ✅ Profil yönetimi
- ✅ Dosya paylaşımı

## Kurulum

### Gereksinimler

- XAMPP (PHP 7.4+, MySQL, Apache)
- Modern bir web tarayıcısı

### Adımlar

1. **XAMPP Kurulumu**
   - XAMPP'ı bilgisayarınıza kurun
   - Apache ve MySQL servislerini başlatın

2. **Dosyaları Yükleme**
   - `discord-clone` klasörünü `C:\xampp\htdocs\` içine kopyalayın

3. **Veritabanı Kurulumu**
   - Tarayıcınızda `http://localhost/phpmyadmin` adresine gidin
   - `database.sql` dosyasını içe aktarın
   - VEYA `discord_clone` veritabanını oluşturun ve SQL dosyasını çalıştırın

4. **Yapılandırma**
   - `includes/config.php` dosyasını düzenleyin (gerekirse)
   - Varsayılan ayarlar XAMPP için yapılandırılmıştır

5. **Klasör İzinleri**
   - `assets/uploads/` klasörüne yazma izni verin:
   ```
   chmod 755 assets/uploads/
   chmod 755 assets/uploads/avatars/
   chmod 755 assets/uploads/channel_media/
   ```

6. **Erişim**
   - Tarayıcınızda `http://localhost/discord-clone/` adresine gidin

## Varsayılan Giriş Bilgileri

- **Kullanıcı adı:** admin
- **Şifre:** admin123

## Kullanım

### Kayıt Olma
1. Ana sayfadan "Hesap Oluştur" sekmesine tıklayın
2. Kullanıcı adı, e-posta ve şifre girin
3. "Hesap Oluştur" butonuna tıklayın

### Sunucu Oluşturma
1. Sol taraftaki "+" butonuna tıklayın
2. Sunucu adı girin
3. "Oluştur" butonuna tıklayın

### Kanal Oluşturma
1. Sunucu başlığına tıklayın
2. "Kanal Ekle" seçeneğine tıklayın
3. Kanal adı ve türü seçin

### Mesaj Gönderme
1. Bir metin kanalına tıklayın
2. Mesaj kutusuna yazın
3. Enter tuşuna basın veya gönder butonuna tıklayın

### Tema Değiştirme
1. Sağ alt köşedeki tema butonuna tıklayın
2. Koyu veya açık tema arasında geçiş yapın

## Dosya Yapısı

```
discord-clone/
├── admin/              # Admin paneli
├── api/                # API endpoint'leri
├── assets/
│   ├── css/           # Stil dosyaları
│   ├── js/            # JavaScript dosyaları
│   └── uploads/       # Yüklenen dosyalar
├── includes/          # PHP kütüphaneleri
├── pages/             # Sayfa dosyaları
├── database.sql       # Veritabanı şeması
├── index.php          # Giriş sayfası
└── README.md          # Bu dosya
```

## Güvenlik

- Şifreler bcrypt ile hashlenir
- CSRF koruması
- SQL injection koruması (prepared statements)
- XSS koruması
- Oturum yönetimi

## Geliştirme

### Yapılacaklar

- [ ] WebSocket entegrasyonu (gerçek zamanlı güncellemeler)
- [ ] WebRTC sesli/görüntülü sohbet
- [ ] Ekran paylaşımı
- [ ] Dosya paylaşımı iyileştirmeleri
- [ ] Mobil uygulama
- [ ] Bot sistemi

## Lisans

Bu proje eğitim amaçlıdır.

## İletişim

Sorularınız ve önerileriniz için iletişime geçebilirsiniz.
