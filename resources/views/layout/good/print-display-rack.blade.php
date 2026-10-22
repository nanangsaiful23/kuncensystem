<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Cetak Label Harga Rak</title>
	<style type="text/css">
		@page
		{
			size: A4 landscape;
			margin: 0.1in;
		}

		@media print
		{
			@page
			{
				size: A4 landscape;
				margin: 0.1in;
			}
		}

		:root {
			--navy: #0b1f4b;    /* teks nama & footer */
			--red: #d90429;     /* harga & aksen */
			--yellow: #ffd60a;  /* label satuan */
			--tint: #fff7cc;    /* kuning sangat muda untuk area nama (hemat tinta) */
		}

		* { box-sizing: border-box; }

		html, body {
			margin: 0;
			padding: 0;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}

		body {
			font-family: Arial, Helvetica, 'Segoe UI', sans-serif;
			color: var(--navy);
		}

		/* ===== Tabel (layout tidak diubah) ===== */
		/* Area cetak A4 landscape: 11.69in x 8.27in dikurangi margin 0.1in => 11.49in x 8.07in.
		   Tabel selebar area cetak; 3 baris per halaman (tiap baris 2.67in). */
		table {
			border-collapse: collapse;
			width: 11.49in;
			table-layout: fixed;   /* semua kolom sama lebar, tidak terpengaruh isi sel */
		}

		tr {
			page-break-inside: avoid;
			break-inside: avoid;
		}

		table, th, td {
			border: 0.1px solid black;
			color: var(--navy);
		}

		td {
			padding: 0;
			vertical-align: top;
			background: #fff;
		}

		/* Tinggi tiap label dibuat tetap supaya semua sel seragam (1 sel = 1 barang, berapa pun jumlah harganya) */
		.cell { overflow: hidden; }
		.cols-3 { width: 33.333%; }
		.cols-2 { width: 50%; height: 3.95in; }
		.cols-2 .name-box  { height: 1.5in; }
		.cols-2 .price-box { height: 2.15in; }

		/* ===== Nama barang ===== */
		.name-box {
			height: 1.2in;
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 0.08in 0.18in;
			background: var(--tint);
			border-top: 8px solid var(--red);
			border-bottom: 2px solid var(--red);
		}

		.name {
			max-height: 100%;
			width: 100%;
			overflow: hidden;
			text-align: center;
			font-weight: 700;
			line-height: 1.12;
			color: var(--navy);
			overflow-wrap: anywhere;
		}

		/* ===== Harga ===== */
		.price-box {
			height: 1.17in;
			overflow: hidden;
			display: flex;
		}

		.price-col {
			flex: 1 1 0;
			min-width: 0;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 0.06in;
			padding: 0 0.08in;
		}

		.price-col + .price-col {
			border-left: 2px dashed #e5a0ab;
		}

		.unit {
			font-size: 14px;
			font-weight: 700;
			line-height: 1;
			letter-spacing: 0.03em;
			color: var(--navy);
			background: var(--yellow);
			border-radius: 999px;
			padding: 4px 14px;
			white-space: nowrap;
			max-width: 100%;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.amount {
			width: 100%;
			text-align: center;
			white-space: nowrap;
			overflow: hidden;
			line-height: 1.1;
			font-weight: 700;
			color: var(--red);
		}

		.amount .cur {
			font-size: 0.45em;
			margin-right: 0.12em;
			vertical-align: 0.8em;
			color: var(--navy);
		}

		/* ===== Footer ===== */
		.foot {
			height: 0.3in;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0 0.18in;
			border-top: 1px solid #c9d3e6;
			font-size: 11px;
			color: var(--navy);
		}

		.foot .brand {
			font-weight: 700;
			letter-spacing: 0.04em;
			color: var(--red);
		}
	</style>
</head>
<body>
<?php
		$total  = sizeof($goods);
		$col    = 3;   // default 3 kolom
		$fontSz = 0;
		if($total % 9 == 0)
		{
			$col = 3;
			$fontSz = 0;
		}
		elseif($total % 4 == 0)
		{
			$col = 2;
			$fontSz = 20;
		}
	?>
	<table class="space-top-2">
		<colgroup>
			@for($c = 0; $c < $col; $c++)
				<col style="width: {{ 100 / $col }}%;">
			@endfor
		</colgroup>
		<tbody>
			<?php $i = 0; ?>
			@while(isset($goods[$i * $col]))
			<tr>
				@for($j = 0; $j < $col; $j++)
					@if(isset($goods[$i * $col + $j]))
						<?php
							$item   = $goods[$i * $col + $j];
							$prices = $item['price'];
							$n      = sizeof($prices);

							// Ukuran awal nama berdasarkan panjang teks (disempurnakan lagi oleh JS agar pas di kotak)
							$nameLen = mb_strlen($item['name']);
							if     ($nameLen <= 12) $nameSize = 36;
							elseif ($nameLen <= 20) $nameSize = 29;
							elseif ($nameLen <= 32) $nameSize = 23;
							elseif ($nameLen <= 48) $nameSize = 19;
							elseif ($nameLen <= 70) $nameSize = 16;
							else                    $nameSize = 13;
							$nameSize += $fontSz;

							// Ukuran awal harga berdasarkan jumlah satuan harga
							$priceSize = $n == 1 ? 54 : ($n == 2 ? 34 : 24);
							$priceSize += $fontSz + 15;
						?>
						<td class="cell cols-{{ $col }}">
							<div class="name-box">
								<div class="name fit" data-min="10" style="font-size: {{ $nameSize }}px;">{{ mb_strtoupper($item['name']) }}</div>
							</div>

							<div class="price-box">
								@foreach($prices as $price)
									<div class="price-col">
										<span class="unit">per {{ mb_strtoupper($price['unit']) }}</span>
										<div class="amount fit" data-min="12" style="font-size: {{ $priceSize }}px;"><span class="cur">Rp</span>{{ formatNumber($price['price']) }}</div>
									</div>
								@endforeach
							</div>

							<div class="foot">
								<span class="brand">ntnmart</span>
								<span>{{ date('d-m-Y') }}</span>
							</div>
						</td>
					@endif
				@endfor
			</tr>
			<?php $i++; ?>
			@if($i % 6 == 0 && $i > 0)
				<tr style="opacity: 0; border: 0px !important; height: 0; line-height: 0; font-size: 0;">
					@for($j = 0; $j < $col; $j++)
						<td style="border: 0px !important; padding: 0; height: 0; line-height: 0; font-size: 0;">{{ $i }}</td>
					@endfor
				</tr>
			@endif
			@endwhile
		</tbody>
	</table>

	<script type="text/javascript">
		// Perkecil font sampai teks muat di kotaknya (nama: tidak terpotong, harga: tidak melebar)
		function fitText(el) {
			var size = parseFloat(window.getComputedStyle(el).fontSize);
			var min  = parseFloat(el.getAttribute('data-min')) || 10;
			while ((el.scrollWidth > el.clientWidth + 0.5 || el.scrollHeight > el.clientHeight + 0.5) && size > min) {
				size -= 0.5;
				el.style.fontSize = size + 'px';
			}
		}

		window.addEventListener('load', function () {
			var els = document.querySelectorAll('.fit');
			for (var k = 0; k < els.length; k++) { fitText(els[k]); }
			window.print();
		});

		// window.setTimeout(function(){
		//	window.location = window.location.origin + '/{{ $role }}/print-barcode/rack';
		// }, 5000);
	</script>
</body>
</html>