@extends('layouts.app')

@section('content')
<section class="crud-shell">
    <header class="crud-heading">
        <div class="crud-heading-copy">
            <p class="eyebrow">Manajemen data</p>
            <h1 class="crud-title">Daftar Siswa</h1>
            <p class="crud-description">Lihat dan kelola data siswa.</p>
        </div>
        <a href="{{ route('siswas.create') }}" class="button button-primary crud-create">
            <span class="button-icon" aria-hidden="true">+</span>
            Tambah Siswa
        </a>
    </header>

    @if (session('success'))
        <p class="flash-success" role="status">{{ session('success') }}</p>
    @endif

    <div class="crud-summary">
        <span class="crud-summary-count">{{ $siswas->total() }}</span>
        <span>siswa terdaftar</span>
    </div>

    <div class="table-wrap">
        <table class="barang-table">
            <caption class="visually-hidden">Daftar siswa</caption>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>No Absen</th>
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siswas as $siswa)
                    <tr>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->kelas }}</td>
                        <td>{{ $siswa->no_absen }}</td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('siswas.edit', $siswa) }}" class="button button-secondary button-small">Edit</a>
                                <form method="POST" action="{{ route('siswas.destroy', $siswa) }}" class="inline-form" onsubmit="return confirm('Yakin ingin menghapus data siswa ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-danger button-small">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty-state" colspan="4">
                            <div class="crud-empty-state">
                                <span class="empty-state-mark" aria-hidden="true">+</span>
                                <h2>Belum ada siswa</h2>
                                <p>Tambahkan data siswa pertama untuk mulai mengelola daftar.</p>
                                <a href="{{ route('siswas.create') }}" class="button button-primary">Tambah Siswa</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="crud-pagination">
        {{ $siswas->links() }}
    </div>
</section>
@endsection