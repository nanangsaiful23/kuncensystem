{{-- Riwayat edit / hapus / batal oleh siapa (App\Models\ActionLog). $logs = koleksi ActionLog. --}}
@if($logs->isNotEmpty())
<div style="margin:12px 0;padding:10px 14px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc">
  <div style="font-weight:700;margin-bottom:6px"><i class="fa fa-history"></i> Riwayat Perubahan</div>
  @foreach($logs as $log)
    <div style="font-size:13px;padding:4px 0;border-top:1px dashed #e2e8f0">
      <strong>{{ $log->teks() }}</strong>@if($log->note) &mdash; {{ $log->note }}@endif
    </div>
  @endforeach
</div>
@endif
