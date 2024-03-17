<html>
    <link rel="stylesheet" href="{{asset('assets/bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Mulish:wght@600;900&display=swap" rel="stylesheet">

    <style type="text/css">
    	html
    	{
    		height: 100%;
    	}
		body {
                width: 1080px;
                /*height: 100%;*/
                height: 1920px;
                margin: 0;
                padding: 0;
                font-family: 'Mulish';
            }
    	.center {
		  display: block;
		  margin-left: auto;
		  margin-right: auto;
		  width: 50%;
		}

		.helper {
		    display: inline-block;
		    height: 100%;
		    vertical-align: middle;
		}
    </style>
	
	<body>
		<div class="wrapper">
			<div class="content-wrapper">
				<section class="content">
    				<div class="row">
						<div class="col-sm-12">
							<div class="col-sm-6" style="height: 148">
								<img src="{{asset('assets/images/logo-qr-kiri.png')}}">
							</div>
							<div class="col-sm-6" style=" height: 148;">
								<span class="helper"></span><img src="{{asset('assets/images/logo-qr-kanan.png')}}" style="vertical-align: middle; float: right; height: 50%;">
							</div>
						</div>
						<div class="col-sm-12" style="margin-top: 10%; width: 140%; margin-left: 15%">
							<img src="{{asset('assets/images/logo-qr-tengah.png')}}">
						</div>
						<div class="col-sm-12" style="margin-top: 10%; margin-left: 14%">
							<div>{!! QrCode::size(799)->generate($member->id); !!}</div>
						</div>
						<div class="col-sm-12" style="margin-top: 10%;">
							<div style="text-align: center;">
								<h1 style="font-weight: 900; padding: 3px; font-size: 100px">{{ $member->name }}</h1>
								<h5 style="font-weight: 600; font-size: 50px">{{ $member->address }}</h5>
							</div>
						</div>
						<div class="col-sm-12" style="margin-top: 20%">
							<div class="col-sm-3" style="height: 148">
								<img src="{{asset('assets/images/logo-qr-bawah.png')}}">
							</div>
							<div class="col-sm-7" style=" height: 100; background-color: #EE7214; text-align: center; border-radius: 20px; height: 150px">
								<h4 style="font-size: 40px;">Untuk Pemesanan Hubungi</h4><br>
								<h3 style="margin-top: -10px; font-weight: 600; font-size: 50px"><b>{{ config('app.phone_number') }}</h3>
							</div>
						</div>
    				</div>
    			</section>
    		</div>
    	</div>
	</body>


    <script src="{{asset('assets/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
</html>