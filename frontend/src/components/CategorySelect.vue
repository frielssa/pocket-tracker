<template>
  <select
    :value="modelValue"
    @change="$emit('update:modelValue', $event.target.value)"
    class="bg-neutral-950 border border-neutral-700 focus:border-white rounded-lg p-3 text-sm text-white outline-none cursor-pointer w-full"
    required
  >
    <option value="" disabled>Pilih kategori</option>
    <option v-for="c in options" :key="c.name" :value="c.name">
      {{ c.name }}{{ c.legacy ? ' (lama)' : '' }}
    </option>
  </select>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useCategories } from '../composables/useCategories'

const props = defineProps({
  modelValue: { type: String, default: '' },
  type: { type: String, default: 'expense' } // 'income' | 'expense'
})
const emit = defineEmits(['update:modelValue'])

const { categories, load, byType } = useCategories()

onMounted(() => {
  load().catch((err) => console.error('Gagal memuat kategori:', err))
})

// Kategori sesuai tipe transaksi. Jika nilai saat ini tidak ada di daftar (data lama / kategori yang
// sudah dihapus), tetap ditampilkan sebagai opsi "(lama)" agar data tidak hilang saat diedit.
const options = computed(() => {
  const list = byType(props.type).map((c) => ({ name: c.name }))
  const current = props.modelValue
  if (current && !categories.value.some((c) => c.name === current)) {
    list.unshift({ name: current, legacy: true })
  }
  return list
})

// Saat tipe berganti (Pemasukan <-> Pengeluaran), kosongkan kategori yang hanya ada di tipe lainnya
watch(
  () => props.type,
  (type) => {
    const current = props.modelValue
    if (!current) return
    const inCurrentType = categories.value.some((c) => c.type === type && c.name === current)
    const inOtherType = categories.value.some((c) => c.type !== type && c.name === current)
    if (!inCurrentType && inOtherType) {
      emit('update:modelValue', '')
    }
  }
)
</script>