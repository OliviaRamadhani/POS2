<script setup lang="ts">
import { h, ref, onMounted } from "vue";
import { useDelete } from "@/libs/hooks";
import { createColumnHelper } from "@tanstack/vue-table";
import type { Pembelian } from "@/types/laporan"; // Ganti dengan path yang sesuai
import axios from "@/libs/axios";
import { formatRupiah } from "@/libs/utilss";
import DatePicker from 'vue3-datepicker';

const column = createColumnHelper<Pembelian>();
const paginateRef = ref<any>(null);
const transactions = ref<Pembelian[]>([]); // Menyimpan data transaksi
const selectedTransaction = ref<Pembelian | null>(null);
const selectedDate = ref<string>('');


// Fungsi filter transaksi berdasarkan tanggal yang dipilih
const filterByDate = async () => {
    if (!selectedDate.value) {
        transactions.value = []; // Kosongkan data jika tidak ada tanggal dipilih
        return;
    }

    try {
        const formattedDate = new Date(selectedDate.value).toISOString().split('T')[0]; // Format ke YYYY-MM-DD
        const response = await axios.post('/inventori/laporan', {
            date: formattedDate, // Kirimkan tanggal yang dipilih ke server
        });
        
        // Pastikan respons berisi data yang diharapkan
        if (response.data && Array.isArray(response.data)) {
            transactions.value = response.data; // Update data transaksi berdasarkan respons
        } else {
            console.error('Data tidak valid:', response.data);
            transactions.value = []; // Kosongkan data jika ada error
        }
    } catch (error) {
        console.error('Error fetching transactions', error);
        transactions.value = []; // Kosongkan data jika ada error
    }
};

// Panggil filterByDate ketika komponen dimuat untuk mendapatkan data awal (opsional)
filterByDate();

const { delete: deletePembelian } = useDelete({
    onSuccess: () => paginateRef.value.refetch(),
})

// Mendapatkan data transaksi saat komponen dimuat
onMounted(async () => {
    try {
        const response = await axios.post('/inventori/laporan');
        transactions.value = response.data; 
    } catch (error) {
        console.error('Error fetching transactions:', error);
    }
});

// Fungsi untuk mencetak laporan transaksi
const printTransaction = async () => {
    try {
        const response = await axios.post('/inventori/laporan');
        const transactions = response.data.data;

        // Memformat data transaksi
        const printContent = `
            <html>
            <head>
                <title>Laporan Transaksi</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                    h1 {
                        text-align: center;
                        color: #0070C0;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        margin-top: 20px;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 10px;
                        text-align: center;
                        font-size: 14px;
                    }
                    th {
                        background-color: #0070C0;
                        color: white;
                    }
                    tr:nth-child(even) {
                        background-color: #f2f2f2;
                    }
                    tr:hover {
                        background-color: #ddd;
                    }
                    tfoot {
                        font-weight: bold;
                        background-color: #0070C0;
                        color: white;
                    }
                </style>
            </head>
            <body>
                <h1>Laporan Transaksi</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Pembelian</th>
                            <th>Status Pembayaran</th>
                            <th>Total</th>
                            <th>Tanggal Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactions.map((transaction, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${transaction.pembelian_id}</td>
                                <td>${transaction.status}</td>
                                <td>${formatRupiah(transaction.total_price)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">Total Transaksi: ${transactions.length}</td>
                        </tr>
                    </tfoot>
                </table>
            </body>
            </html>
        `;

        const newWindow = window.open('', '_blank');
        if (newWindow) {
 newWindow.document.write(printContent);
            newWindow.document.close();
            newWindow.print();
            newWindow.close();
        } else {
            console.error("Gagal membuka jendela baru.");
        }

    } catch (error) {
        console.error("Error fetching transactions for printing:", error);
    }
};


// Fungsi untuk mengekspor laporan transaksi ke Excel
const exportTransaction = async () => {
    try {
        const response = await axios.get('/inventori/laporan/export', {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'DATA TRANSAKSI SIAM.xlsx');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error("Error downloading the Excel file:", error);
    }
};


// Mendapatkan data transaksi saat komponen dimuat
onMounted(async () => {
    try {
        const response = await axios.post('/inventori/laporan');
        transactions.value = response.data;
        paginateRef.value.refetch(); 
    } catch (error) {
        console.error('Error fetching transactions:', error);
    }
});

const markAsProcessed = async (transaction: Pembelian) => {
    // Simpan status pesanan ke backend
    transaction.created = true; // Mark as processed
    try {
        await axios.put(`/inventori/laporan/${transaction.id}`, {
            created: transaction.created,
        });
        paginateRef.value.refetch();
    } catch (error) {
        console.error('Error updating transaction status:', error);
    }
};

const columns = [
    column.display({
        header: "No",
        cell: (cell) => {
            return cell.row.index + 1; // Menggunakan indeks baris sebagai nomor urut
        },
    }),
    column.accessor("id", {
        header: "ID Pembelian",
        cell: (cell) => cell.getValue().toString().padStart(3, '0'), // Memastikan minimal 3 digit dengan padding '0'
    }),
    column.accessor("customer_name", {
        header: "Nama",
    }),
    column.accessor("items", {
        header: "Produk yang Dibeli",
        cell: (cell) => {
            // Pisahkan setiap produk dengan <br /> untuk membuat jarak vertikal
            const itemsList = cell.getValue().split("\n").map(item => `<div>${item}</div>`).join('');
            return h('div', { innerHTML: itemsList }); // Gunakan innerHTML untuk render div dengan newline
        }
    }),
    column.accessor("total_price", {
        header: "Total",
        cell: (cell) => formatRupiah(cell.getValue()),
    }),
    column.accessor("status", {
        header: "Status Pembayaran",
    }),
    column.accessor("created_at", {
        header: "Tanggal Pesanan",
        cell: (cell) => {
            return new Date(cell.getValue()).toLocaleDateString("id-ID");
        },
    }),
    column.accessor("created", {
        header: "Pesanan Dibuat",
        cell: (cell) => cell.getValue() ? "On Process" : "Procces",
    }),
    column.accessor("id", {
        header: "Aksi",
        cell: (cell) =>
            h("div", { class: "d-flex gap-2" }, [
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-info",
                        onClick: () => selectedTransaction.value = cell.row.original,
                    },
                    h("i", { class: "la la-eye fs-2" })
                ),
                // h(
                //     "button",
                //     {
                //         class: "btn btn-sm btn-icon btn-success",
                //         disabled: cell.row.original.created, // Disable if already processed
                //         onClick: () => markAsProcessed(cell.row.original),
                //     },
                //     h("i", { class: "fa fa-check fs-2" }) // Font Awesome check icon
                    
                // ),
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-danger",
                        onClick: () =>
                            deletePembelian(`/inventori/laporan/${cell.getValue()}`),
                    },
                    h("i", { class: "la la-trash fs-2" })
                ),
            ]),
    }),
];

const refresh = () => paginateRef.value.refetch();
</script>

<template>
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center">
        <h2 class="mb-0">Laporan Transaksi</h2>
  
        <!-- Button for printing the reservations list -->
        <button
          type="button"
          class="btn btn-sm btn-secondary ms-auto"
          @click="printTransaction"
        >
          Print
          <i class="la la-print"></i>
        </button>
  
        <!-- Button for exporting the reservations list to Excel -->
        <button
          type="button"
          class="btn btn-sm btn-secondary ms-2"
          @click="exportTransaction"
        >
          Export Excel
          <i class="la la-file-excel"></i>
        </button>
      </div>
  
      <!-- filter by date -->
      <div class="card-body">
    <div class="col-md-4 mb-4">
      <label class="form-label fw-bold fs-6 required" for="reservation-date">
        <i class="la la-calendar"></i> Pilih Tanggal
      </label>
      <DatePicker
        v-model="selectedDate"
        :format="dateFormat"
        @change="filterByDate"
        class="form-control form-control-lg form-control-solid"
      />
    </div>
  </div>
  
        <paginate
          ref="paginateRef"
          id="table-transactions"
          url="/inventori/laporan"
          :columns="columns"
          :data="transactions"
        ></paginate>
      </div>
    
  
    <!-- Detail Transaksi Modal -->
    <div v-if="selectedTransaction" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h5>Detail Transaksi</h5>
          <button class="modal-close" @click="selectedTransaction = null">
            &times;
          </button>
        </div>
  
        <div class="modal-body">
          <p><strong>ID Pembelian:</strong> {{ selectedTransaction?.id }}</p>
          <p><strong>Nama:</strong> {{ selectedTransaction?.customer_name }}</p>
          <p><strong>Pesanan:</strong> {{ selectedTransaction?.items }}</p>
          <p><strong>Total Harga:</strong> {{ formatRupiah(selectedTransaction?.total_price) }}</p>
          <p><strong>Status Pembayaran:</strong> {{ selectedTransaction?.status }}</p>
          <p><strong>Tanggal Transaksi:</strong> {{ new Date(selectedTransaction?.created_at).toLocaleDateString("id-ID") }}</p>
          <p><strong>Status Pesanan Dibuat:</strong> {{ selectedTransaction?.created ? 'On Process' : 'Procces' }}</p>
        </div>
  
        <button class="btn btn-secondary" @click="selectedTransaction = null">
          Tutup Detail
        </button>
      </div>
    </div>
  </template>

<style scoped>
  /* CARD STYLING */
  .card {
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 16px;
  }
  
  .card-body {
    padding: 16px;
  }
  
  /* FORM INPUT */
  .form-control {
    max-width: 300px;
  }
  
  /* MODAL STYLING */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  
  .modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .modal-close {
    cursor: pointer;
    font-size: 1.5rem;
    background: none;
    border: none;
  }
  
  .modal-body {
    margin-top: 20px;
  }
  
  /* TABLE STYLING */
  table {
    border-collapse: collapse;
    width: 100%;
    margin: 0;
    table-layout: auto; /* Ensure table adapts based on content */
  }
  
  th,
  td {
    border: 1px solid black;
    padding: 8px;
    text-align: center;
  }
  
  th {
    background-color: #0070C0;
 color: white;
  }
  
  tr:nth-child(even) {
    background-color: #f2f2f2;
  }
  
  tr:hover {
    background-color: #ddd;
  }
  
  tfoot {
    font-weight: bold;
    background-color: #0070C0;
    color: white;
  }
  
  /* BUTTON STYLING */
  .btn {
    transition: background-color 0.3s ease;
  }
  
  .btn:hover {
    background-color: #0062a0;
  }
  
  @media (max-width: 768px) {
    .card-body {
      padding: 8px;
    }
  
    .form-control {
      max-width: 100%;
    }
  }
</style>