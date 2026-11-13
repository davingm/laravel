<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PengembalianController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);

        return view('pages.returns.index', [
            'peminjamans' => Peminjaman::query()
                ->with(['user', 'items.alat'])
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

            foreach ($loan->items()->orderBy('alat_id')->get() as $item) {
                Alat::query()->lockForUpdate()->findOrFail($item->alat_id)->increment('stok', $item->jumlah);
            }

            $returnedAt = now();
            $daysLate = 0;
            if ($loan->tanggal_kembali !== null && $returnedAt->copy()->startOfDay()->greaterThan($loan->tanggal_kembali->copy()->startOfDay())) {
                $daysLate = (int) $loan->tanggal_kembali->copy()->startOfDay()->diffInDays($returnedAt->copy()->startOfDay());
            }

            $loan->update([
                'status' => 'dikembalikan',
                'dikembalikan_pada' => $returnedAt,
                'hari_terlambat' => $daysLate,
                'jumlah_denda' => $daysLate * (int) config('peminjaman.denda_per_hari', 1000),
            ]);
        });

        return redirect()->route('peminjaman.pengembalians.index')->with('success', 'Pengembalian alat sudah dikonfirmasi.');
    }

    public function markFinePaid(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);
        abort_unless($peminjaman->status === 'dikembalikan' && $peminjaman->jumlah_denda > 0, 409, 'Tidak ada denda yang perlu dibayar.');
        abort_if($peminjaman->denda_dibayar_pada !== null, 409, 'Denda ini sudah ditandai lunas.');

        $peminjaman->update(['denda_dibayar_pada' => now()]);

        return redirect()->route('peminjaman.peminjamans.show', $peminjaman)->with('success', 'Pembayaran denda ditandai lunas.');
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
