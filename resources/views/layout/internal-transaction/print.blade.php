<html>
	<style type="text/css">

		table {
		  border-collapse: collapse;
		}

		table, th, td {
		  /*border: 0.1px solid black;*/
		}
	</style>
	<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
	<body style="font-size: 10px;">
		<div style="text-align: center;">
			NTN Mart<br>
			Getasan, Semarang<br>
			Menerima pesanan<br>
			wa/telp 0823-2292-2654
			<hr>
			{{ displayDateTime($transaction->created_at) }}<br>
			Kasir: {{ getActor($transaction->role, $transaction->role_id)->name }}<br>
			Member: {{ $transaction->member->name }}
		</div>
		<table style="font-size: 10px; text-align: center;">
			<?php $i = 1; ?>
			@foreach($transaction->details as $detail)
				<tr>
					<td>{{ $i }}.<br></td>
					<td style="text-align: left !important;">
						{{ $detail->good_unit->good->name . ' ' . $detail->good_unit->unit->name }}
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td>
						{{ $detail->quantity . ' * ' . $detail->good_unit->unit->name . ' @' . printRupiah(checkNull($detail->selling_price))  . ' = '}}
					</td>
					<td style="text-align: right !important;">
						@if($detail->type == 'retur') - @endif {{ printRupiah(checkNull($detail->selling_price) * $detail->quantity) }}
					</td>
				</tr>
				@if($detail->discount_price != 0) 
					<tr>
						<td></td>
						<td style="text-align: right;">Diskon</td>
						<td style="text-align: right !important;">-{{ printRupiah(checkNull($detail->discount_price)) }}</td>
					</tr>
				@endif
			@endforeach
			<tr style="margin-top: 10px;">
				<td colspan="3"><hr></td>
			</tr>
			<tr style="margin-top: 10px; text-align: right !important">
				<td colspan="2">Total harga</td>
				<td>{{ printRupiah(checkNull($transaction->total_item_price) - checkNull($transaction->details->sum('discount_price'))) }}</td>
			</tr>
			<tr>
				<td style="text-align: right !important" colspan="2">
					Total Diskon per Item
				</td>
				<td style="text-align: right !important">
					-{{ printRupiah(checkNull($transaction->details->sum('discount_price'))) }}
				</td>
			</tr>
			<tr>
				<td style="text-align: right !important" colspan="2">
					Potongan Akhir
				</td>
				<td style="text-align: right !important">
					-{{ printRupiah(checkNull($transaction->total_discount_price)) }}
				</td>
			</tr>
			<tr>
				<td style="text-align: right !important" colspan="2">
					Total akhir
				</td>
				<td style="text-align: right !important">
					{{ printRupiah(checkNull($transaction->total_sum_price)) }}
				</td>
			</tr>
			<tr style="margin-top: 10px;">
				<td></td>
				<td colspan="2"><hr></td>
			</tr>
			<tr style="margin-top: 10px;">
				<td style="text-align: right !important" colspan="2">Bayar</td>
				<td style="text-align: right !important">{{ printRupiah(checkNull($transaction->money_paid)) }}</td>
			</tr>
			<tr style="margin-top: 10px;">
				<td style="text-align: right !important" colspan="2">Kembali</td>
				<td style="text-align: right !important">{{ printRupiah(checkNull($transaction->money_returned)) }}</td>
			</tr>
		</table>
		<div style="text-align: center;">
			<hr>
			Terima kasih<br>
			Anda telah hemat sejumlah {{ showRupiah(checkNull($transaction->details->sum('discount_price')) + checkNull($transaction->total_discount_price)) }}<br>
			Keberkahan di setiap transaksi
		</div>
	</body>

	<script type="text/javascript">		
        $(document).ready (function (){
        	window.print();
        }); 

	    window.setTimeout(function(){
      		window.location = window.location.origin + '/{{ $role }}/internal-transaction/create';
	    }, 5000);
	</script>
</html>