<template>
  <div class="payment-page">
    <video autoplay muted loop class="background-video">
      <source src="@/assets/images/asiks.mp4" type="video/mp4">
      Your browser does not support the video tag.
    </video>
    <h1 class="title" :style="{ color: '#091057' }"></h1>
    <img src="@/assets/images/spice.png" alt="Logo" class="logo" /> <!-- Tambahkan Logo di sini -->

    <router-link to="/landing/PAGE">
              <button class="btn">BACK</button>
            </router-link>

            <button @click="handleGeneratePDF" class="btn">Download PDF</button>


    <div v-if="receiptVisible" class="receipt">
      <h2>Struk Pembayaran</h2>
      <p>Nomer Antrian: {{ queueNumber }}</p>

      
      <div class="summary">
        <h3>Detail Pembayaran</h3>
        <p>Metode Pembayaran: {{ selectedPayment }}</p>
        <h4>Item Pesanan:</h4>
    <ul>
      <li v-for="item in cart" :key="item.id">
        {{ item.name }} - {{ item.quantity }} x {{ currency(item.price) }} = {{ currency(item.price * item.quantity) }}
      </li>
    </ul>
        <p><strong>Total Pembayaran: {{ currency(total) }}</strong></p>
        <p class="payment-instruction">Silakan bayar di kasir Melalui Cash / Debit </p> <!-- Tambahkan teks di sini -->
      </div>
     
      <div v-if="selectedPayment === 'debit'" class="qr-code">
        <h3>Scan QR Code & QRIS Payment </h3>
        <img :src="generateQRCode()" alt="QR Code" />
      </div>
    </div>

    </div>
    <!-- Modal Konfirmasi -->
    <div v-if="showConfirmationModal" class="modal">
      <div class="modal-content">
        <h3>Konfirmasi Pembayaran</h3> click
        <p>Apakah Anda yakin ingin melanjutkan pembayaran?</p> 
        <button @click="confirmPayment" class="btn btn-success">Ya</button>
        <button @click="cancelPayment" class="btn btn-secondary">Tidak</button>
      </div>
    </div>
    
</template>

<script setup lang="ts">
import { currency } from '@/libs/utils';
import { useRoute } from 'vue-router';
import { ref, computed, onMounted } from 'vue';
import QRCode from 'qrcode-generator';
import jsPDF from 'jspdf';
import axios from 'axios';
import { useDownloadPdf } from '@/libs/hooks';
import { toast } from 'vue3-toastify';


const uuid = ref(); // UUID pembelian yang diambil dari transaksi
const route = useRoute();
const cart = ref([]);
const selectedPayment = ref(null);
const receiptVisible = ref(false);
const queueNumber = ref(null);
const showConfirmationModal = ref(false);
const orderId = ref(""); // This should be dynamically generated
const totalAmount = ref(0);
const snapToken = '{{ $snapToken }}';
const blueColor = '#0000FF';  // Atau warna biru hex atau nama warna

const { download: downloadPdf } = useDownloadPdf({
  onSuccess: () => {
    toast.success('Success download')
  }
})

const handleGeneratePDF = async () => {
  console.log(route.params); // Tambahkan log ini
  if (uuid.value) {
    try {
      // Panggil API backend untuk menghasilkan PDF
      downloadPdf(`/pembelian/${uuid.value}/pdf`)

      // Buat URL dari file PDF
      // const fileURL = window.URL.createObjectURL(new Blob([response.data]));
      
      // // Buat link untuk download
      // const fileLink = document.createElement('a');
      // fileLink.href = fileURL;
      // fileLink.setAttribute('download', struk_pembelian_${uuid}.pdf);

      // // Klik link secara otomatis untuk download
      // document.body.appendChild(fileLink);
      // fileLink.click();
      // document.body.removeChild(fileLink);
    } catch (error) {
      console.error('Gagal mengambil PDF:', error);
    }
  } else {
    console.error('UUID pembelian tidak tersedia');
  }
};



// const generatePDF = async (uuid: string) => {
//   try {
//     // Panggil API untuk mendapatkan data pembelian
//     const response = await axios.get(/api/pembelian/${uuid});

//     if (response.data.success) {
//       const pembelian = response.data.data;

//       // Inisialisasi jsPDF
//       const doc = new jsPDF();

//       // Tambahkan judul
//       doc.setFontSize(20);
//       doc.text('Struk Pembelian', 10, 10);

//       // Informasi dasar
//       doc.setFontSize(12);
//       doc.text(ID Pembelian: ${pembelian.uuid}, 10, 20);
//       doc.text(Tanggal: ${new Date(pembelian.created_at).toLocaleDateString()}, 10, 30);

//       // Tambahkan tabel produk
//       let startY = 40;
//       doc.text('Produk', 10, startY);
//       doc.text('Kuantitas', 120, startY);

//       // Data produk dan kuantitas
//       const items = pembelian.items.split("\n"); // Misalkan item disimpan sebagai string terpisah oleh "\n"
//       items.forEach((item: string, index: number) => {
//         startY += 10;
//         const [productName, quantity] = item.split(":");
//         doc.text(productName.trim(), 10, startY);
//         doc.text(quantity.trim(), 120, startY);
//       });

//       // Tambahkan total harga
//       startY += 20;
//       doc.setFontSize(14);
//       doc.text(Total Harga: Rp ${pembelian.total_price}, 10, startY);

//       // Tambahkan status pembayaran
//       startY += 10;
//       doc.text(Status Pembayaran: ${pembelian.status}, 10, startY);

//       // Download PDF
//       doc.save(struk_pembelian_${pembelian.uuid}.pdf);
//     } else {
//       console.error('Pembelian tidak ditemukan');
//     }
//   } catch (error) {
//     console.error('Gagal mengambil data pembelian:', error);
//   }
// };




function selectPayment(method: string) {
  selectedPayment.value = method;
}

// cara penggunaan: handlePayment(1234-1234-1234-1234) <--
function handlePayment() {
  const pembelian_id = route.params.pembelian_id
  console.log(pembelian_id)
  // const cartQuery: any = route.query.cart;
  // const cart = JSON.parse(cartQuery);

  // // Dapatkan UUID dari url
  // const uuid: any = cart[0]?.uuid;

  // if (!uuid) {
  //   console.warn('UUID tidak terdeteksi')
  //   return;
  // }

  // axios.post(/orders/checkout/${uuid})
  //   .then(response => {
      // if (window.snap) {
      //   window.snap.pay(response.data.payment_url, {
      //     onSuccess: (result) => {
      //       console.log("Pembayaran berhasil:", result);
      //     }
      //   });
      // }
  //   })
  //   .catch(error => {
  //       console.error('Error during order checkout:', error);
  //   });
}




function showReceipt() {
  queueNumber.value = Math.floor(Math.random() * 1000);
  receiptVisible.value = true;
  showConfirmationModal.value = false;
}

function confirmPayment() {
  queueNumber.value = Math.floor(Math.random() * 1000);
  receiptVisible.value = true;
  showConfirmationModal.value = false; // Tutup modal setelah konfirmasi
}

// Fungsi untuk membatalkan pembayaran
function cancelPayment() {
  showConfirmationModal.value = false; // Tutup modal tanpa melakukan apa-apa
}

function reset() {
  selectedPayment.value = null;
  receiptVisible.value = false;
  queueNumber.value = null;
}

onMounted(() => {
  const cartParam = route.query.cart;
  uuid.value = route.params.uuid
  if (cartParam) {
    cart.value = JSON.parse(cartParam);
  }

  console.log(cartParam)
});

const total = computed(() => {
  return cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0);
});

const totalItems = computed(() => {
  return orderItems.value.reduce((acc, item) => acc + item.quantity, 0);
});


function generateQRCode() {
  const qr = QRCode(0, 'H');
  const paymentInfo = `Pembayaran: ${total.value}`;
  qr.addData(paymentInfo);
  qr.make();
  return qr.createDataURL(4);
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

/* Set font keseluruhan */
body, .container, .btn {
  font-family: 'Poppins', sans-serif;
}

/* Gaya utama untuk tombol */
.btn {
  padding: 20px 25px;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  background-color: #193553;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0px 4px 10px rgba(0, 123, 255, 0.3);
  transition: all 0.3s ease;
  margin: 5px;
}

/* Hover effect */
.btn:hover {
  background-color: #ffffff;
  color: #cbad53;
  box-shadow: 0px 6px 15px rgba(0, 86, 179, 0.4);
  transform: translateY(-2px);
}

.background-video {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: -1;
}

.payment-page {
  max-width: 600px;
  margin: auto;
  text-align: center;
  padding: 20px;
  background: #ffffff;
  border-radius: 10px;
}

.title {
  font-size: 28px;
  color: #091057;
  margin-bottom: 20px;
}

.payment-options {
  display: flex;
  justify-content: space-around;
  margin: 20px 0;
}

.payment-option {
  cursor: pointer;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 12px;
  transition: all 0.3s;
  background: #f0f0f0;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  text-align: center;
  flex: 1;
  margin: 0 10px;
}

.payment-option:hover {
  background: #e0f7fa;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.btn-success {
  margin-top: 20px;
  background-color: #28a745;
}

.btn-black {
  background-color: #000000;
  color: #ffffff;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s;
}

.btn-black:hover {
  background-color: #444444;
}

.btn-secondary {
  background-color: #000000;
}

.receipt {
  margin-top: 90px;
  padding: 20px 150px;
  background: #e0f7fa;
  border-radius: 20px;
  max-width: 800px;
  width: 100%;
  height: auto;
  overflow: auto;
  margin: 20px auto;
}

.qr-code {
  border-radius: 30px;
  margin-top: 20px;
  margin-bottom: auto;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #ffffff;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  text-align: center;
  max-width: 500px;
  width: 90%;
  transition: transform 0.3s ease;
  transform: translateY(20px);
}

.modal-content h3 {
  font-size: 24px;
  margin-bottom: 10px;
  color: #333;
}

.modal-content p {
  font-size: 16px;
  margin-bottom: 20px;
  color: #555;
}

.modal-content .btn {
  padding: 12px 24px;
  border: none;
  border-radius: 5px;
  color: #ffffff;
  cursor: pointer;
  font-size: 16px;
  margin: 0 10px;
  transition: background 0.3s ease;
}

.modal-content .btn-success {
  background-color: #000000;
}

.modal-content .btn-secondary {
  background-color: #8e8e8e;
}

.modal-content .btn-success:hover {
  background-color: #006400;
}

.modal-content .btn-secondary:hover {
  background-color: #b30000;
}

.qr-code img {
  width: 200px;
  height: 200px;
}

.logo {
  max-width: 400px;
  margin: 30px auto;
  display: block;
}

.summary {
  margin-top: 20px;
}

.payment-instruction {
  font-size: 20px;
  color: #000000;
  margin-top: 10px;
}
</style>
