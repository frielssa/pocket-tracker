<template>
  <div class="w-full min-h-screen bg-neutral-950 text-neutral-100 p-6 md:p-10 box-border">
    <!-- Header Full Width -->
    <header class="w-full flex justify-between items-center pb-6 mb-8 border-b border-neutral-800">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">💰 PocketTracker</h1>
        <p class="text-sm text-neutral-400 mt-1">Sistem Pencatatan Kas & Keuangan Pribadi</p>
      </div>
      <button 
        @click="showForm = !showForm" 
        class="bg-white text-black px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-neutral-200 transition-colors cursor-pointer shadow"
      >
        {{ showForm ? '✕ Tutup Form' : '+ Catat Transaksi' }}
      </button>
    </header>

    <div 
  v-for="item in transactions" 
  :key="item.id" 
  class="flex justify-between items-center p-4 bg-neutral-900 border border-neutral-800 rounded-xl hover:border-neutral-700 transition-all"
>
  <div>
    <p class="font-semibold text-base text-white">{{ item.title }}</p>
    <div class="flex items-center gap-2 text-xs text-neutral-400 mt-1">
      <span class="bg-neutral-800 px-2 py-0.5 rounded text-neutral-300">{{ item.category }}</span>
      <span>•</span>
      <span>{{ String(item.date).split('T')[0] }}</span>
    </div>
  </div>

  <div class="flex items-center gap-4">
    <div :class="['font-bold text-base', isIncome(item.type) ? 'text-emerald-400' : 'text-rose-400']">
      {{ isIncome(item.type) ? '+' : '-' }} Rp {{ formatRupiah(item.amount) }}
    </div>

    <!-- Tombol Hapus Transaksi -->
    <button 
      @click="deleteTransaction(item.id)" 
      class="text-neutral-500 hover:text-rose-500 transition-colors p-1 cursor-pointer"
      title="Hapus Transaksi"
    >
      🗑️
    </button>
  </div>
</div>
    <main class="w-full space-y-8">
      <!-- Form Input Transaksi -->
      <section v-if="showForm" class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl space-y-4 shadow-xl">
        <h3 class="font-semibold text-xl text-white">Tambah Transaksi Baru</h3>
        <form @submit.prevent="addTransaction" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <input 
            v-model="form.title" 
            type="text" 
            placeholder="Keterangan (misal: Gaji, Makan)" 
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white placeholder-neutral-500 outline-none w-full" 
            required 
          />
          <input 
            v-model="form.amount" 
            type="number" 
            min="1000"
            step="1000"
            placeholder="Nominal (Min. Rp 1.000)" 
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white placeholder-neutral-500 outline-none w-full" 
            required 
          />
          <select 
            v-model="form.type" 
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white outline-none cursor-pointer w-full"
          >
            <option value="income">Pemasukan (+)</option>
            <option value="expense">Pengeluaran (-)</option>
          </select>
          <input 
            v-model="form.category" 
            type="text" 
            placeholder="Kategori (misal: Makanan)" 
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white placeholder-neutral-500 outline-none w-full" 
            required 
          />
          <input 
            v-model="form.date" 
            type="date" 
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white outline-none w-full" 
            required 
          />
          <button 
            type="submit" 
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-3 rounded-lg lg:col-span-5 transition-colors cursor-pointer shadow"
          >
            Simpan Transaksi
          </button>
        </form>
      </section>

      <!-- 3 Kartu Ringkasan Saldo -->
      <section class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
        <div class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg">
          <span class="text-xs text-neutral-400 uppercase font-medium tracking-wider">Total Saldo Kas</span>
          <div class="text-3xl font-extrabold mt-3 text-white">Rp {{ formatRupiah(summary.balance) }}</div>
        </div>
        <div class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg">
          <span class="text-xs text-neutral-400 uppercase font-medium tracking-wider">Total Pemasukan</span>
          <div class="text-3xl font-extrabold text-emerald-400 mt-3">Rp {{ formatRupiah(summary.total_income) }}</div>
        </div>
        <div class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg">
          <span class="text-xs text-neutral-400 uppercase font-medium tracking-wider">Total Pengeluaran</span>
          <div class="text-3xl font-extrabold text-rose-400 mt-3">Rp {{ formatRupiah(summary.total_expense) }}</div>
        </div>
      </section>

    
      <!-- Riwayat Transaksi -->
      <section class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 shadow-lg w-full">
        <h3 class="font-semibold text-xl mb-6 text-white">Riwayat Transaksi</h3>
        
        <div class="space-y-3">
          <div 
            v-for="item in transactions" 
            :key="item.id" 
            class="flex justify-between items-center p-4 bg-neutral-950 border border-neutral-800 hover:border-neutral-700 rounded-xl transition-all"
          >
            <div>
              <p class="font-semibold text-base text-white">{{ item.title }}</p>
              <div class="flex gap-2 text-xs text-neutral-400 mt-1">
                <span class="bg-neutral-800 px-2 py-0.5 rounded text-neutral-300">{{ item.category }}</span>
                <span>•</span>
                <span>{{ item.date }}</span>
              </div>
            </div>
            <div :class="['font-bold text-base', isIncome(item.type) ? 'text-emerald-400' : 'text-rose-400']">
              {{ isIncome(item.type) ? '+' : '-' }} Rp {{ formatRupiah(item.amount) }}
            </div>
          </div>

          <div v-if="transactions.length === 0" class="text-center py-12 text-neutral-500">
            <p class="text-lg">Belum ada data transaksi.</p>
            <p class="text-sm mt-1">Klik tombol "+ Catat Transaksi" di atas untuk menambahkan data baru.</p>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const API_URL = 'http://localhost:8000/api/transactions'

const showForm = ref(false)

const form = ref({
  title: '',
  amount: '',
  type: 'income',
  category: '',
  date: new Date().toISOString().split('T')[0]
})

const transactions = ref([])
const summary = ref({
  balance: 0,
  total_income: 0,
  total_expense: 0
})

const fetchTransactions = async () => {
  try {
    const res = await axios.get(API_URL)
    // Mengambil data sesuai struktur response controller
    transactions.value = res.data.data || []
    if (res.data.summary) {
      summary.value = res.data.summary
    }
  } catch (err) {
    console.error('Gagal mengambil data:', err)
  }
}

onMounted(() => {
  fetchTransactions()
})

const addTransaction = async () => {
  if (Number(form.value.amount) < 1000) {
    alert('Nominal transaksi minimal Rp 1.000')
    return
  }

  try {
    await axios.post(API_URL, {
      ...form.value,
      amount: Number(form.value.amount)
    })
    
    // Refresh data aktual dari backend
    await fetchTransactions()

    // Reset Form
    form.value.title = ''
    form.value.amount = ''
    form.value.category = ''
    showForm.value = false
  } catch (err) {
    alert('Gagal menyimpan transaksi. Cek validasi data.')
    console.error(err)
  }
}

const deleteTransaction = async (id) => {
  if (!confirm('Yakin ingin menghapus transaksi ini?')) return

  try {
    await axios.delete(`${API_URL}/${id}`)
    await fetchTransactions()
  } catch (err) {
    alert('Gagal menghapus transaksi.')
    console.error('Gagal menghapus transaksi:', err)
  }
}

const isIncome = (type) => String(type).toLowerCase() === 'income'
const formatRupiah = (val) => new Intl.NumberFormat('id-ID').format(val || 0)
</script>