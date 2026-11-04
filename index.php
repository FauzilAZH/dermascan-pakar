<?php
// Hubungkan langsung ke Database MySQL XAMPP
require_once 'koneksi.php';

// Tentukan halaman aktif (default: dashboard atau riwayat)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Proses Diagnosa jika form disubmit
$diagnosis_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_diagnosa'])) {
    $nama_pasien   = mysqli_real_escape_string($koneksi, $_POST['nama_pasien']);
    $usia          = intval($_POST['usia']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $gejala_pilih  = isset($_POST['gejala']) ? $_POST['gejala'] : [];
    $cf_user_input = isset($_POST['cf_user']) ? $_POST['cf_user'] : [];

    if (!empty($gejala_pilih)) {
        // Ambil semua aturan dari database
        $q_rules = mysqli_query($koneksi, "
            SELECT r.*, p.nama_penyakit, p.kode_penyakit, p.kategori, p.deskripsi, p.solusi, p.pencegahan, g.nama_gejala, g.kode_gejala 
            FROM rule r
            JOIN penyakit p ON r.id_penyakit = p.id_penyakit
            JOIN gejala g ON r.id_gejala = g.id_gejala
        ");

        $penyakit_cf = [];
        $penyakit_gejala = [];
        $penyakit_info = [];

        while ($r = mysqli_fetch_assoc($q_rules)) {
            $p_code = $r['kode_penyakit'];
            $g_id   = $r['id_gejala'];
            $cf_pakar = floatval($r['cf_pakar']);

            if (!isset($penyakit_info[$p_code])) {
                $penyakit_info[$p_code] = $r;
                $penyakit_cf[$p_code] = null;
                $penyakit_gejala[$p_code] = [];
            }

            // Jika gejala ini dipilih oleh user
            if (in_array($g_id, $gejala_pilih)) {
                $cf_u = floatval($cf_user_input[$g_id]);
                $cf_gejala = $cf_u * $cf_pakar; // CF = CF_User * CF_Pakar

                $penyakit_gejala[$p_code][] = [
                    'kode_gejala' => $r['kode_gejala'],
                    'nama_gejala' => $r['nama_gejala'],
                    'cf_user'     => $cf_u,
                    'cf_pakar'    => $cf_pakar,
                    'cf_gejala'   => $cf_gejala
                ];

                // Kombinasi Certainty Factor: CF_combine = CF1 + CF2 * (1 - CF1)
                if ($penyakit_cf[$p_code] === null) {
                    $penyakit_cf[$p_code] = $cf_gejala;
                } else {
                    $penyakit_cf[$p_code] = $penyakit_cf[$p_code] + ($cf_gejala * (1 - $penyakit_cf[$p_code]));
                }
            }
        }

        // Urutkan penyakit berdasarkan CF tertinggi
        $hasil_ranking = [];
        foreach ($penyakit_cf as $p_code => $cf_val) {
            if ($cf_val !== null && count($penyakit_gejala[$p_code]) > 0) {
                $hasil_ranking[] = [
                    'kode'       => $p_code,
                    'nama'       => $penyakit_info[$p_code]['nama_penyakit'],
                    'kategori'   => $penyakit_info[$p_code]['kategori'],
                    'deskripsi'  => $penyakit_info[$p_code]['deskripsi'],
                    'solusi'     => $penyakit_info[$p_code]['solusi'],
                    'pencegahan' => $penyakit_info[$p_code]['pencegahan'],
                    'cf'         => $cf_val,
                    'persen'     => round($cf_val * 100, 2),
                    'gejala'     => $penyakit_gejala[$p_code]
                ];
            }
        }

        usort($hasil_ranking, function ($a, $b) {
            return $b['cf'] <=> $a['cf'];
        });

        if (!empty($hasil_ranking)) {
            $top = $hasil_ranking[0];
            $tgl_sekarang = date('Y-m-d H:i:s');
            $cf_top = $top['cf'];
            $persen_top = $top['persen'] . '%';
            $penyakit_top = $top['nama'];

            // Simpan riwayat ke tabel `konsultasi`
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO konsultasi (id_user, nama_pasien, usia, jenis_kelamin, tanggal, hasil_penyakit, nilai_cf, persentase) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt, "sisssds", $nama_pasien, $usia, $jenis_kelamin, $tgl_sekarang, $penyakit_top, $cf_top, $persen_top);
            mysqli_stmt_execute($stmt);

            $diagnosis_result = [
                'pasien'  => $nama_pasien,
                'usia'    => $usia,
                'gender'  => $jenis_kelamin,
                'ranking' => $hasil_ranking
            ];
            $page = 'hasil_diagnosa';
        }
    }
}
// Handler Ubah Password
$pesan_password = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_ubah_password'])) {
    $pass_lama    = $_POST['pass_lama'] ?? '';
    $pass_baru    = $_POST['pass_baru'] ?? '';
    $pass_konfirm = $_POST['pass_konfirm'] ?? '';

    $q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = 'admin' LIMIT 1");
    $data_admin = mysqli_fetch_assoc($q_user);

    if ($data_admin) {
        if ($pass_lama !== $data_admin['password']) {
            $pesan_password = ['type' => 'danger', 'text' => 'Password lama yang Anda masukkan salah!'];
        } elseif (strlen($pass_baru) < 4) {
            $pesan_password = ['type' => 'warning', 'text' => 'Password baru minimal harus 4 karakter!'];
        } elseif ($pass_baru !== $pass_konfirm) {
            $pesan_password = ['type' => 'warning', 'text' => 'Konfirmasi password baru tidak cocok!'];
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE username = 'admin'");
            mysqli_stmt_bind_param($stmt, "s", $pass_baru);
            if (mysqli_stmt_execute($stmt)) {
                $pesan_password = ['type' => 'success', 'text' => 'Password Administrator berhasil diperbarui!'];
            } else {
                $pesan_password = ['type' => 'danger', 'text' => 'Gagal memperbarui password di database.'];
            }
        }
    }
    $page = 'password';
}

// Handler Tambah User Baru
$pesan_user = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
    $user_baru = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $pwd_baru  = mysqli_real_escape_string($koneksi, trim($_POST['password'] ?? ''));
    $role_baru = mysqli_real_escape_string($koneksi, trim($_POST['role'] ?? 'user'));

    if (!empty($nama_baru) && !empty($user_baru) && !empty($pwd_baru)) {
        $ins = mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, username, password, role, status) VALUES ('$nama_baru', '$user_baru', '$pwd_baru', '$role_baru', 'aktif')");
        if ($ins) {
            $pesan_user = ['type' => 'success', 'text' => "Pengguna <b>$user_baru</b> ($nama_baru) berhasil ditambahkan ke database!"];
        } else {
            $pesan_user = ['type' => 'danger', 'text' => "Gagal menambahkan pengguna. Username mungkin sudah terdaftar."];
        }
    }
    $page = 'user';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dermascan Pakar - Sistem Pakar Diagnosa Penyakit Kulit</title>
  <!-- Bootstrap 4 & FontAwesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Chart.js untuk Grafik Donut -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- PALET WARNA MEDIS MODERN (Teal & Clean Slate) -->
  <style>
    :root {
      --primary-teal: #0D9488;      /* Teal Medis */
      --primary-dark: #0F766E;      /* Teal Gelap untuk Brand */
      --secondary-slate: #475569;   /* Slate Slate */
      --bg-mint-soft: #F0FDF4;      /* Off-White / Mint Soft */
      --accent-coral: #F43F5E;      /* Coral Merah Muda Alert */
      --sidebar-dark: #1E293B;      /* Deep Slate Dark */
      --sidebar-active: #0F172A;    /* Slate Tergelap */
    }

    body {
      background-color: var(--bg-mint-soft);
      color: #1E293B;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }

    /* Top Navbar Teal Medis */
    .main-header {
      background-color: var(--primary-teal);
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 18px 0 0;
      color: #fff;
      box-shadow: 0 2px 4px rgba(13, 148, 136, 0.15);
    }
    .main-header .brand-logo {
      background-color: var(--primary-dark);
      width: 230px;
      height: 52px;
      display: flex;
      align-items: center;
      padding-left: 18px;
      font-weight: 700;
      font-size: 1.15rem;
      letter-spacing: 0.5px;
      color: #fff;
      text-decoration: none;
    }
    .main-header .brand-logo i {
      margin-right: 8px;
      font-size: 1.25rem;
    }
    .main-header .nav-left {
      display: flex;
      align-items: center;
    }
    .main-header .sidebar-toggle {
      color: #fff;
      padding: 0 15px;
      cursor: pointer;
      font-size: 1.1rem;
      text-decoration: none;
    }
    .user-badge {
      display: flex;
      align-items: center;
      color: #fff;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .user-avatar {
      background-color: var(--accent-coral);
      color: #fff;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 0.85rem;
      margin-right: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    /* Sidebar Dark Modern */
    .wrapper-container {
      display: flex;
      min-height: calc(100vh - 52px);
    }
    .sidebar {
      width: 230px;
      background-color: var(--sidebar-dark);
      flex-shrink: 0;
      box-shadow: 2px 0 6px rgba(0,0,0,0.06);
    }
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .sidebar-menu li a {
      display: flex;
      align-items: center;
      padding: 12px 18px;
      color: #94A3B8;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 500;
      border-left: 3px solid transparent;
      transition: all 0.2s;
    }
    .sidebar-menu li a:hover, .sidebar-menu li.active a {
      color: #fff;
      background-color: var(--sidebar-active);
      border-left-color: var(--primary-teal);
    }
    .sidebar-menu li a i {
      width: 26px;
      font-size: 0.95rem;
    }

    /* Content Area */
    .content-area {
      flex-grow: 1;
      padding: 22px 25px;
      background-color: var(--bg-mint-soft);
    }
    .page-title {
      color: var(--sidebar-dark);
      font-size: 1.55rem;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    .page-title i {
      color: var(--primary-teal);
      margin-right: 10px;
    }

    /* Table Teal Medis Header */
    .table-teal-header thead th {
      background-color: var(--primary-teal) !important;
      color: #fff !important;
      border: none !important;
      font-weight: 600;
      font-size: 0.88rem;
      padding: 11px 14px;
      vertical-align: middle;
    }
    .table-teal-header tbody td {
      vertical-align: middle;
      font-size: 0.88rem;
      color: #334155;
      padding: 11px 14px;
      border-top: 1px solid #E2E8F0;
      background-color: #fff;
    }
    .table-teal-header tbody tr:nth-of-type(even) td {
      background-color: #F8FAFC;
    }
    .badge-cf {
      background-color: #E2E8F0;
      color: var(--secondary-slate);
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 0.85rem;
      display: inline-block;
    }
    .btn-detail {
      background-color: #F8FAFC;
      border: 1px solid #CBD5E1;
      color: var(--secondary-slate);
      font-size: 0.8rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 4px;
      transition: all 0.2s;
    }
    .btn-detail:hover {
      background-color: var(--primary-teal);
      border-color: var(--primary-teal);
      color: #fff;
      text-decoration: none;
    }

    /* Card Box for Chart */
    .box-chart {
      background: #fff;
      border-radius: 6px;
      border: 1px solid #CBD5E1;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    .box-chart-header {
      background-color: var(--primary-teal);
      color: #fff;
      padding: 10px 16px;
      font-weight: 600;
      font-size: 0.95rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .box-chart-header .tools i {
      margin-left: 8px;
      cursor: pointer;
      opacity: 0.9;
    }
    .box-chart-body {
      padding: 18px 15px;
    }
    .pagination-custom .page-link {
      color: var(--secondary-slate);
      border: 1px solid #CBD5E1;
      font-size: 0.85rem;
      padding: 6px 14px;
    }
    .pagination-custom .page-item.active .page-link {
      background-color: var(--primary-teal);
      border-color: var(--primary-teal);
      color: #fff;
    }

    /* Article Card Styles */
    .card-article {
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 8px;
      transition: all 0.25s ease-in-out;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .card-article:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(13, 148, 136, 0.1);
      border-color: var(--primary-teal);
    }
    .badge-source {
      background-color: #CCFBF1;
      color: var(--primary-dark);
      font-weight: 600;
      font-size: 0.72rem;
      padding: 3px 8px;
      border-radius: 4px;
    }
    .btn-visit {
      background-color: var(--primary-teal);
      color: #fff;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    .btn-visit:hover {
      background-color: var(--primary-dark);
      color: #fff;
      text-decoration: none;
    }
  </style>
</head>
<body>

  <!-- Top Navbar (Teal Medis & Nama Baru: Dermascan Pakar) -->
  <header class="main-header">
    <div class="nav-left">
      <a href="?page=dashboard" class="brand-logo">
        <i class="fas fa-stethoscope"></i> Dermascan Pakar
      </a>
      <a href="#" class="sidebar-toggle" onclick="toggleSidebar(event)">
        <i class="fas fa-bars"></i>
      </a>
    </div>
        <!-- User Profile Dropdown Interaktif -->
    <div class="dropdown">
      <a href="#" class="user-badge dropdown-toggle text-white text-decoration-none" id="dropdownUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="user-avatar">S</span>
        <span>Admin</span>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2" aria-labelledby="dropdownUser" style="border-radius: 8px; min-width: 230px;">
        <div class="px-3 py-2 border-bottom bg-light">
          <small class="text-muted d-block font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">LOGIN SEBAGAI</small>
          <div class="font-weight-bold text-dark">Administrator Klinik</div>
          <span class="badge badge-success mt-1" style="font-size: 0.7rem;">Super Admin</span>
        </div>
        <a class="dropdown-item py-2 small" href="?page=user"><i class="fas fa-users-cog mr-2 text-info"></i> Data Pengguna</a>
        <a class="dropdown-item py-2 small" href="?page=password"><i class="fas fa-key mr-2 text-warning"></i> Ubah Password</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item py-2 small text-secondary" href="javascript:void(0)" onclick="alert('Info Sesi:
Login sebagai: Administrator Klinik (admin)
Peran: Super Admin
Status: Aktif');"><i class="fas fa-id-badge mr-2 text-primary"></i> Detail Profil</a>
      </div>
    </div>
  </header>

  <div class="wrapper-container">
    
    <!-- Sidebar Menu (Slate Dark) -->
    <aside class="sidebar" id="sidebarNav">
      <ul class="sidebar-menu">
        <li class="<?= $page === 'dashboard' ? 'active' : '' ?>">
          <a href="?page=dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li class="<?= $page === 'diagnosa' ? 'active' : '' ?>">
          <a href="?page=diagnosa"><i class="fas fa-user-md"></i> Konsultasi</a>
        </li>
        <li class="<?= $page === 'penyakit' ? 'active' : '' ?>">
          <a href="?page=penyakit"><i class="fas fa-bug"></i> Data Penyakit</a>
        </li>
        <li class="<?= $page === 'gejala' ? 'active' : '' ?>">
          <a href="?page=gejala"><i class="fas fa-syringe"></i> Data Gejala</a>
        </li>
        <li class="<?= $page === 'aturan' ? 'active' : '' ?>">
          <a href="?page=aturan"><i class="fas fa-sync-alt"></i> Data Aturan</a>
        </li>
        <li class="<?= $page === 'artikel' ? 'active' : '' ?>">
          <a href="?page=artikel"><i class="fas fa-book-medical"></i> Data Artikel</a>
        </li>
        <li class="<?= ($page === 'riwayat' || $page === 'hasil_diagnosa') ? 'active' : '' ?>">
          <a href="?page=riwayat"><i class="far fa-clock"></i> Data Riwayat</a>
        </li>
        <li class="<?= $page === 'user' ? 'active' : '' ?>">
          <a href="?page=user"><i class="fas fa-user"></i> Data User</a>
        </li>
        <li class="<?= $page === 'password' ? 'active' : '' ?>">
          <a href="?page=password"><i class="fas fa-lock"></i> Ubah Password</a>
        </li>
      </ul>
    </aside>

    <!-- Main Content Dynamic Views -->
    <main class="content-area">

      <?php if ($page === 'dashboard'): ?>
        <!-- ==================== TAMPILAN DASHBOARD ==================== -->
        <div class="page-title">
          <i class="fas fa-tachometer-alt"></i> Dashboard Sistem Pakar
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <div class="card text-white p-3 shadow-sm border-0" style="background-color: var(--primary-teal); border-radius: 8px;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h2 class="font-weight-bold mb-0">15</h2>
                  <div class="small font-weight-semibold">Data Penyakit</div>
                </div>
                <i class="fas fa-bug fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-white p-3 shadow-sm border-0" style="background-color: #0284C7; border-radius: 8px;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h2 class="font-weight-bold mb-0">68</h2>
                  <div class="small font-weight-semibold">Data Gejala</div>
                </div>
                <i class="fas fa-syringe fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-white p-3 shadow-sm border-0" style="background-color: #EAB308; border-radius: 8px;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h2 class="font-weight-bold mb-0">86</h2>
                  <div class="small font-weight-semibold">Basis Aturan (Rules)</div>
                </div>
                <i class="fas fa-sync-alt fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-white p-3 shadow-sm border-0" style="background-color: var(--accent-coral); border-radius: 8px;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <?php
                  $c_k = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM konsultasi"));
                  ?>
                  <h2 class="font-weight-bold mb-0"><?= $c_k['c'] ?></h2>
                  <div class="small font-weight-semibold">Riwayat Konsultasi</div>
                </div>
                <i class="far fa-clock fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm p-4 text-center mt-3 bg-white" style="border-radius: 10px;">
          <h4 class="font-weight-bold" style="color: var(--primary-teal);">Sistem Pakar Diagnosa Penyakit Kulit (Dermascan Pakar)</h4>
          <p class="text-muted small">Aplikasi ini terhubung langsung ke basis data MySQL `db_sistem_pakar_kulit` secara dinamis dengan metode Forward Chaining dan Certainty Factor.</p>
          <div class="mt-2">
            <a href="?page=diagnosa" class="btn text-white px-4 py-2 font-weight-bold shadow-sm" style="background-color: var(--primary-teal);">
              <i class="fas fa-play mr-1"></i> Mulai Konsultasi Baru
            </a>
            <a href="?page=riwayat" class="btn btn-outline-secondary px-4 py-2 ml-2 font-weight-semibold">
              <i class="far fa-chart-bar mr-1"></i> Lihat Data Riwayat & Grafik
            </a>
          </div>
        </div>

      <?php elseif ($page === 'riwayat'): ?>
        <!-- ==================== TAMPILAN DATA RIWAYAT ==================== -->
        <div class="page-title">
          <i class="far fa-clock"></i> Data Riwayat
        </div>

        <div class="row">
          <!-- Kolom Kiri: Tabel Riwayat dengan Header Teal -->
          <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 6px; overflow: hidden;">
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-teal-header mb-0">
                    <thead>
                      <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 170px;">Tanggal</th>
                        <th>Penyakit</th>
                        <th style="width: 100px;">Nilai CF</th>
                        <th style="width: 90px; text-align: center;">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $q_riwayat = mysqli_query($koneksi, "SELECT * FROM konsultasi ORDER BY tanggal DESC LIMIT 10");
                      $no = 1;
                      $penyakit_counts = [];
                      $total_konsultasi = mysqli_num_rows($q_riwayat);

                      if ($total_konsultasi > 0):
                        while ($row = mysqli_fetch_assoc($q_riwayat)):
                          $p_name = $row['hasil_penyakit'];
                          if (!isset($penyakit_counts[$p_name])) {
                            $penyakit_counts[$p_name] = 0;
                          }
                          $penyakit_counts[$p_name]++;
                      ?>
                        <tr>
                          <td class="text-center font-weight-bold text-muted"><?= $no++ ?></td>
                          <td><?= date('Y-m-d H:i:s', strtotime($row['tanggal'])) ?></td>
                          <td><?= htmlspecialchars($row['hasil_penyakit']) ?></td>
                          <td><span class="badge-cf"><?= number_format($row['nilai_cf'], 2) ?></span></td>
                          <td class="text-center">
                            <button onclick="lihatDetail(<?= $row['id_konsultasi'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pasien'])) ?>', '<?= htmlspecialchars(addslashes($row['hasil_penyakit'])) ?>', '<?= $row['persentase'] ?>', '<?= $row['tanggal'] ?>')" class="btn-detail">
                              <i class="far fa-eye"></i> Detail
                            </button>
                          </td>
                        </tr>
                      <?php endwhile; else: ?>
                        <tr>
                          <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat konsultasi.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
              <ul class="pagination pagination-custom">
                <li class="page-item disabled"><a class="page-link" href="#">Back</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
              </ul>
            </div>
          </div>

          <!-- Kolom Kanan: Card Grafik Donut -->
          <div class="col-lg-4">
            <div class="box-chart">
              <div class="box-chart-header">
                <div><i class="fas fa-chart-pie mr-1"></i> Grafik</div>
                <div class="tools">
                  <i class="fas fa-minus"></i>
                  <i class="fas fa-times"></i>
                </div>
              </div>
              <div class="box-chart-body text-center">
                <div style="position: relative; height: 210px; width: 100%;">
                  <canvas id="grafikDonut"></canvas>
                </div>
                <div id="chartLegend" class="mt-3 text-left pl-3" style="font-size: 0.8rem;"></div>
              </div>
            </div>
          </div>
        </div>

      <?php elseif ($page === 'artikel'): ?>
        <!-- ==================== TAMPILAN DATA ARTIKEL & REFERENSI JURNAL ==================== -->
        <div class="page-title">
          <i class="fas fa-book-medical"></i> Data Artikel & Referensi Medis Penyakit Kulit
        </div>
        <p class="text-muted small mb-4">
          Daftar literatur ilmiah, jurnal kedokteran, dan referensi resmi terpercaya (CDC, StatPearls NCBI, DermNet NZ, AAD, dan JRAMI) yang mendasari basis pengetahuan Dermascan Pakar.
        </p>

        <?php
        // Daftar Artikel & Tautan Referensi Resmi
        $articles = [
          [
            'title'    => 'Sistem Pakar Diagnosa Penyakit Kulit Menggunakan Metode Forward Chaining dan Certainty Factor',
            'disease'  => 'Jurnal Acuan Utama Sistem Pakar',
            'source'   => 'JRAMI (Jurnal Riset dan Aplikasi Mahasiswa Informatika)',
            'badge'    => 'Jurnal Utama',
            'author'   => 'Hera Syakira, Mercy Hermawati, Nurul Frijuniarsi (2026)',
            'summary'  => 'Penelitian rancang bangun sistem pakar di Klinik Restu Ibu dengan menggabungkan pelacakan aturan Forward Chaining dan perhitungan tingkat keyakinan Certainty Factor.',
            'url'      => 'https://journal.lppmunindra.ac.id/index.php/jrami/'
          ],
          [
            'title'    => 'Clinical Guidance for Cellulitis (Diagnosis and Management)',
            'disease'  => 'Cellulitis (Selulitis)',
            'source'   => 'CDC (Centers for Disease Control and Prevention, USA)',
            'badge'    => 'CDC Official',
            'author'   => 'Division of Bacterial Diseases, CDC (2024)',
            'summary'  => 'Pedoman klinis resmi CDC mengenai infeksi bakteri akut dermis dan subkutan oleh Streptococcus grup A / Staphylococcus, tanda edema merah hangat meluas, dan penanganan antibiotik.',
            'url'      => 'https://www.cdc.gov/group-a-strep/hcp/clinical-guidance/cellulitis.html'
          ],
          [
            'title'    => 'Vitiligo: Clinical Presentation, Pathophysiology, and Features',
            'disease'  => 'Vitiligo (Bercak Putih)',
            'source'   => 'DermNet New Zealand Dermatology Resource',
            'badge'    => 'DermNet NZ',
            'author'   => 'DermNet NZ Dermatology Trust (2023)',
            'summary'  => 'Panduan dermatologi lengkap mengenai hilangnya melanosit (depigmentasi), karakteristik makula putih susu tanpa sisik, dan diagnosis diferensial bercak hipopigmentasi.',
            'url'      => 'https://dermnetnz.org/topics/vitiligo'
          ],
          [
            'title'    => 'Acne Vulgaris: Guidelines of Care for the Management of Acne',
            'disease'  => 'Acne Vulgaris (Jerawat)',
            'source'   => 'American Academy of Dermatology (AAD)',
            'badge'    => 'AAD Guidelines',
            'author'   => 'Work Group of American Academy of Dermatology (2024)',
            'summary'  => 'Panduan klinis penatalaksanaan jerawat, klasifikasi komedo terbuka/tertutup, papul, pustul, nodul kistik, serta terapi topikal retinoid dan benzoil peroksida.',
            'url'      => 'https://www.aad.org/member/clinical-quality/guidelines/acne'
          ],
          [
            'title'    => 'Furuncle (Boil) and Carbuncle: Pathology and Clinical Practice',
            'disease'  => 'Bisul (Furunkel / Karbunkel)',
            'source'   => 'NCBI Bookshelf / StatPearls Publishing',
            'badge'    => 'NCBI StatPearls',
            'author'   => 'Leman, S. et al. (2023). Bookshelf ID: NBK513141',
            'summary'  => 'Tinjauan medis infeksi piogenik folikel rambut akibat Staphylococcus aureus, pembentukan mata nanah fluktuatif, kompres hangat, dan penanganan drainase.',
            'url'      => 'https://www.ncbi.nlm.nih.gov/books/NBK513141/'
          ],
          [
            'title'    => 'Keloid and Hypertrophic Scar: Etiology, Risk Factors, and Therapy',
            'disease'  => 'Keloid',
            'source'   => 'NCBI Bookshelf / StatPearls Publishing',
            'badge'    => 'NCBI StatPearls',
            'author'   => 'Robles, D. T. & Berg, D. (2023). Bookshelf ID: NBK535358',
            'summary'  => 'Pembahasan komprehensif mengenai proliferasi fibroblas berlebihan melebihi tepi luka asli paska trauma/operasi, serta terapi injeksi kortikosteroid intralesi.',
            'url'      => 'https://www.ncbi.nlm.nih.gov/books/NBK535358/'
          ],
          [
            'title'    => 'Miliaria (Heat Rash / Prickly Heat): Types, Clinical Presentation and Treatment',
            'disease'  => 'Miliaria (Biang Keringat)',
            'source'   => 'NCBI Bookshelf / StatPearls Publishing',
            'badge'    => 'NCBI StatPearls',
            'author'   => 'Guerra, K. C., Toncar, A., Krishnamurthy, K. (2024). NBK537176',
            'summary'  => 'Penjelasan mekanisme oklusi saluran kelenjar keringat ekrin saat cuaca panas, sensasi tersengat pedih (*prickly heat*), dan resolusi cepat di lingkungan sejuk/ber-AC.',
            'url'      => 'https://www.ncbi.nlm.nih.gov/books/NBK537176/'
          ],
          [
            'title'    => 'Cutaneous Candidiasis: Intertrigo, Satellite Lesions, and Antifungal Management',
            'disease'  => 'Kandidiasis Kutis',
            'source'   => 'NCBI Bookshelf / StatPearls Publishing',
            'badge'    => 'NCBI StatPearls',
            'author'   => 'Singh, G., Beer, J., Tcheung, W. (2024). Bookshelf ID: NBK560624',
            'summary'  => 'Infeksi jamur oportunistik Candida pada area lipatan kulit yang lembap, tanda patognomonis lesi satelit (*satellite pustules*), dan terapi antijamur topikal.',
            'url'      => 'https://www.ncbi.nlm.nih.gov/books/NBK560624/'
          ],
          [
            'title'    => 'Shingles (Herpes Zoster): Clinical Overview and Transmission',
            'disease'  => 'Herpes Zoster',
            'source'   => 'CDC (Centers for Disease Control and Prevention)',
            'badge'    => 'CDC Official',
            'author'   => 'National Center for Immunization and Respiratory Diseases (2024)',
            'summary'  => 'Reaktivasi virus Varicella Zoster laten pada ganglion sensorik dorsal, erupsi vesikel unilateral sesuai dermatom saraf, serta tatalaksana antivirus dini.',
            'url'      => 'https://www.cdc.gov/shingles/about/index.html'
          ],
          [
            'title'    => 'Scabies: Biology, Clinical Features, and Group Treatment Guidelines',
            'disease'  => 'Skabies / Kudis',
            'source'   => 'CDC Parasites & Global Health',
            'badge'    => 'CDC Official',
            'author'   => 'CDC Parasitic Diseases Information (2024)',
            'summary'  => 'Infestasi tungau Sarcoptes scabiei var. hominis, karakteristik terowongan kanalikulus di sela jari, pruritus nokturna hebat, dan terapi Permetrin 5% serempak.',
            'url'      => 'https://www.cdc.gov/parasites/scabies/'
          ],
          [
            'title'    => 'Psoriasis: Clinical Presentation, Plaque Types, and Auspitz Sign',
            'disease'  => 'Psoriasis',
            'source'   => 'DermNet New Zealand Dermatology Resource',
            'badge'    => 'DermNet NZ',
            'author'   => 'DermNet NZ Trust (2023)',
            'summary'  => 'Penyakit inflamasi kulit autoimun yang ditandai dengan plak eritematosa bersisik tebal transparan mika, tanda Auspitz, dan terapi topikal analog vitamin D.',
            'url'      => 'https://dermnetnz.org/topics/psoriasis'
          ],
          [
            'title'    => 'Atopic Dermatitis (Eczema): Guidelines for Diagnosis and Assessment',
            'disease'  => 'Dermatitis Atopik / Eksim',
            'source'   => 'American Academy of Dermatology (AAD)',
            'badge'    => 'AAD Guidelines',
            'author'   => 'Sidbury, R. et al., AAD Guidelines Work Group (2023)',
            'summary'  => 'Panduan diagnosis dermatitis atopik pada anak dan dewasa, riwayat atopi keluarga, penanganan kerusakan sawar stratum korneum dengan emolien teratur.',
            'url'      => 'https://www.aad.org/member/clinical-quality/guidelines/atopic-dermatitis'
          ]
        ];
        ?>

        <div class="row">
          <?php foreach ($articles as $art): ?>
            <div class="col-md-6 mb-4">
              <div class="card-article h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge-source"><?= $art['badge'] ?></span>
                    <span class="small font-weight-bold" style="color: var(--secondary-slate);"><?= $art['disease'] ?></span>
                  </div>
                  <h6 class="font-weight-bold text-dark mb-1" style="font-size: 0.98rem; line-height: 1.4;">
                    <?= $art['title'] ?>
                  </h6>
                  <p class="text-muted small mb-2"><strong>Penulis/Sumber:</strong> <?= $art['author'] ?></p>
                  <p class="small text-secondary" style="line-height: 1.5;">
                    <?= $art['summary'] ?>
                  </p>
                </div>
                <div class="pt-2 border-top mt-2 d-flex justify-content-between align-items-center">
                  <span class="small text-muted"><i class="fas fa-university mr-1"></i> <?= $art['source'] ?></span>
                  <a href="<?= $art['url'] ?>" target="_blank" rel="noopener noreferrer" class="btn-visit">
                    Kunjungi Referensi <i class="fas fa-external-link-alt ml-1"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($page === 'diagnosa'): ?>
        <!-- ==================== TAMPILAN FORM KONSULTASI ==================== -->
        <div class="page-title">
          <i class="fas fa-user-md"></i> Konsultasi Diagnosa Penyakit Kulit
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
          <div class="card-body p-4">
            <form method="POST" action="">
              <h5 class="font-weight-bold mb-3 border-bottom pb-2" style="color: var(--primary-teal);">1. Data Pasien</h5>
              <div class="form-row mb-4">
                <div class="col-md-5">
                  <label class="font-weight-semibold small">Nama Pasien</label>
                  <input type="text" name="nama_pasien" class="form-control form-control-sm" value="Pasien Konsultasi" required>
                </div>
                <div class="col-md-3">
                  <label class="font-weight-semibold small">Usia (Tahun)</label>
                  <input type="number" name="usia" class="form-control form-control-sm" value="23" min="1" max="100" required>
                </div>
                <div class="col-md-4">
                  <label class="font-weight-semibold small">Jenis Kelamin</label>
                  <select name="jenis_kelamin" class="form-control form-control-sm">
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>
              </div>

              <h5 class="font-weight-bold mb-3 border-bottom pb-2" style="color: var(--primary-teal);">2. Pilih Gejala yang Dialami (Database: 68 Gejala)</h5>
              <p class="text-muted small mb-3">Centang gejala yang dirasakan lalu tentukan nilai keyakinan Anda:</p>

              <div class="table-responsive border rounded" style="max-height: 480px; overflow-y: auto; background: #fff;">
                <table class="table table-sm table-hover mb-0">
                  <thead class="sticky-top" style="background-color: #F1F5F9;">
                    <tr>
                      <th class="text-center" style="width: 50px;">Pilih</th>
                      <th style="width: 80px;">Kode</th>
                      <th>Nama Gejala</th>
                      <th style="width: 200px;">Tingkat Keyakinan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $q_gejala = mysqli_query($koneksi, "SELECT * FROM gejala ORDER BY id_gejala ASC");
                    while ($g = mysqli_fetch_assoc($q_gejala)):
                    ?>
                      <tr>
                        <td class="text-center">
                          <input type="checkbox" name="gejala[]" value="<?= $g['id_gejala'] ?>" id="chk_<?= $g['id_gejala'] ?>" onchange="toggleSelect(<?= $g['id_gejala'] ?>)">
                        </td>
                        <td class="font-weight-bold font-italic text-muted"><?= $g['kode_gejala'] ?></td>
                        <td>
                          <label for="chk_<?= $g['id_gejala'] ?>" class="mb-0 cursor-pointer"><?= htmlspecialchars($g['nama_gejala']) ?></label>
                        </td>
                        <td>
                          <select name="cf_user[<?= $g['id_gejala'] ?>]" id="cf_<?= $g['id_gejala'] ?>" class="form-control form-control-sm" disabled>
                            <option value="1.0">Sangat Yakin (1.0)</option>
                            <option value="0.8" selected>Yakin (0.8)</option>
                            <option value="0.6">Cukup Yakin (0.6)</option>
                            <option value="0.4">Sedikit Yakin (0.4)</option>
                            <option value="0.2">Tidak Yakin (0.2)</option>
                          </select>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>

              <div class="mt-4 text-center">
                <button type="submit" name="proses_diagnosa" class="btn text-white px-5 py-2 font-weight-bold shadow-sm" style="background-color: var(--primary-teal);">
                  <i class="fas fa-calculator mr-2"></i> Proses Diagnosa Sekarang
                </button>
              </div>
            </form>
          </div>
        </div>

      <?php elseif ($page === 'hasil_diagnosa' && $diagnosis_result): ?>
        <!-- ==================== TAMPILAN HASIL DIAGNOSA ==================== -->
        <div class="page-title">
          <i class="fas fa-clipboard-check"></i> Hasil Diagnosa Pasien
        </div>

        <?php $top_res = $diagnosis_result['ranking'][0]; ?>
        <div class="card shadow-sm mb-4" style="border: 1px solid var(--primary-teal); border-radius: 8px;">
          <div class="card-header text-white font-weight-bold" style="background-color: var(--primary-teal);">
            Diagnosis Utama: <?= htmlspecialchars($top_res['nama']) ?>
          </div>
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h3 class="font-weight-bold text-dark"><?= $top_res['kode'] ?> - <?= htmlspecialchars($top_res['nama']) ?></h3>
                <span class="badge badge-info p-2 mb-2">Kategori: <?= $top_res['kategori'] ?></span>
                <p class="text-muted"><?= htmlspecialchars($top_res['deskripsi']) ?></p>
                <div class="alert alert-light border p-3" style="border-color: var(--primary-teal) !important;">
                  <strong style="color: var(--primary-teal);">Pengobatan / Solusi:</strong><br>
                  <?= htmlspecialchars($top_res['solusi']) ?>
                  <hr>
                  <strong style="color: var(--primary-teal);">Pencegahan:</strong><br>
                  <?= htmlspecialchars($top_res['pencegahan']) ?>
                </div>
              </div>
              <div class="col-md-4 text-center">
                <div class="p-3 bg-light rounded border" style="border-color: var(--primary-teal) !important;">
                  <span class="text-muted small text-uppercase font-weight-bold">Tingkat Keyakinan</span>
                  <h1 class="display-4 font-weight-bold my-2" style="color: var(--primary-teal);"><?= $top_res['persen'] ?>%</h1>
                  <span class="badge-cf">CF: <?= number_format($top_res['cf'], 4) ?></span>
                </div>
              </div>
            </div>

            <h6 class="font-weight-bold mt-4 mb-2">Gejala Pendukung yang Ditemukan:</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="bg-light">
                  <tr>
                    <th>Kode</th>
                    <th>Nama Gejala</th>
                    <th class="text-center">CF Pasien</th>
                    <th class="text-center">CF Pakar</th>
                    <th class="text-center">CF Gejala</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($top_res['gejala'] as $mg): ?>
                    <tr>
                      <td class="font-weight-bold" style="color: var(--primary-teal);"><?= $mg['kode_gejala'] ?></td>
                      <td><?= htmlspecialchars($mg['nama_gejala']) ?></td>
                      <td class="text-center"><?= number_format($mg['cf_user'], 2) ?></td>
                      <td class="text-center"><?= number_format($mg['cf_pakar'], 2) ?></td>
                      <td class="text-center font-weight-bold" style="color: var(--primary-teal);"><?= number_format($mg['cf_gejala'], 4) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <a href="?page=riwayat" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left"></i> Lihat Riwayat</a>
              <a href="?page=diagnosa" class="btn text-white" style="background-color: var(--primary-teal);"><i class="fas fa-redo"></i> Diagnosa Ulang</a>
            </div>
          </div>
        </div>

      <?php elseif ($page === 'penyakit'): ?>
        <!-- ==================== TAMPILAN DATA PENYAKIT ==================== -->
        <div class="page-title">
          <i class="fas fa-bug"></i> Data Penyakit Kulit (15 Penyakit di Database)
        </div>
        <div class="card border-0 shadow-sm" style="border-radius: 6px; overflow: hidden;">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-teal-header mb-0">
                <thead>
                  <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 80px;">Kode</th>
                    <th>Nama Penyakit</th>
                    <th style="width: 140px;">Kategori</th>
                    <th style="width: 120px;">Keparahan</th>
                    <th>Pengobatan / Solusi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q_p = mysqli_query($koneksi, "SELECT * FROM penyakit ORDER BY id_penyakit ASC");
                  $n = 1;
                  while ($row = mysqli_fetch_assoc($q_p)):
                  ?>
                    <tr>
                      <td class="text-center"><?= $n++ ?></td>
                      <td class="font-weight-bold" style="color: var(--primary-teal);"><?= $row['kode_penyakit'] ?></td>
                      <td><strong><?= htmlspecialchars($row['nama_penyakit']) ?></strong></td>
                      <td><span class="badge badge-<?= $row['kategori'] === 'Menular' ? 'danger' : 'success' ?> p-1"><?= $row['kategori'] ?></span></td>
                      <td><span class="badge badge-warning p-1"><?= $row['tingkat_keparahan'] ?></span></td>
                      <td class="small text-muted"><?= htmlspecialchars(substr($row['solusi'], 0, 100)) ?>...</td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      <?php elseif ($page === 'gejala'): ?>
        <!-- ==================== TAMPILAN DATA GEJALA ==================== -->
        <div class="page-title">
          <i class="fas fa-syringe"></i> Data Gejala (68 Gejala di Database)
        </div>
        <div class="card border-0 shadow-sm" style="border-radius: 6px; overflow: hidden;">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-teal-header mb-0">
                <thead>
                  <tr>
                    <th style="width: 60px;">No</th>
                    <th style="width: 100px;">Kode Gejala</th>
                    <th>Deskripsi Gejala</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q_g = mysqli_query($koneksi, "SELECT * FROM gejala ORDER BY id_gejala ASC");
                  $n = 1;
                  while ($row = mysqli_fetch_assoc($q_g)):
                  ?>
                    <tr>
                      <td class="text-center"><?= $n++ ?></td>
                      <td class="font-weight-bold font-monospace" style="color: var(--primary-teal);"><?= $row['kode_gejala'] ?></td>
                      <td><?= htmlspecialchars($row['nama_gejala']) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      <?php elseif ($page === 'aturan'): ?>
        <!-- ==================== TAMPILAN DATA ATURAN ==================== -->
        <div class="page-title">
          <i class="fas fa-sync-alt"></i> Data Aturan (Rule & CF Pakar di Database)
        </div>
        <div class="card border-0 shadow-sm" style="border-radius: 6px; overflow: hidden;">
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
              <table class="table table-teal-header mb-0">
                <thead class="sticky-top">
                  <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 80px;">Basis</th>
                    <th>Penyakit</th>
                    <th>Gejala Terkait</th>
                    <th style="width: 120px; text-align: center;">CF Pakar</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q_r = mysqli_query($koneksi, "
                    SELECT r.*, p.kode_penyakit, p.nama_penyakit, g.kode_gejala, g.nama_gejala
                    FROM rule r
                    JOIN penyakit p ON r.id_penyakit = p.id_penyakit
                    JOIN gejala g ON r.id_gejala = g.id_gejala
                    ORDER BY r.id_rule ASC
                  ");
                  $n = 1;
                  while ($row = mysqli_fetch_assoc($q_r)):
                  ?>
                    <tr>
                      <td class="text-center"><?= $n++ ?></td>
                      <td class="font-weight-bold text-muted"><?= $row['kode_basis'] ?></td>
                      <td><?= $row['kode_penyakit'] ?> - <?= htmlspecialchars($row['nama_penyakit']) ?></td>
                      <td><?= $row['kode_gejala'] ?> - <?= htmlspecialchars($row['nama_gejala']) ?></td>
                      <td class="text-center font-weight-bold" style="color: var(--primary-teal);"><?= number_format($row['cf_pakar'], 2) ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

            <?php elseif ($page === 'user'): ?>
        <!-- ==================== TAMPILAN DATA USER ==================== -->
        <div class="page-title d-flex justify-content-between align-items-center">
          <div><i class="fas fa-users-cog"></i> Manajemen Data Pengguna (Users)</div>
          <button class="btn btn-sm text-white" style="background-color: var(--primary-teal);" data-toggle="modal" data-target="#modalTambahUser">
            <i class="fas fa-user-plus mr-1"></i> Tambah Pengguna
          </button>
        </div>

        <?php if ($pesan_user): ?>
          <div class="alert alert-<?= $pesan_user['type'] ?> alert-dismissible fade show" role="alert">
            <?= $pesan_user['text'] ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm p-4">
          <p class="text-muted small">Daftar akun pengguna dan administrator yang terdaftar di basis data <code>users</code>.</p>
          <div class="table-responsive">
            <table class="table table-hover table-bordered small">
              <thead class="thead-light">
                <tr>
                  <th width="5%" class="text-center">No</th>
                  <th>Nama Lengkap</th>
                  <th>Username</th>
                  <th>Role Hak Akses</th>
                  <th>Status Akun</th>
                  <th width="15%" class="text-center">Aksi / ID</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $q_all_users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id_user ASC");
                $no_u = 1;
                while ($u = mysqli_fetch_assoc($q_all_users)):
                ?>
                  <tr>
                    <td class="text-center"><?= $no_u++ ?></td>
                    <td class="font-weight-bold text-dark"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td>
                      <span class="badge badge-<?= $u['role'] === 'admin' ? 'success' : 'secondary' ?> p-1">
                        <i class="fas <?= $u['role'] === 'admin' ? 'fa-user-cog' : 'fa-user' ?>"></i> <?= ucfirst($u['role']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-<?= $u['status'] === 'aktif' ? 'primary' : 'danger' ?> p-1">
                        <?= ucfirst($u['status']) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-light border text-muted">ID: #<?= $u['id_user'] ?></span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($page === 'password'): ?>
        <!-- ==================== TAMPILAN UBAH PASSWORD ==================== -->
        <div class="page-title">
          <i class="fas fa-lock"></i> Ubah Password Administrator
        </div>

        <?php if ($pesan_password): ?>
          <div class="alert alert-<?= $pesan_password['type'] ?> alert-dismissible fade show" role="alert">
            <?= $pesan_password['text'] ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm p-4">
              <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-shield-alt mr-1" style="color: var(--primary-teal);"></i> Formulir Penggantian Password</h6>
              <p class="text-muted small">Silakan perbarui kata sandi akun <b>admin</b> untuk menjaga keamanan basis data sistem pakar.</p>

              <form method="POST" action="?page=password">
                <div class="form-group">
                  <label class="small font-weight-bold">Password Saat Ini (Lama)</label>
                  <input type="password" name="pass_lama" class="form-control form-control-sm" placeholder="Ketik password lama Anda..." required>
                  <small class="text-muted">Password awal bawaan database: <code>admin123</code></small>
                </div>
                <div class="form-group">
                  <label class="small font-weight-bold">Password Baru</label>
                  <input type="password" name="pass_baru" class="form-control form-control-sm" placeholder="Ketik password baru minimal 4 karakter..." required>
                </div>
                <div class="form-group">
                  <label class="small font-weight-bold">Konfirmasi Password Baru</label>
                  <input type="password" name="pass_konfirm" class="form-control form-control-sm" placeholder="Ulangi ketik password baru..." required>
                </div>
                <button type="submit" name="proses_ubah_password" class="btn text-white btn-sm px-4 mt-2" style="background-color: var(--primary-teal);">
                  <i class="fas fa-save mr-1"></i> Simpan Perubahan Password
                </button>
              </form>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm p-4 bg-light">
              <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-info-circle mr-1" style="color: var(--primary-teal);"></i> Akun Terdaftar & Keamanan</h6>
              <p class="small text-muted mb-3">Informasi akun administrator yang sedang login saat ini:</p>
              <ul class="small pl-3 mb-3 text-secondary">
                <li><strong>Username:</strong> <code>admin</code></li>
                <li><strong>Nama Pengguna:</strong> Administrator Klinik</li>
                <li><strong>Peran:</strong> Super Admin (Pakar Kulit)</li>
                <li><strong>Status Akun:</strong> Aktif & Terverifikasi</li>
              </ul>
              <div class="alert alert-info py-2 small mb-0">
                <i class="fas fa-lightbulb mr-1"></i> <strong>Tips Keamanan:</strong> Gunakan kombinasi huruf dan angka agar kata sandi tidak mudah ditebak oleh pihak lain.
              </div>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- Default Fallback ke Dashboard -->
        <script>window.location.href = "?page=dashboard";</script>
      <?php endif; ?>

    </main>
  </div>

  <!-- Modal Detail Riwayat Pasien -->
  <div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header text-white" style="background-color: var(--primary-teal);">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-info-circle mr-1"></i> Detail Konsultasi Pasien</h6>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body small">
          <p><strong>Nama Pasien:</strong> <span id="mNama">-</span></p>
          <p><strong>Tanggal Konsultasi:</strong> <span id="mTgl">-</span></p>
          <p><strong>Hasil Diagnosis:</strong> <span id="mPenyakit" class="font-weight-bold" style="color: var(--primary-teal);">-</span></p>
          <p><strong>Nilai CF & Persentase:</strong> <span id="mPersen" class="badge badge-success">-</span></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Tambah User -->
  <div class="modal fade" id="modalTambahUser" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header text-white" style="background-color: var(--primary-teal);">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-1"></i> Tambah Pengguna Baru</h6>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form method="POST" action="?page=user">
          <div class="modal-body small">
            <div class="form-group">
              <label class="font-weight-bold">Nama Lengkap</label>
              <input type="text" name="nama_lengkap" class="form-control form-control-sm" placeholder="Contoh: dr. Budi Santoso, Sp.KK" required>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Username</label>
              <input type="text" name="username" class="form-control form-control-sm" placeholder="Contoh: budi_spkk" required>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Password</label>
              <input type="password" name="password" class="form-control form-control-sm" placeholder="Masukkan password..." required>
            </div>
            <div class="form-group">
              <label class="font-weight-bold">Peran (Role)</label>
              <select name="role" class="form-control form-control-sm">
                <option value="user">User / Pasien</option>
                <option value="admin">Administrator / Dokter Pakar</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
            <button type="submit" name="tambah_user" class="btn text-white btn-sm" style="background-color: var(--primary-teal);">Simpan Pengguna</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function toggleSidebar(e) {
      e.preventDefault();
      var sidebar = document.getElementById('sidebarNav');
      if (sidebar.style.display === 'none') {
        sidebar.style.display = 'block';
      } else {
        sidebar.style.display = 'none';
      }
    }

    function toggleSelect(id) {
      var chk = document.getElementById('chk_' + id);
      var sel = document.getElementById('cf_' + id);
      sel.disabled = !chk.checked;
    }

    function lihatDetail(id, nama, penyakit, persen, tgl) {
      document.getElementById('mNama').innerText = nama;
      document.getElementById('mTgl').innerText = tgl;
      document.getElementById('mPenyakit').innerText = penyakit;
      document.getElementById('mPersen').innerText = persen;
      $('#modalDetail').modal('show');
    }

    // Inisialisasi Grafik Donut
    <?php if ($page === 'riwayat'): ?>
    document.addEventListener("DOMContentLoaded", function() {
      <?php
      $chart_labels = [];
      $chart_data = [];
      $colors = ['#F59E0B', '#38BDF8', '#F43F5E', '#0D9488', '#8B5CF6'];

      $total_all = array_sum($penyakit_counts);
      $idx = 0;
      $legend_html = "";

      foreach ($penyakit_counts as $p_name => $count) {
          $pct = $total_all > 0 ? round(($count / $total_all) * 100) : 0;
          $chart_labels[] = $p_name;
          $chart_data[] = $count;
          $c_color = $colors[$idx % count($colors)];

          $legend_html .= "<div class='mb-1 d-flex align-items-center'>" .
                          "<span style='display:inline-block; width:12px; height:12px; background-color:{$c_color}; margin-right:8px; border-radius:2px;'></span>" .
                          "<span>{$p_name} <strong>{$pct}%</strong></span></div>";
          $idx++;
      }
      ?>

      var ctx = document.getElementById('grafikDonut').getContext('2d');
      var donutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($chart_labels) ?>,
          datasets: [{
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: ['#F59E0B', '#38BDF8', '#F43F5E', '#0D9488', '#8B5CF6'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            display: false
          },
          cutoutPercentage: 45
        }
      });

      document.getElementById('chartLegend').innerHTML = `<?= $legend_html ?>`;
    });
    <?php endif; ?>
  </script>
</body>
</html>
