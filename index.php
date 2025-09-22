<?php
// koneksi database
$host = "localhost:3306";
$user = "root";
$pass = "root";
$db   = "orange";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

function rapikan_nomor($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 1) === "0") {
        $nomor = "62" . substr($nomor, 1);
    }
    return $nomor;
}

$alertJS = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor     = rapikan_nomor($_POST['nomor']);
    $nama      = trim($_POST['nama']) ?: "#";
    $latitude  = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $jenis     = $_POST['jenis'];
    $custom    = $_POST['custom_pesan'] ?? "";
    $tarif     = intval($_POST['tarif']);

    // cek pernah kirim?
    $cek = $conn->query("SELECT * FROM log_kirim WHERE nomor='$nomor' LIMIT 1");
    $pernah = ($cek->num_rows > 0);

    // simpan ke database
    $conn->query("INSERT INTO log_kirim (nomor, nama, latitude, longitude, tarif, waktu) 
                  VALUES ('$nomor', '$nama', '$latitude', '$longitude', '$tarif', NOW())");

    // buat pesan sesuai jenis
    if ($jenis === "tanya") {
        $pesan = "Permisi kak $nama, saya dari ShopeeFood. Apakah titik pengantaran sudah sesuai aplikasi?";

    } elseif ($jenis === "info") {
        $pesan = "Permisi kak $nama, saya dari ShopeeFood. Saya sudah sampai di titik pengantaran ($latitude,$longitude). Tarif: Rp".number_format($tarif,0,",",".")."%0A".
                 "Klik lokasi: https://www.google.com/maps?q=$latitude,$longitude";
    } else {
        $pesan = $custom;

    }

    $url = "https://wa.me/$nomor?text=$pesan";

    if ($pernah) {
        $alertJS = "
            Swal.fire({
                icon: 'warning',
                title: 'Pernah kirim!',
                text: 'Nomor customer sudah pernah dikirim sebelumnya'
            }).then(() => {
                window.location.href = '$url';
            });
        ";
    } else {
        header("Location: $url");
        exit;
    }
}

$hari_ini = date("Y-m-d");
$qCount = $conn->query("SELECT COUNT(*) as jml FROM log_kirim WHERE DATE(waktu)='$hari_ini'");
$rowCount = $qCount->fetch_assoc();
$jml_order = $rowCount['jml'] ?? 0;

$riwayat = $conn->query("SELECT nomor, nama, tarif, waktu FROM log_kirim WHERE DATE(waktu)='$hari_ini' ORDER BY waktu DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopeeFood Order</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff5ef;
            display: flex;
        }
        .sidebar {
            width: 200px;
            background: #ff6600;
            color: white;
            height: 100vh;
            padding: 20px 10px;
            position: fixed;
            top: 0;
            left: 0;
        }
        .sidebar h3 {
            margin-bottom: 20px;
            text-align: center;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .sidebar a:hover {
            background: #e65c00;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
            width: 100%;
        }
        .card {
            background: #fff;
            max-width: 480px;
            margin: auto;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #ff6600;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            width: 100%;
            background: #ff6600;
            border: none;
            padding: 12px;
            color: #fff;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        .info {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }
        .jumlah {
            color: #ff6600;
            font-size: 20px;
        }
        #map {
            margin-top: 15px;
            width: 100%;
            height: 250px;
            border-radius: 12px;
        }
        .riwayat {
            margin-top: 20px;
        }
        .riwayat-item {
            background: #fff3e6;
            border: 1px solid #ffcc99;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        #jam-digital {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #ff6600;
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 8px;
            font-family: monospace;
            z-index: 9999;
        }
    </style>
    <!-- Tombol buka/tutup -->
<span style="font-size:24px;cursor:pointer;position:fixed;top:10px;left:10px;z-index:10000;color:#ff6600;" onclick="toggleSidebar()">☰</span>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">×</a>
  <a href="index.php">🏠 Home</a>
  <a href="admin_log.php">📜 Histori</a>
</div>

<style>
/* Sidebar style */
.sidebar {
  height: 100%;
  width: 0; /* awal tertutup */
  position: fixed;
  z-index: 9999;
  top: 0;
  left: 0;
  background-color: #ff6600;
  overflow-x: hidden;
  transition: 0.3s;
  padding-top: 60px;
  border-top-right-radius: 12px;
  border-bottom-right-radius: 12px;
  box-shadow: 2px 0 6px rgba(0,0,0,0.3);
}
.sidebar a {
  padding: 12px 20px;
  text-decoration: none;
  font-size: 18px;
  color: white;
  display: block;
  transition: 0.2s;
}
.sidebar a:hover {
  background-color: #e65c00;
}
.sidebar .closebtn {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 28px;
}
</style>

<script>
function toggleSidebar() {
  let sidebar = document.getElementById("mySidebar");
  if (sidebar.style.width === "250px") {
    sidebar.style.width = "0";
  } else {
    sidebar.style.width = "250px";
  }
}
</script>

</head>
<body>
    <div class="sidebar">
        <h3>📌 Menu</h3>
        <a href="index.php">🏠 Home</a>
        <a href="admin_log.php">📜 Histori</a>
    </div>

    <div class="content">
        <div id="jam-digital"></div>
        <div class="card">
            <h2>📦 Driver Oren 🚴</h2>
            <form method="post">
                <label>Nomor HP Customer:</label>
                <input type="text" name="nomor" required>

                <label>Nama (opsional):</label>
                <input type="text" name="nama">

                <label>Jenis Pesan:</label>
                <select name="jenis" id="jenis" required onchange="toggleCustom()">
                    <option value="tanya">Tanya Titik</option>
                    <option value="info">Info Sudah Sampai</option>
                    <option value="custom">Pesan Custom</option>
                </select>

                <div id="customBox" style="display:none;">
                    <label>Pesan Custom:</label>
                    <textarea name="custom_pesan" rows="3"></textarea>
                </div>

                <label>Tarif (Rp):</label>
                <input type="number" name="tarif" required>

                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <button type="submit">Kirim ke WhatsApp</button>
            </form>

            <div class="info">
                Order hari ini: <span class="jumlah"><?= $jml_order ?></span>
            </div>

            <div id="map"></div>

            <div class="riwayat">
                <h3>Riwayat Order Hari Ini</h3>
                <?php if ($riwayat->num_rows > 0): ?>
                    <?php while($r = $riwayat->fetch_assoc()): ?>
                        <div class="riwayat-item">
                            📱 <?= htmlspecialchars($r['nomor']) ?> - <?= htmlspecialchars($r['nama']) ?>
                            <div>💰 Rp<?= number_format($r['tarif'],0,",",".") ?></div>
                            <div>⏰ <?= date("H:i:s", strtotime($r['waktu'])) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Belum ada order hari ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
 
    <script>
        function toggleCustom(){
            let jenis = document.getElementById("jenis").value;
            document.getElementById("customBox").style.display = (jenis === "custom") ? "block" : "none";
        }

        // jalankan sweetalert dari PHP (kalau pernah kirim)
        <?= $alertJS ?>

        // ambil lokasi otomatis
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;

                let iframe = "<iframe width='100%' height='250' style='border:0;border-radius:12px;' loading='lazy' allowfullscreen src='https://www.google.com/maps?q="+pos.coords.latitude+","+pos.coords.longitude+"&hl=id&z=15&output=embed'></iframe>";
                document.getElementById('map').innerHTML = iframe;
            });
        }

        // jam digital
        function updateJam() {
            let now = new Date();
            document.getElementById("jam-digital").innerText = 
                now.toLocaleTimeString('id-ID', { hour12: false });
        }
        setInterval(updateJam, 1000);
        updateJam();
    </script>
</body>
</html>
