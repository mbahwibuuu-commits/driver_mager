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

// tanggal default = hari ini
$filterDate = isset($_GET['tanggal']) ? $_GET['tanggal'] : date("Y-m-d");

// ambil log berdasarkan tanggal
$stmt = $conn->prepare("SELECT * FROM log_kirim WHERE DATE(waktu) = ? ORDER BY waktu DESC");
$stmt->bind_param("s", $filterDate);
$stmt->execute();
$logs = $stmt->get_result();

// hitung jumlah order hari itu
$stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM log_kirim WHERE DATE(waktu) = ?");
$stmt2->bind_param("s", $filterDate);
$stmt2->execute();
$countToday = $stmt2->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Log ShopeeFood</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff5ef;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 500px;
            background: #fff;
            padding: 20px;
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
            color: #ff6600;
            margin-bottom: 10px;
        }
        form {
            text-align: center;
            margin-bottom: 15px;
        }
        input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            background: #ff6600;
            border: none;
            padding: 8px 14px;
            color: #fff;
            font-weight: bold;
            border-radius: 8px;
            margin-left: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #e65c00;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 15px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #ff6600;
            color: #fff;
        }
        tr:hover {
            background: #fdf1e8;
        }
        .total-box {
            background: #ff6600;
            color: #fff;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        a.map-link {
            color: #ff6600;
            text-decoration: none;
            font-weight: bold;
        }
        a.map-link:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Tombol buka/tutup -->
<span style="font-size:24px;cursor:pointer;position:fixed;top:10px;left:10px;z-index:10000;color:#ff6600;" onclick="toggleSidebar()">☰</span>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">×</a>
  <a href="index.php">🏠 Home</a>
  <a href="log_kirim.php">📜 Histori</a>
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

</head>
<body>
    <div class="container">
        <h2>📑 Log Pengiriman</h2>

        <form method="get">
            <input type="date" name="tanggal" value="<?= $filterDate ?>">
            <button type="submit">Filter</button>
        </form>

        <table>
            <tr>
                <th>No</th>
                <th>Nomor</th>
                <th>Nama</th>
                <th>Lokasi</th>
                <th>Waktu</th>
            </tr>
            <?php 
            $no = 1;
            while ($row = $logs->fetch_assoc()): 
                $mapLink = "https://www.google.com/maps?q=".$row['latitude'].",".$row['longitude'];
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nomor']); ?></td>
                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td><a class="map-link" href="<?= $mapLink ?>" target="_blank">📍 GMaps</a></td>
                <td><?= $row['waktu']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="total-box">
            🔥 Total Order (<?= $filterDate ?>): <?= $countToday; ?>
        </div>
    </div>
</body>
</html>
