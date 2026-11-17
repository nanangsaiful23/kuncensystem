<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Member {{ $member->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@600;900&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 16px; background: #eee; font-family: 'Mulish', sans-serif; text-align: center; }
        #preview { width: 100%; max-width: 360px; aspect-ratio: 9 / 16; box-shadow: 0 2px 12px rgba(0,0,0,.25); border-radius: 12px; background: #fff; }
        .btn-download { display: inline-block; margin: 16px 0 8px; padding: 12px 28px; background: #EE7214; color: #fff; border: 0; border-radius: 8px; font-size: 16px; font-weight: 900; cursor: pointer; text-decoration: none; }
        .hint { color: #666; font-size: 13px; }
        canvas { display: none; }
    </style>
</head>
<body>
    <img id="preview" alt="QR Code {{ $member->name }}">
    <br>
    <a id="download" class="btn-download" href="#" download="{{ $file_name }}">Download QR (Ukuran HP)</a>
    <div class="hint">Jika unduhan tidak berjalan, tekan lama gambar lalu pilih "Simpan gambar".</div>

    <canvas id="card" width="1080" height="1920"></canvas>

    <script>
    (function () {
        var data = {
            qr: @json($qr_rows),
            name: @json((string) $member->name),
            address: @json((string) $member->address),
            phone: @json((string) config('app.phone_number')),
            fileName: @json($file_name),
            images: {
                kiri: @json(asset('assets/images/logo-qr-kiri.png')),
                kanan: @json(asset('assets/images/logo-qr-kanan.png')),
                tengah: @json(asset('assets/images/logo-qr-tengah.png')),
                bawah: @json(asset('assets/images/logo-qr-bawah.png'))
            }
        };

        var W = 1080, H = 1920, ORANGE = '#EE7214';
        var canvas = document.getElementById('card');
        var ctx = canvas.getContext('2d');

        function loadImage(src) {
            return new Promise(function (resolve) {
                var img = new Image();
                img.onload = function () { resolve(img); };
                img.onerror = function () { resolve(null); };
                img.src = src;
            });
        }

        function roundRect(x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        }

        // Kecilkan font sampai teks muat di lebar maksimal.
        function fitFont(text, weight, size, minSize, maxWidth) {
            do {
                ctx.font = weight + ' ' + size + 'px Mulish, sans-serif';
                if (ctx.measureText(text).width <= maxWidth) break;
                size -= 2;
            } while (size > minSize);
            return size;
        }

        // Pecah teks menjadi maksimal maxLines baris.
        function wrapLines(text, maxWidth, maxLines) {
            var words = text.split(/\s+/), lines = [], line = '';
            for (var i = 0; i < words.length; i++) {
                var test = line ? line + ' ' + words[i] : words[i];
                if (ctx.measureText(test).width > maxWidth && line) {
                    lines.push(line);
                    line = words[i];
                } else {
                    line = test;
                }
            }
            if (line) lines.push(line);
            if (lines.length > maxLines) {
                lines = lines.slice(0, maxLines);
                var last = lines[maxLines - 1];
                while (last.length && ctx.measureText(last + '…').width > maxWidth) last = last.slice(0, -1);
                lines[maxLines - 1] = last + '…';
            }
            return lines;
        }

        function drawImageFit(img, x, y, maxW, maxH, align) {
            if (!img) return;
            var s = Math.min(maxW / img.width, maxH / img.height);
            var w = img.width * s, h = img.height * s;
            var dx = align === 'right' ? x + maxW - w : align === 'center' ? x + (maxW - w) / 2 : x;
            ctx.drawImage(img, dx, y + (maxH - h) / 2, w, h);
        }

        function drawQr(x, y, size) {
            var n = data.qr.length, quiet = 3, cell = size / (n + quiet * 2);
            ctx.fillStyle = '#fff';
            ctx.fillRect(x, y, size, size);
            ctx.fillStyle = '#000';
            for (var r = 0; r < n; r++) {
                var row = data.qr[r];
                for (var c = 0; c < n; c++) {
                    if (row.charAt(c) === '1') {
                        // +0.5px menutup celah anti-aliasing antar modul
                        ctx.fillRect(x + (c + quiet) * cell, y + (r + quiet) * cell, cell + 0.5, cell + 0.5);
                    }
                }
            }
        }

        function draw(img) {
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, W, H);

            // Header
            drawImageFit(img.kiri, 60, 60, 262, 148, 'left');
            drawImageFit(img.kanan, W - 60 - 341, 60, 341, 148, 'right');
            drawImageFit(img.tengah, 60, 260, W - 120, 150, 'center');

            // QR code dengan bingkai oranye
            var qrSize = 780, qrX = (W - qrSize) / 2, qrY = 470;
            roundRect(qrX - 14, qrY - 14, qrSize + 28, qrSize + 28, 28);
            ctx.fillStyle = ORANGE;
            ctx.fill();
            drawQr(qrX, qrY, qrSize);

            // Nama & alamat
            ctx.fillStyle = '#000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'alphabetic';
            fitFont(data.name, '900', 96, 48, W - 120);
            ctx.fillText(data.name, W / 2, 1380);

            if (data.address) {
                ctx.font = '600 46px Mulish, sans-serif';
                ctx.fillStyle = '#333';
                wrapLines(data.address, W - 160, 2).forEach(function (line, i) {
                    ctx.fillText(line, W / 2, 1460 + i * 58);
                });
            }

            // Footer kontak
            var boxX = 220, boxY = 1640, boxW = W - 60 - boxX, boxH = 180;
            drawImageFit(img.bawah, 60, boxY + 10, 140, boxH - 20, 'center');
            roundRect(boxX, boxY, boxW, boxH, 24);
            ctx.fillStyle = ORANGE;
            ctx.fill();
            ctx.fillStyle = '#000';
            ctx.font = '600 40px Mulish, sans-serif';
            ctx.fillText('Untuk Pemesanan Hubungi', boxX + boxW / 2, boxY + 70);
            fitFont(data.phone, '900', 60, 36, boxW - 40);
            ctx.fillText(data.phone, boxX + boxW / 2, boxY + 145);
        }

        var fontsReady = document.fonts && document.fonts.load
            ? Promise.all([
                document.fonts.load('900 96px Mulish'),
                document.fonts.load('600 46px Mulish')
              ]).catch(function () {})
            : Promise.resolve();

        Promise.all([
            fontsReady,
            loadImage(data.images.kiri),
            loadImage(data.images.kanan),
            loadImage(data.images.tengah),
            loadImage(data.images.bawah)
        ]).then(function (res) {
            draw({ kiri: res[1], kanan: res[2], tengah: res[3], bawah: res[4] });

            var link = document.getElementById('download');
            canvas.toBlob(function (blob) {
                var url = URL.createObjectURL(blob);
                document.getElementById('preview').src = url;
                link.href = url;
                link.download = data.fileName;
                link.click(); // unduh otomatis
            }, 'image/png');
        });
    })();
    </script>
</body>
</html>
