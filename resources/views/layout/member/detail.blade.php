<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Detail Member</h3>
          </div>

          {!! Form::model($member, array('class' => 'form-horizontal')) !!}
            <div class="box-body">
              @include('layout' . '.member.form', ['SubmitButtonText' => 'View'])
            </div>
          {!! Form::close() !!}

        </div>
      </div>
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Riwayat transaksi</h3>
          </div>
          <div class="box-body" style="overflow-x:scroll; color: black !important">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th width="30%">Tanggal</th>
                <th>ID Transaksi</th>
                <th>Jumlah Transaksi</th>
                <th>ID Pembayaran</th>
                <th>Jumlah Pembayaran</th>
              </tr>
              </thead>
              <tbody id="table-good">
                @foreach($member->getAllRecords() as $date)
                  <tr>
                    <td>{{ displayDate($date) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>