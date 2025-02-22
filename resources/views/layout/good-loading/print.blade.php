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
			LOADING<br>
			{{ displayDateTime($good_loading->created_at) }}<br>
			<hr class="new2">
		</div>
		<div style="text-align: center;">
			<table style="font-size: 10px; text-align: center;">
				<?php $i = 1; ?>
				@foreach($good_loading->detailsWithDeleted() as $detail)
					<tr>
						<td style="text-align: left !important;">
							<b>{{ $i++ . '. ' . $detail->good_unit->good->name }}</b>
						</td>
					</tr>
					<tr>
						<td style="text-align: left !important;">
							{{ $detail->quantity . ' * ' . $detail->good_unit->unit->name . ' (Stok lama: ' . $detail->good_unit->good->getStockWoLastLoad($good_loading->id) . ')' }}
						</td>
					</tr>
				@endforeach
			</table>
			Total item: {{ --$i }} | Total qty: {{ $good_loading->details->sum('quantity') }}
		</div>
	</body>

	<script type="text/javascript">		
        $(document).ready (function (){
        	window.print();
        }); 

	    window.setTimeout(function(){
      		window.location = window.location.origin + '/admin/good-loading/normal/create';
	    }, 5000);
	</script>
</html>