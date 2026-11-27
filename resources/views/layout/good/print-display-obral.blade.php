<?php
	// Label rak OBRAL: harga normal dicoret, harga obral besar, hemat Rp & %.
	// Tata letak sama dengan print-display-rack (A4 mendatar, layout => [kolom, baris]).
	$layouts = [2 => [1, 2], 4 => [2, 2], 9 => [3, 3], 16 => [4, 4]];
	$layout  = (int) request('layout', 9);
	if (!isset($layouts[$layout])) $layout = 9;
	list($col, $rows) = $layouts[$layout];

	$marginTop    = 0.1;
	$marginBottom = 0;
	$marginSide   = 0.1;
	$slack        = 0.1;

	$tableW = 11.69 - 2 * $marginSide;
	$pageH  = 8.27 - $marginTop - $marginBottom - $slack;
	$pageMargin = number_format($marginTop, 3, '.', '') . 'in ' . number_format($marginSide, 3, '.', '') . 'in '
	            . number_format($marginBottom, 3, '.', '') . 'in ' . number_format($marginSide, 3, '.', '') . 'in';
	$tableWcss  = number_format($tableW, 3, '.', '') . 'in';
	$cellH = $pageH / $rows;
	$scale = $cellH / 2.67;
	$minName  = max(7, round(9 * $scale));
	$minPrice = max(9, round(12 * $scale));
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Cetak Label Obral</title>
	<style type="text/css">
		@page { size: A4 landscape; margin: {{ $pageMargin }}; }
		@media print { @page { size: A4 landscape; margin: {{ $pageMargin }}; } }

		:root {
			--navy: #0b1f4b;
			--red: #d90429;
			--yellow: #ffd60a;
			--pink: #be185d;
			--tint: #fff1f5;
		}

		* { box-sizing: border-box; }
		html, body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
		body { font-family: Arial, Helvetica, 'Segoe UI', sans-serif; color: var(--navy); }

		table { border-collapse: collapse; width: {{ $tableWcss }}; margin: 0 auto; table-layout: fixed; }
		tr { page-break-inside: avoid; break-inside: avoid; }
		table, th, td { border: 0.1px solid black; color: var(--navy); }
		td { padding: 0; vertical-align: top; background: #fff; }
		td.cell { height: var(--h); }
		td.empty { border: 0 !important; background: transparent; }

		/* Pita OBRAL (11%) */
		.ribbon {
			height: calc(var(--h) * 0.11);
			display: flex; align-items: center; justify-content: center; gap: .4em;
			background: var(--red); color: #fff !important;
			font-weight: 800; letter-spacing: .12em;
			font-size: max(9px, calc(17px * var(--s)));
		}

		/* Nama (27%) */
		.name-box {
			height: calc(var(--h) * 0.27);
			overflow: hidden; display: flex; align-items: center; justify-content: center;
			padding: calc(0.05in * var(--s)) calc(0.16in * var(--s));
			background: var(--tint);
			border-bottom: calc(2px * var(--s)) solid var(--red);
		}
		.name {
			max-height: 100%; width: 100%; overflow: hidden; text-align: center;
			font-weight: 700; line-height: 1.12; color: var(--navy); overflow-wrap: anywhere;
		}

		/* Harga (50%) */
		.price-box { height: calc(var(--h) * 0.50); overflow: hidden; display: flex; }
		.price-col {
			flex: 1 1 0; min-width: 0;
			display: flex; flex-direction: column; align-items: center; justify-content: center;
			gap: calc(0.03in * var(--s));
			padding: 0 calc(0.06in * var(--s));
		}
		.price-col + .price-col { border-left: 2px dashed #e5a0ab; }
		.unit {
			font-size: max(8px, calc(12px * var(--s)));
			font-weight: 700; line-height: 1; letter-spacing: 0.03em;
			color: var(--navy); background: var(--yellow);
			border-radius: 999px; padding: calc(3px * var(--s)) calc(12px * var(--s));
			white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis;
		}
		.normal {
			font-size: max(9px, calc(17px * var(--s)));
			font-weight: 700; color: #6b7280; white-space: nowrap;
			text-decoration: line-through; text-decoration-thickness: 2px; text-decoration-color: var(--red);
		}
		/* flex: none — tanpa ini kolom flex memampatkan harga, lalu fitText mengecilkannya terus */
		.unit, .normal, .amount, .save { flex: none; }
		.amount {
			width: 100%; text-align: center; white-space: nowrap; overflow: hidden;
			line-height: 1.15; font-weight: 800; color: var(--red);
		}
		.amount .cur { font-size: 0.42em; margin-right: 0.1em; vertical-align: 0.7em; color: var(--navy); }
		.save {
			max-width: 100%; text-align: center; line-height: 1.2;
			font-size: max(7px, calc(12px * var(--s)));
			font-weight: 800; color: #fff; background: var(--pink);
			border-radius: 4px; padding: calc(2px * var(--s)) calc(6px * var(--s));
		}

		/* Footer (12%) */
		.foot {
			height: calc(var(--h) * 0.12);
			overflow: hidden; display: flex; align-items: center; justify-content: space-between;
			padding: 0 calc(0.16in * var(--s));
			border-top: 1px solid #c9d3e6;
			font-size: max(8px, calc(11px * var(--s)));
			color: var(--navy);
		}
		.foot .limit { font-weight: 800; color: var(--red); letter-spacing: .04em; }
		.foot .brand { font-weight: 700; letter-spacing: 0.04em; color: var(--red); }
	</style>
</head>
<body>
	<table style="--h: {{ round($cellH, 4) }}in; --s: {{ round($scale, 4) }};">
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
							$n      = max(1, sizeof($prices));

							$nameLen = mb_strlen($item['name']);
							if     ($nameLen <= 12) $baseName = 30;
							elseif ($nameLen <= 20) $baseName = 25;
							elseif ($nameLen <= 32) $baseName = 20;
							elseif ($nameLen <= 48) $baseName = 17;
							elseif ($nameLen <= 70) $baseName = 14;
							else                    $baseName = 12;
							$nameSize = max($minName, round($baseName * $scale));

							$basePrice = $n == 1 ? 56 : ($n == 2 ? 40 : 30);
							$priceSize = max($minPrice, round($basePrice * $scale));
						?>
						<td class="cell">
							<div class="ribbon">🏷️ OBRAL</div>

							<div class="name-box">
								<div class="name fit" data-min="{{ $minName }}" style="font-size: {{ $nameSize }}px;">{{ mb_strtoupper($item['name']) }}</div>
							</div>

							<div class="price-box">
								@foreach($prices as $price)
									<div class="price-col">
										<span class="unit">per {{ mb_strtoupper($price['unit']) }}</span>
										@if($price['normal'])
											<span class="normal">Rp{{ formatNumber($price['normal']) }}</span>
										@endif
										<div class="amount fit" data-axis="x" data-min="{{ $minPrice }}" style="font-size: {{ $priceSize }}px;"><span class="cur">Rp</span>{{ formatNumber($price['price']) }}</div>
										@if($price['normal'])
											<span class="save">HEMAT Rp{{ formatNumber($price['normal'] - $price['price']) }} <span style="white-space:nowrap">(-{{ round(($price['normal'] - $price['price']) / $price['normal'] * 100) }}%)</span></span>
										@endif
									</div>
								@endforeach
							</div>

							<div class="foot">
								<span class="limit">SELAMA STOK ADA</span>
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

	@if(empty($goods))
		<p style="font-family:Arial;padding:2rem">Tidak ada barang untuk dicetak.</p>
	@endif

	<script type="text/javascript">
		// Perkecil font sampai teks muat di kotaknya. Harga (data-axis="x") hanya
		// dicek lebarnya: tulisan "Rp" kecil yang dinaikkan selalu membuatnya
		// sedikit lebih tinggi dari kotaknya, jadi tanpa ini harga mengecil terus.
		function fitText(el) {
			var size = parseFloat(window.getComputedStyle(el).fontSize);
			var min  = parseFloat(el.getAttribute('data-min')) || 10;
			var xOnly = el.getAttribute('data-axis') === 'x';
			while ((el.scrollWidth > el.clientWidth + 0.5 || (!xOnly && el.scrollHeight > el.clientHeight + 0.5)) && size > min) {
				size -= 0.5;
				el.style.fontSize = size + 'px';
			}
		}

		window.addEventListener('load', function () {
			var els = document.querySelectorAll('.fit');
			for (var k = 0; k < els.length; k++) { fitText(els[k]); }
			@if(!empty($goods)) window.print(); @endif
		});
	</script>
</body>
</html>
