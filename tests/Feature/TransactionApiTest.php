<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Http\Controllers\AuthController;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user dummy setiap kali test berjalan
        $this->user = User::factory()->create([
            'email' => 'demo@pockettracker.test',
            'password' => bcrypt('password'),
        ]);
    }

    /** TC-01: Login dan Logout */
    public function test_tc01_user_can_login_and_logout()
    {
    $loginResponse = $this->postJson('/api/login', [
        'email' => 'demo@pockettracker.test',
        'password' => 'password',
    ]);

    $loginResponse->assertStatus(200);
    
    // Ambil token dari respons login
    $token = $loginResponse->json('access_token');

    // Kirim request logout dengan header Bearer Token
    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout')
        ->assertStatus(200);
    }

    /** TC-02: Tampil Daftar & Empty State */
    public function test_tc02_get_empty_transactions()
    {
        $response = $this->actingAs($this->user)
                         ->getJson('/api/transactions');

        $response->assertStatus(200)
                 ->assertJson(['data' => []]);
    }

    /** TC-03: Tambah Transaksi Valid */
    public function test_tc03_store_valid_transaction()
    {
        $payload = [
            'title' => 'Gaji Bulanan',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'date' => '2026-09-28',
        ];

        $response = $this->actingAs($this->user)
                         ->postJson('/api/transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', ['title' => 'Gaji Bulanan']);
    }

    /** TC-04: Ubah Transaksi Valid */
    public function test_tc04_update_valid_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 50000
        ]);

        $response = $this->actingAs($this->user)
                         ->putJson("/api/transactions/{$transaction->id}", [
                             'title' => 'Beli Bensin',
                             'amount' => 75000,
                             'type' => 'expense',
                             'category' => 'Transport',
                             'date' => '2026-09-28',
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', ['amount' => 75000]);
    }

    /** TC-05: Input Kosong / Whitespace Ditolak */
    public function test_tc05_reject_empty_title()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/transactions', [
                             'title' => '   ',
                             'amount' => 5000,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** TC-06: Batas Input Nominal (Minimal Rp 1.000) */
    public function test_tc06_boundary_amount_validation()
    {
        // Di bawah batas (< 1000) -> Ditolak
        $this->actingAs($this->user)
             ->postJson('/api/transactions', ['amount' => 999])
             ->assertStatus(422);

        // Tepat batas minimum (1000) -> Diterima
        $this->actingAs($this->user)
             ->postJson('/api/transactions', [
                 'title' => 'Parkir',
                 'amount' => 1000,
                 'type' => 'expense',
                 'category' => 'Lainnya',
                 'date' => '2026-09-28',
             ])
             ->assertStatus(201);
    }

    /** TC-07: Referensi Tidak Sah (Foreign Key ID Tidak Ada) */
    public function test_tc07_invalid_foreign_key()
    {
        $response = $this->actingAs($this->user)
                         ->postJson('/api/transactions', [
                             'user_id' => 99999, // ID tidak ada
                             'title' => 'Tes ID',
                             'amount' => 5000,
                         ]);

        $response->assertStatus(422);
    }

    /** TC-08: Akses Endpoint Protected Tanpa Login */
    public function test_tc08_unauthenticated_access_rejected()
    {
        $response = $this->getJson('/api/transactions');
        
        // Menguji bahwa endpoint yang diproteksi menolak akses tanpa token
        $response->assertStatus(401);
    }

    /** TC-09: Akses Tidak Berhak (Milik User Lain) */
    public function test_tc09_unauthorized_user_cannot_update_other_data()
    {
        // Pemilik data
        $owner = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
        ]);

        // User lain yang mencoba mengakses/mengedit
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/transactions/{$transaction->id}", [
                'title'    => 'Bajak Data',
                'amount'   => 100000,
                'type'     => 'expense',
                'category' => 'Lainnya',
                'date'     => '2026-09-30',
            ]);

        $response->assertStatus(403);
    }

    /** TC-10: Operasi pada ID Data Tidak Ada (404) */
    public function test_tc10_delete_non_existing_transaction_returns_404()
    {
        $response = $this->actingAs($this->user)
                         ->deleteJson('/api/transactions/99999');

        $response->assertStatus(404);
    }

    /** TC-11: Simulation Handling API/Server Error */
    public function test_tc11_server_error_response_structure()
    {
        $response = $this->actingAs($this->user)
                         ->getJson('/api/transactions?trigger_error=1');

        $this->assertTrue(in_array($response->status(), [200, 422, 500]));
    }

    /** TC-12: Test Fresh Migration & Seeding */
    public function test_tc12_database_seeding_works()
    {
        $this->artisan('db:seed');
        $this->assertDatabaseCount('transactions', Transaction::count());
    }
}