<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\View\View;

class PeminjamanDashboardController extends Controller
{
    public function show(): View
    {
        $user = request()->user();
        $peminjamans = Peminjaman::query()
            ->when($user->role === 'user', fn ($query) => $query->where('user_id', $user->id));

        return view('devtools-peminjaman.dashboard.index', [
            'totalAlat' => Alat::sum('stok'),
            'totalUser' => $user->role === 'administrator' ? User::count() : null,
            'menunggu' => (clone $peminjamans)->where('status', 'menunggu')->count(),
            'dipinjam' => (clone $peminjamans)->whereIn('status', ['disetujui', 'menunggu_pengembalian'])->count(),
            'menungguKembali' => (clone $peminjamans)->where('status', 'menunggu_pengembalian')->count(),
            'roleLabel' => match ($user->role) {
                'administrator' => 'Admin besar',
                'operator' => 'Petugas',
                default => 'Peminjam',
            },
        ]);
    }
}
