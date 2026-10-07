<template>
  <div class="w-full min-h-screen bg-neutral-950 text-neutral-100 p-6 md:p-10 box-border">
    <!-- Header -->
    <header class="w-full flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 mb-8 border-b border-neutral-800 gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">🏷️ Kategori</h1>
        <p class="text-sm text-neutral-400 mt-1">Kelola kategori dan lihat ringkasan pemakaiannya</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <input
          v-model="month"
          type="month"
          class="bg-neutral-900 border border-neutral-700 focus:border-white rounded-lg px-3 py-2.5 text-sm text-white outline-none"
        />
        <button
          @click="month = ''"
          :class="[
            'px-4 py-2.5 rounded-lg font-semibold text-sm border cursor-pointer transition-colors',
            month === '' ? 'bg-white text-black border-white' : 'border-neutral-700 text-neutral-300 hover:border-neutral-500'
          ]"
        >
          Semua waktu
        </button>
        <router-link
          to="/dashboard"
          class="border border-neutral-700 text-neutral-300 hover:text-white hover:border-neutral-500 px-4 py-2.5 rounded-lg font-semibold text-sm transition-colors"
        >
          ← Dashboard
        </router-link>
      </div>
    </header>

    <main class="w-full space-y-8">
      <!-- Ringkasan periode -->
      <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg">
          <span class="text-xs text-neutral-400 uppercase font-medium tracking-wider">Total Pemasukan</span>
          <div class="text-3xl font-extrabold text-emerald-400 mt-3">Rp {{ formatRupiah(totals.income) }}</div>
        </div>
        <div class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg">
          <span class="text-xs text-neutral-400 uppercase font-medium tracking-wider">Total Pengeluaran</span>
          <div class="text-3xl font-extrabold text-rose-400 mt-3">Rp {{ formatRupiah(totals.expense) }}</div>
        </div>
      </section>

      <!-- Form tambah kategori -->
      <section class="bg-neutral-900 border border-neutral-800 p-6 rounded-2xl shadow-lg space-y-4">
        <h3 class="font-semibold text-xl text-white">Tambah Kategori</h3>
        <form @submit.prevent="addCategory" class="grid grid-cols-1 md:grid-cols-[1fr_12rem_5rem_auto] gap-4 items-center">
          <input
            v-model="newForm.name"
            type="text"
            maxlength="50"
            placeholder="Nama kategori (misal: Kopi)"
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white placeholder-neutral-500 outline-none w-full"
            required
          />
          <select
            v-model="newForm.type"
            class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white outline-none cursor-pointer w-full"
          >
            <option value="expense">Pengeluaran (-)</option>
            <option value="income">Pemasukan (+)</option>
          </select>
          <input
            v-model="newForm.color"
            type="color"
            class="w-full h-12 rounded-lg bg-neutral-950 border border-neutral-700 cursor-pointer p-1"
            title="Warna kategori"
          />
          <button
            type="submit"
            class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-6 py-3 rounded-lg transition-colors cursor-pointer shadow"
          >
            Tambah
          </button>
        </form>
      </section>

      <!-- Daftar kategori per tipe -->
      <section v-for="section in sections" :key="section.type" class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 shadow-lg space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-xl text-white">{{ section.title }}</h3>
          <span class="text-xs text-neutral-500">{{ section.items.length }} kategori</span>
        </div>

        <p v-if="loading && !section.items.length" class="text-neutral-500 text-sm">Memuat...</p>
        <p v-else-if="!section.items.length" class="text-neutral-500 text-sm">Belum ada kategori.</p>

        <div
          v-for="c in section.items"
          :key="c.id"
          class="bg-neutral-950 border border-neutral-800 rounded-xl p-4 space-y-3"
        >
          <!-- Mode edit -->
          <div v-if="editingId === c.id" class="flex flex-wrap items-center gap-2">
            <input v-model="editForm.color" type="color" class="w-10 h-10 rounded bg-transparent cursor-pointer" />
            <input
              v-model="editForm.name"
              maxlength="50"
              class="flex-1 min-w-[8rem] bg-neutral-900 border border-neutral-700 focus:border-white rounded-lg p-2 text-sm text-white outline-none"
              @keyup.enter="saveEdit(c)"
            />
            <button @click="saveEdit(c)" class="bg-amber-600 hover:bg-amber-500 text-white px-3 py-2 rounded-lg text-sm font-semibold cursor-pointer">
              Simpan
            </button>
            <button @click="cancelEdit" class="border border-neutral-700 text-neutral-300 hover:border-neutral-500 px-3 py-2 rounded-lg text-sm cursor-pointer">
              Batal
            </button>
          </div>

          <!-- Mode tampil -->
          <template v-else>
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="inline-block w-3.5 h-3.5 rounded-full shrink-0" :style="{ backgroundColor: c.color }"></span>
                <div class="min-w-0">
                  <p class="font-semibold text-white truncate">{{ c.name }}</p>
                  <p class="text-xs text-neutral-400">{{ c.count }} transaksi</p>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <div class="text-right">
                  <p :class="['font-bold', section.type === 'income' ? 'text-emerald-400' : 'text-rose-400']">
                    Rp {{ formatRupiah(c.total) }}
                  </p>
                  <p class="text-xs text-neutral-500">{{ percent(c, section.type) }}%</p>
                </div>
                <button @click="startEdit(c)" class="text-neutral-400 hover:text-amber-400 p-1 cursor-pointer text-lg" title="Ubah kategori">✏️</button>
                <button @click="removeCategory(c)" class="text-neutral-400 hover:text-rose-500 p-1 cursor-pointer text-lg" title="Hapus kategori">🗑️</button>
              </div>
            </div>
            <div class="w-full bg-neutral-800 h-2 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :style="{ width: percent(c, section.type) + '%', backgroundColor: c.color }"
              ></div>
            </div>
          </template>
        </div>
      </section>

      <!-- Nama kategori yang dipakai transaksi tapi belum terdaftar -->
      <section v-if="unlisted.length" class="bg-neutral-900 border border-amber-500/30 rounded-2xl p-6 shadow-lg space-y-4">
        <div>
          <h3 class="font-semibold text-xl text-white">Kategori Lain (belum terdaftar)</h3>
          <p class="text-xs text-neutral-400 mt-1">
            Nama ini dipakai oleh transaksi lama, tetapi belum ada di daftar kategori. Daftarkan agar muncul di dropdown.
          </p>
        </div>
        <div
          v-for="u in unlisted"
          :key="u.type + '|' + u.name"
          class="flex items-center justify-between gap-3 bg-neutral-950 border border-neutral-800 rounded-xl p-4"
        >
          <div class="min-w-0">
            <p class="font-semibold text-white truncate">{{ u.name }}</p>
            <p class="text-xs text-neutral-400">
              {{ u.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }} · {{ u.count }} transaksi · Rp {{ formatRupiah(u.total) }}
            </p>
          </div>
          <button
            @click="adoptCategory(u)"
            class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-sm font-semibold cursor-pointer shrink-0"
          >
            Daftarkan
          </button>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../lib/api'
import { useCategories } from '../composables/useCategories'

const { load: reloadDropdown } = useCategories()

// Bulan berjalan (waktu lokal) sebagai filter awal
const now = new Date()
const month = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`)

const loading = ref(false)
const categories = ref([])
const unlisted = ref([])
const totals = ref({ income: 0, expense: 0 })

const newForm = ref({ name: '', type: 'expense', color: '#10b981' })
const editingId = ref(null)
const editForm = ref({ name: '', color: '#10b981' })

const formatRupiah = (val) => new Intl.NumberFormat('id-ID').format(val || 0)

const apiError = (err, fallback) => {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat().join('\n')
  return err.response?.data?.message || fallback
}

const fetchSummary = async () => {
  loading.value = true
  try {
    const res = await api.get('/categories/summary', {
      params: month.value ? { month: month.value } : {}
    })
    categories.value = res.data.data || []
    unlisted.value = res.data.unlisted || []
    totals.value = res.data.totals || { income: 0, expense: 0 }
  } catch (err) {
    console.error('Gagal mengambil ringkasan kategori:', err)
  } finally {
    loading.value = false
  }
}

// Muat ulang halaman ini + cache dropdown di form transaksi
const afterChange = async () => {
  await fetchSummary()
  reloadDropdown(true).catch(() => {})
}

const byTotalDesc = (a, b) => b.total - a.total || a.name.localeCompare(b.name)

const sections = computed(() => [
  {
    type: 'expense',
    title: 'Pengeluaran per Kategori',
    items: categories.value.filter((c) => c.type === 'expense').sort(byTotalDesc)
  },
  {
    type: 'income',
    title: 'Pemasukan per Kategori',
    items: categories.value.filter((c) => c.type === 'income').sort(byTotalDesc)
  }
])

const percent = (c, type) => {
  const total = totals.value[type] || 0
  return total > 0 ? Math.min(100, Math.round((c.total / total) * 100)) : 0
}

const addCategory = async () => {
  try {
    await api.post('/categories', {
      name: newForm.value.name.trim(),
      type: newForm.value.type,
      color: newForm.value.color
    })
    newForm.value.name = ''
    await afterChange()
  } catch (err) {
    alert(apiError(err, 'Gagal menambahkan kategori.'))
  }
}

const adoptCategory = async (item) => {
  try {
    await api.post('/categories', { name: item.name, type: item.type, color: '#6b7280' })
    await afterChange()
  } catch (err) {
    alert(apiError(err, 'Gagal mendaftarkan kategori.'))
  }
}

const startEdit = (c) => {
  editingId.value = c.id
  editForm.value = { name: c.name, color: c.color }
}

const cancelEdit = () => {
  editingId.value = null
}

const saveEdit = async (c) => {
  try {
    await api.put(`/categories/${c.id}`, {
      name: editForm.value.name.trim(),
      color: editForm.value.color
    })
    editingId.value = null
    await afterChange()
  } catch (err) {
    alert(apiError(err, 'Gagal memperbarui kategori.'))
  }
}

const removeCategory = async (c) => {
  if (!confirm(`Hapus kategori "${c.name}"?\nTransaksi lama tetap memakai nama ini.`)) return

  try {
    await api.delete(`/categories/${c.id}`)
    await afterChange()
  } catch (err) {
    alert(apiError(err, 'Gagal menghapus kategori.'))
  }
}

watch(month, fetchSummary)
onMounted(fetchSummary)
</script>