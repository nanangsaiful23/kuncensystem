<style type="text/css">
  table, th, td
  {
    border: solid 2px black !important;
  }
</style>

<div class="content-wrapper">

  @include('layout' . '.alert-message', ['type' => $default['type'], 'data' => $default['data'], 'color' => $default['color']])

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">List Jurnal</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body">
            {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
           <div class="col-sm-1">
              {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
            </div>
            {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()">
              </div>
            </div>
            {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-1 control-label')) !!}
            <div class="col-sm-2">
              <div class="input-group date">
                <input type="text" class="form-control pull-right" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()">
              </div>
            </div>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th width="10%">Tanggal</th>
                <th>Nama</th>
                <th style="background-color: #E5F9DB">No Akun</th>
                <th style="background-color: #E5F9DB">Akun</th>
                <th style="background-color: #E5F9DB">Debet</th>
                <th style="background-color: #FFABAB">No Akun</th>
                <th style="background-color: #FFABAB">Akun</th>
                <th style="background-color: #FFABAB">Kredit</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($journals as $journal)
                  <tr>
                    <td>{{ displayDate($journal->journal_date) }}</td>
                    <td>{{ $journal->name }}</td>
                    <td style="background-color: #E5F9DB">{{ $journal->debit_account()->code }}</td>
                    <td style="background-color: #E5F9DB">{{ $journal->debit_account()->name }}</td>
                    <td style="background-color: #E5F9DB">{{ showRupiah($journal->debit) }}</td>
                    <td style="background-color: #FFABAB">{{ $journal->credit_account()->code }}</td>
                    <td style="background-color: #FFABAB">{{ $journal->credit_account()->name }}</td>
                    <td style="background-color: #FFABAB">{{ showRupiah($journal->credit) }}</td>
                  </tr>
                @endforeach
              </tbody>
              <div id="renderField">
                @if($pagination != 'all')
                  {{ $journals->render() }}
                @endif
              </div>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

@section('js-addon')
  <script type="text/javascript">
    $(document).ready(function(){
      $('#datepicker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })

      $('#datepicker2').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })
      
        $("#search-input").keyup( function(e){
          if(e.keyCode == 13)
          {
            ajaxFunction();
          }
        });

        $("#search-btn").click(function(){
            ajaxFunction();
        });

    });

    function changeDate()
    {
      window.location = window.location.origin + '/{{ $role }}/journal/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      window.location = window.location.origin + '/{{ $role }}/journal/{{ $start_date }}/{{ $end_date }}/' + show;
    }
  </script>
@endsection