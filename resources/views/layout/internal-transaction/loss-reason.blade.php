{{--
    Penyebab barang keluar untuk jenis transaksi 5215 - Biaya Penyusutan Barang.
    Hanya tampil saat jenis 5215 dipilih. Dipakai untuk merinci stok keluar
    (terjual / retur / expired / rusak / hilang) di daftar barang & reorder.
--}}
@if(\App\Services\StockOutflow::hasLossReason())
    <div id="loss-reason-box" style="display: none; margin-top: 6px;">
        <label for="loss_reason" style="font-weight: normal;">Penyebab barang keluar</label>
        <select class="form-control" style="width: 100%;" name="loss_reason" id="loss_reason">
            @foreach(\App\Services\StockOutflow::LOSS_REASONS as $value => $label)
                <option value="{{ $value }}" @if(($selectedLossReason ?? 'hilang') == $value) selected @endif>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            function toggleLossReason() {
                $('#loss-reason-box').toggle($('#type').val() == '5215');
            }

            $('#type').on('change', toggleLossReason);
            toggleLossReason();
        });
    </script>
@endif
