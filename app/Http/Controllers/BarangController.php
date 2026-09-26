<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use App\Support\Frontend;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return Frontend::render('barang.index', 'barang.index', [
            'title' => 'Barang | '.config('app.name', 'Laravel'),
            'description' => 'Kelola daftar barang.',
            'barangs' => Barang::query()->latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return Frontend::render('barang.create', 'barang.create', [
            'title' => 'Tambah Barang | '.config('app.name', 'Laravel'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBarangRequest $request): RedirectResponse
    {
        $barang = Barang::create($request->validated());

        return redirect()->route('barang.show', $barang)->with('success', 'Barang berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Barang $barang): View
    {
        return Frontend::render('barang.show', 'barang.show', [
            'title' => $barang->nama.' | Barang',
            'barang' => $barang,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Barang $barang): View
    {
        return Frontend::render('barang.edit', 'barang.edit', [
            'title' => 'Edit '.$barang->nama.' | Barang',
            'barang' => $barang,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBarangRequest $request, Barang $barang): RedirectResponse
    {
        $barang->update($request->validated());

        return redirect()->route('barang.show', $barang)->with('success', 'Barang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Barang $barang): RedirectResponse
    {
        $barang->delete();

        return redirect()->route('barang.index')->with('success', 'Barang berhasil dihapus.');
    }
}
