<template>
  <div class="min-h-screen bg-neutral-950 text-white flex items-center justify-center p-4">
    <div class="bg-neutral-900 border border-neutral-800 p-8 rounded-2xl w-full max-w-md space-y-6">
      <h2 class="text-2xl font-bold text-center">📝 Daftar Akun</h2>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="text-sm text-neutral-400">Nama</label>
          <input v-model="name" type="text" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>
        <div>
          <label class="text-sm text-neutral-400">Email</label>
          <input v-model="email" type="email" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>
        <div>
          <label class="text-sm text-neutral-400">Password (minimal 8 karakter)</label>
          <input v-model="password" type="password" minlength="8" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>
        <div>
          <label class="text-sm text-neutral-400">Ulangi Password</label>
          <input v-model="passwordConfirmation" type="password" class="w-full bg-neutral-950 border border-neutral-700 rounded-lg p-3 text-sm mt-1 focus:border-white outline-none" required />
        </div>

        <p v-if="errorMessage" class="text-sm text-rose-400 whitespace-pre-line">{{ errorMessage }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-60 text-white font-semibold py-3 rounded-lg cursor-pointer"
        >
          {{ loading ? 'Memproses...' : 'Daftar' }}
        </button>
      </form>

      <p class="text-sm text-center text-neutral-400">
        Sudah punya akun?
        <router-link to="/login" class="text-emerald-400 hover:text-emerald-300 font-semibold">Masuk</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref('')
const loading = ref(false)
const router = useRouter()

const handleRegister = async () => {
  errorMessage.value = ''

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Password dan ulangi password tidak sama.'
    return
  }

  loading.value = true
  try {
    const res = await axios.post('http://localhost:8000/api/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    }, { headers: { Accept: 'application/json' } })

    localStorage.setItem('token', res.data.access_token)
    router.push('/dashboard')
  } catch (err) {
    if (!err.response) {
      errorMessage.value = 'Gagal terhubung ke server backend.'
    } else if (err.response.data?.errors) {
      errorMessage.value = Object.values(err.response.data.errors).flat().join('\n')
    } else {
      errorMessage.value = err.response.data?.message || 'Pendaftaran gagal.'
    }
  } finally {
    loading.value = false
  }
}
</script>