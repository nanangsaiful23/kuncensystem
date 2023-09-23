<style type="text/css">
  .select2-container--default .select2-selection--multiple .select2-selection__choice
  {
    background-color: rgb(60, 141, 188) !important;
  }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
            <h5 id="total"></h5>
          </div>
          <div class="box-body" style="overflow-x:scroll">
            {!! Form::model(old(),array('url' => route($role . '.print-display'), 'method' => 'POST')) !!}
              <div class="form-group col-sm-12">
                <input type="radio" name="type" value="rack" required="required">
                <label for="html">Display Rak</label><br>
                <input type="radio" name="type" value="list" required="required">
                <label for="css">Display List</label><br>
              </div>
              <div class="form-group">
                <select class="form-control select2" data-placeholder="Silahkan pilih barang" style="width: 100%;" onchange="changeDiv()" id="good-list">
                  <div>
                    <option value="">Silahkan pilih barang</option>
                    @foreach(getGoodUnits() as $good)
                      <option value="{{ $good->good_unit_id . ';;;' . $good->good_name . ' ' . $good->unit->name }}">{{ $good->good_name . ' ' . $good->unit->name }}</option>
                    @endforeach
                  </div>
                </select>
              </div>
              <?php $i = 1; ?>
              <div id="div-result"></div>
              <div id="row-data-{{ $i }}">
                <div class="form-group col-sm-3">
                  <input type="text" id="id-{{ $i }}" name="ids[]" class="form-control" placeholder="id">
                </div>
                <div class="form-group col-sm-6">
                  <input type="text" id="name-{{ $i }}" name="names[]" class="form-control" placeholder="nama">
                </div>
                <div class="form-group col-sm-2">
                  <input type="text" id="quantity-{{ $i }}" name="quantities[]" class="form-control" onchange="addElement('{{ $i }}')" placeholder="jumlah">
                </div>
                <div class="form-group col-sm-1">
                  <i class="fa fa-times" onclick="deleteItem('{{ $i }}')" style="color: red"></i>
                </div>
              </div>
              {!! Form::submit('Print', ['class' => 'btn btn-primary btn-flat btn-block form-control'])  !!}
            {!! Form::close() !!}
          </div> 
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    var total_item = 1;

    $(document).ready(function(){
      $('.select2').select2();

    });

    function changeDiv()
    {
      var good = $("#good-list").val().split(";;;");
      $("#id-" + total_item).val(good[0]);
      $("#name-" + total_item).val(good[1]);
      
      if($("#quantity-" + total_item).val() != null)
        total_item += 1;
    }

    function addElement(index)
    {
      console.log($("#quantity-" + total_item).val());
      if($("#quantity-" + total_item).val() == null)
      {
        index = parseInt(index) + 1;
        index = index.toString();
        htmlResult = '<div id="row-data-' + index + '"><div class="form-group col-sm-3"><input type="text" name="ids[]" class="form-control" id="id-' + index + '"></div><div class="form-group col-sm-6"><input type="text" name="names[]" class="form-control" id="name-' + index + '"></div><div class="form-group col-sm-2"><input type="text" name="quantities[]" class="form-control" onchange="addElement(' + index + ')" id="quantity-' + index+ '"></div><div class="form-group col-sm-1"><i class="fa fa-times" onclick="deleteItem(\'' + index + '\')" style="color: red"></i></div></div>';

        $("#div-result").prepend(htmlResult);
      }

      total_quantity = 0;
      for (var i = 1; i < total_item; i++) 
      {
        if($("#quantity-" + i).val() != null)
          total_quantity += parseInt($("#quantity-" + i).val());
      }
      $("#total").html("Jumlah barang yang akan diprint " + total_quantity + " <br>(max 24 untuk print rak & max 17 untuk print list dalam satu halaman)");
    }

    function deleteItem(index)
    {
      console.log('masuk hapus');
      $("#row-data-" + index).remove();

      total_quantity = 0;
      for (var i = 1; i < total_item; i++) 
      {
        if($("#quantity-" + i).val() != null)
          total_quantity += parseInt($("#quantity-" + i).val());
      }
      $("#total").html("Jumlah barang " + total_quantity + " dari 24 (untuk print rak)");
    }
  </script>
@endsection