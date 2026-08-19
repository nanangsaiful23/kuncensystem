<html>
	<style type="text/css">
		@page 
		{ 
			size: 8.27in 11.69in;  
			margin: 0.1in; 
		}

		@media print
		{
			@page 
			{ 
				size: 8.27in 11.69in;  
				margin: 0.1in; 
			}

		  table, th, td  {
		    font-size: 14pt; /* Standard readable print size */
		    -webkit-print-color-adjust: exact !important; /* For Chrome, Safari, and Edge */
    		print-color-adjust: exact !important;   
		  }
		}

		table {
			border-collapse: collapse;
		}

		table, th, td {
			padding-left: 10px;
		}
	</style>
	<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
	<body>
		<table class="space-top-2">
			<tbody>	
				@for($i = 0; $i < sizeof($goods); $i++)
					<tr @if($i % 2 == 0) style="background-color: rgba(255, 255, 0, 0.1);" @endif>
						<td width="80%"><h1>{{ $goods[$i]['name']}}</h1></td>
						<td style="text-align: right;"><h1>Rp{{ printRupiah($goods[$i]['price']) }}</h1></td>
					</tr>
				@endfor
			</tbody>
		</table>
    	<div style="text-align: right; margin-right: 50px;">
        	<span style="font-size: 10px;">ntnmart {{ date('d-m-Y') }}</span>
    	</div>
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