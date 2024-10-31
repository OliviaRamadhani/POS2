<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembelian - {{ $pembelian->uuid }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000;
        }
        .receipt {
            width: 280px;
            margin: 20px auto;
            padding: 15px;
            border: 1px solid #000;
            text-align: center;
        }
        .logo img {
            width: 150px;
            height: auto;
        }
        h1, h2, h3 {
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        h3 {
            font-size: 12px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 5px 0;
            font-size: 12px;
        }
        th {
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        td {
            text-align: right;
        }
        .left-align {
            text-align: left;
        }
        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
            font-size: 14px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
        }
        .customer-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin: 5px 0;
        }
        .separator {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .id-pembelian {
            font-size: 50px; /* Increase font size */
            text-align: center; /* Center the text */
            font-weight: bold; /* Make it bold for emphasis */
            margin: 10px 0; /* Add margin for spacing */
        }
    </style>
</head>
<body>

    <div class="receipt">
        <!-- BAGIAN 1: Logo dan informasi perusahaan -->
        <div class="logo">
            <img src="{{ public_path('spice.png') }}" alt="Logo">
        </div>
        <h1>Siam Spice Co.</h1>
        <h3> Jl. Sambikerep No. 78</h3>
        <h3> IG : @siamspiceco_</h3>
        
        <!-- BAGIAN 2: Informasi Customer, ID Pembelian, Tanggal, dan Jam -->
        <div class="customer-info id-pembelian">
            <span>{{ $pembelian->id }}</span>
        </div>
        <div class="customer-info">
            <span>Tanggal:</span>
            <span>{{ $pembelian->created_at->format('d-m-Y') }}</span>
        </div>
        <div class="customer-info">
            <span>Nama :</span>
            <span>{{ $pembelian->customer_name }}</span>
        </div>
        
        <hr>

        <!-- BAGIAN 3: Daftar item pembelian -->
        <table>
            <thead>
                <tr>
                    <th class="left-align">Nama Item</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pembelian->item as $item)
                    <tr>
                        <td class="left-align">{{ $item['name'] }}</td>
                        <td>Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- BAGIAN 4: Total harga -->
        <div class="total">
            Total: Rp {{ number_format($pembelian->total_price, 0, ',', '.') }}
        </div>

        <!-- BAGIAN 5: Footer -->
        <div class="footer">
            Terima Kasih atas Kunjungan Anda!
        </div>
    </div>

</body>
</html>
