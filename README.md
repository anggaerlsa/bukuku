# Bukuku

Ruang kerja **worldbuilding** untuk penulis novel — tempat merancang dan mendata dunia cerita: benua, kerajaan, kota, tokoh, dan hubungan di antara mereka. Mirip World Anvil atau Campfire, tapi berbahasa Indonesia dan dibuat sesuai kebutuhan sendiri.

Ini **bukan** aplikasi untuk membaca novel. Ini meja kerja penulisnya.

Satu penulis bisa punya banyak **Dunia**, dan tiap dunia bebas temanya — fantasi abad pertengahan, kehidupan modern, atau fiksi ilmiah. Aplikasinya sendiri netral; rasa sebuah dunia datang dari genre yang dipilih, sebutan lokasi yang bebas diketik, dan atribut khusus yang ditentukan penulisnya sendiri.

## Fitur

**Dunia** — tiap dunia milik satu penulis, punya premis, sampul, status (Konsep / Aktif / Arsip), dan tag genre.

**Lokasi berjenjang lima tingkat** — Benua → Negara → Provinsi → Kota → Desa, ditampilkan sebagai pohon. Tiap tingkat punya **tabelnya sendiri** di database, bukan satu tabel dengan kolom `parent_id`. Tiap lokasi bisa diberi sebutan bebas sebagai identitas dalam dunia — tingkat *Provinsi* boleh tampil sebagai *Dukedom*, tingkat *Kota* sebagai *Metropolis* — tanpa mengubah strukturnya.

**Pencarian lokasi lintas tingkat** — mencari nama, sebutan, atau ringkasan di kelima tabel sekaligus. Hasilnya tidak berubah jadi daftar datar: pohonnya dipangkas, baris yang cocok disorot, dan induknya tetap ditampilkan supaya hirarkinya tetap terbaca.

**Karakter** — peran, ras, usia, status, penampilan, kepribadian, latar belakang, dan tujuan.

**Tautan Karakter ↔ Lokasi** — tiap karakter punya *asal* dan *domisili* yang menunjuk lokasi sungguhan di dunia itu; halaman lokasi menampilkan siapa saja yang berasal dari sana dan siapa yang tinggal di sana.

**Relasi antar-karakter** — keluarga, pasangan, mentor/murid, sekutu, musuh, atasan/bawahan. Tiap relasi disimpan **sekali**; sisi sebaliknya diturunkan otomatis, jadi kalau A adalah orang tua B, halaman B menampilkan A sebagai orang tuanya tanpa perlu diisi dua kali.

**Galeri gambar** — banyak gambar per karakter maupun per lokasi, dengan keterangan, unggahan berkas atau tautan URL, dan tombol untuk menjadikan salah satunya sampul.

**Atribut khusus per dunia** — penulis menentukan sendiri kolom tambahan sesuai tema dunianya (*Tingkat Mana*, *Klearans Keamanan*, *Kasta*). Bisa berupa teks, angka, pilihan, atau ya/tidak, dan diarahkan ke Karakter, semua Lokasi, atau satu tingkat lokasi saja.

**Peran pengguna** — Superadmin, Admin, dan Penulis, memakai `spatie/laravel-permission`. Penulis hanya bisa mengelola dunia miliknya sendiri. Pendaftaran mandiri dimatikan; akun dibuat lewat menu Pengguna.

## Teknologi

Laravel 12 · Blade + Alpine.js · Tailwind CSS v3 · Vite · MySQL · `spatie/laravel-permission`

## Menjalankan secara lokal

```bash
git clone https://github.com/anggaerlsa/bukuku.git
cd bukuku
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Isi bagian `DB_*` di `.env`, lalu:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Dikembangkan memakai [Laravel Herd](https://herd.laravel.com/) di `https://bukuku.test`.

### Akun awal

Seeder membuat tiga akun: superadmin, admin, dan penulis. Surel dan kata sandinya **tidak disimpan di repositori** — nilainya dibaca dari `.env` lewat `config/seed.php`, dan kalau tidak diisi akan memakai nilai demo yang aman (lihat `.env.example`). Ubah `SEED_*` di `.env` sebelum menjalankan seeder pada instalasi sungguhan.

Masuk bisa memakai nama pengguna atau surel.

## Catatan arsitektur

**Kenapa satu tabel per tingkat lokasi.** Kelima tingkat sengaja dipisah secara fisik di database. Konsekuensinya, tidak ada satu tabel `locations` yang bisa dirujuk. Karena itu semua rujukan ke "sebuah lokasi" bersifat polimorfik dengan **morph map parsial** (`App\Support\ImageOwners::types()`, dipasang di `AppServiceProvider`): kolom `*_type` menyimpan kunci pendek (`kota`, `character`), bukan nama kelas penuh, sehingga cocok dengan segmen `{tier}` di rute. Map-nya sengaja parsial supaya `model_has_roles` milik Spatie tetap menyimpan `App\Models\User`.

Di formulir, sebuah lokasi dikirim sebagai satu token `tier:id`. `App\Support\LocationLookup` yang menyusun, mengurai, dan menyelesaikan token itu dalam lingkup satu dunia, sekaligus membangun daftar pilihan berkelompok lengkap dengan jalur leluhurnya (`Benua › Negara › Kota`) — satu query per tingkat, tanpa N+1.

**Kenapa relasi karakter hanya satu baris.** Menyimpan dua baris cermin membuka peluang keduanya berbeda seiring waktu. Karena itu relasi disimpan sekali dan arah sebaliknya dihitung dari peta kebalikan di `CharacterRelation::TYPES`. Kedua arah mustahil melenceng.

**Kenapa pencarian lokasi berjalan di memori.** Halaman lokasi sudah memuat seluruh pohon lewat eager loading. Menyaringnya di memori berarti nol query tambahan, sekaligus memudahkan aturan "simpul dipertahankan kalau ia cocok atau ada keturunannya yang cocok" — aturan yang justru menjaga leluhurnya tetap tampak.

**Penghapusan dan berkas unggahan.** Menghapus sebuah dunia menghapus barisnya lewat cascade di database, dan cascade tidak pernah menjalankan hook Eloquent. Karena itu `WorldController::destroy` mengumpulkan lebih dulu berkas galeri, potret karakter, dan peta lokasi milik dunia itu, supaya tidak ada berkas yatim tertinggal di disk.
