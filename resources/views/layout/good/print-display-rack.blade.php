<?php
	// Pilihan jumlah label per halaman A4 landscape (dikirim dari halaman pilih barang: name="layout")
	// layout => [kolom, baris]
	$layouts = [2 => [1, 2], 4 => [2, 2], 9 => [3, 3], 16 => [4, 4], 25 => [5, 5]];
	$layout  = (int) request('layout', 9);
	if (!isset($layouts[$layout])) $layout = 9;
	list($col, $rows) = $layouts[$layout];

	// ===== PENGATURAN MARGIN (satuan inci) — ubah angka di sini bila posisi cetak perlu digeser =====
	$marginTop    = 0.1;    // jarak tepi atas kertas ke tabel (naikkan bila terlalu mepet atas)
	$marginBottom = 0;    // jarak tepi bawah kertas
	$marginSide   = 0.1;    // jarak tepi kiri & kanan
	$slack        = 0.1;    // cadangan tinggi agar printer tidak memecah jadi 2 halaman
	                        // (sisa ini ada di bawah tabel, jadi jarak bawah efektif = $marginBottom + $slack)

	// A4 landscape: 11.69in x 8.27in
	$tableW = 11.69 - 2 * $marginSide;
	$pageH  = 8.27 - $marginTop - $marginBottom - $slack;     // tinggi total tabel
	$pageMargin = number_format($marginTop, 3, '.', '') . 'in ' . number_format($marginSide, 3, '.', '') . 'in '
	            . number_format($marginBottom, 3, '.', '') . 'in ' . number_format($marginSide, 3, '.', '') . 'in';
	$tableWcss  = number_format($tableW, 3, '.', '') . 'in';
	$cellH = $pageH / $rows;       // tinggi 1 label (inci)
	$scale = $cellH / 2.67;        // 1.0 = ukuran label 3x3 (acuan desain); semua font/jarak mengikuti skala ini
	$minName  = max(7, round(10 * $scale));
	$minPrice = max(9, round(12 * $scale));
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Cetak Label Harga Rak</title>
	<style type="text/css">
		@page
		{
			size: A4 landscape;
			margin: {{ $pageMargin }};
		}

		@media print
		{
			@page
			{
				size: A4 landscape;
				margin: {{ $pageMargin }};
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

		/* ===== Tabel =====
		   --h : tinggi 1 label, --s : skala font/jarak (diisi dari PHP pada elemen <table>) */
		table {
			border-collapse: collapse;
			width: {{ $tableWcss }};
			margin: 0 auto;        /* di tengah secara horizontal */
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

		td.cell { height: var(--h); }
		td.empty { border: 0 !important; background: transparent; }

		/* ===== Nama barang (42% tinggi label) ===== */
		.name-box {
			height: calc(var(--h) * 0.42);
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: calc(0.08in * var(--s)) calc(0.18in * var(--s));
			background: var(--tint);
			border-top: calc(8px * var(--s)) solid var(--red);
			border-bottom: calc(2px * var(--s)) solid var(--red);
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

		/* ===== Harga (46% tinggi label) ===== */
		.price-box {
			height: calc(var(--h) * 0.46);
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
			gap: calc(0.06in * var(--s));
			padding: 0 calc(0.08in * var(--s));
		}

		.price-col + .price-col {
			border-left: 2px dashed #e5a0ab;
		}

		.unit {
			font-size: max(8px, calc(14px * var(--s)));
			font-weight: 700;
			line-height: 1;
			letter-spacing: 0.03em;
			color: var(--navy);
			background: var(--yellow);
			border-radius: 999px;
			padding: calc(4px * var(--s)) calc(14px * var(--s));
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

		/* ===== Footer (12% tinggi label) ===== */
		.foot {
			height: calc(var(--h) * 0.12);
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0 calc(0.18in * var(--s));
			border-top: 1px solid #c9d3e6;
			font-size: max(8px, calc(11px * var(--s)));
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
	<table class="space-top-2" style="--h: {{ round($cellH, 4) }}in; --s: {{ round($scale, 4) }};">
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
							if     ($nameLen <= 12) $baseName = 36;
							elseif ($nameLen <= 20) $baseName = 29;
							elseif ($nameLen <= 32) $baseName = 23;
							elseif ($nameLen <= 48) $baseName = 19;
							elseif ($nameLen <= 70) $baseName = 16;
							else                    $baseName = 13;
							$nameSize = max($minName, round($baseName * $scale));

							// Ukuran awal harga berdasarkan jumlah satuan harga (1 barang tetap 1 kolom)
							$basePrice = $n == 1 ? 69 : ($n == 2 ? 49 : 39);
							$priceSize = max($minPrice, round($basePrice * $scale));
						?>
						<td class="cell">
							<div class="name-box">
								<div class="name fit" data-min="{{ $minName }}" style="font-size: {{ $nameSize }}px;">{{ mb_strtoupper($item['name']) }}</div>
							</div>

							<div class="price-box">
								@foreach($prices as $price)
									<div class="price-col">
										<span class="unit">per {{ mb_strtoupper($price['unit']) }}</span>
										<div class="amount fit" data-min="{{ $minPrice }}" style="font-size: {{ $priceSize }}px;"><span class="cur">Rp</span>{{ formatNumber($price['price']) }}</div>
									</div>
								@endforeach
							</div>

							<div class="foot">
								<span class="brand">ntnmart</span>
								<span>{{ date('d-m-Y') }}</span>
							</div>
						</td>
					@else
						<td class="cell empty"></td>
					@endif
				@endfor
			</tr>
			<?php $i++; ?>
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