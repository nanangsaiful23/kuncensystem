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
					@for($j = 0; $j < 3; $j++)
						<!-- <tr> -->
							@if(isset($goods[$i * 3 + $j]))
					            	<td style="width: 3.74in;" colspan="{{ sizeof($goods[$i * 3 + $j]['price']) }}">
						            	<div style="display: flex; text-align: center; align-items: center; height: 1.42in;">
							            	<span style="font-size: 18px; display: inline-block; margin: 0 auto;">{{ strtoupper($goods[$i * 3 + $j]['name']) }}</span><br>
							            </div>
							            <div style="width: 3.74in; height: 1in; display: flex;">
						            		@foreach($goods[$i * 3 + $j]['price'] as $price)
						            			<?php $w = 3.74/sizeof($goods[$i * 3 + $j]['price']); ?>
								            	<div style="text-align: center; width: {{ $w }}in;">
									            	<span style="font-size: 13px;">per {{ strtoupper($price['unit']) }}</span><br>
									            	<span style="font-size: 19px;"><b>{{ showRupiah($price['price']) }}</b></span>
						            			</div>
						            		@endforeach
							            </div>
						            	<div style="text-align: right; margin-right: 50px; height: 0.3in;">
							            	<span style="font-size: 10px;">ntnmart {{ date('d-m-Y') }}</span>
						            	</div>
							        </td>
				            @endif
				        <!-- </tr> -->
					@endfor
						        </tr>
					<?php $i++; ?>
					@if($i % 6 == 0 && $i > 0) 
						<tr style="opacity: 0; border: 0px !important;">
							@for($j = 0; $j < 3; $j++)
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