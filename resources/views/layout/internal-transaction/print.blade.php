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
			{{ displayDateTime($transaction->created_at) }}<br>
			Kasir: {{ getActor($transaction->role, $transaction->role_id)->name }}<br>
			<hr class="new2">
		</div>
		<div style="text-align: center;">
			<table style="font-size: 10px; text-align: center;">
				<?php $i = 1; ?>
				@foreach($transaction->details as $detail)
					<tr>
						<td style="text-align: left !important;">
							@if($detail->type == 'retur') Retur: @endif
							<b>{{ showShortName($detail->good_unit->good->name) }}</b>
						</td>
					</tr>
					<tr>
						<td style="text-align: left !important;">
							{{ $detail->quantity . ' * ' . $detail->good_unit->unit->name }}
						</td>
					</tr>
				@endforeach
			</table>
		</div>
		<hr class="new2">
		<div style="text-align: center;">
			<table style="font-size: 10px; text-align: center;">
				<tr>
					<td style="text-align: right !important">
						<b>Total Akhir
					</td>
					<td style="text-align: right !important">
						<b>{{ showRupiah(checkNull($transaction->total_sum_price)) }}
					</td>
				</tr>
			</table>
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