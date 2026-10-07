<template>
  <div
    v-if="meta && meta.last_page > 1"
    class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6 text-sm"
  >
    <p class="text-neutral-400">
      Menampilkan {{ from }}–{{ to }} dari {{ meta.total }} {{ label }}
    </p>

    <div class="flex items-center gap-1">
      <button
        :disabled="meta.current_page <= 1"
        @click="go(meta.current_page - 1)"
        class="px-3 py-1.5 rounded-lg border border-neutral-700 text-neutral-300 hover:border-neutral-500 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
      >
        ‹
      </button>

      <template v-for="p in pages" :key="p.key">
        <span v-if="p.ellipsis" class="px-2 text-neutral-500">…</span>
        <button
          v-else
          @click="go(p.page)"
          :class="[
            'px-3 py-1.5 rounded-lg border cursor-pointer',
            p.page === meta.current_page
              ? 'bg-white text-black border-white font-semibold'
              : 'border-neutral-700 text-neutral-300 hover:border-neutral-500'
          ]"
        >
          {{ p.page }}
        </button>
      </template>

      <button
        :disabled="meta.current_page >= meta.last_page"
        @click="go(meta.current_page + 1)"
        class="px-3 py-1.5 rounded-lg border border-neutral-700 text-neutral-300 hover:border-neutral-500 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
      >
        ›
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // { current_page, last_page, per_page, total } dari API
  meta: { type: Object, default: null },
  label: { type: String, default: 'data' }
})
const emit = defineEmits(['change'])

const from = computed(() => (props.meta.current_page - 1) * props.meta.per_page + 1)
const to = computed(() => Math.min(props.meta.current_page * props.meta.per_page, props.meta.total))

// Halaman pertama, terakhir, dan sekitar halaman aktif; sisanya disingkat dengan "…"
const pages = computed(() => {
  const c = props.meta.current_page
  const l = props.meta.last_page
  const nums = [...new Set([1, l, c - 1, c, c + 1])].filter((n) => n >= 1 && n <= l).sort((a, b) => a - b)

  const out = []
  nums.forEach((n, i) => {
    if (i > 0 && n - nums[i - 1] > 1) out.push({ key: `e${n}`, ellipsis: true })
    out.push({ key: `p${n}`, page: n })
  })
  return out
})

const go = (page) => {
  if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) return
  emit('change', page)
}
</script>