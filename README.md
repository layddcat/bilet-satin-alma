# bilet-satin-alma

# Bilet Satın Alma Platformu (BSAP)

PHP, SQLite ve Docker tabanlı, çok rollü (User, Company Admin, Admin) bir otobüs bileti satış sistemi.

## Teknolojiler

* **Backend:** PHP 8.2
* **Sunucu:** Apache
* **Veritabanı:** SQLite
* **PDF:** tFPDF (UTF-8 Desteği)
* **Paketleme:** Docker / Docker Compose
---

Kurulum ve Çalıştırma

Bu proje, tüm bağımlılıkları içeren bir Docker konteyneri ile çalışmak üzere tasarlanmıştır.

```bash
git clone https://github.com/layddcat/bilet-satin-alma.git
cd bilet-satin-alma
docker compose up --build -d
http://localhost:8080
```
Rol,E-posta,Şifre,Açıklama
👑 Süper Admin,admin@bsap.com,admin123,Tüm yetkilere sahip yönetici.
🏢 Firma Admini,firma@kamilkoc.com,firma123,Sadece 'Kamil Koç' seferlerini yönetir.
👤 Yolcu (User),yolcu@mail.com,yolcu123,Bilet alma testi için (Varsayılan Bakiye: 800 TL).
