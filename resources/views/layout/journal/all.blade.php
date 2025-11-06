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
            <h3 class="box-title">{{ $default['page_name'] }}</h3>
            @include('layout.search-form')
          </div>
          <div class="box-body">
            <div class="col-sm-12">
              {!! Form::label('show', 'Show', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-1">
                {!! Form::select('show', getPaginations(), $pagination, ['class' => 'form-control', 'style'=>'width: 100%', 'id' => 'show', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('journal_type', 'Tipe', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-2">
                {!! Form::select('journal_type', getJournalTypes(), $type, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'journal_type', 'onchange' => 'advanceSearch()']) !!}
              </div>
              {!! Form::label('code', 'Akun', array('class' => 'col-sm-1 control-label')) !!}
             <div class="col-sm-2">
                {!! Form::select('code', getAccountLists(), $code, ['class' => 'form-control select2', 'style'=>'width: 100%', 'id' => 'code', 'onchange' => 'advanceSearch()']) !!}
              </div>
            </div>
            <div class="col-sm-12" style="margin-top: 10px">
              {!! Form::label('start_date', 'Tanggal Awal', array('class' => 'col-sm-2 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" id="datepicker" name="start_date" value="{{ $start_date }}" onchange="changeDate()">
                </div>
              </div>
              {!! Form::label('end_date', 'Tanggal Akhir', array('class' => 'col-sm-2 control-label')) !!}
              <div class="col-sm-2">
                <div class="input-group date">
                  <input type="text" class="form-control pull-right" id="datepicker2" name="end_date" value="{{ $end_date }}" onchange="changeDate()">
                </div>
              </div>
            </div>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <a href="{{ url($role . '/journal/create') }}" class="btn btn-success" style="margin-bottom: 10px;">Tambah Jurnal</a>
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th width="5%">Highlight</th>
                <th width="15%">Tipe</th>
                <th width="10%">@if($order == 'desc')<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/id/asc/' . $pagination) }}">@else<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/id/desc/' . $pagination) }}">@endif <i class="fa fa-sort" aria-hidden="true"></i> Journal ID</a></th>
                <th width="10%">Tipe ID</th>
                <th width="10%">Created_at</th>
                <th width="10%">@if($order == 'desc')<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/journal_date/asc/' . $pagination) }}">@else<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/journal_date/desc/' . $pagination) }}">@endif <i class="fa fa-sort" aria-hidden="true"></i> Tanggal</a></th>
                <th>Nama</th>
                <th style="background-color: #E5F9DB">@if($order == 'desc')<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit_account_id/asc/' . $pagination) }}">@else<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit_account_id/desc/' . $pagination) }}">@endif <i class="fa fa-sort" aria-hidden="true"></i> No Akun</a></th>
                <th style="background-color: #E5F9DB">Akun</th>
                <th style="background-color: #E5F9DB">@if($order == 'desc')<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit/asc/' . $pagination) }}">@else<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/debit/desc/' . $pagination) }}">@endif <i class="fa fa-sort" aria-hidden="true"></i> Debet</a></th>
                <th style="background-color: #FFABAB">@if($order == 'desc')<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/credit_account_id/asc/' . $pagination) }}">@else<a href="{{ url($role . '/journal/' . $code . '/' . $type . '/' . $start_date . '/' . $end_date . '/credit_account_id/desc/' . $pagination) }}">@endif <i class="fa fa-sort" aria-hidden="true"></i> No Akun</a></th>
                <th style="background-color: #FFABAB">Akun</th>
                <th style="background-color: #FFABAB">Kredit</th>
                <th width="10%">Edit</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($journals as $journal)
                  <tr id="div-journal-{{ $journal->id }}">
                    <td><input type="checkbox" name="journals[]" id="journal-{{ $journal->id }}" onclick="highlight('journal-{{ $journal->id }}')"></td>
                    <td>{{ $journal->type }}</td>
                    <td>{{ $journal->id }}</td>
                    <td>{{ $journal->type_id }}</td>
                    <td>{{ $journal->created_at }}</td>
                    <td>{{ displayDate($journal->journal_date) }}</td>
                    <td>@if($journal->type == 'good_loading')
                          <a href="{{ url($role . '/good-loading/' . $journal->type_id . '/detail') }}" style="color: blue;">{{ $journal->name }}</a>
                        @elseif($journal->type == 'transaction' || $journal->type == 'penyusutan' || $journal->type == 'operasional')
                          <a href="{{ url($role . '/transaction/' . $journal->type_id . '/detail') }}" style="color: blue;">{{ $journal->name }}</a>
                        @else
                          {{ $journal->name }}
                        @endif
                    </td>
                    <td style="background-color: #E5F9DB">{{ $journal->debit_account()->code }}</td>
                    <td style="background-color: #E5F9DB">{{ $journal->debit_account()->name }}</td>
                    <td style="background-color: #E5F9DB">{{ showRupiah($journal->debit) }}</td>
                    <td style="background-color: #FFABAB">{{ $journal->credit_account()->code }}</td>
                    <td style="background-color: #FFABAB">{{ $journal->credit_account()->name }}</td>
                    <td style="background-color: #FFABAB">{{ showRupiah($journal->credit) }}</td>
                    <td class="center"><a href="{{ url($role . '/journal/' . $journal->id . '/edit') }}"><i class="fa fa-file orange" aria-hidden="true"></i></a></td>
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
      $('.select2').select2();
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
      window.location = window.location.origin + '/{{ $role }}/journal/{{ $code }}/{{ $type }}/' + $("#datepicker").val() + '/' + $("#datepicker2").val() + '/{{ $sort }}/{{ $order }}/{{ $pagination }}';
    }

    function advanceSearch()
    {
      var show        = $('#show').val();
      var code        = $('#code').val();
      var type        = $('#journal_type').val();
      window.location = window.location.origin + '/{{ $role }}/journal/' + code + '/' + type + '/{{ $start_date }}/{{ $end_date }}/{{ $sort }}/{{ $order }}/' + show;
    }

    function highlight(id)
    {
      if($("#" + id).prop('checked') == true)
        $('#div-' + id).css('background-color', "{{ config('app.app_color') }}");
      else
        $('#div-' + id).css('background-color', "white");
    }
  </script>
@endsection