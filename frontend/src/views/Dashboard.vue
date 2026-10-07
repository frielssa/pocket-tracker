<template>
  <div class="w-full min-h-screen bg-neutral-950 text-neutral-100 p-6 md:p-10 box-border">
    <!-- Header -->
    <header class="w-full flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 mb-8 border-b border-neutral-800 gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">💰 PocketTracker</h1>
        <p class="text-sm text-neutral-400 mt-1">Sistem Pencatatan Kas & Keuangan Pribadi</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <!-- Tombol Ekspor CSV -->
        <button
          @click="downloadCSV"
          class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition-colors cursor-pointer shadow flex items-center gap-2"
        >
          📥 Unduh CSV
        </button>
        <!-- Tombol Form Transaksi -->
        <button
          @click="openCreateForm"
          class="bg-white text-black px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-neutral-200 transition-colors cursor-pointer shadow"
        >
          {{ showForm ? '✕ Tutup Form' : '+ Catat Transaksi' }}
        </button>
        <!-- Tombol Kategori -->
        <router-link
          to="/categories"
          class="border border-neutral-700 text-neutral-300 hover:text-white hover:border-neutral-500 px-4 py-2.5 rounded-lg font-semibold text-sm transition-colors cursor-pointer"
        >
          🏷️ Kategori
        </router-link>
        <!-- Tombol Logout -->
        <button
          @click="logout"
          class="border border-neutral-700 text-neutral-300 hover:text-white hover:border-neutral-500 px-4 py-2.5 rounded-lg font-semibold text-sm transition-colors cursor-pointer"
        >
          Keluar
        </button>
      </div>
    </header>

    <main class="w-full space-y-8">
      <!-- Form Input Transaksi (Tambah & Edit) -->
      <section v-if="showForm" class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl space-y-4 shadow-xl">
        <h3 class="font-semibold text-xl text-white">
          {{ isEditing ? 'Edit Transaksi' : 'Tambah Transaksi Baru' }}
        </h3>
        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
          <CategorySelect v-model="form.category" :type="form.type" />
          <input
            v-model="form.date"
            type="date"
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white outline-none w-full"
            required
          />
          <button
            type="submit"
            :class="[isEditing ? 'bg-amber-600 hover:bg-amber-500' : 'bg-emerald-600 hover:bg-emerald-500', 'text-white font-semibold py-3 rounded-lg lg:col-span-5 transition-colors cursor-pointer shadow']"
          >
            {{ isEditing ? 'Update Transaksi' : 'Simpan Transaksi' }}
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

      <!-- Visualisasi Grafik Ringkasan Bulanan -->
      <section v-if="chartData.length > 0" class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 shadow-lg w-full space-y-4">
        <h3 class="font-semibold text-xl text-white">📊 Ringkasan Keuangan Bulanan</h3>
        <div class="space-y-4 pt-2">
          <div v-for="item in chartData" :key="item.month" class="bg-neutral-950 p-4 rounded-xl border border-neutral-800 space-y-2">
            <div class="flex justify-between items-center text-sm font-semibold text-neutral-300">
              <span>Bulan: {{ item.month }}</span>
              <span class="text-neutral-400">Net: Rp {{ formatRupiah(item.income - item.expense) }}</span>
            </div>

            <!-- Bar Pemasukan -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs text-emerald-400">
                <span>Pemasukan</span>
                <span>Rp {{ formatRupiah(item.income) }}</span>
              </div>
              <div class="w-full bg-neutral-800 h-2 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: getPercentage(item.income, item.income + item.expense) + '%' }"></div>
              </div>
            </div>

            <!-- Bar Pengeluaran -->
            <div class="space-y-1">
              <div class="flex justify-between text-xs text-rose-400">
                <span>Pengeluaran</span>
                <span>Rp {{ formatRupiah(item.expense) }}</span>
              </div>
              <div class="w-full bg-neutral-800 h-2 rounded-full overflow-hidden">
                <div class="bg-rose-500 h-full rounded-full transition-all duration-500" :style="{ width: getPercentage(item.expense, item.income + item.expense) + '%' }"></div>
              </div>
            </div>
          </div>
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

              <!-- Action Buttons -->
              <div class="flex items-center gap-2">
                <button
                  @click="editTransaction(item)"
                  class="text-neutral-400 hover:text-amber-400 transition-colors p-1 cursor-pointer text-lg"
                  title="Edit Transaksi"
                >
                  ✏️
                </button>

                <button
                  @click="deleteTransaction(item.id)"
                  class="text-neutral-400 hover:text-rose-500 transition-colors p-1 cursor-pointer text-lg"
                  title="Hapus Transaksi"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
          <div v-if="transactions.length === 0" class="text-center py-12 text-neutral-500">
            <p class="text-lg">Belum ada data transaksi.</p>
            <p class="text-sm mt-1">Klik tombol "+ Catat Transaksi" di atas untuk menambahkan data baru.</p>
          </div>
        </div>

        <Pagination :meta="meta" label="transaksi" @change="goToPage" />
      </section>
    </main>

    <AiChatWidget @changed="refreshAll" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import AiChatWidget from '@/components/AiChatWidget.vue'
import CategorySelect from '@/components/CategorySelect.vue'
import Pagination from '@/components/Pagination.vue'

const router = useRouter()

const TOKEN_KEY = 'token' // harus sama dengan key yang disimpan di Login.vue

// Instance axios khusus API: token otomatis ditambahkan ke setiap request
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: { Accept: 'application/json' }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Jika token kedaluwarsa / tidak valid -> hapus token dan kembali ke halaman login
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY)
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

const showForm = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = ref({
  title: '',
  amount: '',
  type: 'income',
  category: '',
  date: new Date().toISOString().split('T')[0]
})

const transactions = ref([])
const chartData = ref([])
const summary = ref({
  balance: 0,
  total_income: 0,
  total_expense: 0
})

// Pagination riwayat transaksi
const PER_PAGE = 10
const currentPage = ref(1)
const meta = ref(null)

// Ambil data transaksi (per halaman). Ringkasan saldo tetap dihitung dari seluruh data.
const fetchTransactions = async () => {
  try {
    const res = await api.get('/transactions', {
      params: { page: currentPage.value, per_page: PER_PAGE }
    })
    transactions.value = res.data.data || []
    meta.value = res.data.meta || null
    if (res.data.summary) {
      summary.value = res.data.summary
    }

    // Halaman saat ini sudah kosong (mis. item terakhir di halaman itu baru dihapus): mundur ke halaman terakhir
    if (transactions.value.length === 0 && meta.value && currentPage.value > meta.value.last_page) {
      currentPage.value = Math.max(1, meta.value.last_page)
      return fetchTransactions()
    }
  } catch (err) {
    console.error('Gagal mengambil data transaksi:', err)
  }
}

const goToPage = async (page) => {
  currentPage.value = page
  await fetchTransactions()
}

// Ambil data grafik
const fetchChartData = async () => {
  try {
    const res = await api.get('/transactions/chart')
    chartData.value = res.data.data || []
  } catch (err) {
    console.error('Gagal mengambil data grafik:', err)
  }
}


// Muat ulang semua data (dipanggil setelah AI Agent mengubah data)
const refreshAll = async () => {
  await fetchTransactions()
  await fetchChartData()
}

// Unduh file CSV
const downloadCSV = async () => {
  try {
    const response = await api.get('/transactions/export', { responseType: 'blob' })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `transactions_${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    if (err.response?.status !== 401) alert('Gagal mengunduh CSV.')
  }
}

// Logout: beritahu server (cabut token), lalu bersihkan token di browser
const logout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    // Tetap lanjut logout di sisi browser walau server gagal / token sudah mati
  } finally {
    localStorage.removeItem(TOKEN_KEY)
    router.push('/login')
  }
}

onMounted(() => {
  // Tanpa token, tidak ada gunanya memanggil API
  if (!localStorage.getItem(TOKEN_KEY)) {
    router.push('/login')
    return
  }
  fetchTransactions()
  fetchChartData()
})

const openCreateForm = () => {
  if (showForm.value && !isEditing.value) {
    showForm.value = false
  } else {
    resetForm()
    showForm.value = true
  }
}

const editTransaction = (item) => {
  isEditing.value = true
  editingId.value = item.id
  form.value = {
    title: item.title,
    amount: item.amount,
    type: item.type,
    category: item.category,
    date: String(item.date).split('T')[0]
  }
  showForm.value = true
}

const resetForm = () => {
  isEditing.value = false
  editingId.value = null
  form.value = {
    title: '',
    amount: '',
    type: 'income',
    category: '',
    date: new Date().toISOString().split('T')[0]
  }
}

const handleSubmit = async () => {
  if (Number(form.value.amount) < 1000) {
    alert('Nominal transaksi minimal Rp 1.000')
    return
  }

  try {
    const payload = {
      ...form.value,
      title: form.value.title.trim(),
      amount: Number(form.value.amount)
    }

    if (isEditing.value) {
      await api.put(`/transactions/${editingId.value}`, payload)
    } else {
      await api.post('/transactions', payload)
      currentPage.value = 1
    }

    await fetchTransactions()
    await fetchChartData()
    resetForm()
    showForm.value = false
  } catch (err) {
    if (err.response?.status === 401) return // sudah dialihkan ke login oleh interceptor

    // Tampilkan pesan validasi dari Laravel (422) jika ada
    const errors = err.response?.data?.errors
    if (errors) {
      alert(Object.values(errors).flat().join('\n'))
    } else {
      alert('Gagal menyimpan transaksi.')
    }
    console.error('Detail Error:', err.response)
  }
}

const deleteTransaction = async (id) => {
  if (!confirm('Yakin ingin menghapus transaksi ini?')) return

  try {
    await api.delete(`/transactions/${id}`)
    await fetchTransactions()
    await fetchChartData()
  } catch (err) {
    if (err.response?.status !== 401) alert('Gagal menghapus transaksi.')
  }
}

const isIncome = (type) => String(type).toLowerCase() === 'income'
const formatRupiah = (val) => new Intl.NumberFormat('id-ID').format(val || 0)
const getPercentage = (value, total) => total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0
</script>