@extends('layouts.app')

@section('title', 'Adjust Stok Bahan')

@section('main')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Adjust Stok: {{ $material->name }}</h1>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-6">
          <div class="card">
            <form action="{{ route('raw-materials.adjust',$material) }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="alert alert-light border mb-4" role="alert">
                  <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                      <div class="font-weight-bold mb-1">Ringkasan Stok</div>
                      <div>Stok saat ini: <strong>{{ number_format($material->stock_qty, 1, ',', '.') }} {{ $material->unit }}</strong></div>
                      <div>Harga rata-rata: <strong>{{ number_format($material->unit_cost, 1, ',', '.') }}</strong></div>
                    </div>
                    <div class="text-muted small mt-2 mt-md-0">
                      SKU: {{ $material->sku }}
                    </div>
                  </div>
                  <hr>
                  @if(isset($lastMovement) && $lastMovement)
                    <div class="mb-1">Pergerakan terakhir: <strong>{{ ucfirst(str_replace('_',' ', $lastMovement->type)) }}</strong></div>
                    <div class="small text-muted">
                      {{ optional($lastMovement->occurred_at)->format('d/m/Y H:i') ?? '—' }} • Qty {{ number_format($lastMovement->qty_change, 1, ',', '.') }} • Harga {{ number_format($lastMovement->unit_cost, 1, ',', '.') }}
                      @if($lastMovement->notes)
                        <div class="mt-1">Catatan: {{ $lastMovement->notes }}</div>
                      @endif
                    </div>
                  @else
                    <div class="small text-muted">Belum ada histori pergerakan stok.</div>
                  @endif
                </div>
                @if(isset($expenseSources) && $expenseSources->count())
                <div class="form-group">
                  <label>Ambil dari Uang Keluar</label>
                  <select class="form-control" id="expense-source-select">
                    <option value="">- Pilih pengeluaran -</option>
                    @foreach($expenseSources as $source)
                      <option value="{{ $source->id }}" data-vendor="{{ $source->vendor }}" data-notes="{{ $source->notes }}" data-amount="{{ $source->amount }}" data-date="{{ optional($source->date)->toDateString() }}">
                        {{ optional($source->date)->format('d/m/Y') ?? '-' }} • {{ $source->vendor ?? ($source->notes ?? 'Tanpa nama') }} • {{ number_format($source->amount ?? 0, 0, ',', '.') }}
                      </option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">Pilih pengeluaran untuk mengisi otomatis.</small>
                </div>
                @endif
                <div class="form-group">
                  <label>Qty Change (boleh negatif)</label>
                  <input type="number" step="0.1" name="qty_change" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Unit Cost (opsional, untuk pembelian/positif)</label>
                  <input type="number" step="0.1" name="unit_cost" class="form-control">
                </div>
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea name="notes" class="form-control"></textarea>
                </div>
              </div>
              <div class="card-footer text-right">
                <button class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('expense-source-select');
    if (!select) return;
    const unitCostInput = document.querySelector('input[name="unit_cost"]');
    const notesInput = document.querySelector('textarea[name="notes"]');
    select.addEventListener('change', function () {
      const option = select.options[select.selectedIndex];
      if (!option || !option.value) {
        return;
      }
      const vendor = option.dataset.vendor || '';
      const notes = option.dataset.notes || '';
      const amount = option.dataset.amount || '';
      if (unitCostInput && amount) {
        unitCostInput.value = parseFloat(amount).toFixed(2);
      }
      if (notesInput && !notesInput.value) {
        notesInput.value = notes || vendor;
      }
    });
  });
</script>
@endpush
