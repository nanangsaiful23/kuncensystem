<style type="text/css">
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(60, 141, 188) !important;
    }

    /* ══════════════════════════════════════════════════════════
       LAYOUT FORM STOCK OPNAME — minimalis, 2 kolom.
       Kiri (lebar)  = Daftar Barang Opname (fokus utama)
       Kanan (sempit)= Info opname + pencarian barang
       Di-scope ke .fso-wrap supaya tidak nabrak style global.
       ══════════════════════════════════════════════════════════ */
    .fso-wrap { font-family: inherit; }
    .fso-wrap *, .fso-wrap *::before, .fso-wrap *::after { box-sizing: border-box; }

    .fso-callout {
        background: #fdf2f2; border: 1px solid #f5c2c2; border-left: 4px solid #dc3545;
        border-radius: 6px; padding: 12px 16px; margin-bottom: 18px;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .fso-callout .fso-callout-txt { font-size: 13px; color: #7a3b3b; }
    .fso-callout .fso-callout-txt strong { color: #b02a2a; }

    .fso-main-row { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; }
    .fso-main-col { flex: 1 1 620px; min-width: 0; }
    .fso-side-col { flex: 0 0 280px; max-width: 320px; }
    @media (max-width: 991px) {
        .fso-side-col { flex: 1 1 100%; max-width: 100%; order: -1; }
    }

    /* ── Kolom kiri: tabel, minim dekorasi, maksimal fokus ── */
    .fso-main-head {
        display: flex; align-items: baseline; justify-content: space-between;
        margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #2c3e50;
    }
    .fso-main-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: #2c3e50; }
    .fso-main-head span { font-size: 12px; color: #9aa1ac; }

    .fso-tbl-wrap { overflow-x: auto; border: 1px solid #e3e6ec; border-radius: 6px; }
    .fso-tbl { width: 100%; border-collapse: collapse; min-width: 640px; margin: 0; }
    .fso-tbl thead th {
        background: #f7f8fa; color: #4b5568; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .03em;
        padding: 9px 10px; border-bottom: 2px solid #e3e6ec; white-space: nowrap;
        text-align: left;
    }
    .fso-tbl tbody td {
        padding: 7px 9px; border-bottom: 1px solid #edf0f4; vertical-align: middle;
    }
    .fso-tbl tbody tr:nth-child(even) td { background: #fafbfc; }
    .fso-tbl tbody tr:nth-child(odd)  td { background: #ffffff; }
    .fso-tbl tbody tr:hover td { background: #eef6ff; }
    .fso-tbl textarea, .fso-tbl input[type="text"] {
        width: 100%; min-width: 80px; border: 1px solid #d7dce3; border-radius: 4px;
        padding: 5px 7px; font-size: 12.5px; resize: vertical;
    }
    .fso-tbl textarea[readonly] { background: #f3f4f6; color: #374151; }
    .fso-tbl-del {
        color: #dc3545; cursor: pointer; font-size: 15px;
        display: flex; align-items: center; justify-content: center;
    }
    .fso-tbl-del:hover { color: #a71d2a; }

    /* ── Kolom kanan: sidebar ringkas, tanpa kotak berat ── */
    .fso-side-block { margin-bottom: 22px; }
    .fso-side-block:last-child { margin-bottom: 0; }
    .fso-side-title {
        font-size: 11px; font-weight: 800; color: #9aa1ac; text-transform: uppercase;
        letter-spacing: .06em; margin-bottom: 10px;
    }
    .fso-field { margin-bottom: 12px; }
    .fso-field:last-child { margin-bottom: 0; }
    .fso-field label {
        display: block; font-size: 12px; font-weight: 600; color: #4b5568; margin-bottom: 4px;
    }
    .fso-field .required { color: #dc3545; }
    .fso-field input.form-control { font-size: 13px; }

    .fso-submit-bar { margin-top: 22px; }

    /* ══════════════════════════════════════════════════════════
       MODAL BARANG MINUS — palet warna sendiri (.mg-*), plus
       kolom input "Stok Real" langsung di dalam tabel modal.
       ══════════════════════════════════════════════════════════ */
    .mg-modal .modal-content { border-radius: 8px; overflow: hidden; border: none; }
    .mg-head {
        background: #dc3545; color: #fff; padding: 14px 18px;
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: none;
    }
    .mg-head h4 { margin: 0; font-size: 16px; font-weight: 700; color: #fff; }
    .mg-head .close { color: #fff; opacity: .9; text-shadow: none; font-size: 22px; }
    .mg-head .close:hover { opacity: 1; }

    .mg-body { padding: 16px 18px; background: #fff; }
    .mg-hint {
        font-size: 12px; color: #7a3b3b; background: #fdf2f2; border: 1px solid #f5c2c2;
        border-radius: 5px; padding: 8px 12px; margin-bottom: 12px;
    }
    .mg-search-row { display: flex; gap: 10px; margin-bottom: 12px; }
    .mg-search-row input {
        flex: 1; border: 1px solid #d7dce3; border-radius: 5px; padding: 8px 12px; font-size: 13px;
    }
    .mg-search-row button {
        background: #f3f4f6; border: 1px solid #d7dce3; border-radius: 5px;
        padding: 8px 16px; font-size: 13px; color: #374151; white-space: nowrap;
    }
    .mg-search-row button:hover { background: #e9ebee; }

    .mg-tbl-wrap { max-height: 420px; overflow-y: auto; border: 1px solid #eceff3; border-radius: 6px; }
    .mg-tbl { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 640px; }
    .mg-tbl thead th {
        position: sticky; top: 0; z-index: 1;
        background: #dc3545; color: #fff; font-weight: 700; font-size: 12px;
        text-transform: uppercase; letter-spacing: .03em;
        padding: 9px 10px; text-align: left; white-space: nowrap;
    }
    /* Baris genap: putih. Baris ganjil: merah muda pucat. Teks SELALU #1f2937 —
       tidak pernah lagi merah-di-atas-merah seperti sebelumnya. */
    .mg-tbl tbody tr:nth-child(even) td { background: #ffffff; color: #1f2937; }
    .mg-tbl tbody tr:nth-child(odd)  td { background: #fdecec; color: #1f2937; }
    .mg-tbl tbody tr:hover td { background: #ffe3e3 !important; }
    .mg-tbl tbody td { padding: 7px 10px; border-bottom: 1px solid #f1d4d4; vertical-align: middle; }
    .mg-tbl tbody tr:last-child td { border-bottom: none; }
    .mg-stock-neg { color: #b02a2a; font-weight: 800; white-space: nowrap; }
    .mg-empty-cell { text-align: center; padding: 18px; color: #8a8f98 !important; background: #fff !important; }
    .mg-real-input {
        width: 90px; border: 1.5px solid #f0b3b3; border-radius: 4px;
        padding: 5px 8px; font-size: 13px; font-weight: 700; color: #1f2937;
        background: #fff8e1;
    }
    .mg-real-input:focus { outline: none; border-color: #dc3545; background: #fffbe6; }

    .mg-footer {
        padding: 12px 18px; background: #f7f8fa; border-top: 1px solid #eceff3;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
    }
    .mg-footer .mg-count { font-size: 12px; color: #8a8f98; }
    .mg-footer .mg-btns { display: flex; gap: 8px; }
    .mg-btn-close {
        background: #fff; border: 1px solid #d7dce3; color: #4b5568;
        border-radius: 5px; padding: 7px 16px; font-size: 13px;
    }
    .mg-btn-add {
        background: #dc3545; border: 1px solid #dc3545; color: #fff;
        border-radius: 5px; padding: 7px 18px; font-size: 13px; font-weight: 700;
    }
    .mg-btn-add:hover { background: #b02a2a; border-color: #b02a2a; }
</style>

<div class="panel-body fso-wrap">
    <?php $goods = getGoods() ?>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- CALLOUT BARANG STOK MINUS                              --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    @if(($minusCount ?? 0) > 0)
    <div class="fso-callout">
        <div class="fso-callout-txt">
            <strong>{{ $minusCount }} barang stok minus</strong> terdeteksi — data sistem tidak sinkron dengan stok fisik.
        </div>
        <button type="button" class="btn btn-warning btn-flat btn-sm" onclick="openMinusGoodsModal()">
            <i class="fa fa-list"></i> Tampilkan &amp; Opname Barang Minus
        </button>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- LAYOUT 2 KOLOM: TABEL (kiri, fokus) + INFO (kanan)     --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="fso-main-row">

        {{-- ── KIRI: DAFTAR BARANG OPNAME (fokus utama) ── --}}
        <div class="fso-main-col">
            <div class="fso-main-head">
                <h3>Daftar Barang Opname</h3>
                <span>Isi stok fisik riil di kolom "Stock Baru"</span>
            </div>

            <div class="fso-tbl-wrap">
                <table class="fso-tbl">
                    <thead>
                        <tr>
                            <th style="width:14%">Barcode</th>
                            <th style="width:33%">Nama</th>
                            <th style="width:14%">Satuan</th>
                            <th style="width:13%">Stock Lama</th>
                            <th style="width:13%">Stock Baru</th>
                            <th style="width:6%">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="table-transaction">
                        <?php $i = 1; ?>
                        <tr id="row-data-{{ $i }}">
                            <td>
                                <textarea type="text" name="barcodes[]" class="form-control" id="barcode-{{ $i }}" style="height: 56px"></textarea>
                            </td>
                            <td>
                                {!! Form::textarea('name_temps[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'name_temp-'.$i, 'style' => 'height: 56px')) !!}
                                {!! Form::text('names[]', null, array('id'=>'name-' . $i, 'style' => 'display:none')) !!}
                            </td>
                            <td>
                                {!! Form::text('unit_names[]', null, array('class' => 'form-control', 'readonly' => 'readonly', 'id' => 'unit_name-'.$i)) !!}
                                <input type="hidden" name="base_qtys[]" id="base_qty-{{ $i}}">
                                <input type="hidden" name="units[]" id="unit-{{ $i}}">
                            </td>
                            <td>
                                {!! Form::textarea('old_stocks[]', null, array('class' => 'form-control', 'readonly' =>
                                'readonly', 'id' => 'old_stock-'.$i, 'style' => 'height: 56px')) !!}
                            </td>
                            <td>
                                {!! Form::textarea('new_stocks[]', null, array('class' => 'form-control', 'id' => 'new_stock-'.$i, 'style' => 'height: 56px')) !!}
                            </td>
                            <td class="text-center">
                                <span class="fso-tbl-del" id="delete-{{ $i }}" onclick="deleteItem('{{ $i }}')">
                                    <i class="fa fa-times"></i>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{ csrf_field() }}

            <div class="fso-submit-bar">
                @if($SubmitButtonText == 'Edit')
                {!! Form::submit($SubmitButtonText, ['class' => 'btn btn-warning btn-flat btn-block form-control']) !!}
                @elseif($SubmitButtonText == 'Tambah')
                <div onclick="event.preventDefault(); submitForm();" class='btn btn-success btn-flat btn-block form-control'>Proses Stock Opname</div>
                @elseif($SubmitButtonText == 'View')
                @endif
            </div>
        </div>

        {{-- ── KANAN: INFO OPNAME + PENCARIAN BARANG ── --}}
        <div class="fso-side-col">

            <div class="fso-side-block">
                <div class="fso-side-title">Informasi Opname</div>
                <div class="fso-field">
                    {!! Form::label('note', 'Catatan') !!}
                    <input type="text" name="note" class="form-control" id="note" placeholder="Contoh: Opname rutin bulanan">
                </div>
                <div class="fso-field">
                    {!! Form::label('checker', 'PIC Check Barang') !!} <span class="required">*</span>
                    <input type="text" name="checker" class="form-control" id="checker" required="required" placeholder="Nama petugas">
                </div>
            </div>

            <div class="fso-side-block">
                <div class="fso-side-title">Cari &amp; Tambah Barang</div>
                <div class="fso-field">
                    {!! Form::label('all_barcode', 'Cari barcode') !!}
                    <input type="text" name="all_barcode" class="form-control" id="all_barcode"
                        onchange="searchByBarcode()" placeholder="Scan / ketik lalu Enter">
                </div>
                <div class="fso-field">
                    {!! Form::label('keyword', 'Cari keyword') !!}
                    <input type="text" name="search_good" class="form-control" id="search_good"
                        placeholder="Ketik nama barang lalu Enter">
                </div>

                <div class="modal modal-primary fade" id="modal_search">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Hasil Keyword (klik nama barang)</h4>
                            </div>
                            <div class="modal-body">
                                <div id="result_good"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- MODAL BARANG STOK MINUS — kini dengan input stok real  --}}
{{-- langsung di tabel, sekali kerja langsung terbawa ke     --}}
{{-- tabel utama saat "Tambahkan Terpilih ke Opname".        --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="modal fade mg-modal" id="modal_minus_goods">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="mg-head">
        <h4>🔴 Barang Stok Minus</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="mg-body">
        <div class="mg-hint">
            <i class="fa fa-info-circle"></i>
            Isi kolom <strong>Stok Real</strong> di sini langsung (hasil hitung fisik), lalu centang dan klik "Tambahkan Terpilih" —
            stok real yang sudah diisi akan otomatis terbawa ke tabel utama, tidak perlu diketik ulang.
        </div>
        <div class="mg-search-row">
          <input type="text" id="minus_keyword"
                 placeholder="Cari nama / kode barang..."
                 onkeyup="if(event.keyCode==13){ searchMinusGoods(); }">
          <button type="button" onclick="searchMinusGoods()">
              <i class="fa fa-search"></i> Cari
          </button>
        </div>
        <div class="mg-tbl-wrap">
          <table class="mg-tbl">
            <thead>
              <tr>
                <th style="width:5%"><input type="checkbox" id="minus_check_all" onclick="toggleAllMinus(this)"></th>
                <th style="width:13%">Kode</th>
                <th>Nama Barang</th>
                <th style="width:12%">Satuan</th>
                <th style="width:13%">Stok Sistem</th>
                <th style="width:14%">Stok Real</th>
              </tr>
            </thead>
            <tbody id="minus_goods_body">
              <tr><td colspan="6" class="mg-empty-cell">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="mg-footer">
        <span class="mg-count" id="minus_selected_info"></span>
        <div class="mg-btns">
            <button type="button" class="mg-btn-close" data-dismiss="modal">Tutup</button>
            <button type="button" class="mg-btn-add" onclick="addSelectedMinusGoods()">
                <i class="fa fa-plus"></i> Tambahkan Terpilih ke Opname
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

{!! Form::close() !!}

@section('js-addon')
<script type="text/javascript">
    var total_item = 1;
    var total_real_item=0;
          $(document).ready (function (){
              $('.select2').select2();
              $("#all_barcode").focus();

            $("#search_good").keyup( function(e){
              if(e.keyCode == 13)
              {
                ajaxFunction();
              }
            });
          });

          function fillItem(good,index)
          {
              var bool = false;

              if(good.length != 0 && good.getPcsSellingPrice.unit_qty == good.getPcsSellingPrice.base_qty)
              {
                  document.getElementById("name-" + total_item).value = good.id;
                  document.getElementById("name_temp-" + total_item).value = good.name;
                  document.getElementById("barcode-" + total_item).value = good.code;
                  document.getElementById("unit-" + total_item).value = good.getPcsSellingPrice.unit_id;
                  document.getElementById("unit_name-" + total_item).value = good.getPcsSellingPrice.unit;
                  document.getElementById("base_qty-" + total_item).value = good.getPcsSellingPrice.unit_qty;
                  document.getElementById("old_stock-" + total_item).value = good.old_stock;

                  editPrice(total_item);
                  total_real_item += 1;
                  document.getElementById("all_barcode").value = '';
              }
              else
              {
                  // alert('Barang tidak ditemukan');
              }
          }

          function searchByBarcode()
          {

              $.ajax({
                url: "{!! url($role . '/good/searchByBarcode/') !!}/" + $("#all_barcode").val(),
                success: function(result){
                  var good = result.good;
                  
                  searchItemByName(good.id);
                },
                error: function(){
                }
              });
          }

          function searchItemByName(id)
          {
              $.ajax({
                url: "{!! url($role . '/good/searchById/') !!}/" + id,
                success: function(result){
                    var index=-1;
                    var r = result.units;

                    for (var i = 0; i < r.length; i++) {
                        const getPcsSellingPrice = {unit_id: r[i].unit_id, unit_qty: r[i].unit_qty, base_qty: r[i].good_base_qty, base_buy_price: r[i].good_base_buy_price, buy_price: r[i].buy_price, selling_price: r[i].selling_price, unit: r[i].unit};
                        const good = {id: r[i].good_id, name: r[i].name, code: r[i].code, getPcsSellingPrice: getPcsSellingPrice, old_stock: r[i].stock};

                        fillItem(good,index);
                        $('#modal_search').modal('hide');
                        $('#search_good').val('');
                        $('#result_good').val('');
                    }
                },
                error: function(){
                }
              });
          }

          function changeFocus(index)
          {
              $("#barcode-" + index).focus();
          }

          function submitForm()
          {
              var isi=true;
              isi = checkNumber(isi);
              if(total_real_item == 0 )
              {
                  alert('Silahkan pilih barang');
                  isi=false;
              }
              if(isi)
              {
                  document.getElementById('loading-form').submit();
                  // alert('hay');
              }
          }

          function formatNumber(name)
          {
              num = document.getElementById(name).value;
              num = num.toString().replace(/,/g,'');
              document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
          }
          function unFormatNumber(num)
          {
              return num.replace(/,/g,'');

          }
          function deleteItem(index)
          {
              $("#row-data-" + index).remove();
              total_real_item-=1;
              changeTotal();
          }

          function editPrice(index)
          {

              temp1=parseInt(index)+1
              htmlResult = '<tr id="row-data-' + temp1+ '"><td><textarea type="text" name="barcodes[]" class="form-control" id="barcode-' + temp1+ '" onchange="searchName(' + temp1+ ')"></textarea></td><td width="20%"><textarea  class="form-control" readonly="readonly" id="name_temp-' + temp1+ '" name="name_temps[]" type="text" style="height: 56px"></textarea><textarea id="name-' + temp1 + '" name="names[]" type="text" style="display:none"></textarea></td><td><textarea  class="form-control" readonly="readonly" id="unit_name-' + temp1 + '" name="unit_names[]" type="text"></textarea><input type="hidden" name="base_qtys[]" id="base_qty-' + temp1 + '"><input type="hidden" name="units[]" id="unit-' + temp1 + '"></td><td><textarea class="form-control" readonly="readonly" id="old_stock-' + temp1+ '" name="old_stocks[]" type="text"></textarea></td><td><textarea class="form-control" id="new_stock-' + temp1+ '" name="new_stocks[]" type="text"></textarea></td><td class="text-center"><span class="fso-tbl-del" id="delete-' + temp1+'" onclick="deleteItem('
              + temp1+ ')"><i class="fa fa-times"></i></span></td></tr>';
              htmlResult += "<script>$('#exp-"+temp1+"').datepicker({autoclose: true,format: 'yyyy-mm-dd',todayHighlight: true});<\/script>";
              if(index == total_item)
              {
                  total_item += 1;
                  $("#table-transaction").prepend(htmlResult);
                  // $("#table-transaction").append(s);
              }
              document.getElementById("all_barcode").value = '';
              // $("#all_barcode").focus();

          }

        function ajaxFunction()
        {   
            $('#modal_search').modal('show');   

              $.ajax({
                url: "{!! url($role . '/good/searchByKeyword/') !!}/" + $("#search_good").val(),
                success: function(result){
                    htmlResult = '';

                    htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
                  var r = result.goods;

                  for (var i = 0; i < r.length; i++) {
                    if((i%2) == 0) 
                    {
                        color = '#FFF1CE';
                    }
                    else color = "#FDEFF4";
                    htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchItemByName(\"" + r[i].id + "\")'>" + r[i].name + "</textarea>";
                  }
                  $("#result_good").html(htmlResult);
                  $('.modal-body').css('height',$( window ).height()*0.5);
                },
                error: function(){
                    console.log('error');
                }
              });
        }

        /* ══════════════════════════════════════════════════════
           BARANG STOK MINUS — dengan input Stok Real langsung
           di dalam modal, supaya tidak dua kali kerja.
           ══════════════════════════════════════════════════════ */
        var minusGoodsData = [];

        function openMinusGoodsModal()
        {
            $('#modal_minus_goods').modal('show');
            searchMinusGoods();
        }

        function searchMinusGoods()
        {
            var keyword = $('#minus_keyword').val();
            $('#minus_goods_body').html('<tr><td colspan="6" class="mg-empty-cell">Memuat data...</td></tr>');

            $.ajax({
                url: "{!! url($role . '/stock-opname/minus-goods') !!}",
                data: { keyword: keyword },
                success: function(result) {
                    minusGoodsData = result.goods;
                    renderMinusGoodsList(minusGoodsData);
                },
                error: function() {
                    $('#minus_goods_body').html('<tr><td colspan="6" class="mg-empty-cell">Gagal memuat data.</td></tr>');
                }
            });
        }

        function renderMinusGoodsList(goods)
        {
            if (goods.length == 0) {
                $('#minus_goods_body').html('<tr><td colspan="6" class="mg-empty-cell">🎉 Tidak ada barang dengan stok minus.</td></tr>');
                $('#minus_selected_info').text('');
                return;
            }

            var html = '';
            for (var i = 0; i < goods.length; i++) {
                var g = goods[i];
                html += '<tr>'
                      + '<td><input type="checkbox" class="minus-chk" data-idx="' + i + '" onclick="autoCheckOnRealInput(' + i + ')"></td>'
                      + '<td>' + (g.code || '-') + '</td>'
                      + '<td>' + g.name + '</td>'
                      + '<td>' + (g.unit_name || '-') + '</td>'
                      + '<td class="mg-stock-neg">' + g.last_stock + '</td>'
                      + '<td><input type="text" class="mg-real-input" data-idx="' + i + '" placeholder="cth: 12" '
                      + 'oninput="autoCheckMinus(' + i + ')"></td>'
                      + '</tr>';
            }
            $('#minus_goods_body').html(html);
            $('#minus_selected_info').text(goods.length + ' barang ditemukan');
        }

        function toggleAllMinus(el)
        {
            $('.minus-chk').prop('checked', el.checked);
        }

        /* Kalau user langsung mengetik stok real tanpa mencentang dulu,
           checkbox baris itu otomatis ikut tercentang — supaya alur kerja
           "isi dulu baru pilih" tetap cepat, tidak wajib klik 2 tempat. */
        function autoCheckMinus(idx)
        {
            var val = $('.mg-real-input[data-idx="' + idx + '"]').val();
            if (val !== '') {
                $('.minus-chk[data-idx="' + idx + '"]').prop('checked', true);
            }
        }

        function autoCheckOnRealInput(idx)
        {
            // no-op, dipertahankan untuk kompatibilitas pemanggilan inline;
            // logika utama ada di autoCheckMinus()
        }

        /* Mengisi 1 baris tabel opname dengan data barang minus, memakai
           pola yang PERSIS sama dengan fillItem() bawaan (barcode scan),
           jadi tetap kompatibel 100% dengan logic store() yang sudah ada.
           realStock (jika diisi di modal) langsung dipakai sebagai Stock
           Baru — tidak perlu diketik ulang di tabel utama. */
        function fillMinusGood(good, realStock)
        {
            var idx = total_item;

            document.getElementById("name-" + idx).value = good.good_id;
            document.getElementById("name_temp-" + idx).value = good.name;
            document.getElementById("barcode-" + idx).value = good.code;
            document.getElementById("unit-" + idx).value = good.unit_id;
            document.getElementById("unit_name-" + idx).value = good.unit_name;
            document.getElementById("base_qty-" + idx).value = good.unit_qty;

            var oldStockEl = document.getElementById("old_stock-" + idx);
            oldStockEl.value = good.last_stock;
            oldStockEl.style.color = '#b02a2a';
            oldStockEl.style.fontWeight = '700';

            var newStockEl = document.getElementById("new_stock-" + idx);
            if (realStock !== undefined && realStock !== null && realStock !== '') {
                // Stok real sudah diisi di modal — langsung terpakai, tidak
                // perlu diketik ulang. Diberi highlight hijau supaya user
                // sadar nilai ini sudah otomatis terisi dari modal.
                newStockEl.value = realStock;
                newStockEl.style.background = '#eaf7ee';
            } else {
                // Belum diisi di modal — biarkan kosong seperti biasa,
                // tetap wajib diisi manual di tabel utama.
                newStockEl.value = '';
                newStockEl.style.background = '#fff3cd';
                newStockEl.setAttribute('placeholder', 'Isi stok fisik riil');
            }

            editPrice(idx);
            total_real_item += 1;
        }

        function addSelectedMinusGoods()
        {
            var checks = document.querySelectorAll('.minus-chk:checked');

            if (checks.length == 0) {
                alert('Pilih minimal satu barang terlebih dahulu (atau isi Stok Real untuk otomatis memilihnya).');
                return;
            }

            if ($('#note').val() == '') {
                $('#note').val('Opname perbaikan stok minus');
            }

            for (var i = 0; i < checks.length; i++) {
                var idx = checks[i].getAttribute('data-idx');
                var realStock = $('.mg-real-input[data-idx="' + idx + '"]').val();
                fillMinusGood(minusGoodsData[idx], realStock);
            }

            $('#modal_minus_goods').modal('hide');
            document.getElementById("checker").focus();
        }

        function checkNumber(isi) 
        {
            for(i = 0; i < total_real_item; i++)
            {
                val = $('#new_stock-' + i).val();
                if($.isNumeric(val) == false && val != null)
                {
                    isi = false;
                    alert('Input hanya berupa angka');
                }
            }

            return isi;
        }
</script>
@endsection