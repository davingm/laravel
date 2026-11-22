<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);

        return view('pages.reports.index', [
            'peminjamans' => Peminjaman::query()
                ->with(['user', 'items.alat'])
                ->latest()
                ->paginate(20),
            'totalPeminjaman' => Peminjaman::query()->count(),
            'sedangDipinjam' => Peminjaman::query()->whereIn('status', ['disetujui', 'menunggu_pengembalian'])->count(),
            'totalDenda' => Peminjaman::query()->sum('jumlah_denda'),
        ]);
    }

    public function export(): StreamedResponse
    {
        abort_unless(in_array(request()->user()->role, ['administrator', 'operator'], true), 403);

        $response = response()->streamDownload(function (): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['No.', 'Peminjam', 'Alat', 'Total barang', 'Status', 'Tanggal pinjam', 'Rencana kembali', 'Dikembalikan', 'Denda']);

            Peminjaman::query()->with(['user', 'items.alat'])->orderBy('id')->chunk(200, function ($peminjamans) use ($file): void {
                foreach ($peminjamans as $peminjaman) {
                    $alat = $peminjaman->items
                        ->map(fn ($item): string => $item->alat->nama_alat.' x '.$item->jumlah)
                        ->implode('; ');

                    fputcsv($file, [
                        $peminjaman->id,
                        $peminjaman->user->name,
                        $alat,
                        $peminjaman->items->sum('jumlah'),
                        $peminjaman->status,
                        $peminjaman->tanggal_dipinjam?->format('Y-m-d H:i'),
                        $peminjaman->tanggal_kembali?->format('Y-m-d'),
                        $peminjaman->dikembalikan_pada?->format('Y-m-d H:i'),
                        $peminjaman->jumlah_denda,
                    ]);
                }
            });

            fclose($file);
        }, 'laporan-peminjaman-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);

        return $response;
    }
}
