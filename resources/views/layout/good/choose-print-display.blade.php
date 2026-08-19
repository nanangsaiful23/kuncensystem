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
          {!! Form::model(old(),array('url' => route($role . '.print-display'), 'method' => 'POST', 'id' => 'print-form')) !!}
          <div class="box-body" style="overflow-x:scroll">
              <div class="form-group col-sm-12">
                <input type="radio" name="type" value="rack" required="required">
                <label for="html">Display Rak</label><br>
                <input type="radio" name="type" value="list" required="required">
                <label for="css">Display List</label><br>
              </div>
              <div class="form-group col-sm-7" style="height: 40px!important; font-size: 20px;">
                  {!! Form::label('keyword', 'Cari keyword', array('class' => 'col-sm-3 control-label')) !!}
                  <div class="col-sm-8">
                      <input type="text" name="search_good" class="form-control" id="search_good">
                  </div>
                   <div class="modal modal-primary fade" id="modal_search">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Hasil Keyword (klik nama barang)</h4>
                        </div>
                        <div class="modal-body">
                          <div id="result_good"></div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
              </div>
              <?php $i = 1; ?>
              <div id="div-result"></div>
              <div id="row-data-{{ $i }}">
                <div class="col-sm-12">
                  
                  <div class="form-group col-sm-3">
                    <input type="text" id="id-{{ $i }}" name="ids[]" class="form-control" placeholder="id">
                  </div>
                  <div class="form-group col-sm-6">
                    <input type="text" id="name-{{ $i }}" name="names[]" class="form-control" placeholder="nama">
                  </div>
                  <div class="form-group col-sm-2">
                    <input type="text" id="quantity-{{ $i }}" name="quantities[]" class="form-control" placeholder="jumlah" onchange="checkTotal()">
                  </div>
                  <div class="form-group col-sm-1">
                    <i class="fa fa-times" onclick="deleteItem('{{ $i }}')" style="color: red"></i>
                  </div>
                </div>
              </div>
          </div> 
              <div onclick="event.preventDefault(); submitForm(this);" class= 'btn btn-success btn-flat btn-block form-control'>Print</div>
            {!! Form::close() !!}
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

      $("#search_good").keyup( function(e){
        if(e.keyCode == 13)
        {
          ajaxFunction("all_barcode");
        }
      });

    });

    function ajaxFunction(name)
    {
        
        $('#modal_search').modal('show');   

          $.ajax({
            url: "{!! url($role . '/good/searchByKeywordGoodUnit/') !!}/" + $("#search_good").val(),
            success: function(result){
                htmlResult = '';

                htmlResult += "<style type='text/css'>.modal-div:hover { background-color: white; }</style>";
              var r = result.good_units;

              for (var i = 0; i < r.length; i++) {
                if(r[i].stock == 0) 
                {
                    color = '#D1D3D4';
                }
                else if(r[i].stock < 0) 
                {
                    color = '#D9C4B0';
                }
                else
                {
                    color = '#9EBC8A';
                }
                htmlResult += "<textarea class='col-sm-12 modal-div' style='display:inline-block; color:black; cursor: pointer; min-height:40px; max-height:80px; background-color:" + color + "; padding: 5px;' onclick='searchByKeyword(\"" + r[i].good_unit_id + "\")'>" + r[i].name + " " + r[i].unit + "</textarea>";
              }
              $("#result_good").html(htmlResult);
              $('.modal-body').css('height',$( window ).height()*0.5);
            },
            error: function(){
                console.log('error');
            }
          });
    }

    function searchByKeyword(good_unit_id)
    {        
        $.ajax({
          url: "{!! url($role . '/good/searchByGoodUnit/') !!}/" + good_unit_id,
          success: function(result){
            var good = result.good;
            if(good.stock <= 0)
            {
                document.getElementById("message").style.display = "block";
                htmlResult2 = "> " + good.name + " stock: " + good.stock + "<br>";
                $("#empty-item").append(htmlResult2);
            }

            $("#id-" + total_item).val(good_unit_id);
            $("#name-" + total_item).val(good.name);
            $("#quantity-" + total_item).val(1);
      
            addElement(total_item);
            total_item += 1;

            $('#modal_search').modal('hide');
            $('#search_good').val('');
            $('#result_good').val('');
        },
          error: function(){
          }
        });
    }

    function addElement(index)
    {
      // console.log($("#quantity-" + total_item).val());
      // if($("#quantity-" + total_item).val() == null)
      // {
        index = parseInt(index) + 1;
        index = index.toString();
        htmlResult = '<div id="row-data-' + index + '"><div class="col-sm-12"><div class="form-group col-sm-3"><input type="text" name="ids[]" class="form-control" id="id-' + index + '"></div><div class="form-group col-sm-6"><input type="text" name="names[]" class="form-control" id="name-' + index + '"></div><div class="form-group col-sm-2"><input type="text" name="quantities[]" class="form-control" id="quantity-' + index+ '" onchange="checkTotal()"></div><div class="form-group col-sm-1"><i class="fa fa-times" onclick="deleteItem(\'' + index + '\')" style="color: red"></i></div></div></div>';

        $("#div-result").prepend(htmlResult);
      // }

        checkTotal();

    }

    function deleteItem(index)
    {
      // console.log('masuk hapus');
      $("#row-data-" + index).remove();

      checkTotal();
    }

    function checkTotal()
    {
      total_quantity = 0;
      for (var i = 1; i <= total_item; i++) 
      {
        if($("#quantity-" + i).val() != null && $("#quantity-" + i).val() != '')
          total_quantity += parseInt($("#quantity-" + i).val());
        console.log(parseInt($("#quantity-" + i).val()));
      }
      $("#total").html("Jumlah barang yang akan diprint " + total_quantity + " <br>(max 24 untuk print rak & max 17 untuk print list dalam satu halaman)");
    }

    function submitForm(btn)
    {
                btn.disabled = true;
                document.getElementById('print-form').submit();
                // alert('hay');
    }
  </script>
@endsection