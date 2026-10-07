import { ref } from 'vue'
import api, { TOKEN_KEY } from '../lib/api'

// State dibagi antar komponen (modul-level) supaya dropdown tidak memanggil API berulang kali
const categories = ref([])
let loadedFor = null // token pengguna yang datanya sedang dimuat (mencegah data bocor antar akun)
let pending = null

export function useCategories() {
  const load = async (force = false) => {
    const token = localStorage.getItem(TOKEN_KEY)

    if (!force && loadedFor === token && token) return
    if (pending) return pending

    pending = api
      .get('/categories')
      .then((res) => {
        categories.value = res.data.data || []
        loadedFor = token
      })
      .finally(() => {
        pending = null
      })

    return pending
  }

  const byType = (type) => categories.value.filter((c) => c.type === type)

  return { categories, load, byType }
}