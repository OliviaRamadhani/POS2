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

        <div class="card-body">
            <div class="col-md-4 mb-4">
                <label
                    class="form-label fw-bold fs-6 required"
                    for="date-picker"
                >
                    <i class="la la-calendar"></i> Pilih Tanggal
                </label>
                <VuePicDatePicker
                    id="date-picker"
                    v-model="selectedDate"
                    :format="dateFormat"
                    @update:model-value="filterByDate"
                    :min-date="minDate"
                    :max-date="maxDate"
                    class="form-control form-control-lg form-control-solid"
                />
            </div>

            <div v-if="isLoading" class="d-flex justify-content-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div v-else-if="error" class="alert alert-danger" role="alert">
                {{ error }}
            </div>

            <div
                v-else-if="!transactions.length && selectedDate"
                class="alert alert-info"
                role="alert"
            >
                Tidak ada transaksi pada tanggal yang dipilih
            </div>

            <paginate
                v-else
                ref="paginateRef"
                id="table-transactions"
                url="/inventori/laporan"
                :columns="columns"
                :data="transactions"
            />
        </div>
    </div>

    <!-- Transaction Detail Modal -->
    <TransitionRoot appear :show="!!selectedTransaction" as="template">
        <Dialog as="div" class="modal-overlay" @close="closeModal">
            <div class="modal-content">
                <DialogTitle as="div" class="modal-header">
                    <h5>Detail Transaksi</h5>
                    <button class="modal-close" @click="closeModal">
                        &times;
                    </button>
                </DialogTitle>

                <div class="modal-body" v-if="selectedTransaction">
                    <dl class="transaction-details">
                        <div class="detail-item">
                            <dt>ID Pembelian:</dt>
                            <dd>{{ formatId(selectedTransaction.id) }}</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Nama:</dt>
                            <dd>{{ selectedTransaction.customer_name }}</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Pesanan:</dt>
                            <dd>
                                <ul class="items-list">
                                    <li
                                        v-for="item in parseItems(
                                            selectedTransaction.items
                                        )"
                                        :key="item"
                                    >
                                        {{ item }}
                                    </li>
                                </ul>
                            </dd>
                        </div>
                        <div class="detail-item">
                            <dt>Total Harga:</dt>
                            <dd>
                                {{
                                    formatRupiah(
                                        selectedTransaction.total_price
                                    )
                                }}
                            </dd>
                        </div>
                        <div class="detail-item">
                            <dt>Status Pembayaran:</dt>
                            <dd>
                                <span
                                    :class="
                                        getStatusClass(
                                            selectedTransaction.status
                                        )
                                    "
                                >
                                    {{ selectedTransaction.status }}
                                </span>
                            </dd>
                        </div>
                        <div class="detail-item">
                            <dt>Tanggal Transaksi:</dt>
                            <dd>
                                {{ formatDate(selectedTransaction.created_at) }}
                            </dd>
                        </div>
                    </dl>

                    <button
                        @click="markAsProcessed(selectedTransaction)"
                        class="btn btn-primary mt-4 w-100"
                        :disabled="isProcessing"
                    >
                        {{
                            isProcessing
                                ? "Memproses..."
                                : "Tandai Sudah Diproses"
                        }}
                    </button>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup lang="ts">
import { h, ref, onMounted } from "vue";
import { Dialog, DialogTitle, TransitionRoot } from "@headlessui/vue";
import { createColumnHelper } from "@tanstack/vue-table";
import VuePicDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import axios from "@/libs/axios";
import { formatRupiah } from "@/libs/utilss";

interface Pembelian {
    id: number;
    customer_name: string;
    items: string;
    total_price: number;
    status: "Pending" | "Paid" | "Cancelled";
    created_at: string;
    created: boolean;
}

// State management
const transactions = ref<Pembelian[]>([]);
const selectedTransaction = ref<Pembelian | null>(null);
const selectedDate = ref<Date | null>(null);
const isLoading = ref(false);
const error = ref<string>("");
const isProcessing = ref(false);

// Constants
const dateFormat = "yyyy-MM-dd";
const minDate = new Date("2020-01-01");
const maxDate = new Date();

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
                            <th>Nama Pelanggan</th>
                            <th>Produk yang Dibeli</th>
                            <th>Status Pembayaran</th>
                            <th>Total</th>
                            <th>Tanggal Pesanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactions.map((transaction, index) => {
                            const formattedDate = new Date(transaction.created_at).toISOString().split("T")[0];
                            const items = transaction.items.split("\n").map(item => `<div>${item}</div>`).join("");
                            return `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${formatId(transaction.id)}</td>
                                    <td>${transaction.customer_name}</td>
                                    <td>${items}</td>
                                    <td>${transaction.status}</td>
                                    <td>${formatRupiah(transaction.total_price)}</td>
                                    <td>${formattedDate}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7">Total Transaksi: ${transactions.length}</td>
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



const exportTransaction = async () => {
    try {
        const response = await axios.get('/inventori/laporan/export', {
            responseType: 'blob',
        });

        // Buat URL objek dari respons data
        const url = window.URL.createObjectURL(new Blob([response.data]));
        
        // Buat elemen tautan untuk mengunduh file
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'DATA TRANSAKSI SIAM.xlsx');

        // Menyimpan file dengan format tanggal yang telah diformat sebelumnya
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error("Error downloading the Excel file:", error);
    }
};


// Column helper
const column = createColumnHelper<Pembelian>();

// Utility functions
const formatId = (id: number) => id.toString().padStart(3, "0");
const formatDate = (date: string) => new Date(date).toLocaleDateString("id-ID");
const parseItems = (items: string) => items.split("\n").filter(Boolean);
const closeModal = () => (selectedTransaction.value = null);

const getStatusClass = (status: Pembelian["status"]) => ({
    "badge bg-warning": status === "Pending",
    "badge bg-success": status === "Paid",
    "badge bg-danger": status === "Cancelled",
});

// API calls
const filterByDate = async (date: Date | null) => {
    if (!date) {
        transactions.value = [];
        return;
    }

    isLoading.value = true;
    error.value = "";

    try {
        const formattedDate = date.toISOString().split("T")[0];
        const response = await axios.post("/inventori/laporan", {
            date: formattedDate,
        });

        if (response.data.data && Array.isArray(response.data.data)) {
            transactions.value = response.data.data;
        } else {
            throw new Error("Format data tidak valid");
        }
    } catch (err) {
        error.value =
            err instanceof Error
                ? err.message
                : "Terjadi kesalahan saat mengambil data";
        transactions.value = [];
    } finally {
        isLoading.value = false;
    }
};

const markAsProcessed = async (transaction: Pembelian) => {
    isProcessing.value = true;
    try {
        await axios.put(`/inventori/laporan/${transaction.id}/process`);
        transaction.created = true;
        closeModal();
    } catch (err) {
        error.value = "Gagal memproses transaksi";
    } finally {
        isProcessing.value = false;
    }
};

const deletePembelian = async (url: string) => {
    if (!confirm("Apakah Anda yakin ingin menghapus transaksi ini?")) return;

    try {
        await axios.delete(url);
        await filterByDate(selectedDate.value);
    } catch (err) {
        error.value = "Gagal menghapus transaksi";
    }
};

// Table columns configuration
const columns = [
    column.display({
        id: "number",
        header: "No",
        cell: (info) => info.row.index + 1,
    }),
    column.accessor("id", {
        header: "ID Pembelian",
        cell: (info) => formatId(info.getValue()),
    }),
    column.accessor("customer_name", {
        header: "Nama",
    }),
    column.accessor("items", {
        header: "Produk yang Dibeli",
        cell: (info) => {
            const items = parseItems(info.getValue());
            return h(
                "div",
                { class: "items-container" },
                items.map((item) => h("div", { class: "item" }, item))
            );
        },
    }),
    column.accessor("total_price", {
        header: "Total",
        cell: (info) => formatRupiah(info.getValue()),
    }),
    column.accessor("status", {
        header: "Status Pembayaran",
        cell: (info) =>
            h(
                "span",
                {
                    class: getStatusClass(info.getValue()),
                },
                info.getValue()
            ),
    }),
    column.accessor("created_at", {
        header: "Tanggal Pesanan",
        cell: (info) => formatDate(info.getValue()),
    }),
    column.accessor("created", {
        header: "Status Proses",
        cell: (info) => (info.getValue() ? "Sudah Diproses" : "Belum Diproses"),
    }),
    column.accessor("id", {
        header: "Aksi",
        cell: (info) =>
            h("div", { class: "d-flex gap-2" }, [
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-info",
                        onClick: () =>
                            (selectedTransaction.value = info.row.original),
                    },
                    h("i", { class: "la la-eye fs-2" })
                ),
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-danger",
                        onClick: () =>
                            deletePembelian(
                                `/inventori/laporan/${info.getValue()}`
                            ),
                    },
                    h("i", { class: "la la-trash fs-2" })
                ),
            ]),
    }),
];

// Initialize component
onMounted(async () => {
    if (selectedDate.value) {
        await filterByDate(selectedDate.value);
    }
});
</script>

<style scoped>
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

.form-control {
    max-width: 300px;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: grid;
    place-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.modal-close {
    cursor: pointer;
    font-size: 1.5rem;
    background: none;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.modal-close:hover {
    background-color: #f0f0f0;
}

.transaction-details {
    display: grid;
    gap: 16px;
    margin: 0;
}

.detail-item {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 8px;
}

.detail-item dt {
    font-weight: 600;
    color: #666;
}

.items-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.items-list li {
    padding: 4px 0;
    border-bottom: 1px solid #eee;
}

.items-list li:last-child {
    border-bottom: none;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
}

.items-container {
    text-align: left;
}

.item {
    padding: 2px 0;
}

@media (max-width: 768px) {
    .card-body {
        padding: 12px;
    }

    .form-control {
        max-width: 100%;
    }

    .detail-item {
        grid-template-columns: 1fr;
    }
}
</style>    