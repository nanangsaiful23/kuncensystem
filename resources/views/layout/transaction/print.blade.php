<html>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=AR+One+Sans&family=Glegoo:wght@300;400;500;600">
	<style type="text/css">
		body, table, th, td
		{
			font-family: "Glegoo" !important;
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
	<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
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
							<b>{{ showShortName($detail->good_unit->good->name) }}</b>
						</td>
					</tr>
					<tr>
						<td style="text-align: left !important;">
							{{ $detail->quantity . ' * ' . $detail->good_unit->unit->name . ' @' . printRupiah(checkNull($detail->selling_price)) }}
						</td>
						<td style="text-align: right !important;">
							@if($detail->type == 'retur') - @endif {{ showRupiah(checkNull($detail->selling_price) * $detail->quantity) }}
						</td>
					</tr>
					@if($detail->discount_price != 0) 
						<tr>
							<td style="text-align: right;">Diskon</td>
							<td style="text-align: right !important;">-{{ printRupiah(checkNull($detail->discount_price)) }}</td>
						</tr>
					@endif
				@endforeach
				<tr style="margin-top: 10px;">
					<td colspan="2"><hr></td>
				</tr>
				<tr style="margin-top: 10px; text-align: right !important">
					<td>Total Harga</td>
					<td>{{ showRupiah(checkNull($transaction->total_item_price) - checkNull($transaction->details->sum('discount_price'))) }}</td>
				</tr>
				<tr>
					<td style="text-align: right !important">
						Total Diskon per Item
					</td>
					<td style="text-align: right !important">
						-{{ showRupiah(checkNull($transaction->details->sum('discount_price'))) }}
					</td>
				</tr>
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
        $(document).ready (function (){
        	window.print();
        }); 

	    window.setTimeout(function(){
      		window.location = window.location.origin + '/{{ $role }}/transaction/create';
	    }, 5000);
	</script>
</html>