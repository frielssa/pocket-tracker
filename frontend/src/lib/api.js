import axios from 'axios'

export const API_BASE_URL = 'http://localhost:8000/api'
export const TOKEN_KEY = 'token'

// Instance axios bersama: token otomatis dipasang, dan sesi habis (401) diarahkan ke /login
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: { Accept: 'application/json' }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY)
      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }
    return Promise.reject(error)
  }
)

export default api