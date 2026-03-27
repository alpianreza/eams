<style>
  body {
    font-family: sans-serif;
    font-size: 11px;
  }

  h3 {
    margin-bottom: 5px;
  }

  .meta td {
    padding: 2px 4px;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
  }

  th,
  td {
    border: 1px solid #000;
    padding: 5px;
  }

  .center {
    text-align: center;
  }
</style>

<h3><?= esc($title) ?></h3>

<table class="meta">
  <tr>
    <td>Item</td>
    <td>:</td>
    <td><?= esc($itemName) ?></td>
  </tr>
  <tr>
    <td>No Inventaris</td>
    <td>:</td>
    <td><?= esc($inventoryNo) ?></td>
  </tr>
  <tr>
    <td>Lokasi</td>
    <td>:</td>
    <td><?= esc($location) ?></td>
  </tr>
  <tr>
    <td>Periode</td>
    <td>:</td>
    <td><?= esc($periodLabel) ?></td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style="width:5%">No</th>
      <th>Pertanyaan</th>
      <th style="width:15%">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($questions as $i => $q): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= esc($q['question']) ?></td>
        <td class="center"><?= $q['status_symbol'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p style="margin-top:10px;">
  Keterangan: &#10003; = sesuai, &#10007; = tidak sesuai, - = tidak berlaku
</p>
