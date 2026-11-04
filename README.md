# Dermascan Pakar - Sistem Pakar Diagnosa Penyakit Kulit

Aplikasi web sistem pakar untuk mendiagnosa 15 jenis penyakit kulit (menular dan tidak menular) berdasarkan 68 gejala klinis dengan menggabungkan metode penalaran **Forward Chaining** (pencocokan aturan IF-THEN) dan perhitungan ketidakpastian **Certainty Factor (CF)**.

> **Acuan Penelitian:**  
> *Sistem Pakar Diagnosa Penyakit Kulit Menggunakan Metode Forward Chaining dan Certainty Factor*  
> Jurnal Riset dan Aplikasi Mahasiswa Informatika (JRAMI), Vol 07 No 04 Tahun 2026, Hal. 804–813.

---

## 📋 Fitur Utama Aplikasi
1. **Mesin Inferensi Ganda:**
   * *Forward Chaining:* Menelusuri fakta gejala yang dipilih pengguna dan mencocokkannya dengan basis aturan produksi.
   * *Certainty Factor (CF):* Menghitung probabilitas tingkat keyakinan diagnosis berdasarkan perkalian $CF_{user} \times CF_{pakar}$ dan kombinasi bertahap $CF_{combine} = CF_1 + CF_2 \times (1 - CF_1)$.
2. **Basis Pengetahuan Komprehensif:**
   * **15 Penyakit Kulit:** Dermatitis Atopik, Tinea, Dermatitis Seboroik, Prurigo, Impetigo Krustosa, Herpes Zoster, Urtikaria, Skabies, Psoriasis, Varisela, Acne Vulgaris, Vitiligo, Selulitis, Bisul (Furunkel), dan Keloid.
   * **68 Gejala Klinis:** Katalog gejala terstruktur dengan skala keyakinan pengguna (0.2 s/d 1.0).
   * **86 Basis Aturan (*Rules*):** Aturan utama dan aturan alternatif.
3. **Data Artikel & Referensi Medis Resmi:**
   * Dilengkapi rujukan ilmiah ke CDC, NCBI StatPearls, DermNet New Zealand, dan American Academy of Dermatology (AAD).
4. **Data Riwayat & Grafik:**
   * Menampilkan riwayat konsultasi pasien dan visualisasi grafik donat persentase penyakit secara dinamis.
5. **Cetak Dokumen Diagnosis (PDF):**
   * Fitur cetak ramah printer untuk rekam medis konsultasi pasien.

---

## 🛠️ Cara Pasang (Instalasi) di Komputer Lokal

### Persyaratan Sistem:
* XAMPP / Laragon (Apache, MySQL, PHP versi 7.4 atau 8.x)
* Peramban Web (Google Chrome, Microsoft Edge, atau Mozilla Firefox)

### Langkah-langkah:
1. **Nyalakan XAMPP:**
   * Buka *XAMPP Control Panel*.
   * Klik tombol **Start** pada modul **Apache** dan **MySQL**.

2. **Import Database ke phpMyAdmin:**
   * Buka browser dan akses `http://localhost/phpmyadmin/`.
   * Klik tab **Import** di bagian menu atas.
   * Klik tombol **Choose File**, lalu pilih file:
     ```text
     database_sistem_pakar_kulit.sql
     ```
   * Gulir ke bawah dan klik tombol **Import** (atau **Kirim**).
   * Database `db_sistem_pakar_kulit` beserta 8 tabelnya akan otomatis terbuat.

3. **Menempatkan File Proyek:**
   * Salin seluruh isi folder proyek ini ke dalam folder `htdocs` XAMPP, contoh:
     ```text
     C:\xampp\htdocs\sp_kulit\
     ```

4. **Jalankan Aplikasi:**
   * Buka browser dan kunjungi alamat:
     ```text
     http://localhost/sp_kulit/
     ```
   * Aplikasi web PHP yang terhubung langsung ke MySQL siap digunakan!

---

## 🚀 Cara Publish ke Vercel (Online 100% Gratis)

Aplikasi ini telah disediakan dalam versi **`index.html` mandiri (Static App)** yang siap di-deploy langsung ke Vercel tanpa perlu setup server backend:

### Cara 1: Deploy Otomatis via GitHub (Paling Mudah)
1. Repository proyek ini sudah terhubung ke GitHub:  
   👉 **[https://github.com/FauzilAZH/dermascan-pakar](https://github.com/FauzilAZH/dermascan-pakar)**
2. Buka situs **[https://vercel.com](https://vercel.com)** dan klik **Log In** dengan akun GitHub Anda (`FauzilAZH`).
3. Pada halaman dashboard Vercel, klik tombol **"Add New..."** &rarr; pilih **"Project"**.
4. Cari dan pilih repository **`dermascan-pakar`**, lalu klik tombol **"Import"**.
5. Pada bagian konfigurasi, biarkan pengaturan default (*Framework Preset: Other*).
6. Klik tombol biru **"Deploy"**.
7. Tunggu sekitar 20–30 detik, web Anda akan langsung online dan memiliki alamat resmi seperti:
   ```text
   https://dermascan-pakar.vercel.app
   ```

### Cara 2: Deploy Cepat via Terminal (Vercel CLI)
1. Buka Terminal / PowerShell di folder proyek ini.
2. Jalankan perintah:
   ```bash
   npx vercel
   ```
3. Login dan ikuti instruksi di layar (tekan Enter untuk opsi default).
4. Selesai! Tautan web online Anda akan langsung disalin ke *clipboard*.

---

## 📖 Panduan Cara Menggunakan Aplikasi

1. **Memulai Konsultasi:**
   * Pada menu navigasi atau halaman beranda, pilih menu **Konsultasi** (atau **Mulai Konsultasi**).
2. **Mengisi Data Pasien:**
   * Masukkan **Nama Pasien**, **Usia**, dan pilih **Jenis Kelamin**.
3. **Memilih Gejala:**
   * Gulir daftar gejala atau gunakan kolom **Pencarian Cepat** untuk mencari gejala spesifik (misal: "komedo", "gatal", "bintik").
   * Centang kotak pada gejala yang dialami.
   * Tentukan **Tingkat Keyakinan** Anda pada menu dropdown di samping gejala:
     * *Sangat Yakin (1.0)*
     * *Yakin (0.8)*
     * *Cukup Yakin (0.6)*
     * *Sedikit Yakin (0.4)*
     * *Tidak Yakin (0.2)*
4. **Memproses Diagnosa:**
   * Klik tombol **"Hitung Diagnosa Penyakit"**.
5. **Membaca Hasil:**
   * **Diagnosis Utama:** Menampilkan nama penyakit dengan tingkat keyakinan persentase tertinggi ($CF_{akhir}$).
   * **Solusi & Pencegahan:** Rekomendasi tindakan pengobatan dan pencegahan klinis.
   * **Tabel Rincian Gejala:** Menampilkan perkalian $CF_{pasien} \times CF_{pakar} = CF_{gejala}$.
   * **Ranking Alternatif:** Penyakit lain yang kemungkinan gejalanya menyerupai.
   * **Cetak PDF:** Klik tombol **Cetak / PDF** untuk menyimpan lembar hasil diagnosis.
6. **Melihat Literatur Medis:**
   * Klik menu **Data Artikel** untuk melihat ringkasan penyakit dan membuka tautan jurnal resmi (CDC, NCBI, DermNet, AAD).

---

## 📁 Struktur File Proyek

```text
├── index.html                     # Aplikasi web statis mandiri (siap Vercel)
├── index.php                      # Aplikasi web dinamis PHP terhubung MySQL (XAMPP)
├── koneksi.php                    # Skrip koneksi database MySQL
├── database_sistem_pakar_kulit.sql # File dump database lengkap (15 Penyakit, 68 Gejala, 86 Aturan)
├── basis_pengetahuan_penyakit_kulit.md # Dokumentasi lengkap aturan inferensi & tabel keputusan
├── class_diagram.puml             # Kode PlantUML untuk diagram kelas (UML)
└── README.md                      # Panduan instalasi dan penggunaan aplikasi
```

---

## 👨‍💻 Pengembang & Lisensi
Dikembangkan untuk keperluan akademis mata kuliah **Sistem Pakar (Semester 5)** dengan standar arsitektur perangkat lunak berbasis kaidah inferensi kecerdasan buatan.

