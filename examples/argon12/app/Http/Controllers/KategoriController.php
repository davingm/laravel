<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        return view('pages.categories.index', [
            'kategoris' => Kategori::query()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('pages.categories.create', [

        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Kategori::create($request->validate([
            'nama_kategori' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
        ]));

        return redirect()->route('peminjaman.kategoris.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function show(Kategori $kategori): RedirectResponse
    {
        return redirect()->route('peminjaman.kategoris.edit', $kategori);
    }

    public function edit(Kategori $kategori): View
    {
        return view('pages.categories.edit', [
            'kategori' => $kategori,

        ]);
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->validate([
            'nama_kategori' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
        ]));

        return redirect()->route('peminjaman.kategoris.index')->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->alats()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus selama masih digunakan oleh alat.');
        }

        $kategori->delete();

        return redirect()->route('peminjaman.kategoris.index')->with('success', 'Data berhasil dihapus.');
    }
}
