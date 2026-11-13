<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $peminjamans = Peminjaman::query()
            ->with(['user', 'items.alat'])
            ->when($user->role === 'user', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(15);

        return view('pages.loans.index', compact('peminjamans'));
    }

    public function create(): View
    {
        return view('pages.loans.create', [
            'users' => User::query()->orderBy('name')->get(),
            'alats' => Alat::query()->with('kategori')->orderBy('nama_alat')->get(),
        ]);
    }

    public function show(Peminjaman $peminjaman): View
    {
        abort_unless(
            request()->user()->role !== 'user' || $peminjaman->user_id === request()->user()->id,
            404,
        );

        return view('pages.loans.show', [
            'peminjaman' => $peminjaman->load(['user', 'items.alat.kategori']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedLoan($request, true);
        $items = $this->selectedItems($data['items']);
        $loan = DB::transaction(fn (): Peminjaman => $this->createLoan($data, $items));

        return redirect()->route('peminjaman.peminjamans.show', $loan)
            ->with('success', 'Pengajuan peminjaman berhasil dibuat.');
    }

    public function request(Request $request): RedirectResponse
    {
        $data = $this->validatedLoan($request, false);
        $items = $this->selectedItems($data['items']);
        $loan = DB::transaction(function () use ($request, $data, $items): Peminjaman {
            $this->assertAvailableQuantities(collect($items)->mapWithKeys(
                fn (array $item): array => [$item['alat_id'] => $item['jumlah']],
            )->all(), true);
            return $this->createLoan($data + ['user_id' => $request->user()->id], $items);
        });

        return redirect()->route('peminjaman.peminjamans.show', $loan)
            ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function edit(Peminjaman $peminjaman): View
    {
        abort_unless($peminjaman->status === 'menunggu', 404);

        return view('pages.loans.edit', [
            'peminjaman' => $peminjaman->load('items'),
            'users' => User::query()->orderBy('name')->get(),
            'alats' => Alat::query()->with('kategori')->orderBy('nama_alat')->get(),
        ]);
    }

    public function update(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless($peminjaman->status === 'menunggu', 404);
        $data = $this->validatedLoan($request, true);
        $items = $this->selectedItems($data['items']);

        DB::transaction(function () use ($peminjaman, $data, $items): void {
            $peminjaman->update(collect($data)->except('items')->all());
            $peminjaman->items()->delete();
            $peminjaman->items()->createMany($items);
        });

        return redirect()->route('peminjaman.peminjamans.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function destroy(Peminjaman $peminjaman): RedirectResponse
    {
        DB::transaction(function () use ($peminjaman): void {
            $loan = Peminjaman::query()->with('items')->lockForUpdate()->findOrFail($peminjaman->id);
            if ($loan->stokSedangDipakai()) {
                $this->changeStock($loan->items, true);
            }
            $loan->delete();
        });

        return redirect()->route('peminjaman.peminjamans.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    public function approve(Peminjaman $peminjaman): RedirectResponse
    {
        DB::transaction(function () use ($peminjaman): void {
            $loan = Peminjaman::query()->lockForUpdate()->findOrFail($peminjaman->id);
            abort_unless($loan->status === 'menunggu', 409, 'Pengajuan ini sudah diproses.');
            $items = $loan->items()->orderBy('alat_id')->get();
            abort_if($items->isEmpty(), 409, 'Pengajuan tidak memiliki alat.');
            $this->assertAvailableQuantities($items->mapWithKeys(fn ($item) => [$item->alat_id => $item->jumlah])->all(), true);
            $this->changeStock($items, false);
            $loan->update(['status' => 'disetujui', 'tanggal_dipinjam' => now()]);
        });

        return redirect()->route('peminjaman.peminjamans.show', $peminjaman)->with('success', 'Peminjaman disetujui.');
    }

    public function reject(Peminjaman $peminjaman): RedirectResponse
    {
        abort_unless($peminjaman->status === 'menunggu', 409, 'Pengajuan ini sudah diproses.');
        $peminjaman->update(['status' => 'ditolak']);

        return redirect()->route('peminjaman.peminjamans.show', $peminjaman)->with('success', 'Peminjaman ditolak.');
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

        return redirect()->route('peminjaman.peminjamans.show', $peminjaman)->with('success', 'Permintaan pengembalian berhasil dikirim.');
    }

    /** @return array<string, mixed> */
    private function validatedLoan(Request $request, bool $includeUser): array
    {
        return $request->validate([
            ...($includeUser ? ['user_id' => ['required', 'integer', 'exists:users,id']] : []),
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'min:0'],
            'tanggal_kembali' => ['nullable', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /** @param array<int|string, int|string> $submitted
     *  @return list<array{alat_id: int, jumlah: int}>
     */
    private function selectedItems(array $submitted): array
    {
        $items = [];
        foreach ($submitted as $alatId => $quantity) {
            if ((int) $quantity > 0) {
                $items[] = ['alat_id' => (int) $alatId, 'jumlah' => (int) $quantity];
            }
        }

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Pilih minimal satu alat dan jumlahnya.']);
        }

        return $items;
    }

    /** @param list<array{alat_id: int, jumlah: int}> $items */
    private function createLoan(array $data, array $items): Peminjaman
    {
        $loan = Peminjaman::create(collect($data)->except('items')->all() + ['status' => 'menunggu']);
        $loan->items()->createMany($items);

        return $loan;
    }

    /** @param array<int|string, int|string> $quantities */
    private function assertAvailableQuantities(array $quantities, bool $lock = false): void
    {
        $query = Alat::query()->whereIn('id', array_keys($quantities))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $alats = $query->get()->keyBy('id');

        foreach ($quantities as $alatId => $quantity) {
            $alat = $alats->get((int) $alatId);
            if (! $alat || $alat->stok < $quantity) {
                throw ValidationException::withMessages([
                    'items' => 'Stok tidak mencukupi untuk salah satu alat yang dipilih. Periksa jumlah dan coba lagi.',
                ]);
            }
        }
    }

    private function changeStock(Collection $items, bool $restore): void
    {
        foreach ($items->sortBy('alat_id') as $item) {
            $alat = Alat::query()->lockForUpdate()->findOrFail($item->alat_id);
            if ($restore) {
                $alat->increment('stok', $item->jumlah);
            } else {
                $alat->decrement('stok', $item->jumlah);
            }
        }
    }
}
