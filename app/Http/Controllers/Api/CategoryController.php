<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Kategori milik pengguna (untuk dropdown di form transaksi) + ringkasan per kategori (halaman Kategori).
 *
 * Catatan desain: kolom transactions.category tetap berupa teks (nama kategori), sehingga data lama dan
 * test yang sudah ada tidak terpengaruh. Tabel categories adalah daftar pilihan untuk dropdown.
 */
final class CategoryController extends Controller
{
    // GET /api/categories  (untuk dropdown)
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        Category::ensureDefaultsFor($userId);

        $categories = Category::query()
            ->where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    // GET /api/categories/summary?month=YYYY-MM  (untuk halaman Kategori)
    public function summary(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        Category::ensureDefaultsFor($userId);

        $query = Transaction::query()->where('user_id', $userId);

        $month = $request->input('month');
        $validMonth = is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1;

        if ($validMonth) {
            $start = Carbon::parse($month . '-01')->startOfDay();
            $query->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        }

        $rows = $query
            ->selectRaw('category, type, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('category', 'type')
            ->get();

        // Kunci: "tipe|nama_huruf_kecil"
        $stats = [];
        foreach ($rows as $row) {
            $type = $row->type instanceof \BackedEnum ? (string) $row->type->value : (string) $row->type;
            $key = $type . '|' . mb_strtolower(trim((string) $row->category));

            $stats[$key]['name']  = $stats[$key]['name'] ?? (string) $row->category;
            $stats[$key]['type']  = $type;
            $stats[$key]['count'] = ($stats[$key]['count'] ?? 0) + (int) $row->cnt;
            $stats[$key]['total'] = ($stats[$key]['total'] ?? 0.0) + (float) $row->total;
        }

        $categories = Category::query()
            ->where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $listedKeys = [];
        $data = $categories->map(function (Category $c) use ($stats, &$listedKeys) {
            $key = $c->type . '|' . mb_strtolower($c->name);
            $listedKeys[$key] = true;

            return [
                'id'    => $c->id,
                'name'  => $c->name,
                'type'  => $c->type,
                'color' => $c->color,
                'count' => $stats[$key]['count'] ?? 0,
                'total' => $stats[$key]['total'] ?? 0,
            ];
        })->values()->all();

        // Nama kategori yang dipakai transaksi tetapi belum terdaftar di tabel categories (data lama / ketikan bebas)
        $unlisted = collect($stats)
            ->reject(fn ($s, $key) => isset($listedKeys[$key]))
            ->values()
            ->map(fn ($s) => [
                'name'  => $s['name'],
                'type'  => $s['type'],
                'count' => $s['count'],
                'total' => $s['total'],
            ])
            ->all();

        $totals = ['income' => 0.0, 'expense' => 0.0];
        foreach ($stats as $s) {
            if (isset($totals[$s['type']])) {
                $totals[$s['type']] += $s['total'];
            }
        }

        return response()->json([
            'data'     => $data,
            'unlisted' => $unlisted,
            'totals'   => $totals,
            'month'    => $validMonth ? $month : null,
        ]);
    }

    // POST /api/categories
    public function store(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $data = $request->validate([
            'name'  => [
                'required', 'string', 'max:50',
                Rule::unique('categories', 'name')->where(
                    fn ($q) => $q->where('user_id', $userId)->where('type', $request->input('type'))
                ),
            ],
            'type'  => ['required', Rule::in(['income', 'expense'])],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], $this->messages());

        $category = Category::create([
            'user_id' => $userId,
            'name'    => trim($data['name']),
            'type'    => $data['type'],
            'color'   => $data['color'] ?? '#10b981',
        ]);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan',
            'data'    => $category,
        ], 201);
    }

    // PUT /api/categories/{id}  (hanya nama & warna; tipe tidak bisa diubah)
    public function update(Request $request, int|string $id): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
        }
        if ((int) $category->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name'  => [
                'required', 'string', 'max:50',
                Rule::unique('categories', 'name')
                    ->where(fn ($q) => $q->where('user_id', $userId)->where('type', $category->type))
                    ->ignore($category->id),
            ],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], $this->messages());

        $oldName = $category->name;
        $newName = trim($data['name']);

        DB::transaction(function () use ($category, $data, $oldName, $newName, $userId) {
            $category->update([
                'name'  => $newName,
                'color' => $data['color'] ?? $category->color,
            ]);

            // Ikut ganti nama pada transaksi lama (hanya yang bertipe sama)
            if ($oldName !== $newName) {
                Transaction::query()
                    ->where('user_id', $userId)
                    ->where('type', $category->type)
                    ->where('category', $oldName)
                    ->update(['category' => $newName]);
            }
        });

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'data'    => $category->fresh(),
        ]);
    }

    // DELETE /api/categories/{id}
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $category = Category::find($id);
        if (! $category) {
            return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
        }
        if ((int) $category->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $usedBy = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', $category->type)
            ->where('category', $category->name)
            ->count();

        $category->delete();

        return response()->json([
            'message'           => 'Kategori berhasil dihapus',
            'transactions_used' => $usedBy, // transaksi lama tetap memakai nama ini
        ]);
    }

    // ------------------------------------------------------------------

    private function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max'      => 'Nama kategori maksimal 50 karakter.',
            'name.unique'   => 'Kategori dengan nama dan tipe yang sama sudah ada.',
            'type.required' => 'Tipe kategori wajib dipilih.',
            'type.in'       => 'Tipe kategori harus income atau expense.',
            'color.regex'   => 'Warna harus berformat #RRGGBB.',
        ];
    }
}