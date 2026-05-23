<?php
$kd = "DP-";

// Ambil saldo user dari tabel saldo
$total_saldo = 0;
$query_saldo = mysqli_query($koneksi, "SELECT total_saldo FROM saldo WHERE id_akun_saldo = '$id_akun_masuk'");
if ($data_saldo = mysqli_fetch_array($query_saldo)) {
    $total_saldo = $data_saldo['total_saldo'];
}

// Ambil data QRIS aktif
$qris_aktif = null;
$query_qris = mysqli_query($koneksi, "SELECT id_qris, merchant_name, qris_code, aktif, gambar_qris FROM qris WHERE aktif = 'Y' LIMIT 1");
if ($data = mysqli_fetch_assoc($query_qris)) {
    $qris_aktif = $data;
}

// Flag untuk menentukan apakah QRIS harus ditampilkan
$tampilkan_qris = false;
$nominal_deposit = 0;
?>


<?php
  // Inisialisasi default untuk mencegah warning variabel tidak terdefinisi
  $kategori_rekening_aktif = '';
  if (isset($_GET['kategori_rekening'])) {
    $kategori_rekening_aktif = $_GET['kategori_rekening'];
  } elseif (isset($_POST['kategori_rekening_aktif'])) {
    $kategori_rekening_aktif = $_POST['kategori_rekening_aktif'];
  } elseif (isset($_SESSION['kategori_rekening_aktif'])) {
    $kategori_rekening_aktif = $_SESSION['kategori_rekening_aktif'];
  }
  // Jika tetap kosong, redirect
  if ($kategori_rekening_aktif === '') {
    echo '
      <script>
        window.location.replace("'.$alamat_website.'deposit/bank");
      </script>';
    exit;
  }
  
  if (isset($_POST['deposit'])) {
    $id_akun_deposit = $id_akun_masuk;
    $kode_deposit = $kd.(generatorRangkaianAcak(10));
    $kategori_rekening_deposit = $kategori_rekening_aktif;
    $jumlah_deposit = $_POST['jumlah_deposit'];
    $nomor_referensi_deposit = $_POST['nomor_referensi_deposit'];
    
    // Patch: handle QRIS, set rekening fields to 0 if kategori QRIS (karena form tidak kirim field ini)
    if ($kategori_rekening_deposit === 'qris') {
        $id_rekening_anggota_deposit = 0;
        $id_rekening_admin_deposit = 0;
    } else {
    // Penanganan field rekening untuk QRIS dan metode lain
    if ($kategori_rekening_aktif == 'qris') {
        $id_rekening_anggota_deposit = 0;
        $id_rekening_admin_deposit = 0;
    } else {
        $id_rekening_anggota_deposit = isset($_POST['id_rekening_anggota_deposit']) && $_POST['id_rekening_anggota_deposit'] !== '' ? $_POST['id_rekening_anggota_deposit'] : 0;
        $id_rekening_admin_deposit = isset($_POST['id_rekening_admin_deposit']) && $_POST['id_rekening_admin_deposit'] !== '' ? $_POST['id_rekening_admin_deposit'] : 0;
    }
    }
    
    $tanggal_deposit = date("Y-m-d H:i:s");
    
    if($jumlah_deposit < 50000) {
      echo '
        <script>
          alert("Deposit Minimal Sebesar Rp 50.000!");
        </script>';
    } else {
       if($_POST['jumlah_deposit']) {
         // Untuk QRIS, tampilkan QRIS dulu sebelum insert
         if ($kategori_rekening_deposit === 'qris') {
             $tampilkan_qris = true;
             $nominal_deposit = $jumlah_deposit;
         } else {
             // Untuk metode lain, langsung insert
             $deposit = mysqli_query($koneksi, "INSERT INTO deposit (id_akun_deposit, kode_deposit, kategori_rekening_deposit, id_rekening_anggota_deposit, id_rekening_admin_deposit, jumlah_deposit, nomor_referensi_deposit, tanggal_deposit) VALUES ('$id_akun_deposit', '$kode_deposit', '$kategori_rekening_deposit', '$id_rekening_anggota_deposit', '$id_rekening_admin_deposit', '$jumlah_deposit', '$nomor_referensi_deposit', '$tanggal_deposit')");
             if($deposit) {
                 echo '
                   <script>
                     alert("Deposit Telah Berhasil");
                 window.location.replace("riwayat_deposit");
                   </script>';
             }
         }
       }
    }
  }

?>


<form method="post" enctype="multipart/form-data">
  <div class="row gy-2 gx-0">
    <div class="col-6">
      <a href="<?php echo $alamat_website.'deposit'; ?>" class="d-flex justify-content-center align-items-center text-uppercase btn-utama mx-3 p-2">
        <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/deposit.svg" alt="Deposit" width="25" height="25" class="me-2">
        Deposit
      </a>
    </div>
    <div class="col-6">
      <a href="<?php echo $alamat_website.'withdraw'; ?>" class="d-flex justify-content-center align-items-center text-uppercase mx-3 p-2">
        <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/withdrawal.svg" alt="Withdraw" width="25" height="25" class="me-2">
        Withdraw
      </a>
    </div>
  </div>
  <div class="row gy-2 gx-0 d-flex justify-content-center align-items-center mt-5">
    <div class="col-10">
      <div class="deposit-note">
        <div class="deposit-note-icon">
          <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/deposit-note.svg" alt="Deposit Note">
        </div>
        <div class="deposit-note-content">
          <span>Catatan:</span>
          <ol>
            <li>Untuk deposit pertama kali member harus menambah akun bank terlebih dahulu.</li>
            <li>Jika ingin deposit diluar nominal yang sudah ditentukan, harap pilih 'Akun Tujuan' lain.</li>
          </ol>
        </div>
      </div>
    </div>
    <div class="col-10">
      <a href="<?php echo $alamat_website.'riwayat_deposit'; ?>" class="d-block text-center text-secondary text-decoration-underline" style="font-size: 14px;">Riwayat Deposit</a>
    </div>
    <div class="col-10">
      <div class="d-flex justify-content-between align-items-center">
        <a href="<?php echo $alamat_website.'rekening/'.$kategori_rekening_aktif; ?>" class="d-flex align-items-center text-secondary" style="font-size: 16px;">
          <i class="ri-add-line fw-bold"></i>
          AKUN
        </a>
        <div class="d-flex flex-column align-items-end">
          <span class="text-secondary">TOTAL SALDO</span>
          <span style="color: #bda270;"><?php echo htmlspecialchars(number_format($total_saldo).'.00', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
    </div>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Metode Pembayaran</span>
        <div class="row g-2">
          <div class="col-4">
            <a href="<?php echo $alamat_website.'deposit/bank'; ?>">
              <?php if ($kategori_rekening_aktif == "bank") {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran-aktif rounded p-2">';
              } else {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran rounded p-2">';
              } ?>
                <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/bank.svg" alt="Bank" width="25" height="25">
                <span style="font-size: 14px;">Bank</span>
              </div>
            </a>
          </div>
          <div class="col-4">
            <a href="<?php echo $alamat_website.'deposit/emoney'; ?>">
              <?php if ($kategori_rekening_aktif == "emoney") {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran-aktif rounded p-2">';
              } else {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran rounded p-2">';
              } ?>
                <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/emoney.svg" alt="E-Money" width="25" height="25">
                <span style="font-size: 14px;">E-Money</span>
              </div>
            </a>
          </div>
          <div class="col-4">
            <a href="<?php echo $alamat_website.'deposit/qris'; ?>">
              <?php if ($kategori_rekening_aktif == "qris") {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran-aktif rounded p-2">';
              } else {
                echo '<div class="d-flex flex-column align-items-center kotak-pembayaran rounded p-2">';
              } ?>
                <img src="<?php echo $alamat_website_admin; ?>assets/images/svg/qr-code.svg" alt="QRIS" width="25" height="25">
                <span style="font-size: 14px;">QRIS</span>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php if ($kategori_rekening_aktif == 'qris' && $qris_aktif): ?>
    <div class="col-10" id="qris-container" style="display:<?php echo ($tampilkan_qris) ? 'block' : 'none'; ?>;">
      <div class="bg-dark rounded p-2 text-center">
        <span class="d-block mb-2" style="font-size: 14px;">Pembayaran via QRIS</span>
        <span class="d-block mb-2">Merchant: <b><?= htmlspecialchars($qris_aktif['merchant_name'], ENT_QUOTES, 'UTF-8') ?></b></span>
        <span class="d-block mb-2">Kode QRIS: <b><?= htmlspecialchars($qris_aktif['qris_code'], ENT_QUOTES, 'UTF-8') ?></b></span>
        <span class="d-block mb-2" style="font-size: 18px; color: #D0B300;">Jumlah Transfer: <b>Rp <?= number_format($nominal_deposit) ?></b></span>
        <?php if (!empty($qris_aktif['gambar_qris'])): ?>
          <img src="<?php echo $alamat_website_admin; ?>assets/images/qris/<?= htmlspecialchars($qris_aktif['gambar_qris'], ENT_QUOTES, 'UTF-8') ?>" alt="QRIS QR Code" width="150" height="150" style="background:#fff; padding:8px; border-radius:8px;">
          <div class="mt-2">
            <a href="<?php echo $alamat_website_admin; ?>assets/images/qris/<?= htmlspecialchars($qris_aktif['gambar_qris'], ENT_QUOTES, 'UTF-8') ?>" download class="btn btn-sm btn-success mt-2">Unduh QRIS</a>
          </div>
        <?php else: ?>
          <span class="text-muted">Belum ada gambar QRIS</span>
        <?php endif; ?>
        <div class="mt-2"><small>Scan QRIS di aplikasi e-wallet/bank Anda</small></div>
        <div class="mt-3">
          <button type="button" class="btn btn-success w-100" onclick="selesaiQRIS()">Sudah Scan & Transfer</button>
        </div>
      </div>
    </div>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Jumlah</span>
        <input type="text" name="jumlah_deposit" class="form-control rounded-0 border-0 mb-2" id="hanya-angka" autocomplete="off" placeholder="Minimal Deposit (Rp 50.000)" required>
        <span class="d-block" id="notif-nominal" style="color: #FF0000;">Silahkan masukan angka untuk jumlah deposit.</span>
        <span class="d-block" style="font-size: 14px;">Jumlah yang harus ditransfer</span>
        <span class="d-block" id="nominal" style="font-size: 24px;">0 (IDR)</span>
      </div>
    </div>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Nomor Referensi</span>
        <input type="text" name="nomor_referensi_deposit" class="form-control rounded-0 border-0 mb-2">
      </div>
    </div>
    <div class="col-10">
      <button type="submit" name="deposit" class="btn btn-utama w-100 text-uppercase py-3" style="font-size: 12px;">Deposit QRIS</button>
    </div>
    <script>
      function selesaiQRIS() {
        // Simpan data deposit ke database
        const form = document.querySelector('form');
        const jumlah = document.getElementById('hanya-angka').value;
        
        if (jumlah.trim() !== '') {
          // Submit form dengan hidden input
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'konfirmasi_qris';
          input.value = '1';
          form.appendChild(input);
          form.submit();
        } else {
          alert('Silahkan masukan jumlah deposit');
        }
      }
    </script>
    <?php else: ?>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Jumlah</span>
        <input type="text" name="jumlah_deposit" class="form-control rounded-0 border-0 mb-2" id="hanya-angka" autocomplete="off" placeholder="Minimal Deposit (Rp 50.000)" required>
        
        <span class="d-block" id="notif-nominal" style="color: #FF0000;">Silahkan masukan angka untuk jumlah deposit.</span>
        <span class="d-block" style="font-size: 14px;">Jumlah yang harus ditransfer</span>
        <span class="d-block" id="nominal" style="font-size: 24px;">0 (IDR)</span>
      </div>
    </div>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Akun Asal</span>
        <select class="form-select rounded-0" name="id_rekening_anggota_deposit" style="font-size: 12px;">
          <?php
            $query_rekening_anggota = mysqli_query($koneksi, "SELECT * FROM rekening_anggota WHERE id_akun_rekening_anggota = '$id_akun_masuk' AND kategori_rekening_anggota = '$kategori_rekening_aktif'");
            $cek_rekening_anggota = mysqli_num_rows($query_rekening_anggota);
            if ($cek_rekening_anggota > 0) {
              while ($data_rekening_anggota = mysqli_fetch_array($query_rekening_anggota)) {
                $id_rekening_anggota = $data_rekening_anggota['id_rekening_anggota'];
                $id_rekening_rekening_anggota = $data_rekening_anggota['id_rekening_rekening_anggota'];
                $nama_rekening_anggota = $data_rekening_anggota['nama_rekening_anggota'];
                $nomor_rekening_anggota = $data_rekening_anggota['nomor_rekening_anggota'];

                $query_rekening = mysqli_query($koneksi, "SELECT * FROM rekening WHERE id_rekening = '$id_rekening_rekening_anggota'");
                $data_rekening = mysqli_fetch_array($query_rekening);
                $kategori_rekening = $data_rekening['kategori_rekening'];
                $jenis_rekening = $data_rekening['jenis_rekening'];

                echo '<option value="'.htmlspecialchars($id_rekening_anggota, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($jenis_rekening, ENT_QUOTES, 'UTF-8').' | '.htmlspecialchars($nomor_rekening_anggota, ENT_QUOTES, 'UTF-8').'</option>';
              }

          ?>
        </select>
      </div>
    </div>

    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Promo Tersedia</span>
        <select class="form-select rounded-0 mb-3" id="" name="" style="font-size: 12px;" required> 
        <option>-- Pilih Promo --</option>
            <option>BONUS NEW MEMBER 100%</option>

            <option>CASHBACK DEPOSIT 50%</option>
            <option>GARANSI KEKALAHAN 100%</option>
        </select>
      </div>
    </div>

    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Akun Tujuan</span>
        <select class="form-select rounded-0 mb-3" id="rekening-admin" name="id_rekening_admin_deposit" style="font-size: 12px;" required>
          <option>-- Pilih Akun Tujuan --</option>
          <?php
            $query_rekening_admin = mysqli_query($koneksi, "SELECT * FROM rekening_admin WHERE kategori_rekening_admin = '$kategori_rekening_aktif'");
            while ($data_rekening_admin = mysqli_fetch_array($query_rekening_admin)) {
              $id_rekening_admin = $data_rekening_admin['id_rekening_admin'];
              $id_rekening_rekening_admin = $data_rekening_admin['id_rekening_rekening_admin'];
              $nama_rekening_admin = $data_rekening_admin['nama_rekening_admin'];
              $nomor_rekening_admin = $data_rekening_admin['nomor_rekening_admin'];

              $query_rekening = mysqli_query($koneksi, "SELECT * FROM rekening WHERE id_rekening = '$id_rekening_rekening_admin'");
              $data_rekening = mysqli_fetch_array($query_rekening);
              $kategori_rekening = $data_rekening['kategori_rekening'];
              $jenis_rekening = $data_rekening['jenis_rekening'];

              echo '<option value="'.htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($jenis_rekening, ENT_QUOTES, 'UTF-8').' | '.htmlspecialchars($nomor_rekening_admin, ENT_QUOTES, 'UTF-8').'</option>';
            }
          }
          ?>
        </select>
        <?php
          $query_rekening_admin = mysqli_query($koneksi, "SELECT * FROM rekening_admin WHERE kategori_rekening_admin = '$kategori_rekening_aktif'");
          while ($data_rekening_admin = mysqli_fetch_array($query_rekening_admin)) {
            $id_rekening_admin = $data_rekening_admin['id_rekening_admin'];
            $id_rekening_rekening_admin = $data_rekening_admin['id_rekening_rekening_admin'];
            $nama_rekening_admin = $data_rekening_admin['nama_rekening_admin'];
            $nomor_rekening_admin = $data_rekening_admin['nomor_rekening_admin'];

            $query_rekening = mysqli_query($koneksi, "SELECT * FROM rekening WHERE id_rekening = '$id_rekening_rekening_admin'");
            $data_rekening = mysqli_fetch_array($query_rekening);
            $kategori_rekening = $data_rekening['kategori_rekening'];
            $jenis_rekening = $data_rekening['jenis_rekening'];
        ?>
        <div class="bank-info rounded p-2" id="rekening-admin-<?php echo htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="d-flex justify-content-between align-items-center" style="font-size: 16px;">
            <span class="text-uppercase"><?php echo htmlspecialchars($nama_rekening_admin, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="text-uppercase"><?php echo htmlspecialchars($jenis_rekening, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="mt-1" id="target-salin-<?php echo htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 20px; letter-spacing: 5px;"><?php echo htmlspecialchars($nomor_rekening_admin, ENT_QUOTES, 'UTF-8'); ?></div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <span style="color: #D0B300;">Biaya Admin: 0</span>
            <div class="d-flex justify-content-center align-items-center" id="tombol-salin-<?php echo htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8'); ?>" style="cursor: pointer;">
              <span class="ri-file-fill me-1" id="ikon-salin-<?php echo htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8'); ?>"></span>
              <span id="text-tombol-salin-<?php echo htmlspecialchars($id_rekening_admin, ENT_QUOTES, 'UTF-8'); ?>">SALIN</span>
            </div>
          </div>
        </div>
        <?php
          }
        ?>
      </div>
    </div>
    <div class="col-10">
      <div class="bg-dark rounded p-2">
        <span class="d-block mb-2" style="font-size: 14px;">Nomor Referensi</span>
        <input type="text" name="nomor_referensi_deposit" class="form-control rounded-0 border-0 mb-2">
      </div>
    </div>
    <div class="col-10">
      <button type="submit" name="deposit" class="btn btn-utama w-100 text-uppercase py-3" style="font-size: 12px;">Deposit</button>
    </div>
    <?php endif; ?>
  </div>
</form>
