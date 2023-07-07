<html>
	<style type="text/css">
		@page 
		{ 
			size: 8.27in 11.69in;  
			size: landscape;
			margin: 0.1in; 
		}

		@media print
		{
			@page 
			{ 
				size: 8.27in 11.69in;  
				size: landscape;
				margin: 0.1in; 
			}
		}

		table {
		  border-collapse: collapse;
		}

		table, th, td {
		  border: 0.1px solid black;
			color: darkblue;
		}
	</style>
	<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
	<body>
		<table class="space-top-2">
			<tbody>	
				<?php $i = 0; ?>
				@while(isset($goods[$i]))
					<tr>
						@for($j = 0; $j < 4; $j++)
							@if(isset($goods[$i * 4 + $j]))
				            	<td style="width: 2.93in; height: 1.3in">
					            	<div style="text-align: center;">
						            	<span style="font-size: 15px;">{{ strtoupper($goods[$i * 4 + $j]['name']) }}</span><br>
						            	<span style="font-size: 13px;">per {{ strtoupper($goods[$i * 4 + $j]['unit']) }}</span><br><br>
						            	<span style="font-size: 19px;"><b>{{ showRupiah($goods[$i * 4 + $j]['price']) }}</b></span><br><br>
					            	</div>
					            	<div style="text-align: right; margin-right: 50px;">
						            	<span style="font-size: 10px;">ntnmart {{ date('d-m-Y') }}</span>
					            	</div>
					            </td>
				            @endif
						@endfor
						<?php $i++; ?>
					</tr>
					@if($i % 6 == 0 && $i > 0) 
						<tr style="opacity: 0; border: 0px !important;">
							@for($j = 0; $j < 4; $j++)
								<td style="border: 0px !important;">{{ $i }}</td>
							@endfor
						</tr>
					@endif
				@endwhile 
			</tbody>
		</table>
	</body>

	<script type="text/javascript">		
        $(document).ready (function (){
        	window.print();
        }); 

	    // window.setTimeout(function(){
     //  		window.location = window.location.origin + '/{{ $role }}/print-barcode/rack';
	    // }, 5000);
	</script>
</html>