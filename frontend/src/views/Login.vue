<template>
  <div class="min-h-screen bg-neutral-950 text-white flex items-center justify-center p-4">
    <div class="bg-neutral-900 border border-neutral-800 p-8 rounded-2xl w-full max-w-md space-y-6">
      <h2 class="text-2xl font-bold text-center">🔐 Login PocketTracker</h2>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="text-sm text-neutral-400">Email</label>
          <input v-model="email" type="email" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>
        <div>
          <label class="text-sm text-neutral-400">Password</label>
          <input v-model="password" type="password" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>

        <p v-if="errorMessage" class="text-sm text-rose-400">{{ errorMessage }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-60 text-white font-semibold py-3 rounded-lg cursor-pointer"
        >
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
      <p class="text-sm text-center text-neutral-400">
            Belum punya akun?
            <router-link to="/register" class="text-emerald-400 hover:text-emerald-300 font-semibold">Daftar</router-link>
    </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const loading = ref(false)
const router = useRouter()

const handleLogin = async () => {
  errorMessage.value = ''
  loading.value = true
  try {
    const res = await axios.post('http://localhost:8000/api/login', {
      email: email.value,
      password: password.value,
    }, { headers: { Accept: 'application/json' } })

    const token = res.data.access_token || res.data.token
    if (!token) {
      errorMessage.value = 'Server tidak mengirim token. Cek response /api/login di tab Network.'
      return
    }

    localStorage.setItem('token', token)
    router.push('/dashboard')
  } catch (err) {
    if (!err.response) {
      errorMessage.value = 'Gagal terhubung ke server backend.'
    } else {
      errorMessage.value = err.response.data?.message || 'Kredensial tidak cocok.'
    }
  } finally {
    loading.value = false
  }
}
</script>