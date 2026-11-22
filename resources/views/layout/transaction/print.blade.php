<html>
	{{-- Font dimuat tanpa menahan halaman: bila internet lambat/mati struk tetap tercetak dengan font cadangan. --}}
	<link id="receipt-font" rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Glegoo:wght@400;700&display=swap" media="none" onload="this.media='all'; window.receiptFontLoaded && window.receiptFontLoaded();" onerror="window.receiptFontFailed && window.receiptFontFailed();">
	<style type="text/css">
		body, table, th, td
		{
			font-family: "Glegoo", Georgia, "Times New Roman", serif !important;
/*			font-weight: bold;*/
		}
		table {
		  border-collapse: collapse;
		  margin-left: auto;
		  margin-right: auto;
		}

		table, th, td {
		  /*border: 0.1px solid black;*/
		}

		hr.new2 {
		  border-top: 1px dashed;
		}
	</style>
	<body style="font-size: 10px;">
		<div style="text-align: center;">
			{{ config('app.name') }}<br>
			{{ config('app.offline_address') }}<br>
			Menerima pesanan<br>
			wa/telp {{ config('app.phone_number') }}
			<hr class="new2">
			{{ displayDateTime($transaction->created_at) }}<br>
			Kasir: {{ getActor($transaction->role, $transaction->role_id)->name }}<br>
			Member: {{ $transaction->member->name }}
			<hr class="new2">
		</div>
		<div style="text-align: center;">
			<table style="font-size: 10px; text-align: center;">
				<?php $i = 1; ?>
				@foreach($transaction->details as $detail)
					<tr>
						<td style="text-align: left !important;" colspan="2">
							@if($detail->type == 'retur') Retur: @endif
							<b>{{ $i++ . '. ' . showShortName($detail->good_unit->good->getFullName()) }}</b>
						</td>
					</tr>
					<tr>
						<td style="text-align: left !important;">
							{{ $detail->quantity . ' * ' . $detail->good_unit->unit->name . ' @' . printRupiah(checkNull($detail->selling_price - ($detail->discount_price / $detail->quantity))) }}
						</td>
						<td style="text-align: right !important;">
							@if($detail->type == 'retur') - @endif {{ showRupiah(checkNull($detail->selling_price * $detail->quantity - $detail->discount_price)) }}
						</td>
					</tr>
					<!-- @if($detail->discount_price != 0) 
						<tr>
							<td style="text-align: right;">Diskon</td>
							<td style="text-align: right !important;">-{{ printRupiah(checkNull($detail->discount_price)) }}</td>
						</tr>
					@endif -->
				@endforeach
				<tr style="margin-top: 10px;">
					<td colspan="2"><hr></td>
				</tr>
				<tr style="margin-top: 10px; text-align: right !important">
					<td>Total Harga</td>
					<td>{{ showRupiah(checkNull($transaction->total_item_price) - checkNull($transaction->details->sum('discount_price'))) }}</td>
				</tr>
				<!-- <tr>
					<td style="text-align: right !important">
						Total Diskon per Item
					</td>
					<td style="text-align: right !important">
						-{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}
					</td>
				</tr> -->
				<tr>
					<td style="text-align: right !important">
						Potongan Akhir
					</td>
					<td style="text-align: right !important">
						-{{ showRupiah(checkNull($transaction->total_discount_price)) }}
					</td>
				</tr>
				@if($transaction->voucher != null)
					<tr>	
						<td style="text-align: right !important">
							<b>Voucher
						</td>
						<td style="text-align: right !important">
							<b>-{{ showRupiah(checkNull($transaction->voucher_nominal)) }}
						</td>
					</tr>
				@endif
				<tr style="margin-top: 10px;">
					<td colspan="2"><hr></td>
				</tr>
				<tr>
					<td style="text-align: right !important">
						<b>Total Akhir
					</td>
					<td style="text-align: right !important">
						<b>{{ showRupiah(checkNull($transaction->total_sum_price)) }}
					</td>
				</tr>
				<tr style="margin-top: 10px;">
					<td style="text-align: right !important"><b>Bayar</td>
					<td style="text-align: right !important"><b>{{ showRupiah(checkNull($transaction->money_paid)) }}</td>
				</tr>
				<tr style="margin-top: 10px;">
					<td style="text-align: right !important"><b>Kembali</td>
					<td style="text-align: right !important"><b>{{ showRupiah(checkNull($transaction->money_returned)) }}</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: left;">
						Total item : {{ --$i }} | Total qty  : {{ $transaction->details->sum('quantity') }}
					</td>
				</tr>
			</table>
		</div>
		<div style="text-align: center;">
			<hr class="new2">
			Terima kasih<br>
			Anda telah hemat sejumlah {{ showRupiah(checkNull($transaction->details->sum('discount_price')) + checkNull($transaction->total_discount_price)) }}<br>
			<b>Barang yang telah dibeli tidak dapat ditukar/dikembalikan<br></b>
			Keberkahan di setiap transaksi
		</div>
	</body>

	<script type="text/javascript">
		// Tanpa jQuery/CDN: cetak begitu font siap (maks. 1,5 dtk), lalu langsung kembali
		// ke halaman asal setelah dialog cetak ditutup, bukan menunggu 5 dtk tetap.
		(function () {
			var printed = false, left = false;
			var backUrl = document.referrer || '{{ url($role . '/transaction/create') }}';

			function goBack() {
				if (left) return;
				left = true;
				window.location = backUrl;
			}

			function doPrint() {
				if (printed) return;
				printed = true;
				window.print();
				// print() menunggu dialog ditutup; jeda singkat agar antrean cetak selesai terkirim.
				setTimeout(goBack, 700);
			}

			window.onafterprint = function () { setTimeout(goBack, 300); };

			window.receiptFontLoaded = function () {
				if (document.fonts && document.fonts.load) {
					document.fonts.load('10px "Glegoo"').then(doPrint, doPrint);
				} else {
					doPrint();
				}
			};
			window.receiptFontFailed = doPrint;

			var font = document.getElementById('receipt-font');
			if (font && font.sheet && font.media === 'all') window.receiptFontLoaded();

			setTimeout(doPrint, 1500);   // font belum datang: cetak dengan font cadangan
			setTimeout(goBack, 10000);   // jaga-jaga bila dialog cetak tidak pernah muncul
		})();
	</script>
</html>