# BiletGo - Online Bilet Satış Sistemi

## Genel Teknik Özet

BiletGo, etkinlik organizatörleri ile kullanıcılar arasında köprü kuran, modern web teknolojileriyle geliştirilmiş kapsamlı bir online bilet satış platformudur. Sistem, kullanıcı dostu arayüzü, güvenli altyapısı ve kapsamlı yönetim paneliyle hem son kullanıcılar hem de organizatörler için ideal bir çözüm sunar.

### Temel Özellikler

- **Dijital Bilet Satışı:** Konser, tiyatro, festival gibi etkinlikler için online bilet satışı.
- **Kullanıcı ve Admin Paneli:** Kullanıcılar için kolay bilet satın alma ve profil yönetimi, yöneticiler için etkinlik, kullanıcı, bilet ve kategori yönetimi.
- **Gerçek Zamanlı Envanter:** Bilet stoklarının anlık takibi ve raporlanması.
- **Dinamik Sepet Sistemi:** AJAX ile hızlı ve kullanıcı dostu alışveriş deneyimi.
- **Responsive Tasarım:** Tüm cihazlarda uyumlu ve modern arayüz.
- **BLOB Görsel Yönetimi:** Etkinlik görsellerinin veritabanında güvenli şekilde saklanması ve gösterimi.

### Kullanılan Teknolojiler

- **Backend:** PHP 7.4+, MySQL, PDO, Session yönetimi
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, JavaScript (ES6), Font Awesome, Chart.js
- **Güvenlik:** bcrypt ile şifreleme, prepared statements, CSRF token, XSS koruması, dosya yükleme kontrolleri

### Güçlü Yönler

- Modern ve responsive tasarım
- Güvenli veritabanı yapısı
- AJAX ile dinamik işlemler
- Kapsamlı admin paneli
- BLOB görsel yönetimi

### Güvenlik Önlemleri

- Session tabanlı oturum yönetimi
- Rol bazlı yetkilendirme (user/admin)
- Güvenli şifre hashleme (bcrypt)
- SQL Injection, XSS ve CSRF korumaları
- Dosya yükleme ve erişim kontrolleri

---

**Sonuç:**  
BiletGo, modern web standartlarına uygun, güvenli ve kullanıcı odaklı bir online bilet satış platformudur. Hem kullanıcılar hem de etkinlik organizatörleri için kolaylık ve güvenlik sunar.