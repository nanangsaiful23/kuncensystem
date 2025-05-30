<div class="content-wrapper">
  @include('layout' . '.error')

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"> Detail Member</h3><br>
          </div>
          <div class="box-body">
            <div class="col-sm-7" style="overflow-x:scroll; overflow-y:scroll; color: black !important">
              <h3>Barang yang sering dibeli</h3>
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>No</th>
                  <th>Barang</th>
                  <th width="10%">Satuan</th>
                  <th width="10%">Total Pembelian</th>
                </tr>
                </thead>
                <tbody id="table-good">
                  <?php $i=1; ?>
                  @foreach($member->getGoodRecords() as $good)
                    <tr>
                      <td>{{ $i++ }}</td>
                      <td>{{ $good->good_name }}</td>
                      <td>{{ $good->unit_name }}</td>
                      <td>{{ $good->total }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
              
            </div>
            <div class="col-sm-5">
              {!! Form::model($member, array('class' => 'form-horizontal')) !!}
                @include('layout' . '.member.form', ['SubmitButtonText' => 'View'])
              {!! Form::close() !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>