<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengembalianController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);

        return view('devtools-peminjaman.pengembalians.index', [
            'peminjamans' => Peminjaman::query()
                ->with(['user', 'alat'])
                ->whereIn('status', ['menunggu_pengembalian', 'dikembalikan'])
                ->latest('updated_at')
                ->paginate(15),
        ]);
    }

    public function complete(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);

        DB::transaction(function () use ($peminjaman): void {
            $loan = Peminjaman::query()->lockForUpdate()->findOrFail($peminjaman->id);
            abort_unless($loan->status === 'menunggu_pengembalian', 409, 'Permintaan pengembalian ini sudah diproses.');

            Alat::query()->lockForUpdate()->findOrFail($loan->alat_id)
                ->increment('stok', $loan->jumlah);

            $loan->update([
                'status' => 'dikembalikan',
                'dikembalikan_pada' => now(),
            ]);
        });

        return redirect()->route('peminjaman.pengembalians.index')->with('success', 'Pengembalian alat sudah dikonfirmasi.');
    }

    public function cancel(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless($peminjaman->status === 'menunggu_pengembalian', 409, 'Permintaan pengembalian ini sudah diproses.');

        $peminjaman->update([
            'status' => 'disetujui',
            'diminta_kembali_pada' => null,
        ]);

        return redirect()->route('peminjaman.pengembalians.index')->with('success', 'Permintaan pengembalian dibatalkan.');
    }
}
