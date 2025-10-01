@extends('layouts.app')

@section('title', 'Edit Uang Keluar')

@section('main')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Uang Keluar</h1>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input type="date" name="date" class="form-control" value="{{ old('date', optional($expense->date)->toDateString()) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Kategori</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">-</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ old('category_id', $expense->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Vendor</label>
                                            <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $expense->vendor) }}" list="vendor-suggestions">
                                            @if(isset($vendorSuggestions) && $vendorSuggestions->count())
                                                <datalist id="vendor-suggestions">
                                                    @foreach($vendorSuggestions as $v)
                                                        <option value="{{ $v }}">
                                                    @endforeach
                                                </datalist>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Catatan</label>
                                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $expense->notes) }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Lampiran (JPG, PNG, PDF, maks 5MB)</label>
                                            <input type="file" name="attachment" class="form-control-file" accept="image/*,.pdf">
                                            @if($expense->attachment_path)
                                                <div class="form-check mt-2">
                                                    <input type="checkbox" class="form-check-input" id="remove-attachment" name="remove_attachment" value="1" {{ old('remove_attachment') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="remove-attachment">Hapus lampiran saat ini</label>
                                                </div>
                                                <small class="form-text text-muted">Lampiran saat ini: <a href="{{ Storage::url($expense->attachment_path) }}" target="_blank">Lihat</a></small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mb-3">Detail Pengeluaran</h5>
                                @php
                                    $oldItems = old('items', $expense->items->map(function ($item) {
                                        return [
                                            'raw_material_id' => $item->raw_material_id,
                                            'description' => $item->description,
                                            'unit' => $item->unit,
                                            'qty' => $item->qty,
                                            'item_price' => $item->total_cost,
                                            'unit_cost' => $item->unit_cost,
                                            'notes' => $item->notes,
                                        ];
                                    })->toArray());
                                    if (empty($oldItems)) {
                                        $oldItems = [[
                                            'raw_material_id' => null,
                                            'description' => null,
                                            'unit' => null,
                                            'qty' => 1,
                                            'item_price' => 0,
                                            'unit_cost' => 0,
                                            'notes' => null,
                                        ]];
                                    }
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="expense-items-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 22%">Bahan Baku</th>
                                                <th style="width: 18%">Deskripsi</th>
                                                <th style="width: 10%">Satuan</th>
                                                <th style="width: 10%">Qty</th>
                                                <th style="width: 15%">Harga (Total)</th>
                                                <th style="width: 15%">Harga Satuan</th>
                                                <th style="width: 15%">Subtotal</th>
                                                <th style="width: 10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($oldItems as $index => $item)
                                                <tr data-row>
                                                    <td>
                                                        <select name="items[{{ $index }}][raw_material_id]" class="form-control" data-material-select>
                                                            <option value="">-</option>
                                                            @foreach($materials as $material)
                                                                <option value="{{ $material->id }}"
                                                                    data-unit="{{ $material->unit }}"
                                                                    data-name="{{ $material->name }}"
                                                                    {{ (string) ($item['raw_material_id'] ?? '') === (string) $material->id ? 'selected' : '' }}>
                                                                    {{ $material->name }} ({{ $material->unit }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Keterangan item">
                                                        <textarea name="items[{{ $index }}][notes]" class="form-control mt-2" rows="2" placeholder="Catatan tambahan">{{ $item['notes'] ?? '' }}</textarea>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}" data-unit-input>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.0001" min="0" class="form-control" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" data-qty-input>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" class="form-control" name="items[{{ $index }}][item_price]" value="{{ $item['item_price'] ?? ($item['total_cost'] ?? 0) }}" data-price-input>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.1" min="0" class="form-control" name="items[{{ $index }}][unit_cost]" value="{{ isset($item['unit_cost']) ? number_format((float) $item['unit_cost'], 1, '.', '') : 0 }}" data-unit-cost-input readonly>
                                                    </td>
                                                    <td class="align-middle" data-subtotal>Rp 0</td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button type="button" class="btn btn-outline-primary" data-add-row><i class="fas fa-plus mr-1"></i>Tambah Item</button>
                                    <div class="text-right">
                                        <div class="h6 mb-0">Total Pengeluaran</div>
                                        <div class="h4 mb-0" id="expense-total">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.querySelector('#expense-items-table tbody');
            const addBtn = document.querySelector('[data-add-row]');
            const totalLabel = document.getElementById('expense-total');
            const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });

            const formatCurrency = (value) => {
                const numeric = Number.isFinite(value) ? value : 0;
                return currencyFormatter.format(numeric);
            };

            const createRow = (data = {}) => {
                const index = table.querySelectorAll('tr').length;
                const tr = document.createElement('tr');
                tr.setAttribute('data-row', '');
                tr.innerHTML = `
                    <td>
                        <select name="items[${index}][raw_material_id]" class="form-control" data-material-select>
                            <option value="">-</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}" data-unit="{{ $material->unit }}" data-name="{{ $material->name }}">{{ $material->name }} ({{ $material->unit }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${index}][description]" placeholder="Keterangan item">
                        <textarea name="items[${index}][notes]" class="form-control mt-2" rows="2" placeholder="Catatan tambahan"></textarea>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${index}][unit]" data-unit-input>
                    </td>
                    <td>
                        <input type="number" step="0.0001" min="0" class="form-control" name="items[${index}][qty]" value="1" data-qty-input>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control" name="items[${index}][item_price]" value="0" data-price-input>
                    </td>
                    <td>
                        <input type="number" step="0.1" min="0" class="form-control" name="items[${index}][unit_cost]" value="0" data-unit-cost-input readonly>
                    </td>
                    <td class="align-middle" data-subtotal>Rp 0</td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                    </td>`;
                table.appendChild(tr);

                if (data.raw_material_id) {
                    tr.querySelector('[data-material-select]').value = data.raw_material_id;
                }
                if (data.description) {
                    tr.querySelector('input[name$="[description]"]').value = data.description;
                }
                if (data.notes) {
                    tr.querySelector('textarea[name$="[notes]"]').value = data.notes;
                }
                if (data.unit) {
                    tr.querySelector('[data-unit-input]').value = data.unit;
                }
                if (data.qty) {
                    tr.querySelector('[data-qty-input]').value = data.qty;
                }
                if (typeof data.item_price !== 'undefined') {
                    tr.querySelector('[data-price-input]').value = data.item_price;
                }
                if (typeof data.unit_cost !== 'undefined') {
                    tr.querySelector('[data-unit-cost-input]').value = parseFloat(data.unit_cost).toFixed(1);
                }

                updateRowMeta(tr);
                recalcRow(tr);
            };

            const updateRowMeta = (row) => {
                const select = row.querySelector('[data-material-select]');
                const description = row.querySelector('input[name$="[description]"]');
                const unitInput = row.querySelector('[data-unit-input]');
                const selected = select.options[select.selectedIndex];
                if (selected && selected.dataset.unit) {
                    unitInput.value = selected.dataset.unit;
                }
                if (selected && selected.dataset.name && description.value.trim() === '') {
                    description.value = selected.dataset.name;
                }
            };

            const recalcRow = (row) => {
                const qty = parseFloat(row.querySelector('[data-qty-input]').value) || 0;
                const price = parseFloat(row.querySelector('[data-price-input]').value) || 0;
                const unitCostInput = row.querySelector('[data-unit-cost-input]');

                if (qty > 0) {
                    unitCostInput.value = (price / qty).toFixed(1);
                } else {
                    unitCostInput.value = '0.0';
                }

                row.querySelector('[data-subtotal]').textContent = formatCurrency(price);
                updateTotal();
            };

            const updateTotal = () => {
                let sum = 0;
                table.querySelectorAll('[data-price-input]').forEach(input => {
                    sum += parseFloat(input.value) || 0;
                });
                totalLabel.textContent = formatCurrency(sum);
            };

            table.addEventListener('change', (event) => {
                const row = event.target.closest('[data-row]');
                if (!row) {
                    return;
                }
                if (event.target.matches('[data-material-select]')) {
                    updateRowMeta(row);
                }
                if (event.target.matches('[data-qty-input]') || event.target.matches('[data-price-input]')) {
                    recalcRow(row);
                }
            });

            table.addEventListener('input', (event) => {
                const row = event.target.closest('[data-row]');
                if (!row) {
                    return;
                }
                if (event.target.matches('[data-qty-input]') || event.target.matches('[data-price-input]')) {
                    recalcRow(row);
                }
            });

            table.addEventListener('click', (event) => {
                if (event.target.closest('[data-remove-row]')) {
                    const rows = table.querySelectorAll('tr');
                    if (rows.length > 1) {
                        event.target.closest('tr').remove();
                        renumberRows();
                        updateTotal();
                    }
                }
            });

            const renumberRows = () => {
                table.querySelectorAll('tr').forEach((row, idx) => {
                    row.querySelectorAll('select, input, textarea').forEach(input => {
                        const name = input.getAttribute('name');
                        if (!name) {
                            return;
                        }
                        input.setAttribute('name', name.replace(/items\[(\d+)\]/, `items[${idx}]`));
                    });
                });
            };

            addBtn.addEventListener('click', () => {
                createRow();
            });

            if (table.querySelectorAll('tr').length === 0) {
                createRow();
            } else {
                table.querySelectorAll('tr').forEach(row => {
                    updateRowMeta(row);
                    recalcRow(row);
                });
            }

            updateTotal();
        });
    </script>
@endpush
