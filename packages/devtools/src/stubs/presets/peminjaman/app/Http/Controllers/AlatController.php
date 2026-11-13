<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlatController extends Controller
{
    public function index(): View
    {
        return view('pages.peminjaman.tools.index', [
            'alats' => Alat::query()->with('kategori')->orderBy('nama_alat')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('pages.peminjaman.tools.create', [
            'kategoris' => Kategori::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Alat::create($request->validate([
            'nama_alat' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'stok' => ['required', 'integer', 'min:0'],
        ]));

        return redirect()->route('peminjaman.alats.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function show(Alat $alat): RedirectResponse
    {
        return redirect()->route('peminjaman.alats.edit', $alat);
    }

    public function edit(Alat $alat): View
    {
        return view('pages.peminjaman.tools.edit', [
            'alat' => $alat,
            'kategoris' => Kategori::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(Request $request, Alat $alat): RedirectResponse
    {
        $alat->update($request->validate([
            'nama_alat' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'stok' => ['required', 'integer', 'min:0'],
        ]));

        return redirect()->route('peminjaman.alats.index')->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(Alat $alat): RedirectResponse
    {
        if ($alat->peminjamans()->exists()) {
            return back()->with('error', 'Alat tidak dapat dihapus karena sudah memiliki riwayat peminjaman.');
        }

        $alat->delete();

        return redirect()->route('peminjaman.alats.index')->with('success', 'Data berhasil dihapus.');
    }
}
