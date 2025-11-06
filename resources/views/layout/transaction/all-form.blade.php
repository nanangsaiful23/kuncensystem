<div class="box-body" style="background-color: {{ $color }}">
  <h3>Transaksi {{ $name }}</h3><br>
  <h4>Total transaksi {{ $name }}: {{ showRupiah($total_sum_price) }}</h4>
  <h4>Total potongan: {{ showRupiah($total_discount_price) }}</h4><br>
</div>
<div class="box-body" style="overflow-x:scroll; background-color: {{ $color }}">
  <table id="example1" class="table table-bordered table-striped">
    <thead>
    <tr>
      <th>ID</th>
      <th>Waktu</th>
      @if($role == 'admin')
        <th>Kasir</th>
      @endif
      <th>Member</th>
      <th>Note</th>
      <th>Total Belanja</th>
      <th>Total Diskon</th>
      <th>Potongan Akhir</th>
      <th>Total Akhir</th>
      <th>Uang Dibayar</th>
      <th>Kembalian</th>
      <th class="center">Detail</th>
      <th class="center">Print</th>
      @if($role == 'admin')
        <th class="center">Retur</th>
      @endif
      @if(\Auth::user()->email == 'admin')
        <th class="center">Edit</th>
        <th class="center">Hapus</th>
      @endif
    </tr>
    </thead>
    <tbody id="table-good">
      @foreach($transactions as $transaction)
        <tr id="div-transaction-{{ $transaction->id }}">
          <td><input type="checkbox" name="transactions[]" id="transaction-{{ $transaction->id }}" onclick="highlight('transaction-{{ $transaction->id }}')"></td>
          <td>{{ $transaction->id }}</td>
          <td>{{ $transaction->created_at }}</td>
          @if($role == 'admin')
            <td>{{ $transaction->actor()->name }}</td>
          @endif
          <td>@if($transaction->member_id != 1){{ $transaction->member->name }}@else - @endif</td>
          <td>{{ $transaction->note }}</td>
          <td>{{ showRupiah($transaction->total_item_price) }}</td>
          <td>{{ showRupiah(checkNull($discount_price)) }}</td>
          <td>{{ showRupiah($transaction->total_discount_price) }}</td>
          <td>{{ showRupiah($transaction->total_sum_price) }}</td>
          <td>{{ showRupiah($transaction->money_paid) }}</td>
          <td>{{ showRupiah($transaction->money_returned) }}</td>
          <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/detail') }}"><i class="fa fa-hand-o-right tosca" aria-hidden="true"></i></a></td>
          <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/print') }}"><i class="fa fa-print tosca" aria-hidden="true"></i></a></td>
          @if($role == 'admin')
          <td><button type="button" class="no-btn" data-toggle="modal" data-target="#modal-reverse-{{$transaction->id}}"><i class="fa fa-hand-o-left red" aria-hidden="true"></i></button>

            <div class="modal modal-reverse fade" id="modal-reverse-{{ $transaction->id }}">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span></button>
                      <h4 class="modal-title">Reverse transaksi</h4>
                  </div>
                  <div class="modal-body">
                      <p>Anda yakin ingin mereverse {{ $transaction->id . ' total ' . showRupiah($transaction->total_sum_price) }}?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline" onclick="event.preventDefault(); document.getElementById('reverse-form-{{$transaction->id}}').submit();">Reverse</button>
                  </div>
                </div>
              </div>
            </div>
            <form id="reverse-form-{{$transaction->id}}" action="{{ url($role . '/transaction/' . $transaction->id . '/reverse') }}" method="POST" style="display: none;">
              {{ csrf_field() }}
              {{ method_field('PUT') }}
            </form>
          </td>
          @endif
          @if(\Auth::user()->email == 'admin')
            <td class="center"><a href="{{ url($role . '/transaction/' . $transaction->id . '/edit') }}"><i class="fa fa-file tosca" aria-hidden="true"></i></a></td>
            <td>
                <button type="button" class="no-btn" data-toggle="modal" data-target="#modal-danger-{{$transaction->id}}"><i class="fa fa-times red" aria-hidden="true"></i></button>

                @include('layout' . '.delete-modal', ['id' => $transaction->id, 'data' => $transaction->created_at, 'formName' => 'delete-form-' . $transaction->id])

                <form id="delete-form-{{$transaction->id}}" action="{{ url($role . '/transaction/' . $transaction->id . '/delete') }}" method="POST" style="display: none;">
                  {{ csrf_field() }}
                  {{ method_field('DELETE') }}
                </form>
            </td>
          @endif
        </tr>
      @endforeach
    </tbody>
    <div id="renderField">
      @if($pagination != 'all')
        {{ $transactions->render() }}
      @endif
    </div>
  </table>
</div>