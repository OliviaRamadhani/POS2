<?php

namespace App\Http\Controllers;

use App\Exports\TransactionReportExport;
use App\Models\Pembelian;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class PembelianController extends Controller
{
    public function store(Request $request)
    {
        // Validasi data
        $validate = $request->validate([
            'user_id' => 'nullable',
            'total_price' => 'required',
            'products_id' => 'required|array', // Validasi produk ID
            'products_id.*' => 'exists:products,id', // Validasi setiap ID produk
            'name' => 'required|array',
            'name.*' => 'required|string',
            'product_quantity' => 'required|array', // Validasi kuantitas
            'product_quantity.*' => 'integer|min:1', // Validasi kuantitas harus integer dan lebih dari 0
            'customer_name' => 'required|string',
        ]);

        // Pastikan panjang array products_id dan product_quantity sesuai
        if (count($request->products_id) !== count($request->product_quantity)) {
            return response()->json(['message' => 'Jumlah produk dan kuantitas tidak sesuai.'], 400);
        }

       // Inisialisasi string untuk menyimpan item
       $items = '';
       foreach ($request->products_id as $index => $productId) {
           $product = Product::find($productId);
           if ($product) {
               // Format nama produk dan kuantitas menjadi string, pisahkan dengan koma jika lebih dari satu
               $items .= $product->name . ': ' . $request->product_quantity[$index] . "\n";
               
           }
       }

       // Hapus koma terakhir dan spasi ekstra
    //    $items = rtrim($items, ', ');

       // Simpan pembelian dengan produk dalam satu kolom string biasa
       $pembelian = Pembelian::create(array_merge($validate, [
           'items' => $items, // Simpan array produk dan kuantitas sebagai string biasa
       ]));
        
        $pembelian->item()->syncWithoutDetaching($request->products_id); // Menyinkronkan produk

        // Konfigurasi Midtrans
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = false; // Ubah ke true jika di produksi
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        // Siapkan parameter transaksi untuk Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $pembelian->uuid, // Menggunakan UUID sebagai order_id
                'gross_amount' => $request->total_price,
            ],
        ];

        try {
            // Dapatkan Snap Token dari Midtrans
            $snapToken = \Midtrans\Snap::getSnapToken($params);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'Pembelian' => $pembelian,
            'payment_url' => $snapToken,
        ]);
    }

    // Fungsi callback untuk menangani notifikasi dari Midtrans
    public function callback(Request $request)
    {
        $notification = $request->all();

        // Contoh bagaimana menangani notifikasi status pembayaran
        $transactionStatus = $notification['transaction_status'];
        $orderId = $notification['order_id'];

        // Cari pembelian berdasarkan order_id (UUID)
        $pembelian = Pembelian::where('uuid', $orderId)->first();

        if (!$pembelian) {
            return response()->json(['message' => 'Pembelian tidak ditemukan'], 404);
        }

        // Perbarui status berdasarkan status transaksi dari Midtrans
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $pembelian->status = 'Paid';
        } elseif ($transactionStatus === 'pending') {
            $pembelian->status = 'Unpaid';
        } elseif ($transactionStatus === 'deny' || $transactionStatus === 'expire' || $transactionStatus === 'cancel') {
            $pembelian->status = 'Unpaid';
        }

        // Simpan perubahan status
        $pembelian->save();

        return response()->json(['message' => 'Notifikasi pembayaran telah diproses']);
    }

    public function index(Request $request)
{
    // Inisialisasi query untuk mendapatkan pembelian
    $query = Pembelian::query();

    // Cek apakah ada input tanggal
    if ($request->has('date') && $request->input('date')) {
        // Jika ada tanggal, filter berdasarkan tanggal tersebut
        $query->whereDate('created_at', $request->input('date'));
    }

    // Urutkan berdasarkan tanggal terbaru
    $query->orderBy('created_at', 'desc');

    // Ambil data pembelian dengan pagination sebelum menjadi Collection
    $pembelians = $query->paginate(10);

    return response()->json($pembelians);
}


    public function destroy($id)
    {
        // Cari pembelian berdasarkan ID
        $pembelian = Pembelian::find($id);

        if (!$pembelian) {
            return response()->json(['message' => 'Pembelian tidak ditemukan'], 404);
        }

        // Hapus pembelian
        $pembelian->delete();

        return response()->json(['message' => 'Pembelian berhasil dihapus']);
    }


    
    public function exportToExcel()
    {
        return Excel::download(new TransactionReportExport, 'LAPORAN PEMBELIAN SIAM.xlsx');
    }
    

    // Fungsi untuk mencetak laporan transaksi
    
    
    // Fungsi untuk mencetak laporan transaksi
    public function printTransaction(Request $request)
{
    $data = Pembelian::select('id', 'uuid', 'customer_name', 'items', 'total_price', 'status', 'created_at') // Tambahkan kolom 'customer_name' dan 'items'
        ->when($request->search, function (Builder $query, string $search) {
            $query->where('uuid', 'like', value: "%$search%")
                ->orWhere('customer_name', 'like', value: "%$search%")
                ->orWhere('items', 'like', value: "%$search%")
                ->orWhere('total_price', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%")
                ->orWhereDate('created_at', '=', $search); // Atur pencarian tanggal sesuai kebutuhan
        })->get();

    return response()->json([
        'success' => true,
        'data' => $data,
    ]);
}


public function generatePDF($uuid)
{
    // Cari pembelian berdasarkan UUID
    $pembelian = Pembelian::with('item')->where('uuid', $uuid)->first();

    if (!$pembelian) {
        return response()->json(['error' => 'Pembelian tidak ditemukan'], 404);
    }

    try {
        // Kirim data ke view untuk pembuatan PDF
        $pdf = PDF::loadView('pdf.pembelian', [
            'pembelian' => $pembelian,
            'quantity' => $pembelian->item->count()
        ]);

        $pdf->setPaper('A5');
        $pdf->output();

        return $pdf->stream("Invoice{$uuid}". '.pdf');

    } catch (\Exception $e) {
        // Log error untuk debugging
        \Log::error('Error generating PDF: ' . $e->getMessage());
        return response()->json(['error' => 'Terjadi kesalahan saat menghasilkan PDF: ' . $e->getMessage()], 500);
    }
}


}