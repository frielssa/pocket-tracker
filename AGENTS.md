# Guidelines Application — PocketTracker

## Stack Configuration
- Backend: Laravel (`http://localhost:8000/api`)
- Frontend: Vue 3 (`http://localhost:5173`)

## API Endpoints
- GET `/api/transactions` : Mengambil daftar transaksi & summary saldo dari database.
- POST `/api/transactions` : Menyimpan transaksi ke database.
- DELETE `/api/transactions/{id}` : Menghapus transaksi dari database.

## Instructions
1. Hubungkan frontend Vue secara langsung ke API Laravel tanpa penyimpanan lokal (localStorage).
2. Sediakan fitur hapus transaksi dengan pemanggilan HTTP DELETE ke `/api/transactions/{id}` lalu refresh data dari database.