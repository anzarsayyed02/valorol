<?php
$folder = "assets/coa/";
$files = scandir($folder);
$coaData = [];

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === "pdf") {

        $name = pathinfo($file, PATHINFO_FILENAME);
        $parts = explode("_", $name);

        $product = ($parts[0] ?? '') . ' ' . ($parts[1] ?? '');
        $batch   = $parts[2] ?? 'N/A';
        $code    = $parts[3] ?? 'N/A';

        $coaData[] = [
            "product" => trim(str_replace("-", " ", $product)),
            "batch"   => $batch,
            "code"    => $code,
            "file"    => $file
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>COA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
.card:hover{transform:translateY(-5px);box-shadow:0 6px 20px rgba(0,0,0,.15)}
</style>
</head>

<body>

<div class="container py-5">
  <h1 class="text-center fw-bold mb-4">Product COA</h1>

  <!-- SEARCH -->
  <div class="input-group mb-4">
    <input type="text" id="searchInput" class="form-control" placeholder="Search by Product / Batch / Code">
    <button class="btn btn-primary" onclick="searchCOA()">Search</button>
  </div>

  <!-- COA LIST -->
  <div class="row g-4" id="coaList">
    <?php foreach ($coaData as $item): ?>
      <div class="col-md-4 coa-card">
        <div class="card h-100 text-center p-3">
          <i class="bi bi-file-earmark-pdf text-danger fs-1"></i>
          <h5 class="mt-2"><?= $item['product'] ?></h5>
          <p class="small text-muted">
            Batch: <?= $item['batch'] ?><br>
            Code: <?= $item['code'] ?>
          </p>
          <div class="d-flex justify-content-center gap-2">
            <a href="assets/coa/<?= $item['file'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">Preview</a>
            <a href="assets/coa/<?= $item['file'] ?>" download class="btn btn-warning btn-sm">Download</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function searchCOA(){
  let q = document.getElementById("searchInput").value.toLowerCase();
  document.querySelectorAll(".coa-card").forEach(card=>{
    card.style.display = card.innerText.toLowerCase().includes(q) ? "block" : "none";
  });
}
</script>

</body>
</html>
