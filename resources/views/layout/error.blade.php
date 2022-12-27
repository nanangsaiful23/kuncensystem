@if($errors->any())
	<section class="content" style="margin-bottom: -140px;">
    	<div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
					<div class="alert alert-danger" style="font-size: 15px; margin-bottom: -100px;">
				        <ul>
				            @foreach ($errors->all() as $error)
				                {{ $error }}<br>
				            @endforeach
				        </ul>
				    </div>
                </div>
            </div>
        </div>
    </section>
@endif