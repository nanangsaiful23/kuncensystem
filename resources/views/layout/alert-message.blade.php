@if($type != null)
	@if($type == 'error')
		<div class="alert alert-{{ $color }} alert-dismissible" id="message">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<h4><i class="icon fa fa-warning"></i> {{ $data }}</h4>
		</div>
	@else
		<div class="alert alert-{{ $color }} alert-dismissible" id="message">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<h4><i class="icon fa fa-warning"></i> {{ $data }} berhasil di {{ $type }}</h4>
		</div>
	@endif
@endif