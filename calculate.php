<?php
// Mengambil data dari form
$jangkaWaktu = $_POST['jangka_waktu'];
$capital = $_POST['capital'];
$nonCapital = $_POST['non_capital'];
$totalInvestasi = $capital + $nonCapital;
$produksi = $_POST['prod_thn_1'];
$declineRate = $_POST['decline'] / 100;
$hargaMinyak = $_POST['harga_minyak'];
$opex = $_POST['opex'];
$pajakRate = $_POST['pajak'] / 100;
$metode = $_POST['metode_depresiasi'];

echo "<h2>Tabel Perhitungan Keekonomian</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr>
        <th>Tahun</th>
        <th>Produksi</th>
        <th>Income</th>
        <th>Opex</th>
        <th>Di</th>
        <th>Taxable Income</th>
        <th>Tax</th>
        <th>NCF Undiscounted</th>
      </tr>";

// Baris Tahun 0
echo "<tr>
        <td>0</td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
        <td>-</td>
        <td>-" . number_format($totalInvestasi, 2) . "</td>
      </tr>";

$nilaiBuku = $totalInvestasi;

// Loop Perhitungan per Tahun
for ($t = 1; $t <= $jangkaWaktu; $t++) {
    // Logika Produksi (mulai tahun ke-5 decline)
    if ($t > 4) {
        $produksi *= (1 - $declineRate);
    }
    
    $income = $produksi * $hargaMinyak;
    
    // Logika Depresiasi
    switch ($metode) {
        case 'straight_line':
            $di = $totalInvestasi / $jangkaWaktu;
            break;
        case 'declining_balance':
            $rate = 0.2; 
            $di = $nilaiBuku * $rate;
            $nilaiBuku -= $di;
            break;
        case 'sum_years_digits':
            $sumYears = ($jangkaWaktu * ($jangkaWaktu + 1)) / 2;
            $di = (($jangkaWaktu - $t + 1) / $sumYears) * $totalInvestasi;
            break;
        default:
            $di = 0;
    }
    
    $taxableIncome = $income - $opex - $di;
    $tax = ($taxableIncome > 0) ? ($taxableIncome * $pajakRate) : 0;
    $ncf = $income - $opex - $tax;

    echo "<tr>
            <td>$t</td>
            <td>" . number_format($produksi, 0) . "</td>
            <td>" . number_format($income, 2) . "</td>
            <td>" . number_format($opex, 2) . "</td>
            <td>" . number_format($di, 2) . "</td>
            <td>" . number_format($taxableIncome, 2) . "</td>
            <td>" . number_format($tax, 2) . "</td>
            <td>" . number_format($ncf, 2) . "</td>
          </tr>";
}
echo "</table>";
?>