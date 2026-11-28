<div class="content-wrapper">
  @include('layout' . '.error')

  @php
    $kurang = $details->filter(fn ($d) => $d->nilai < 0);
    $lebih  = $details->filter(fn ($d) => $d->nilai > 0);
    $sama   = $details->count() - $kurang->count() - $lebih->count();
  @endphp

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] }} #{{ $stock_opname->id }}</h3>
          </div>

          {!! Form::model($stock_opname, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
                <div class="panel-body">
                    <div class="row">
                        <div class="form-group">
                            {!! Form::label('created_at', 'Tanggal', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('created_at', displayDate($stock_opname->created_at), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('note', 'Catatan', array('class' => 'col-sm-2 left control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('note', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('checker', 'PIC Check Barang', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('checker', null, array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('total', 'Total (bersih)', array('class' => 'col-sm-2 control-label')) !!}
                            <div class="col-sm-4">
                                {!! Form::text('total', showRupiah($stock_opname->total), array('class' => 'form-control', 'readonly' => 'readonly')) !!}
                            </div>
                        </div>
                    </div>

                    <div class="so-sum">
                        <div class="so-card bad">
                            <small>Barang kurang (rugi)</small>
                            <b>{{ \App\Services\StockOpnameService::rupiah(-$kurang->sum('nilai')) }}</b>
                            <span>{{ $kurang->count() }} barang</span>
                        </div>
                        <div class="so-card ok">
                            <small>Barang lebih</small>
                            <b>{{ \App\Services\StockOpnameService::rupiah($lebih->sum('nilai')) }}</b>
                            <span>{{ $lebih->count() }} barang</span>
                        </div>
                        <div class="so-card">
                            <small>Stok cocok</small>
                            <b>{{ $sama }}</b>
                            <span>barang</span>
                        </div>
                    </div>

                    <div class="so-journal">
                        <b>Jurnal:</b>
                        @forelse($journals as $journal)
                            <div>
                                @if(canMenu('laporan.jurnal'))
                                    <a href="{{ url($role . '/journal/all/all/' . $journal->journal_date . '/' . $journal->journal_date . '/id/asc/15') }}" target="_blank">#{{ $journal->id }}</a>
                                @else
                                    #{{ $journal->id }}
                                @endif
                                {{ $journal->name }} &mdash; {{ showRupiah($journal->debit) }}
                            </div>
                        @empty
                            <div class="so-muted">
                                @if($kurang->count() + $lebih->count() > 0)
                                    Stock opname lama: jurnalnya dibuat per barang (transaksi / loading).
                                @else
                                    Tidak ada jurnal, semua stok cocok.
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if(canMenu('laporan.sering_hilang'))
                        <p><a href="{{ route('admin.reports.lost-goods') }}"><i class="fa fa-line-chart"></i> Lihat barang yang sering hilang di semua stock opname</a></p>
                    @endif

                    <div class="form-group col-sm-12" style="overflow-x:scroll">
                        <table class="table table-bordered">
                            <thead>
                                <th>Barcode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Stock Lama</th>
                                <th>Stock Baru</th>
                                <th>Selisih</th>
                                <th>Nilai</th>
                            </thead>
                            <tbody>
                                @foreach($details as $detail)
                                    <tr class="{{ $detail->good->deleted_at != null ? 'so-deleted' : ($detail->nilai < 0 ? 'so-bad' : ($detail->nilai > 0 ? 'so-ok' : '')) }}">
                                        <td>
                                            <a href="{{ url($role . '/good/' . $detail->good->id . '/loading/2023-01-01/' . date('Y-m-d') . '/10') }}" class="btn" target="_blank()">
                                            {{ $detail->good->code }}</a>
                                        </td>
                                        <td>
                                            {{ $detail->good->getFullName() }}
                                        </td>
                                        <td>
                                            {{ $detail->good_unit->unit->name }}
                                        </td>
                                        <td>
                                            {{ $detail->old_stock }}
                                        </td>
                                        <td>
                                            {{ $detail->new_stock }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            {{ \App\Services\StockOpnameService::angka($detail->selisih, true) }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            {{ $detail->nilai < 0 ? '-' : ($detail->nilai > 0 ? '+' : '') }}{{ showRupiah(abs($detail->nilai)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>
</div>
    </section>
</div>

<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }
    .so-sum { display: grid; grid-template-columns: repeat(3, minmax(0, 220px)); gap: 10px; margin: 10px 0 14px; }
    .so-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; background: #fff; }
    .so-card small { display: block; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
    .so-card b { display: block; font-size: 20px; color: #0f172a; }
    .so-card span { color: #64748b; font-size: 13px; }
    .so-card.bad b { color: #b91c1c; }
    .so-card.ok b { color: #15803d; }
    .so-journal { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; }
    .so-journal > div { margin-top: 4px; word-break: break-word; }
    .so-muted { color: #64748b; }
    tr.so-bad > td { background: #fef2f2; }
    tr.so-ok > td { background: #f0fdf4; }
    tr.so-deleted > td { background: red; }
    @media (max-width: 600px) { .so-sum { grid-template-columns: 1fr; } }
</style>
@section('js-addon')
@endsection
