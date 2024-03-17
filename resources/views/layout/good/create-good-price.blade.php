<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> {{ $default['page_name'] . ' ' . $good->name }}</h3>
          </div>

          {!! Form::model(old(),array('url' => route($role . '.good.store-price', $good->id), 'enctype'=>'multipart/form-data', 'method' => 'POST', 'class' => 'form-horizontal')) !!}
            <div class="box-body">
              <div class="panel-body" style="color: black !important;">
                <div class="row">
                        <div class="form-group">
                            {!! Form::label('unit_id', 'Satuan', array('class' => 'col-sm-12')) !!}
                            <div class="col-sm-5">
                              {!! Form::select('unit_id', getUnits(), null, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'unit_id']) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('buy_price', 'Harga Beli Baru', array('class' => 'col-sm-12')) !!}
                            <div class="col-sm-5">
                                <input type="text" name="buy_price" class="form-control" id="buy_price" required="required" onkeyup="formatNumber('buy_price')">
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('selling_price', 'Harga Jual Baru', array('class' => 'col-sm-12')) !!}
                            <div class="col-sm-5">
                                <input type="text" name="selling_price" class="form-control" id="selling_price" required="required" onkeyup="formatNumber('selling_price')">
                            </div>
                        </div>

                        <div class="form-group">
                            {!! Form::label('reason', 'Alasan', array('class' => 'col-sm-12')) !!}
                            <div class="col-sm-5">
                                {!! Form::text('reason', null, array('class' => 'form-control')) !!}
                            </div>
                        </div>

                        <div class="form-group">
                            {{ csrf_field() }}

                            <div class="col-sm-5">
                                <hr>
                                {!! Form::submit('Tambah', ['class' => 'btn form-control'])  !!}
                            </div>
                        </div>
                </div>
              </div>

              @section('js-addon')
                  <script type="text/javascript">
                      $(document).ready (function (){
                          $('.select2').select2();
                      });

                      function formatNumber(name)
                      {
                          num = document.getElementById(name).value;
                          num = num.toString().replace(/,/g,'');
                          document.getElementById(name).value = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
                      }

                      function unFormatNumber(num)
                      {
                          return num.replace(/,/g,'');
                      }
                  </script>
              @endsection
            </div>
          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </section>
</div>