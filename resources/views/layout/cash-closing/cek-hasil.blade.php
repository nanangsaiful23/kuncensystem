{{--
  Daftar hasil satu cek kecocokan (App\Services\CekKecocokan).
  Data: $c (App\Models\ReconciliationCheck). Tautannya ke halaman admin.
--}}
@once
<style>
  .ck-list { margin: 6px 0 6px 18px; padding: 0; }
  .ck-list > li { margin-bottom: 6px; }
  .ck-rincian { margin: 4px 0 0; padding-left: 18px; font-size: 13px; }
  .ck-rincian li { margin-bottom: 2px; }
  .ck-list details summary { cursor: pointer; color: #3c8dbc; font-size: 13px; }
  .ck-muted { color: #888; font-size: 12px; }
</style>
@endonce

<ul class="ck-list">
  @foreach($c->masalah() as $m)
    <li>
      <b>{{ $m['label'] }}</b>: {{ $m['pesan'] }}
      @if(! empty($m['url']))
        <a href="{{ url($m['url']) }}">buka</a>
      @endif
      @if(! empty($m['rincian']))
        @php $ckJumlah = count($m['rincian']) + ($m['lainnya'] ?? 0); @endphp
        <details @if($ckJumlah <= 5) open @endif>
          <summary>Rincian ({{ $ckJumlah }})</summary>
          <ul class="ck-rincian">
            @foreach($m['rincian'] as $r)
              <li>
                @if(! empty($r['url']))
                  <a href="{{ url($r['url']) }}">{{ $r['teks'] }}</a>
                @else
                  {{ $r['teks'] }}
                @endif
              </li>
            @endforeach
            @if(! empty($m['lainnya']))
              <li class="ck-muted">dan {{ $m['lainnya'] }} lainnya</li>
            @endif
          </ul>
        </details>
      @endif
    </li>
  @endforeach
</ul>
@php $ckCocok = array_column(array_filter($c->results ?? [], fn ($r) => ! empty($r['ok'])), 'label'); @endphp
@if($ckCocok)
  <div class="ck-muted">Cocok: {{ implode(', ', $ckCocok) }}</div>
@endif
