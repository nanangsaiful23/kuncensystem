<div class="modal modal-danger fade" id="modal-danger-{{ $id }}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">Hapus {{ $data }}</h4>
      </div>
      <div class="modal-body">
          <p>Anda yakin ingin menghapus {{ $data }}?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-outline" onclick="event.preventDefault(); document.getElementById('{{ $formName }}').submit();">Hapus</button>
      </div>
    </div>
  </div>
</div>