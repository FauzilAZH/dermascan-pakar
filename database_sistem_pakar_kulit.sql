-- ====================================================================
-- Database: `db_sistem_pakar_kulit`
-- Deskripsi: Basis Data Sistem Pakar Diagnosa Penyakit Kulit
-- Metode: Forward Chaining dan Certainty Factor (CF)
-- Acuan: Jurnal JRAMI Vol 07 No 04 Tahun 2026 (Hal. 804–813)
-- Entitas: 15 Penyakit (P01 - P15) dan 68 Gejala (G01 - G68)
-- ====================================================================

-- Baris di bawah ini dikomentari agar kompatibel di hosting online (InfinityFree / cPanel):
-- CREATE DATABASE IF NOT EXISTS `db_sistem_pakar_kulit` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `db_sistem_pakar_kulit`;

-- --------------------------------------------------------------------
-- 1. Struktur Tabel `users`
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `detail_konsultasi`;
DROP TABLE IF EXISTS `hasil_konsultasi`;
DROP TABLE IF EXISTS `konsultasi`;
DROP TABLE IF EXISTS `rule`;
DROP TABLE IF EXISTS `penyakit`;
DROP TABLE IF EXISTS `gejala`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;

CREATE TABLE `users` (
  `id_user` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal Pengguna (Admin & User Dummy)
INSERT INTO `users` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `status`) VALUES
(1, 'Administrator Klinik', 'admin', 'admin123', 'admin', 'aktif'),
(2, 'Pasien Konsultasi', 'pasien', 'pasien123', 'user', 'aktif');

-- --------------------------------------------------------------------
-- 2. Struktur Tabel `penyakit` (15 Penyakit)
-- --------------------------------------------------------------------
CREATE TABLE `penyakit` (
  `id_penyakit` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_penyakit` VARCHAR(10) NOT NULL UNIQUE,
  `nama_penyakit` VARCHAR(100) NOT NULL,
  `kategori` ENUM('Menular', 'Tidak Menular') NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `solusi` TEXT NOT NULL,
  `pencegahan` TEXT NOT NULL,
  `tingkat_keparahan` ENUM('Ringan', 'Sedang', 'Berat') NOT NULL DEFAULT 'Ringan',
  `saran_tindak_lanjut` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT 'default.jpg',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_penyakit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `penyakit` (`id_penyakit`, `kode_penyakit`, `nama_penyakit`, `kategori`, `deskripsi`, `solusi`, `pencegahan`, `tingkat_keparahan`, `saran_tindak_lanjut`) VALUES
(1, 'P01', 'Dermatitis Atopik / Eksim', 'Tidak Menular', 
 'Peradangan kulit kronis kambuhan yang sering disertai rasa gatal intens dan riwayat atopik/alergi dalam keluarga.',
 'Oleskan pelembap emolien tebal secara rutin, hindari sabun berbahan keras, dan gunakan salep kortikosteroid topikal sesuai anjuran dokter.',
 'Hindari pemicu alergi (debu, bulu binatang, suhu dingin/panas berlebih), jangan menggaruk kulit, dan gunakan pakaian longgar berbahan katun.',
 'Sedang', 'Konsultasikan ke dokter spesialis kulit bila timbul luka infeksi sekunder atau gatal tidak mereda dengan pelembap.'),

(2, 'P02', 'Tinea / Kurap', 'Menular',
 'Infeksi jamur dermatofita pada kulit yang ditandai dengan bercak kemerahan berbentuk cincin tepi meninggi aktif dan bagian tengah menyembuh.',
 'Gunakan krim antijamur topikal (seperti Mikonazol, Klotrimazol, atau Terbinafin) secara teratur selama 2-4 minggu.',
 'Jaga kulit tetap bersih dan kering, segera ganti pakaian basah/berkeringat, serta jangan bertukar handuk dan pakaian pribadi.',
 'Ringan', 'Bila lesi meluas ke area tubuh lain atau tidak membaik dalam 2 minggu, periksakan diri untuk evaluasi antijamur oral.'),

(3, 'P03', 'Dermatitis Seboroik', 'Tidak Menular',
 'Peradangan kulit pada area yang kaya kelenjar sebasea (kulit kepala, alis, dada atas) yang ditandai sisik berwarna kuning berminyak dan ketombe.',
 'Gunakan sampo obat berbahan ketokonazol, selenium sulfida, atau zinc pyrithione. Gunakan krim antiinflamasi ringan pada area wajah.',
 'Cuci kulit kepala secara teratur, hindari produk perawatan rambut berbahan minyak berat, dan kelola stres fisik maupun psikis.',
 'Ringan', 'Bila kerak semakin tebal atau timbul bau menyengat, segera temui dokter spesialis kulit.'),

(4, 'P04', 'Prurigo', 'Tidak Menular',
 'Kelainan kulit kronis berupa bintil-bintil (nodul) sangat gatal yang diperparah oleh kebiasaan menggaruk terus-menerus hingga luka berdarah.',
 'Oleskan salep kortikosteroid potensi tinggi, gunakan obat antihistamin oral pereda gatal, dan tutup lesi dengan perban agar tidak digaruk.',
 'Gunting kuku hingga pendek, hindari gigitan serangga dengan losion penolak nyamuk, dan kendalikan kecemasan.',
 'Sedang', 'Pemeriksaan laboratorium lanjutan disarankan untuk mencari penyakit sistemik yang mungkin memicu rasa gatal kronis.'),

(5, 'P05', 'Impetigo Krustosa', 'Menular',
 'Infeksi bakteri piogenik superfisial pada kulit (sering di wajah/sekitar hidung) yang ditandai bercak kerak kuning kecokelatan seperti madu.',
 'Kompres lembut kerak dengan air hangat agar terlepas perlahan, lalu oleskan salep antibiotik seperti Mupirosin atau Asam Fusidat.',
 'Jaga kebersihan diri, cuci tangan dengan sabun antiseptik, serta pisahkan handuk dan seprai penderita dari orang lain.',
 'Sedang', 'Bila lepuhan dan kerak bertambah luas dengan cepat, segera periksakan ke klinik untuk mendapatkan resep antibiotik minum.'),

(6, 'P06', 'Herpes Zoster', 'Menular',
 'Penyakit akibat reaktivasi virus Varicella Zoster yang menimbulkan vesikel/lenting cairan bergerombol pada satu sisi tubuh disertai nyeri terbakar.',
 'Konsumsi obat antivirus oral (seperti Asiklovir atau Valasiklovir) dalam 72 jam pertama sejak lenting muncul, serta analgesik pereda nyeri.',
 'Jaga vesikel tetap bersih dan kering, hindari memecahkan lenting, dan istirahat total untuk memulihkan daya tahan tubuh.',
 'Sedang', 'Konsultasi segera ke dokter spesialis saraf atau kulit bila lenting muncul di dekat mata atau nyeri menetap (neuralgia pasca herpes).'),

(7, 'P07', 'Urtikaria / Biduran', 'Tidak Menular',
 'Reaksi vaskular kulit yang ditandai dengan bentol pucat hingga kemerahan timbul mendadak dan dapat hilang dalam beberapa jam atau hari.',
 'Konsumsi obat antihistamin sesuai dosis dokter, kompres dingin pada area bentol, dan hindari paparan udara panas.',
 'Catat dan hindari alergen pemicu (makanan laut, kacang, obat tertentu, suhu dingin, atau stres emosional).',
 'Ringan', 'Bila biduran disertai sesak napas, bibir bengkak, atau pusing hebat (syok anafilaksis), segera kunjungi IGD rumah sakit.'),

(8, 'P08', 'Skabies / Kudis', 'Menular',
 'Infestasi tungau parasit Sarcoptes scabiei yang menyebabkan gatal luar biasa pada malam hari di sela jari, lipatan perut, dan kemaluan.',
 'Oleskan krim Permetrin 5% ke seluruh tubuh dari leher ke bawah, biarkan selama 8-12 jam, kemudian bilas bersih. Ulangi 1 minggu kemudian.',
 'Obati seluruh anggota keluarga atau teman sekamar secara serempak, rendam semua pakaian dan seprai dalam air panas minimal 60 derajat C.',
 'Sedang', 'Pastikan kontrol ulang setelah 2 minggu untuk memastikan tidak ada reinfestasi tungau skabies.'),

(9, 'P09', 'Psoriasis', 'Tidak Menular',
 'Penyakit autoimun kronis yang mempercepat proliferasi sel kulit sehingga menimbulkan plak merah dengan lapisan sisik tebal seperti mika.',
 'Oleskan salep kortikosteroid, analog vitamin D, atau pelembap berbahan asam salisilat. Fototerapi dapat dilakukan pada kasus luas.',
 'Hindari cedera atau goresan pada kulit, kendalikan stres pikiran, hindari rokok dan alkohol yang dapat memicu kekambuhan.',
 'Sedang', 'Lakukan konsultasi berkala dengan dokter spesialis kulit untuk pemantauan jangka panjang dan terapi sistemik bila diperlukan.'),

(10, 'P10', 'Varisela / Cacar Air', 'Menular',
 'Infeksi virus Varicella Zoster yang sangat menular, ditandai bintik berair menyebar ke seluruh tubuh disertai demam dan lemas.',
 'Istirahat tirah baring, konsumsi penurun demam (Parasetamol), oleskan losion kalamin untuk meredakan gatal, dan penuhi asupan cairan.',
 'Lakukan isolasi mandiri hingga seluruh lepuhan mengering menjadi keropeng, dan dapatkan imunisasi vaksin varisela.',
 'Sedang', 'Segera bawa ke fasilitas kesehatan jika demam tinggi tidak turun lebih dari 3 hari atau terjadi sesak napas.'),

(11, 'P11', 'Acne Vulgaris (Jerawat)', 'Tidak Menular',
 'Peradangan kronis pada folikel pilosebasea yang ditandai dengan komedo, benjolan merah (papula), bintil bernanah (pustula), dan nodul nyeri.',
 'Gunakan pembersih wajah mengandung asam salisilat atau benzoil peroksida, serta oleskan retinoid topikal sesuai petunjuk dokter.',
 'Bersihkan wajah teratur 2 kali sehari, gunakan produk berlabel non-comedogenic, jangan memencet jerawat, dan kurangi makanan tinggi gula.',
 'Ringan', 'Konsultasikan ke dokter spesialis kulit untuk penanganan jerawat tipe nodul kistik agar tidak menimbulkan bopeng parut permanen.'),

(12, 'P12', 'Vitiligo (Bercak Putih)', 'Tidak Menular',
 'Kelainan kulit jangka panjang di mana melanosit rusak sehingga kehilangan pigmen kulit, menimbulkan bercak putih susu berbatas tegas.',
 'Terapi topikal kortikosteroid potensi kuat, salep inhibitor kalsineurin, serta terapi sinar ultraviolet (fototerapi UVB pita sempit).',
 'Gunakan tabir surya SPF 30+ berspektrum luas untuk melindungi area putih dari sengatan matahari dan hindari trauma fisik kulit.',
 'Sedang', 'Pemeriksaan rutin disarankan untuk memantau progresivitas lesi dan memeriksa kemungkinan gangguan tiroid/autoimun penyerta.'),

(13, 'P13', 'Cellulitis (Selulitis)', 'Menular',
 'Infeksi bakteri akut pada lapisan dermis dan jaringan subkutan yang menyebabkan kemerahan luas, bengkak, teraba panas, dan nyeri berdenyut.',
 'Pemberian antibiotik oral atau infus intravena secara cepat dan tuntas dari dokter, serta meninggikan posisi kaki/tangan yang bengkak.',
 'Rawat dan bersihkan luka goresan atau gigitan serangga sekecil apa pun dengan cairan antiseptik, dan obati infeksi jamur kutu air.',
 'Berat', 'Merupakan kondisi darurat medis; segera bawa ke rumah sakit jika kemerahan menjalar dengan cepat atau demam tinggi menggigil.'),

(14, 'P14', 'Bisul (Furunkel / Karbunkel)', 'Menular',
 'Infeksi bakteri Staphylococcus aureus yang menimbulkan benjolan merah keras bernanah dan sangat nyeri berdenyut di sekitar pori/akar rambut.',
 'Kompres hangat 3-4 kali sehari selama 15 menit untuk mempercepat pengeluaran nanah secara alami, oleskan salep antibiotik topikal.',
 'Mandi teratur menggunakan sabun antibakteri, hindari memencet atau menusuk bisul secara paksa, dan jangan berbagi pakaian/handuk pribadi.',
 'Sedang', 'Kunjungi dokter bila bisul tumbuh di area segitiga wajah, berukuran sangat besar (karbunkel), atau disertai demam.'),

(15, 'P15', 'Keloid', 'Tidak Menular',
 'Pertumbuhan jaringan parut fibrosa tebal yang menonjol di atas permukaan kulit dan meluas melampaui batas luka asli.',
 'Penyuntikan kortikosteroid intralesi (Triamsinolon asetonid), penggunaan lembaran gel silikon penekan, bedah beku (krioterapi), atau laser.',
 'Hindari tindakan invasif yang tidak esensial seperti tindik telinga, tato, atau operasi kosmetik jika memiliki riwayat bakat keloid.',
 'Ringan', 'Konsultasikan ke dokter spesialis kulit atau bedah plastik untuk kombinasi terapi perataan parut yang optimal.');

-- --------------------------------------------------------------------
-- 3. Struktur Tabel `gejala` (68 Gejala Fix)
-- --------------------------------------------------------------------
CREATE TABLE `gejala` (
  `id_gejala` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_gejala` VARCHAR(10) NOT NULL UNIQUE,
  `nama_gejala` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_gejala`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `gejala` (`id_gejala`, `kode_gejala`, `nama_gejala`) VALUES
(1, 'G01', 'Terasa gatal'),
(2, 'G02', 'Kulit tampak kering'),
(3, 'G03', 'Gatal bertambah saat berkeringat dan udara panas'),
(4, 'G04', 'Bercak kemerahan pada wajah, leher, lipatan tubuh, tangan, atau kaki'),
(5, 'G05', 'Memiliki riwayat alergi, seperti gatal, asma, atau bersin pada pagi hari'),
(6, 'G06', 'Memiliki riwayat alergi pada keluarga'),
(7, 'G07', 'Bercak kemerahan berbentuk cincin, berbatas tegas, dengan bagian tengah sesuai warna kulit atau keputihan'),
(8, 'G08', 'Kulit tampak bersisik halus'),
(9, 'G09', 'Memiliki pekerjaan yang sering terpapar air'),
(10, 'G10', 'Memiliki aktivitas yang sering menimbulkan keringat'),
(11, 'G11', 'Kulit kemerahan disertai sisik dan ketombe'),
(12, 'G12', 'Terdapat sisik berwarna kuning berminyak'),
(13, 'G13', 'Berbau tidak sedap'),
(14, 'G14', 'Keluhan terdapat pada kulit kepala, wajah terutama alis, dada bagian atas, atau lipatan tubuh'),
(15, 'G15', 'Gatal menetap hingga berbulan-bulan'),
(16, 'G16', 'Gatal mengganggu aktivitas sehari-hari'),
(17, 'G17', 'Bintik kemerahan hingga kehitaman, terkadang terdapat cairan putih atau kekuningan di atasnya'),
(18, 'G18', 'Kebiasaan terus menggaruk hingga menimbulkan darah dan luka'),
(19, 'G19', 'Bercak kerak kuning kecoklatan dan kental seperti madu, dapat disertai rasa perih'),
(20, 'G20', 'Bercak menyebar dalam waktu singkat saat disentuh atau digaruk'),
(21, 'G21', 'Sering terdapat di wajah, sekitar mulut dan lubang hidung, dan dapat menyebar lebih luas'),
(22, 'G22', 'Gatal semakin hebat seperti tersengat, panas, terbakar, atau tertusuk'),
(23, 'G23', 'Bintik kemerahan berisi cairan yang bergerombol pada satu sisi atau bagian tubuh tertentu'),
(24, 'G24', 'Memiliki riwayat varisela atau cacar air'),
(25, 'G25', 'Dapat disertai demam, lemas, nyeri sendi, nyeri kepala, serta rasa panas atau terbakar pada bintik'),
(26, 'G26', 'Bentol pucat hingga kemerahan yang terjadi singkat dan dapat hilang dalam beberapa jam atau hari'),
(27, 'G27', 'Kulit lebih sensitif terhadap sentuhan ringan'),
(28, 'G28', 'Gejala muncul kembali setelah sebelumnya sembuh atau kambuh'),
(29, 'G29', 'Bintik kemerahan hingga kehitaman dengan cairan putih atau kekuningan dan gatal memberat pada malam hari'),
(30, 'G30', 'Sulit tidur karena gatal'),
(31, 'G31', 'Keluhan mengenai satu kelompok, seperti keluarga serumah, pondok pesantren, panti asuhan, atau pengungsian'),
(32, 'G32', 'Keluhan terdapat pada lipatan ketiak, payudara, perut, paha, sela jari, bokong, atau kemaluan'),
(33, 'G33', 'Gatal hilang timbul dan dapat disertai rasa panas'),
(34, 'G34', 'Gambaran kemerahan berbentuk bulat atau oval'),
(35, 'G35', 'Sisik tebal dan transparan seperti lapisan mika'),
(36, 'G36', 'Terdapat riwayat keluarga dengan keluhan atau penyakit yang sama'),
(37, 'G37', 'Bintik kemerahan berisi cairan yang dapat menyebar hampir ke seluruh tubuh'),
(38, 'G38', 'Dapat disertai demam, lemas, nyeri sendi, dan nyeri kepala'),
(39, 'G39', 'Dalam beberapa jam berubah menjadi lepuhan berisi cairan'),
(40, 'G40', 'Lepuhan dapat pecah dan menjadi luka'),
(41, 'G41', 'Lepuhan mengering dan membentuk keropeng'),
(42, 'G42', 'Biasanya sembuh dalam 1 sampai 2 minggu'),
(43, 'G43', 'Terdapat komedo terbuka (blackhead)'),
(44, 'G44', 'Terdapat komedo tertutup (whitehead)'),
(45, 'G45', 'Muncul benjolan kecil kemerahan (papula)'),
(46, 'G46', 'Muncul bintil berisi nanah (pustula)'),
(47, 'G47', 'Terdapat benjolan besar dan nyeri (nodul)'),
(48, 'G48', 'Keluhan kulit muncul di wajah, dada, atau punggung'),
(49, 'G49', 'Kulit tampak berminyak'),
(50, 'G50', 'Muncul bercak putih seperti susu'),
(51, 'G51', 'Warna kulit pada bercak kehilangan pigmen'),
(52, 'G52', 'Batas bercak putih terlihat jelas'),
(53, 'G53', 'Bercak putih muncul pada beberapa bagian tubuh'),
(54, 'G54', 'Bercak putih muncul di wajah, tangan, jari, ketiak, atau lipatan tubuh'),
(55, 'G55', 'Bercak putih umumnya tidak terasa gatal'),
(56, 'G56', 'Kulit mengalami kemerahan pada area tertentu'),
(57, 'G57', 'Kulit terasa panas ketika disentuh'),
(58, 'G58', 'Area kulit mengalami pembengkakan'),
(59, 'G59', 'Area kulit yang terkena terasa nyeri'),
(60, 'G60', 'Mengalami menggigil atau tubuh terasa tidak enak'),
(61, 'G61', 'Muncul benjolan merah yang keras dan semakin membesar'),
(62, 'G62', 'Benjolan berisi nanah putih atau kekuningan'),
(63, 'G63', 'Benjolan dapat pecah dan mengeluarkan cairan'),
(64, 'G64', 'Benjolan terasa nyeri atau berdenyut, terutama saat disentuh'),
(65, 'G65', 'Muncul jaringan parut yang menonjol dan keras'),
(66, 'G66', 'Jaringan parut tumbuh melebihi batas luka awal dan tidak mengecil sendiri'),
(67, 'G67', 'Jaringan parut muncul setelah luka, jerawat, tindik, atau operasi'),
(68, 'G68', 'Area jaringan parut terasa gatal');

-- --------------------------------------------------------------------
-- 4. Struktur Tabel `rule` (Basis Pengetahuan & Bobot CF Pakar)
-- --------------------------------------------------------------------
CREATE TABLE `rule` (
  `id_rule` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_basis` VARCHAR(20) NOT NULL,
  `id_penyakit` INT(11) NOT NULL,
  `id_gejala` INT(11) NOT NULL,
  `cf_pakar` DECIMAL(3,2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rule`),
  KEY `fk_rule_penyakit` (`id_penyakit`),
  KEY `fk_rule_gejala` (`id_gejala`),
  CONSTRAINT `fk_rule_penyakit` FOREIGN KEY (`id_penyakit`) REFERENCES `penyakit` (`id_penyakit`) ON DELETE CASCADE,
  CONSTRAINT `fk_rule_gejala` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Rule dan Bobot Pakar untuk 15 Penyakit
INSERT INTO `rule` (`kode_basis`, `id_penyakit`, `id_gejala`, `cf_pakar`) VALUES
-- P01: Dermatitis Atopik / Eksim
('R01', 1, 1, 0.80),   -- G01 Terasa gatal
('R01', 1, 2, 0.80),   -- G02 Kulit kering
('R01', 1, 3, 0.60),   -- G03 Gatal bertambah saat berkeringat
('R01', 1, 4, 0.80),   -- G04 Kemerahan wajah, lipatan
('R01', 1, 5, 0.80),   -- G05 Riwayat alergi pribadi
('R01', 1, 6, 0.80),   -- G06 Riwayat alergi keluarga

-- P02: Tinea / Kurap
('R02', 2, 1, 0.80),   -- G01 Terasa gatal
('R02', 2, 2, 0.40),   -- G02 Kulit kering
('R02', 2, 3, 0.80),   -- G03 Gatal bertambah saat berkeringat
('R02', 2, 7, 1.00),   -- G07 Bercak cincin tepi aktif
('R02', 2, 8, 0.80),   -- G08 Bersisik halus
('R02', 2, 9, 0.60),   -- G09 Terpapar air
('R02', 2, 10, 0.60),  -- G10 Aktivitas berkeringat

-- P03: Dermatitis Seboroik
('R03', 3, 1, 0.60),   -- G01 Terasa gatal
('R03', 3, 2, 0.40),   -- G02 Kulit kering
('R03', 3, 11, 0.80),  -- G11 Kemerahan & ketombe
('R03', 3, 12, 1.00),  -- G12 Sisik kuning berminyak
('R03', 3, 13, 0.60),  -- G13 Bau tidak sedap
('R03', 3, 14, 0.80),  -- G14 Kepala, alis, dada atas

-- P04: Prurigo
('R04', 4, 1, 0.80),   -- G01 Terasa gatal
('R04', 4, 15, 0.80),  -- G15 Gatal berbulan-bulan
('R04', 4, 16, 0.80),  -- G16 Mengganggu aktivitas
('R04', 4, 17, 0.80),  -- G17 Bintik kemerahan kehitaman
('R04', 4, 18, 0.80),  -- G18 Garuk hingga berdarah

-- P05: Impetigo Krustosa
('R05', 5, 1, 0.60),   -- G01 Terasa gatal
('R05', 5, 19, 1.00),  -- G19 Kerak kuning madu
('R05', 5, 20, 0.80),  -- G20 Menyebar cepat disentuh
('R05', 5, 21, 0.80),  -- G21 Sekitar mulut & hidung

-- P06: Herpes Zoster
('R06', 6, 1, 0.40),   -- G01 Terasa gatal
('R06', 6, 22, 0.80),  -- G22 Rasa panas terbakar
('R06', 6, 23, 1.00),  -- G23 Bintik berair sekelompok satu sisi
('R06', 6, 24, 0.80),  -- G24 Riwayat cacar air
('R06', 6, 25, 0.80),  -- G25 Demam, lemas, nyeri sendi

-- P07: Urtikaria / Biduran
('R07', 7, 1, 0.80),   -- G01 Terasa gatal
('R07', 7, 22, 0.60),  -- G22 Rasa tersengat/panas
('R07', 7, 26, 1.00),  -- G26 Bentol hilang timbul
('R07', 7, 27, 0.60),  -- G27 Sensitif sentuhan
('R07', 7, 28, 0.60),  -- G28 Sering kambuh

-- P08: Skabies / Kudis
('R08', 8, 1, 0.80),   -- G01 Terasa gatal
('R08', 8, 29, 0.80),  -- G29 Bintik berair malam hari
('R08', 8, 30, 0.80),  -- G30 Sulit tidur karena gatal
('R08', 8, 31, 1.00),  -- G31 Mengenai satu kelompok
('R08', 8, 32, 0.80),  -- G32 Lipatan sela jari, kemaluan

-- P09: Psoriasis
('R09', 9, 1, 0.60),   -- G01 Terasa gatal
('R09', 9, 33, 0.60),  -- G33 Gatal hilang timbul panas
('R09', 9, 34, 0.60),  -- G34 Bercak bulat atau oval
('R09', 9, 35, 1.00),  -- G35 Sisik tebal lapisan mika
('R09', 9, 36, 0.80),  -- G36 Riwayat keluarga sama

-- P10: Varisela / Cacar Air
('R10', 10, 1, 0.80),  -- G01 Terasa gatal
('R10', 10, 37, 1.00), -- G37 Bintik berair sebar seluruh tubuh
('R10', 10, 38, 0.80), -- G38 Demam, lemas, nyeri kepala
('R10', 10, 39, 0.80), -- G39 Lepuhan berisi cairan
('R10', 10, 40, 0.60), -- G40 Lepuhan pecah
('R10', 10, 41, 0.60), -- G41 Mengering jadi keropeng
('R10', 10, 42, 0.60), -- G42 Sembuh 1-2 minggu

-- P11: Acne Vulgaris (Jerawat)
('R11', 11, 43, 1.00), -- G43 Komedo terbuka (blackhead)
('R11', 11, 44, 1.00), -- G44 Komedo tertutup (whitehead)
('R11', 11, 45, 0.80), -- G45 Benjolan papula
('R11', 11, 46, 0.80), -- G46 Pustula bernanah
('R11', 11, 47, 0.80), -- G47 Nodul besar nyeri
('R11', 11, 48, 0.80), -- G48 Lokasi wajah, dada, punggung
('R11', 11, 49, 0.80), -- G49 Kulit berminyak

-- P12: Vitiligo (Bercak Putih)
('R12', 12, 50, 1.00), -- G50 Bercak putih seperti susu
('R12', 12, 51, 1.00), -- G51 Kehilangan pigmen
('R12', 12, 52, 0.80), -- G52 Batas bercak jelas
('R12', 12, 53, 0.80), -- G53 Bercak di beberapa bagian tubuh
('R12', 12, 54, 0.80), -- G54 Lokasi wajah, tangan, ketiak, lipatan
('R12', 12, 55, 0.60), -- G55 Umumnya tidak gatal

-- P13: Cellulitis (Selulitis)
('R13', 13, 38, 0.80), -- G38 Demam, lemas, nyeri sendi
('R13', 13, 56, 0.80), -- G56 Kemerahan area tertentu
('R13', 13, 57, 0.80), -- G57 Kulit panas disentuh
('R13', 13, 58, 1.00), -- G58 Kulit membengkak (edema)
('R13', 13, 59, 0.80), -- G59 Terasa nyeri
('R13', 13, 60, 0.80), -- G60 Menggigil / tidak enak badan

-- P14: Bisul (Furunkel / Karbunkel)
('R14', 14, 56, 0.60), -- G56 Kemerahan area tertentu
('R14', 14, 57, 0.60), -- G57 Kulit panas disentuh
('R14', 14, 58, 0.80), -- G58 Kulit bengkak
('R14', 14, 61, 1.00), -- G61 Benjolan merah keras membesar
('R14', 14, 62, 1.00), -- G62 Benjolan bernanah putih/kuning
('R14', 14, 63, 0.80), -- G63 Pecah keluar cairan
('R14', 14, 64, 0.80), -- G64 Nyeri berdenyut saat disentuh

-- P15: Keloid
('R15', 15, 1, 0.60),  -- G01 Terasa gatal
('R15', 15, 65, 1.00), -- G65 Parut menonjol dan keras
('R15', 15, 66, 1.00), -- G66 Parut lebihi luka awal & tak mengecil
('R15', 15, 67, 0.80), -- G67 Muncul setelah luka/jerawat/operasi
('R15', 15, 68, 0.80); -- G68 Area parut terasa gatal

-- --------------------------------------------------------------------
-- 5. Struktur Tabel Transaksi Konsultasi
-- --------------------------------------------------------------------
CREATE TABLE `konsultasi` (
  `id_konsultasi` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `nama_pasien` VARCHAR(100) DEFAULT NULL,
  `usia` INT(3) NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
  `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `hasil_penyakit` VARCHAR(100) DEFAULT NULL,
  `nilai_cf` DECIMAL(5,4) DEFAULT NULL,
  `persentase` VARCHAR(10) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_konsultasi`),
  KEY `fk_konsultasi_user` (`id_user`),
  CONSTRAINT `fk_konsultasi_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `detail_konsultasi` (
  `id_detail` INT(11) NOT NULL AUTO_INCREMENT,
  `id_konsultasi` INT(11) NOT NULL,
  `id_gejala` INT(11) NOT NULL,
  `cf_user` DECIMAL(3,2) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `fk_detail_konsultasi` (`id_konsultasi`),
  KEY `fk_detail_gejala` (`id_gejala`),
  CONSTRAINT `fk_detail_konsultasi` FOREIGN KEY (`id_konsultasi`) REFERENCES `konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_gejala` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `hasil_konsultasi` (
  `id_hasil` INT(11) NOT NULL AUTO_INCREMENT,
  `id_konsultasi` INT(11) NOT NULL,
  `id_penyakit` INT(11) NOT NULL,
  `nilai_cf` DECIMAL(5,4) NOT NULL,
  `persentase` DECIMAL(5,2) NOT NULL,
  `ranking` INT(3) NOT NULL,
  PRIMARY KEY (`id_hasil`),
  KEY `fk_hasil_konsultasi` (`id_konsultasi`),
  KEY `fk_hasil_penyakit` (`id_penyakit`),
  CONSTRAINT `fk_hasil_konsultasi` FOREIGN KEY (`id_konsultasi`) REFERENCES `konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  CONSTRAINT `fk_hasil_penyakit` FOREIGN KEY (`id_penyakit`) REFERENCES `penyakit` (`id_penyakit`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pengaturan` (
  `id_pengaturan` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_aplikasi` VARCHAR(100) NOT NULL DEFAULT 'Sistem Pakar Diagnosa Penyakit Kulit',
  `nama_instansi` VARCHAR(100) NOT NULL DEFAULT 'Klinik Restu Ibu',
  `logo` VARCHAR(255) DEFAULT 'logo.png',
  `disclaimer_text` TEXT DEFAULT NULL,
  `footer_text` VARCHAR(255) DEFAULT 'Sistem Pakar Forward Chaining & Certainty Factor',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengaturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengaturan` (`id_pengaturan`, `nama_aplikasi`, `nama_instansi`, `disclaimer_text`, `footer_text`) VALUES
(1, 'Sistem Pakar Diagnosa Penyakit Kulit', 'Klinik Restu Ibu', 'Hasil diagnosis sistem pakar ini merupakan skrining awal pendukung keputusan klinis dan tidak menggantikan pemeriksaan langsung oleh dokter atau tenaga medis spesialis.', 'Copyright 2026 - Sistem Pakar Diagnosa Penyakit Kulit');

