@include('layout.transaction.style')

<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content" style="padding:0">
    {!! Form::model($transaction, array('class' => 'form-horizontal', 'id' => 'loading-form')) !!}
    <div class="page-scroll">

      <div class="page-actions">
        <h4 style="margin:0 10px 0 0;font-weight:700">{{ $default['page_name'] }} #{{ $transaction->id }}</h4>
        <span style="color:var(--clr-muted);font-size:14px">{{ $transaction->created_at }}</span>
        <a class="btn-page" href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-arrow-left"></i> Batal, kembali ke detail</a>
        <span class="kbd-hint"><kbd>Enter</kbd> kolom berikut &middot; <kbd>&uarr;</kbd><kbd>&darr;</kbd> baris &middot; <kbd>F8</kbd> uang dibayar &middot; <kbd>Ctrl</kbd>+<kbd>Enter</kbd> simpan</span>
      </div>

      <div class="info-grid">
        <div class="info-card"><div class="k">PIC / Kasir</div>
          {!! Form::text('actor', $transaction->actor()->name, array('class' => 'form-control', 'readonly' => 'readonly')) !!}</div>
        <div class="info-card"><div class="k">Member</div>
          {!! Form::select('member_id', getMemberLists(), $transaction->member_id, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'member_id']) !!}</div>
        <div class="info-card"><div class="k">Keterangan</div>
          {!! Form::text('note', $transaction->note, array('class' => 'form-control')) !!}</div>
        <div class="info-card"><div class="k">Total Harga</div>
          {!! Form::text('total_item_price', $transaction->total_item_price, array('class' => 'form-control', 'readonly' => 'readonly' ,'id' => 'total_item_price')) !!}</div>
        <div class="info-card"><div class="k">Total Potongan Per Barang</div>
          {!! Form::text('total_discount_items_price', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_discount_items_price')) !!}</div>
        <div class="info-card"><div class="k">Total Potongan Akhir</div>
          {!! Form::text('total_discount_price', $transaction->total_discount_price, array('class' => 'form-control', 'readonly' => 'readonly','id' => 'total_discount_price')) !!}</div>
        <div class="info-card big"><div class="k">Total Akhir</div>
          {!! Form::text('total_sum_price', $transaction->total_sum_price, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'total_sum_price', 'style' => 'font-size:22px;font-weight:800;')) !!}</div>
        <div class="info-card"><div class="k">Total Uang <kbd>F8</kbd></div>
          <input type="text" name="money_paid" class="form-control" id="money_paid" autocomplete="off" oninput="changeReturn()" value="{{ $transaction->money_paid }}"></div>
        <div class="info-card"><div class="k">Kembalian</div>
          {!! Form::text('money_returned', $transaction->money_returned, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'money_returned')) !!}</div>
      </div>

      <div class="panel-table" style="min-height:0">
        <div class="panel-table-header">
          <h6><i class="fa fa-list-ul"></i> Daftar Barang</h6>
          <span class="badge-count">{{ count($transaction->details) }} item</span>
        </div>
        <div class="panel-table-scroll" style="overflow-x:auto">
          <table class="tbl-items">
            <thead>
              <tr>
                <th class="col-no">#</th>
                <th>Barcode</th>
                <th class="col-name">Nama Barang</th>
                <th>Satuan</th>
                <th class="col-qty" style="text-align:center">Jml</th>
                @if(isSupervisor())
                  <th class="col-num" style="text-align:right">Harga Beli</th>
                  <th class="col-num" style="text-align:right">Harga Jual</th>
                  <th class="col-num" style="text-align:right">Total Diskon</th>
                  <th class="col-sum" style="text-align:right">Total Akhir</th>
                @endif
              </tr>
            </thead>
            <tbody id="table-transaction">
              <?php $i = 1; ?>
              <input type="hidden" name="change" id="change">
              @foreach($transaction->details as $detail)
                <tr id="row-data-{{ $i }}" data-i="{{ $i }}" class="{{ $i % 2 ? 'group-odd' : 'group-even' }}">
                  <td class="td-no">{{ $i }}<input type="hidden" name="ids[]" id="id-{{ $i }}" value="{{ $detail->id }}"></td>
                  <td><input type="text" name="barcodes[]" class="fc" id="barcode-{{ $i }}" value="{{ $detail->good_unit->good->code }}" readonly tabindex="-1"></td>
                  <td><input type="text" name="name_temps[]" class="fc" id="name_temp-{{ $i }}" value="{{ $detail->good_unit->good->name }}" readonly tabindex="-1" title="{{ $detail->good_unit->good->name }}" style="font-weight:600"></td>
                  <td><input type="text" name="units[]" class="fc" id="unit-{{ $i }}" value="{{ $detail->good_unit->unit->name }}" readonly tabindex="-1"></td>
                  <td class="col-qty"><input type="text" name="quantities[]" class="fc qty" id="quantity-{{ $i }}" value="{{ $detail->quantity }}" autocomplete="off" inputmode="decimal" oninput="editPrice('{{ $i }}')"></td>
                  <td @if(!isSupervisor()) style="display:none" @endif><input type="text" name="buy_prices[]" class="fc num" id="buy-{{ $i }}" value="{{ $detail->buy_price }}" readonly tabindex="-1"></td>
                  <td @if(!isSupervisor()) style="display:none" @endif><input type="text" name="selling_prices[]" class="fc num" id="selling_price-{{ $i }}" value="{{ $detail->selling_price }}" readonly tabindex="-1"></td>
                  <td @if(!isSupervisor()) style="display:none" @endif><input type="text" name="discount_prices[]" class="fc num" id="discount_price-{{ $i }}" value="{{ $detail->discount_price }}" readonly tabindex="-1"></td>
                  <td @if(!isSupervisor()) style="display:none" @endif><input type="text" name="sum_prices[]" class="fc num sum" id="sum_price-{{ $i }}" value="{{ $detail->sum_price }}" readonly tabindex="-1"></td>
                </tr>
                <?php $i++ ?>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{ method_field('PUT') }}

      <button type="button" class="btn-submit edit" style="margin-top:10px;font-size:18px;padding:12px" onclick="event.preventDefault(); submitForm();">
        <i class="fa fa-save"></i> Simpan perubahan transaksi
      </button>

    </div>
    {!! Form::close() !!}
  </section>
</div>

@section('js-addon')
<script type="text/javascript">
    function num(v){ var n = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isFinite(n) ? n : 0; }
    function qtyNum(v){ var n = parseFloat(String(v == null ? '' : v).trim().replace(',', '.')); return isFinite(n) ? n : 0; }
    function r2(n){ return Math.round(n * 100) / 100; }
    function fmt(n){ return r2(n).toLocaleString('en-US', { maximumFractionDigits:2 }); }
    function plain(n){ return String(r2(n)); }

    $(document).ready(function(){
        $('.select2').select2();
        changeTotal();
        var first = document.getElementById('quantity-1');
        if (first) first.focus();
    });

    function changeLists(id){
        var change = $("#change").val();
        if (change.indexOf(';' + id + ';') === -1) {
            change += id + ';';
        }
        $("#change").val(change);
    }

    function editPrice(i){
        changeLists(i);
        var sum = r2(num($('#selling_price-' + i).val()) * qtyNum($('#quantity-' + i).val())) - num($('#discount_price-' + i).val());
        $('#sum_price-' + i).val(fmt(sum));
        $('#quantity-' + i).toggleClass('is-zero', qtyNum($('#quantity-' + i).val()) <= 0);
        changeTotal();
    }

    function changeTotal(){
        var item = 0, sum = 0, disc = 0;
        $('#table-transaction tr').each(function(){
            var i = $(this).data('i');
            item += r2(num($('#selling_price-' + i).val()) * qtyNum($('#quantity-' + i).val()));
            sum  += num($('#sum_price-' + i).val());
            disc += num($('#discount_price-' + i).val());
        });
        var total = sum - num($('#total_discount_price').val());
        $('#total_item_price').val(fmt(item));
        $('#total_discount_items_price').val(fmt(disc));
        $('#total_sum_price').val(fmt(total));
        changeReturn();
    }

    function changeReturn(){
        $('#money_returned').val(fmt(num($('#money_paid').val()) - num($('#total_sum_price').val())));
    }

    function submitForm(){
        if (window._submitting) return;
        var bad = null;
        $('#table-transaction tr').each(function(){
            var i = $(this).data('i');
            if (qtyNum($('#quantity-' + i).val()) <= 0) { bad = document.getElementById('quantity-' + i); return false; }
        });
        if (bad) { alert('Jumlah harus lebih dari 0'); bad.focus(); return; }
        if (!confirm('Simpan perubahan transaksi ini?')) return;

        window._submitting = true;
        $('.btn-submit.edit').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        $('#loading-form').find('input').filter(function(){
            return /^(quantities|buy_prices|selling_prices|discount_prices|sum_prices)\[\]$|^(total_item_price|total_discount_items_price|total_discount_price|total_sum_price|money_paid|money_returned)$/.test(this.name || '');
        }).each(function(){
            if (this.value !== '') this.value = /^quantities/.test(this.name) ? plain(qtyNum(this.value)) : plain(num(this.value));
        });
        document.getElementById('loading-form').submit();
    }

    /* Keyboard: sama dengan halaman tambah */
    function qtyInputs(){ return $('#table-transaction input[id^="quantity-"]'); }
    document.addEventListener('keydown', function(e){
        if (e.keyCode === 119) { e.preventDefault(); $('#money_paid').focus().select(); return; }                 // F8
        if (e.ctrlKey && e.keyCode === 13) { e.preventDefault(); submitForm(); return; }                          // Ctrl+Enter
        var t = e.target;
        if (!t || !t.id) return;
        if (t.id === 'money_paid') {
            if (e.keyCode === 13) { e.preventDefault(); submitForm(); }
            else if (e.keyCode === 27 || e.keyCode === 38) { e.preventDefault(); var $q = qtyInputs(); if ($q.length) $q.last().focus(); }
            return;
        }
        if (!/^quantity-\d+$/.test(t.id)) return;
        var $all = qtyInputs(), idx = $all.index(t);
        if (e.repeat && (e.keyCode === 13 || e.keyCode === 38 || e.keyCode === 40)) { e.preventDefault(); return; }
        if (e.keyCode === 13) {
            e.preventDefault(); e.stopPropagation();
            if (qtyNum(t.value) <= 0) { alert('Jumlah harus lebih dari 0'); return; }
            if (e.shiftKey) { if (idx > 0) $all.eq(idx - 1).focus(); }
            else if (idx + 1 < $all.length) $all.eq(idx + 1).focus(); else $('#money_paid').focus().select();
        } else if (e.keyCode === 40) { e.preventDefault(); if (idx + 1 < $all.length) $all.eq(idx + 1).focus(); else $('#money_paid').focus().select(); }
        else if (e.keyCode === 38) { e.preventDefault(); if (idx > 0) $all.eq(idx - 1).focus(); }
    }, true);

    $(document).on('focus', '#table-transaction input.fc:not([readonly])', function(){
        var el = this; el._sel = true; el.select(); setTimeout(function(){ el.select(); }, 0);
    });
    $(document).on('mouseup', '#table-transaction input.fc', function(e){ if (this._sel) { this._sel = false; e.preventDefault(); } });
    $('#loading-form').on('keydown', function(e){ if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault(); });
</script>
@endsection
