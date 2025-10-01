@extends('layouts.app')

@section('title', 'Bahan Pokok')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Bahan Pokok</h1>
      <div class="section-header-button">
        <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">Tambah</a>
      </div>
    </div>
    <div class="section-body">
      <div class="card">
        <div class="card-body">
          <form class="form-inline mb-3" method="GET" action="{{ route('raw-materials.index') }}">
            <input type="text" class="form-control mr-2" name="search" placeholder="Cari nama/SKU" value="{{ request('search') }}">
            <button class="btn btn-primary">Cari</button>
          </form>
          @include('layouts.alert')
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Bahan</th>
                  <th>Satuan</th>
                  <th>Harga Rata-rata</th>
                  <th>Stok</th>
                  <th>Stok Minimum</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($materials as $m)
                <tr>
                  <td>{{ $m->sku }}</td>
                  <td>{{ $m->name }}</td>
                  <td>{{ $m->unit }}</td>
                  <td>{{ number_format($m->unit_cost, 1, ',', '.') }}</td>
                  <td>{{ number_format($m->stock_qty, 1, ',', '.') }}</td>
                  <td>{{ number_format($m->min_stock, 1, ',', '.') }}</td>
                  <td class="text-right">
                    <a href="{{ route('raw-materials.edit',$m) }}" class="btn btn-sm btn-info">Edit</a>
                    <a href="{{ route('raw-materials.adjust-form',$m) }}" class="btn btn-sm btn-warning">Adjust</a>
                    <form action="{{ route('raw-materials.destroy',$m) }}" method="POST" class="d-inline js-delete-material">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="float-right">{{ $materials->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-delete-material').forEach(function (form) {
      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const row = this.closest('tr');
        const materialName = row?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'bahan baku ini';

        const result = await Swal.fire({
          title: `Hapus ${materialName}?`,
          text: 'Tindakan ini tidak dapat dibatalkan.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus',
          cancelButtonText: 'Batal',
        });

        if (!result.isConfirmed) {
          return;
        }

        const formData = new FormData(this);
        const action = this.getAttribute('action');
        const methodInput = this.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value.toUpperCase() : 'POST';

        try {
          const response = await fetch(action, {
            method: method === 'POST' ? 'POST' : 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': this.querySelector('input[name="_token"]').value,
              'Accept': 'application/json',
            },
            body: formData,
          });

          if (response.ok) {
            await Swal.fire({
              title: 'Berhasil',
              text: 'Bahan dihapus.',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false,
            });
            window.location.reload();
            return;
          }

          const data = await response.json();
          const message = data?.message || 'Bahan tidak dapat dihapus.';
          await Swal.fire({
            title: 'Gagal',
            text: message,
            icon: 'error',
          });
        } catch (error) {
          await Swal.fire({
            title: 'Gagal',
            text: 'Terjadi kesalahan. Silakan coba lagi.',
            icon: 'error',
          });
        }
      });
    });
  });
</script>
@endpush
