<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $peminjamans = Peminjaman::query()
            ->with(['user', 'alat.kategori'])
            ->when($user->role === 'user', fn ($query) => $query->where('user_id', $user->id))
            ->when($user->role === 'operator', fn ($query) => $query->where('status', 'menunggu'))
            ->latest()
            ->paginate(15);

        return view('devtools-peminjaman.peminjamans.index', [
            'peminjamans' => $peminjamans,
        ]);
    }

    public function create(): View
    {
        return view('devtools-peminjaman.peminjamans.create', [
            'users' => User::query()->orderBy('name')->get(),
            'alats' => Alat::query()->with('kategori')->orderBy('nama_alat')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'alat_id' => ['required', 'integer', 'exists:alats,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal_kembali' => ['nullable', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        Peminjaman::create($data + ['status' => 'menunggu']);

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Pengajuan peminjaman berhasil dibuat.');
    }

    public function request(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alat_id' => ['required', 'integer', 'exists:alats,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal_kembali' => ['nullable', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $alat = Alat::findOrFail($data['alat_id']);

        if ($alat->stok < $data['jumlah']) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah permintaan melebihi stok alat yang tersedia.',
            ]);
        }

        Peminjaman::create($data + [
            'user_id' => $request->user()->id,
            'status' => 'menunggu',
        ]);

        return redirect()->route('peminjaman.alats.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function edit(Peminjaman $peminjaman): View
    {
        abort_unless($peminjaman->status === 'menunggu', 404);

        return view('devtools-peminjaman.peminjamans.edit', [
            'peminjaman' => $peminjaman->load(['user', 'alat']),
            'users' => User::query()->orderBy('name')->get(),
            'alats' => Alat::query()->with('kategori')->orderBy('nama_alat')->get(),
        ]);
    }

    public function update(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless($peminjaman->status === 'menunggu', 404);

        $peminjaman->update($request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'alat_id' => ['required', 'integer', 'exists:alats,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal_kembali' => ['nullable', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]));

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function destroy(Peminjaman $peminjaman): RedirectResponse
    {
        DB::transaction(function () use ($peminjaman): void {
            $peminjaman = Peminjaman::query()->lockForUpdate()->findOrFail($peminjaman->id);

            if ($peminjaman->stokSedangDipakai()) {
                Alat::query()->lockForUpdate()->findOrFail($peminjaman->alat_id)
                    ->increment('stok', $peminjaman->jumlah);
            }

            $peminjaman->delete();
        });

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    public function approve(Peminjaman $peminjaman): RedirectResponse
    {
        DB::transaction(function () use ($peminjaman): void {
            $loan = Peminjaman::query()->lockForUpdate()->findOrFail($peminjaman->id);
            abort_unless($loan->status === 'menunggu', 409, 'Pengajuan ini sudah diproses.');

            $alat = Alat::query()->lockForUpdate()->findOrFail($loan->alat_id);
            if ($alat->stok < $loan->jumlah) {
                throw ValidationException::withMessages([
                    'stok' => "Stok {$alat->nama_alat} tidak cukup untuk memenuhi pengajuan.",
                ]);
            }

            $alat->decrement('stok', $loan->jumlah);
            $loan->update(['status' => 'disetujui']);
        });

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Peminjaman disetujui.');
    }

    public function reject(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless($peminjaman->status === 'menunggu', 409, 'Pengajuan ini sudah diproses.');
        $peminjaman->update(['status' => 'ditolak']);

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Peminjaman ditolak.');
    }

    public function requestReturn(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless(
            $peminjaman->user_id === request()->user()->id && $peminjaman->status === 'disetujui',
            404,
        );

        $peminjaman->update([
            'status' => 'menunggu_pengembalian',
            'diminta_kembali_pada' => now(),
        ]);

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Permintaan pengembalian berhasil dikirim.');
    }
}
