<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()->categories()
            ->withCount('transactions')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->nameValidator($request);
        if ($validator->fails()) {
            return back()->with('danger', $validator->errors()->first());
        }

        $name = $validator->validated()['name'];
        $request->user()->categories()->create(['name' => $name]);   // user_id dari relasi
        ActivityLog::record('Tambah kategori: ' . $name);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        // Hanya mencari di kategori milik user ini (cegah IDOR)
        $model = $request->user()->categories()->find($category);
        if (! $model) {
            abort(404, 'Kategori tidak ditemukan.');
        }

        $validator = $this->nameValidator($request, $model->id);
        if ($validator->fails()) {
            return back()->with('danger', $validator->errors()->first());
        }

        $name = $validator->validated()['name'];
        $model->update(['name' => $name]);
        ActivityLog::record('Ubah kategori #' . $model->id . ' menjadi: ' . $name);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $model = $request->user()->categories()->find($category);
        if (! $model) {
            abort(404, 'Kategori tidak ditemukan.');
        }

        if ($model->transactions()->exists()) {
            return back()->with('danger', 'Kategori masih dipakai transaksi, tidak bisa dihapus.');
        }

        $model->delete();
        ActivityLog::record('Hapus kategori #' . $category);

        return back()->with('success', 'Kategori dihapus.');
    }

    private function nameValidator(Request $request, ?int $ignoreId = null): \Illuminate\Validation\Validator
    {
        $uid = $request->user()->id;

        // Nama unik hanya di antara kategori milik user ini
        $unique = Rule::unique('categories', 'name')->where(fn ($q) => $q->where('user_id', $uid));
        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return Validator::make(
            $request->only('name'),
            ['name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\p{N} &\-_.,()\/]+$/u', $unique]],
            [
                'name.unique' => 'Nama kategori sudah ada.',
                'name.regex'  => 'Nama kategori hanya boleh huruf, angka, spasi, dan & - _ . , ( ) /',
                'name.max'    => 'Nama kategori maksimal 50 karakter.',
                'name.required' => 'Nama kategori wajib diisi.',
            ]
        );
    }
}
