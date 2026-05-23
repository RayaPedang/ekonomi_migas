<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Keekonomian Migas</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 250px; }
    </style>
</head>
<body>
    <h2>Input Data Perhitungan Keekonomian Lapangan Migas</h2>
    <form action="calculate.php" method="POST">
        <div class="form-group">
            <label>Jangka Waktu Proyek (Tahun):</label>
            <input type="number" name="jangka_waktu" required>
        </div>
        <div class="form-group">
            <label>Capital ($M):</label>
            <input type="number" name="capital" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Non-Capital ($M):</label>
            <input type="number" name="non_capital" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Produksi Tahun Ke-1 (Mbbl):</label>
            <input type="number" name="prod_thn_1" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Decline Rate (%):</label>
            <input type="number" name="decline" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Harga Minyak ($/bbl):</label>
            <input type="number" name="harga_minyak" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Opex Per Tahun ($M):</label>
            <input type="number" name="opex" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Metode Depresiasi:</label>
            <select name="metode_depresiasi">
                <option value="straight_line">Garis Lurus (Straight-Line)</option>
                <option value="declining_balance">Saldo Menurun (Declining Balance)</option>
                <option value="sum_years_digits">Jumlah Angka Tahun (SYD)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Pajak (%):</label>
            <input type="number" name="pajak" step="0.01" required>
        </div>
        <button type="submit">Hitung dan Tampilkan Tabel</button>
    </form>
</body>
</html>