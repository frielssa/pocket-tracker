<template>
  <div class="fixed bottom-6 right-6 z-50">
    <!-- Tombol Floating Melayang (Toggle Chat) -->
    <button
      @click="toggleChat"
      class="flex items-center justify-center w-14 h-14 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg transition-transform hover:scale-105 focus:outline-none"
    >
      <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Jendela Chat Pop-up -->
    <div
      v-if="isOpen"
      class="absolute bottom-16 right-0 w-80 sm:w-96 h-[480px] bg-gray-900 border border-gray-700 text-gray-100 rounded-2xl shadow-2xl flex flex-col overflow-hidden"
    >
      <!-- Header Chat -->
      <div class="bg-gray-800 p-4 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
          <h3 class="font-semibold text-sm">PocketTracker AI Assistant</h3>
        </div>
        <button @click="toggleChat" class="text-gray-400 hover:text-white text-xs">Tutup</button>
      </div>

      <!-- Area Pesan (Chat History) -->
      <div ref="chatContainer" class="flex-1 p-4 overflow-y-auto space-y-3 text-sm">
        <div v-for="(msg, index) in messages" :key="index" :class="msg.role === 'user' ? 'text-right' : 'text-left'">
          <div
            :class="[
              'inline-block px-3 py-2 rounded-xl max-w-[85%] whitespace-pre-line',
              msg.role === 'user' 
                ? 'bg-emerald-600 text-white rounded-br-none' 
                : 'bg-gray-800 text-gray-200 border border-gray-700 rounded-bl-none'
            ]"
          >
            {{ msg.content }}
          </div>
        </div>

        <!-- Indicator Loading -->
        <div v-if="isLoading" class="text-left">
          <div class="inline-block px-3 py-2 rounded-xl bg-gray-800 text-gray-400 border border-gray-700 text-xs animate-pulse">
            PocketTracker AI sedang menganalisis...
          </div>
        </div>
      </div>

      <!-- Form Input Prompt -->
      <form @submit.prevent="sendMessage" class="p-3 border-t border-gray-700 bg-gray-800 flex gap-2">
        <input
          v-model="promptInput"
          type="text"
          placeholder="Tanyakan analisis keuangan..."
          class="flex-1 bg-gray-900 text-white text-sm rounded-lg px-3 py-2 border border-gray-700 focus:outline-none focus:border-emerald-500"
          :disabled="isLoading"
        />
        <button
          type="submit"
          :disabled="isLoading || !promptInput.trim()"
          class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3 py-2 rounded-lg text-sm transition"
        >
          Kirim
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api'

const isOpen = ref(false)
const isLoading = ref(false)
const promptInput = ref('')
const chatContainer = ref(null)

const messages = ref([
  {
    role: 'assistant',
    content: 'Halo! Saya PocketTracker AI. Ada yang bisa saya bantu untuk menganalisis keuangan Anda hari ini?'
  }
])

const toggleChat = () => {
  isOpen.value = !isOpen.value
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

const sendMessage = async () => {
  const text = promptInput.value.trim()
  if (!text || isLoading.value) return

  // Tambahkan pesan user ke UI
  messages.value.push({ role: 'user', content: text })
  promptInput.value = ''
  isLoading.value = true
  scrollToBottom()

  try {
  const token = localStorage.getItem('token')
  const res = await axios.post(
    `${API_BASE_URL}/ai/ask`,
    { prompt: text },
    {
      headers: {
        Authorization: token ? `Bearer ${token}` : ''
      }
    }
  )

    if (res.data && res.data.reply) {
      messages.value.push({ role: 'assistant', content: res.data.reply })
    } else {
      messages.value.push({ role: 'assistant', content: 'Tidak ada jawaban dari AI.' })
    }
  } catch (err) {
    console.error(err)
    messages.value.push({
      role: 'assistant',
      content: err.response?.data?.message || 'Maaf, gagal terhubung ke AI Assistant. Pastikan Anda sudah login.'
    })
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}
</script>