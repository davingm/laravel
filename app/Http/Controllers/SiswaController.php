<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Http\Requests\StoreSiswaRequest;
use App\Http\Requests\UpdateSiswaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Support\Frontend;


class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return Frontend::render('siswa.index', 'siswa.index', [
            'title' => 'Siswa | '.config('app.name', 'Laravel'),
            'description' => 'Kelola daftar siswa.',
            'siswas' => Siswa::query()->latest()->paginate(10),
            'sayHay' => 'davingm',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSiswaRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Siswa $siswa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa)
    {
        //
    }
}
