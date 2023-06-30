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
			{{ displayDateTime($journal->created_at) }}<br>
		</div>
		<div style="text-align: center;">
			{{ $journal->name }}<br>
			{{ showRupiah($journal->debit) }}
			<hr>
			Terima kasih<br>
			Keberkahan di setiap transaksi
		</div>
	</body>

	<script type="text/javascript">		
        $(document).ready (function (){
        	window.print();
        }); 

	    window.setTimeout(function(){
      		window.location = window.location.origin + '/{{ $role }}/other-transaction/create';
	    }, 5000);
	</script>
</html>