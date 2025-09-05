@extends('layouts.app')

@section('title', 'Create Product (Wizard)')

@push('style')
    <link rel="stylesheet" href="{{ asset('library/select2/dist/css/select2.min.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Create Product</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Product</div>
                </div>
            </div>

            <div class="section-body">
                @include('components.help_panel', [
                    'id' => 'help-products-create',
                    'title' => 'Panduan singkat • Produk & Resep',
                    'items' => [
                        'Step 1: Isi detail produk (nama, harga, stok, kategori, foto).',
                        'Step 2 (opsional): Centang "This product has a recipe", isi <em>Yield Quantity</em> dan <em>Unit</em>, lalu tambahkan Bahan Pokok + Qty per Yield + Waste % (opsional).',
                        'Step 3: Review lalu <strong>Save</strong>. HPP per unit dihitung dari biaya rata-rata Bahan Pokok saat ini.',
                        'Produksi: gunakan menu Produksi (jika tersedia) untuk mengonsumsi stok Bahan Pokok sesuai resep.',
                    ],
                ])
                <div class="card">
                    <form id="product-wizard-form" action="{{ route('product.wizard.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="card-body">
                            <div id="step-1">
                                <h6 class="mb-3">Step 1 of 3: Product Details</h6>
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" name="price" value="{{ old('price') }}">
                                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Stock</label>
                                    <input type="number" step="0.01" class="form-control @error('stock') is-invalid @enderror" name="stock" value="{{ old('stock') }}">
                                    @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select class="form-control select2 @error('category_id') is-invalid @enderror" name="category_id">
                                        <option value="">Choose a category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Photo Product</label>
                                    <input type="file" class="form-control @error('image') is-invalid @enderror" name="image">
                                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="form-text text-muted">File photo max 2 MB.</small>
                                </div>
                            </div>

                            <div id="step-2" style="display:none">
                                <h6 class="mb-3">Step 2 of 3: Recipe (Optional)</h6>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="recipe_enabled" name="recipe_enabled" value="1" {{ old('recipe_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="recipe_enabled">This product has a recipe</label>
                                </div>

                                <div id="recipe-fields" style="display: none;">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Yield Quantity</label>
                                            <input type="number" step="0.0001" class="form-control @error('yield_qty') is-invalid @enderror" name="yield_qty" value="{{ old('yield_qty') }}">
                                            @error('yield_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Unit (e.g. pcs, cup)</label>
                                            <input type="text" class="form-control @error('unit') is-invalid @enderror" name="unit" value="{{ old('unit') }}">
                                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:40%">Raw Material</th>
                                                    <th style="width:25%">Qty per Yield</th>
                                                    <th style="width:25%">Waste % (optional)</th>
                                                    <th style="width:10%"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-body"></tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-btn">Add ingredient</button>

                                    @error('items')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                                    @error('items.*.raw_material_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    @error('items.*.qty_per_yield')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                    @error('items.*.waste_pct')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div id="step-3" style="display:none">
                                <h6 class="mb-3">Step 3 of 3: Review</h6>
                                <div id="review-content"></div>
                            </div>
                        </div>

                        <div class="card-footer text-right">
                            <button type="button" class="btn btn-secondary" id="btn-back" style="display:none">Back</button>
                            <button type="button" class="btn btn-primary" id="btn-next">Next</button>
                            <button type="submit" class="btn btn-success" id="btn-submit" style="display:none">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('library/select2/dist/js/select2.full.min.js') }}"></script>
<script>
(function() {
    const materials = @json($materials ?? []);
    const oldItems = @json(old('items', []));
    let currentStep = 1;
    let itemIndex = 0;

    function showStep(n) {
        ['#step-1','#step-2','#step-3'].forEach(sel => $(sel).hide());
        $('#step-' + n).show();
        $('#btn-back').toggle(n > 1);
        $('#btn-next').toggle(n < 3);
        $('#btn-submit').toggle(n === 3);
        if (n === 3) buildReview();
        currentStep = n;
    }

    function recipeEnabled() {
        return $('#recipe_enabled').is(':checked');
    }

    function toggleRecipeFields() {
        $('#recipe-fields').toggle(recipeEnabled());
    }

    function materialOptions(selectedId) {
        return materials.map(m => `<option value="${m.id}" ${selectedId == m.id ? 'selected' : ''}>${m.name}</option>`).join('');
    }

    function addItemRow(data = {}) {
        const idx = itemIndex++;
        const row = $(`
            <tr data-index="${idx}">
                <td>
                    <select name="items[${idx}][raw_material_id]" class="form-control material-select" style="width:100%">
                        <option value="">Choose material</option>
                        ${materialOptions(data.raw_material_id || '')}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.0001" class="form-control" name="items[${idx}][qty_per_yield]" value="${data.qty_per_yield || ''}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control" name="items[${idx}][waste_pct]" value="${data.waste_pct ?? ''}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                </td>
            </tr>
        `);
        $('#items-body').append(row);
        row.find('.material-select').select2({ width: '100%' });
    }

    function buildReview() {
        const name = $('input[name=name]').val();
        const price = $('input[name=price]').val();
        const stock = $('input[name=stock]').val();
        const categoryText = $('select[name=category_id] option:selected').text();
        let html = `<div class="mb-2"><strong>Name:</strong> ${name || '-'}</div>`+
                   `<div class="mb-2"><strong>Price:</strong> ${price || '-'}</div>`+
                   `<div class="mb-2"><strong>Stock:</strong> ${stock || '-'}</div>`+
                   `<div class="mb-2"><strong>Category:</strong> ${categoryText || '-'}</div>`;
        if (recipeEnabled()) {
            const y = $('input[name=yield_qty]').val();
            const u = $('input[name=unit]').val();
            html += `<hr><div class="mb-2"><strong>Yield:</strong> ${y || '-'} ${u || ''}</div>`;
            const rows = $('#items-body tr');
            if (rows.length) {
                html += '<div class="mb-2"><strong>Ingredients:</strong></div><ul>';
                rows.each(function() {
                    const mat = $(this).find('select option:selected').text();
                    const q = $(this).find('input[name$="[qty_per_yield]"]').val();
                    const w = $(this).find('input[name$="[waste_pct]"]').val();
                    html += `<li>${mat || '-'} — qty: ${q || '-'}; waste: ${w || '0'}%</li>`;
                });
                html += '</ul>';
            }
        }
        $('#review-content').html(html);
    }

    $(function() {
        $('.select2').select2();
        $('#recipe_enabled').on('change', toggleRecipeFields);
        toggleRecipeFields();

        $('#add-item-btn').on('click', function() { addItemRow(); });
        $('#items-body').on('click', '.remove-item', function() { $(this).closest('tr').remove(); });

        if (oldItems.length) {
            oldItems.forEach(i => addItemRow(i));
            if ($('#recipe_enabled').length) $('#recipe_enabled').prop('checked', true); // ensure shown
            toggleRecipeFields();
        } else {
            // Start with one empty row when recipe is enabled
            if (recipeEnabled()) addItemRow();
        }

        $('#btn-next').on('click', function() { showStep(Math.min(3, currentStep + 1)); });
        $('#btn-back').on('click', function() { showStep(Math.max(1, currentStep - 1)); });

        showStep(1);
    });
})();
</script>
@endpush
