<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Cetak Daftar Harga</title>
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
		}

		:root {
			--navy: #0b1f4b;    /* teks utama */
			--red: #d90429;     /* harga & aksen */
			--yellow: #ffd60a;  /* penanda grosir */
			--tint: #fffbe0;    /* kuning sangat muda untuk baris selang-seling (hemat tinta) */
			--line: #d5dcea;    /* garis pemisah baris */
		}

		* { box-sizing: border-box; }

		html, body {
			margin: 0;
			padding: 0;
			-webkit-print-color-adjust: exact !important;
			print-color-adjust: exact !important;
		}

		body {
			font-family: Arial, Helvetica, 'Segoe UI', sans-serif;
			color: var(--navy);
		}

		table {
			border-collapse: collapse;
			width: 100%;
		}

		/* Header tabel (berulang di tiap halaman) */
		thead td {
			padding: 6px 10px 8px;
			border-bottom: 3px solid var(--red);
		}

		.head {
			display: flex;
			align-items: baseline;
			justify-content: space-between;
		}

		.head .title {
			font-size: 30px;
			font-weight: 700;
			letter-spacing: 0.06em;
			color: var(--navy);
		}

		.head .brand {
			font-size: 16px;
			font-weight: 700;
			color: var(--red);
		}

		.head .brand span {
			font-weight: 400;
			color: var(--navy);
			margin-left: 8px;
		}

		/* Baris barang */
		tbody tr {
			page-break-inside: avoid;
			break-inside: avoid;
			border-bottom: 1px solid var(--line);
		}

		tbody tr.alt {
			background: var(--tint);
		}

		td {
			padding: 10px 12px;
			vertical-align: middle;
		}

		td.col-name {
			width: 58%;
			border-left: 5px solid transparent;
		}

		tr.multi td.col-name {
			border-left-color: var(--red);
		}

		td.col-price {
			width: 42%;
			text-align: right;
		}

		.name {
			font-weight: 700;
			line-height: 1.15;
			max-height: 2.35em;       /* maksimal 2 baris; font dikecilkan otomatis agar nama tetap utuh */
			overflow: hidden;
			overflow-wrap: anywhere;
			color: var(--navy);
		}

		.note {
			display: inline-block;
			margin-top: 4px;
			font-size: 14px;
			font-weight: 700;
			letter-spacing: 0.03em;
			color: var(--red);
		}

		/* Harga */
		.p {
			white-space: nowrap;
			line-height: 1.25;
		}

		.p + .p {
			margin-top: 2px;
		}

		.p .rp {
			font-size: 32px;
			font-weight: 700;
			color: var(--red);
		}

		.p .unit {
			font-size: 22px;
			font-weight: 700;
			color: var(--navy);
			margin-left: 2px;
		}

		.p .tag {
			display: inline-block;
			margin-right: 6px;
			padding: 3px 9px;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.04em;
			vertical-align: 5px;
			color: var(--navy);
			background: var(--yellow);
			border-radius: 999px;
		}

		.foot {
			margin-top: 8px;
			padding-right: 10px;
			text-align: right;
			font-size: 12px;
			color: var(--navy);
		}
	</style>
</head>
<body>
	<table class="space-top-2">
		<thead>
			<tr>
				<td colspan="2">
					<div class="head">
						<span class="title">DAFTAR HARGA</span>
						<span class="brand">ntnmart<span>{{ date('d-m-Y') }}</span></span>
					</div>
				</td>
			</tr>
		</thead>
		<tbody>
			@for($i = 0; $i < sizeof($goods); $i++)
				<?php
					$prices = $goods[$i]['price'];
					$multi  = sizeof($prices) > 1;

					// Ukuran awal nama berdasarkan panjang teks (disempurnakan lagi oleh JS agar pas di kotak)
					$nameLen = mb_strlen($goods[$i]['name']);
					if     ($nameLen <= 22) $nameSize = 32;
					elseif ($nameLen <= 34) $nameSize = 27;
					elseif ($nameLen <= 50) $nameSize = 23;
					elseif ($nameLen <= 70) $nameSize = 19;
					else                    $nameSize = 16;
				?>
				<tr class="{{ $i % 2 == 0 ? 'alt' : '' }} {{ $multi ? 'multi' : '' }}">
					<td class="col-name">
						<div class="name fit" data-min="13" style="font-size: {{ $nameSize }}px;">{{ $goods[$i]['name'] }}</div>
						@if($multi)
							<span class="note">&#9660; BELI BANYAK LEBIH MURAH</span>
						@endif
					</td>
					<td class="col-price">
						@foreach($prices as $price)
							<div class="p">
								@if($multi && !$loop->first)
									<span class="tag">GROSIR</span>
								@endif
								<span class="rp">Rp{{ formatNumber($price['price']) }}</span><span class="unit">/{{ $price['unit'] }}</span>
							</div>
						@endforeach
					</td>
				</tr>
			@endfor
		</tbody>
	</table>
	<div class="foot">ntnmart {{ date('d-m-Y') }}</div>

	<script type="text/javascript">
		// Perkecil font nama sampai muat di kotaknya (maks. 2 baris) agar nama tidak terpotong
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