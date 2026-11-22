@extends('layouts.dashboard')

@section('title', 'Log Aktivitas')
@section('body-class', 'dashboard-page developer-dashboard')
@section('dashboard-content')
<section class="crud-shell">
    <header class="crud-heading"><div><p class="eyebrow">Audit sistem</p><h1 class="crud-title">Log Aktivitas</h1><p class="crud-description">Catatan peminjaman dan tindakan operator.</p></div></header>
    <div class="crud-summary"><span><strong class="crud-summary-count">{{ $activities->total() }}</strong> aktivitas</span></div>
    <div class="table-wrap"><table class="barang-table"><thead><tr><th class="number-column">No.</th><th>Nama</th><th>Role</th><th>Catatan</th><th>Waktu</th></tr></thead><tbody>
        @forelse ($activities as $activity)
            <tr><td class="number-column">{{ $loop->iteration + (($activities->currentPage() - 1) * $activities->perPage()) }}</td><td>{{ $activity->name }}</td><td>{{ ['administrator' => 'Administrator', 'operator' => 'Operator', 'user' => 'User'][$activity->role] ?? $activity->role }}</td><td>{{ $activity->note }}</td><td><time datetime="{{ $activity->created_at->toIso8601String() }}" data-relative-time title="{{ $activity->created_at->format('d/m/Y H:i:s') }}">{{ $activity->created_at->diffForHumans() }}</time></td></tr>
        @empty <tr><td colspan="5" class="empty-state">Belum ada aktivitas.</td></tr>@endforelse
    </tbody></table></div>
    <div class="crud-pagination">{{ $activities->links() }}</div>
</section>
@endsection
