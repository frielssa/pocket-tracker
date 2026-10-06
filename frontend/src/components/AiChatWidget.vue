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
      class="absolute bottom-16 right-0 w-80 sm:w-[26rem] h-[520px] bg-gray-900 border border-gray-700 text-gray-100 rounded-2xl shadow-2xl flex flex-col overflow-hidden"
    >
      <!-- Header Chat -->
      <div class="bg-gray-800 p-4 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
          <h3 class="font-semibold text-sm">PocketTracker AI Agent</h3>
        </div>
        <button @click="toggleChat" class="text-gray-400 hover:text-white text-xs">Tutup</button>
      </div>

      <!-- Area Pesan (Chat History) -->
      <div ref="chatContainer" class="flex-1 p-4 overflow-y-auto space-y-3 text-sm">
        <div v-for="(msg, index) in messages" :key="index" :class="msg.role === 'user' ? 'text-right' : 'text-left'">
          <!-- Pesan pengguna: teks biasa -->
          <div
            v-if="msg.role === 'user'"
            class="inline-block px-3 py-2 rounded-xl max-w-[85%] whitespace-pre-line text-left bg-emerald-600 text-white rounded-br-none"
          >
            {{ msg.content }}
          </div>

          <!-- Pesan AI: Markdown dirender menjadi HTML (sudah disanitasi) -->
          <div
            v-else
            class="ai-md inline-block px-3 py-2 rounded-xl max-w-[92%] overflow-x-auto bg-gray-800 text-gray-200 border border-gray-700 rounded-bl-none"
            v-html="renderMarkdown(msg.content)"
          ></div>

          <!-- Usulan aksi dari AI (butuh persetujuan pengguna) -->
          <div v-if="msg.actions && msg.actions.length" class="mt-2 space-y-2 text-left">
            <div
              v-for="action in msg.actions"
              :key="action.id"
              class="w-full max-w-[92%] bg-gray-800 border border-amber-500/40 rounded-xl p-3 text-xs"
            >
              <p class="text-amber-300 font-semibold mb-1">Usulan aksi</p>
              <p class="text-gray-200">{{ action.summary }}</p>

              <div v-if="action.status === 'pending' || action.status === 'error'" class="flex gap-2 mt-3">
                <button
                  @click="approveAction(action)"
                  class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg font-semibold transition"
                >
                  Setujui
                </button>
                <button
                  @click="cancelAction(action)"
                  class="border border-gray-600 hover:border-gray-400 text-gray-300 px-3 py-1.5 rounded-lg transition"
                >
                  Batal
                </button>
              </div>

              <p v-if="action.status === 'loading'" class="mt-2 text-gray-400 animate-pulse">Memproses...</p>
              <p v-else-if="action.status === 'done'" class="mt-2 text-emerald-400">✅ Berhasil dijalankan</p>
              <p v-else-if="action.status === 'cancelled'" class="mt-2 text-gray-400">Dibatalkan</p>
              <p v-else-if="action.status === 'error'" class="mt-2 text-rose-400">❌ {{ action.error }}</p>
            </div>
          </div>
        </div>

        <!-- Indicator Loading -->
        <div v-if="isLoading" class="text-left">
          <div class="inline-block px-3 py-2 rounded-xl bg-gray-800 text-gray-400 border border-gray-700 text-xs animate-pulse">
            PocketTracker AI sedang bekerja...
          </div>
        </div>
      </div>

      <!-- Form Input Prompt -->
      <form @submit.prevent="sendMessage" class="p-3 border-t border-gray-700 bg-gray-800 flex gap-2">
        <input
          v-model="promptInput"
          type="text"
          maxlength="500"
          placeholder="Tanya atau perintahkan AI..."
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
import { marked } from 'marked'
import DOMPurify from 'dompurify'

// Beri tahu Dashboard setiap kali AI berhasil mengubah data (tambah/ubah/hapus)
const emit = defineEmits(['changed'])

const API_BASE_URL = 'http://localhost:8000/api'

marked.setOptions({ gfm: true, breaks: true })

// Markdown -> HTML, lalu disanitasi agar aman dari XSS sebelum dipakai di v-html
const renderMarkdown = (text) => DOMPurify.sanitize(marked.parse(text ?? ''))

const isOpen = ref(false)
const isLoading = ref(false)
const promptInput = ref('')
const chatContainer = ref(null)

const messages = ref([
  {
    role: 'assistant',
    local: true, // pesan sambutan: tidak dikirim ke AI
    content:
      'Halo! Saya **PocketTracker AI**. Saya bisa menganalisis keuangan Anda dan membantu mencatat transaksi.\n\nContoh: *"Berapa pengeluaran saya bulan ini?"* atau *"Catat makan siang 25 ribu hari ini"*.'
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

const authHeaders = () => {
  const token = localStorage.getItem('token')
  return {
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {})
  }
}

const errorText = (err) => {
  const status = err.response?.status
  if (status === 401) return 'Sesi login Anda sudah berakhir. Silakan login ulang.'
  if (status === 429) return 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'
  return err.response?.data?.message || 'Maaf, gagal terhubung ke AI Assistant.'
}

const sendMessage = async () => {
  const text = promptInput.value.trim()
  if (!text || isLoading.value) return

  messages.value.push({ role: 'user', content: text })
  promptInput.value = ''
  isLoading.value = true
  scrollToBottom()

  // Kirim riwayat percakapan terakhir agar AI punya konteks
  const history = messages.value
    .filter((m) => !m.local)
    .slice(-10)
    .map((m) => ({ role: m.role, content: String(m.content).slice(0, 3900) }))

  try {
    const res = await axios.post(
      `${API_BASE_URL}/ai/ask`,
      { messages: history },
      { headers: authHeaders() }
    )

    const actions = (res.data?.actions || []).map((a) => ({ ...a, status: 'pending', error: '' }))

    messages.value.push({
      role: 'assistant',
      content: res.data?.reply || 'Tidak ada jawaban dari AI.',
      actions
    })
  } catch (err) {
    console.error(err)
    messages.value.push({ role: 'assistant', local: true, content: errorText(err) })
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

// Menjalankan usulan lewat endpoint REST yang sudah ada (validasi & kepemilikan data tetap diperiksa server)
const runAction = (action) => {
  const config = { headers: authHeaders() }

  if (action.type === 'create_transaction') {
    return axios.post(`${API_BASE_URL}/transactions`, action.payload, config)
  }
  if (action.type === 'update_transaction') {
    return axios.put(`${API_BASE_URL}/transactions/${action.transaction_id}`, action.payload, config)
  }
  if (action.type === 'delete_transaction') {
    return axios.delete(`${API_BASE_URL}/transactions/${action.transaction_id}`, config)
  }
  return Promise.reject(new Error('Jenis aksi tidak dikenal'))
}

const approveAction = async (action) => {
  if (action.status === 'loading' || action.status === 'done') return

  action.status = 'loading'
  action.error = ''

  try {
    await runAction(action)
    action.status = 'done'
    // Catat hasilnya di riwayat supaya AI tahu aksi ini sudah dijalankan
    messages.value.push({ role: 'assistant', content: `✅ Aksi dijalankan: ${action.summary}` })
    emit('changed')
  } catch (err) {
    action.status = 'error'
    const errors = err.response?.data?.errors
    action.error = errors ? Object.values(errors).flat().join(' ') : errorText(err)
  } finally {
    scrollToBottom()
  }
}

const cancelAction = (action) => {
  if (action.status === 'loading' || action.status === 'done') return

  action.status = 'cancelled'
  messages.value.push({ role: 'assistant', content: `❌ Pengguna membatalkan: ${action.summary}` })
  scrollToBottom()
}
</script>

<style>
/* Gaya untuk hasil render Markdown (Tailwind CDN tidak menyertakan plugin typography) */
.ai-md > :first-child { margin-top: 0; }
.ai-md > :last-child { margin-bottom: 0; }
.ai-md p { margin: 0.4rem 0; line-height: 1.5; }
.ai-md strong { font-weight: 700; color: #fff; }
.ai-md em { font-style: italic; }
.ai-md ul { list-style: disc; padding-left: 1.25rem; margin: 0.4rem 0; }
.ai-md ol { list-style: decimal; padding-left: 1.25rem; margin: 0.4rem 0; }
.ai-md li { margin: 0.15rem 0; }
.ai-md h1, .ai-md h2, .ai-md h3, .ai-md h4 { font-weight: 700; color: #fff; margin: 0.6rem 0 0.3rem; font-size: 0.95rem; }
.ai-md hr { border: 0; border-top: 1px solid #374151; margin: 0.6rem 0; }
.ai-md a { color: #34d399; text-decoration: underline; }
.ai-md code { background: #111827; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.8rem; }
.ai-md pre { background: #111827; padding: 0.5rem; border-radius: 0.5rem; overflow-x: auto; margin: 0.4rem 0; }
.ai-md pre code { background: transparent; padding: 0; }
.ai-md blockquote { border-left: 3px solid #374151; padding-left: 0.6rem; color: #9ca3af; margin: 0.4rem 0; }
.ai-md table { border-collapse: collapse; margin: 0.4rem 0; font-size: 0.8rem; }
.ai-md th, .ai-md td { border: 1px solid #374151; padding: 0.25rem 0.5rem; text-align: left; }
.ai-md th { background: #111827; }
</style>