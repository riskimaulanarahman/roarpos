
@php
    $isEdit = isset($product) && $product;
    $currentRecipe = $recipe ?? null;
    $rawRows = old('recipe');
    if (is_null($rawRows)) {
        $rawRows = $currentRecipe?->items?->map(fn($item) => [
            'raw_material_id' => $item->raw_material_id,
            'qty_per_yield' => (float) $item->qty_per_yield,
            'waste_pct' => (float) $item->waste_pct,
        ])->toArray() ?? [];
    }
    if (! is_array($rawRows)) {
        $rawRows = [];
    }
    if (empty($rawRows)) {
        $rawRows[] = ['raw_material_id' => null, 'qty_per_yield' => null, 'waste_pct' => 0];
    }
@endphp

@once
    @push('style')
        <style>
            .wizard-stepper { display: flex; flex-wrap: wrap; gap: .75rem; }
            .wizard-stepper .step-btn {
                border-radius: 999px;
                padding: .6rem 1.4rem;
                border: 1px solid rgba(63,82,120,.2);
                background: #fff;
                color: #6777ef;
                display: flex;
                align-items: center;
                font-weight: 600;
                transition: all .2s ease;
            }
            .wizard-stepper .step-btn .step-index {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.8rem;
                height: 1.8rem;
                border-radius: 50%;
                margin-right: .6rem;
                background: rgba(103,119,239,.15);
            }
            .wizard-stepper .step-btn.active {
                background: #6777ef;
                color: #fff;
                box-shadow: 0 4px 14px rgba(103,119,239,.35);
            }
            .wizard-stepper .step-btn.active .step-index {
                background: rgba(255,255,255,.25);
            }
            .wizard-stepper .step-btn.completed {
                border-color: #6777ef;
                color: #6777ef;
            }
            .wizard-step { display: none; }
            .wizard-step.active { display: block; }
            .wizard .table td { vertical-align: middle; }
            .wizard .table-danger td { background: rgba(252,129,152,.1) !important; }
        </style>
    @endpush
@endonce

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Periksa kembali isian Anda.</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="wizard" data-wizard>
    <div class="wizard-stepper mb-4">
        <button type="button" class="step-btn active" data-step-target="1">
            <span class="step-index">1</span>
            <span>Detail Produk</span>
        </button>
        <button type="button" class="step-btn" data-step-target="2">
            <span class="step-index">2</span>
            <span>Resep (Opsional)</span>
        </button>
        <button type="button" class="step-btn" data-step-target="3">
            <span class="step-index">3</span>
            <span>Pratinjau</span>
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="wizard-step active" data-step="1">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="form-group">
                            <label class="font-weight-semibold">Nama Produk</label>
                            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', optional($product)->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-semibold">Kategori</label>
                                <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (string) old('category_id', optional($product)->category_id) === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-semibold">Harga Jual</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', optional($product)->price) }}" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="form-group">
                            <label class="font-weight-semibold d-flex justify-content-between align-items-center">
                                Foto Produk
                                <small class="text-muted font-italic">PNG/JPG maks 2 MB</small>
                            </label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input @error('image') is-invalid @enderror" id="product-image">
                                <label class="custom-file-label" for="product-image">Pilih file…</label>
                                @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @if($isEdit && $product?->image)
                                <small class="form-text text-muted mt-2">Biarkan kosong apabila tidak ingin mengubah foto. Saat ini: {{ $product->image }}</small>
                            @endif
                        </div>
                        <div class="alert alert-info mb-0">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-info-circle fa-lg"></i>
                                </div>
                                <div>
                                    Stok produk akan dihitung otomatis dari persediaan bahan baku pada langkah resep.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-step" data-step="2">
                <div class="alert alert-secondary d-flex align-items-center">
                    <i class="fas fa-leaf fa-lg mr-2 text-success"></i>
                    <div>
                        Langkah ini opsional. Tambahkan komposisi bahan jika ingin memantau HPP dan stok berbasis bahan baku.
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm" data-recipe-table>
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 45%">Bahan</th>
                                <th style="width: 25%">Takaran</th>
                                <th style="width: 20%">Satuan</th>
                                <th style="width: 10%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawRows as $index => $row)
                                <tr data-row-index="{{ $index }}">
                                    <td>
                                        <select name="recipe[{{ $index }}][raw_material_id]" class="form-control" data-material-select>
                                            <option value="">Pilih bahan</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" data-name="{{ $material->name }}" data-unit="{{ $material->unit }}" data-cost="{{ $material->unit_cost }}" data-stock="{{ $material->stock_qty }}" {{ (string) ($row['raw_material_id'] ?? '') === (string) $material->id ? 'selected' : '' }}>
                                                    {{ $material->name }} &middot; Stok: {{ number_format($material->stock_qty, 2) }} {{ $material->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" class="form-control" name="recipe[{{ $index }}][qty_per_yield]" value="{{ $row['qty_per_yield'] ?? '' }}" placeholder="Contoh: 50">
                                    </td>
                                    <td>
                                        <span class="badge badge-light" data-unit-label>—</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-danger small d-none" data-recipe-error></span>
                    <button type="button" class="btn btn-outline-primary" data-add-row><i class="fas fa-plus mr-1"></i>Tambah Bahan</button>
                </div>
            </div>

            <div class="wizard-step" data-step="3">
                <div class="row">
                    <div class="col-lg-5 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-3">Ringkasan Produk</h6>
                                <div class="mb-3">
                                    <div class="text-muted">Nama</div>
                                    <div class="font-weight-semibold" id="preview-name">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Kategori</div>
                                    <div class="font-weight-semibold" id="preview-category">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Harga Jual</div>
                                    <div class="font-weight-semibold" id="preview-price">—</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted">Estimasi Biaya per Unit</div>
                                    <div class="font-weight-semibold text-primary" id="preview-hpp">—</div>
                                </div>
                                <div>
                                    <div class="text-muted">Estimasi Produksi dari Stok Bahan</div>
                                    <div class="font-weight-semibold" id="preview-stock">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-muted text-uppercase mb-0">Komposisi Bahan</h6>
                                    <span class="badge badge-light" id="preview-total-items">0 bahan</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" id="preview-table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Bahan</th>
                                                <th>Takaran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-muted">
                                                <td colspan="2">Belum ada bahan dipilih.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center bg-light">
            <button type="button" class="btn btn-light" data-action="prev" disabled><i class="fas fa-arrow-left mr-1"></i>Sebelumnya</button>
            <div>
                <button type="button" class="btn btn-primary" data-action="next">Berikutnya<i class="fas fa-arrow-right ml-2"></i></button>
                <button type="submit" class="btn btn-success d-none" data-action="submit"><i class="fas fa-save mr-1"></i>Simpan Produk</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const wizard = document.querySelector('[data-wizard]');
                if (!wizard) {
                    return;
                }

                const stepButtons = Array.from(wizard.querySelectorAll('.step-btn'));
                const steps = Array.from(wizard.querySelectorAll('[data-step]'));
                const prevBtn = wizard.querySelector('[data-action="prev"]');
                const nextBtn = wizard.querySelector('[data-action="next"]');
                const submitBtn = wizard.querySelector('[data-action="submit"]');
                const recipeTableBody = wizard.querySelector('[data-recipe-table] tbody');
                const recipeError = wizard.querySelector('[data-recipe-error]');
                let currentStep = 1;
                let rowIndex = recipeTableBody.querySelectorAll('tr').length;

                const formatCurrency = (value) => {
                    const numeric = Number(value);
                    if (Number.isNaN(numeric)) {
                        return '—';
                    }
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(numeric);
                };

                const setUnitLabel = (row) => {
                    const select = row.querySelector('[data-material-select]');
                    const option = select?.options[select.selectedIndex];
                    const label = row.querySelector('[data-unit-label]');
                    label.textContent = option && option.dataset.unit ? option.dataset.unit : '—';
                };

                const cleanRowHighlight = () => {
                    recipeTableBody.querySelectorAll('tr').forEach(row => row.classList.remove('table-danger'));
                };

                const ensureAtLeastOneRow = () => {
                    if (!recipeTableBody.querySelector('tr')) {
                        addRow();
                    }
                };

                const addRow = () => {
                    const template = recipeTableBody.querySelector('tr');
                    const clone = template ? template.cloneNode(true) : document.createElement('tr');
                    const index = rowIndex++;
                    if (!template) {
                        clone.innerHTML = `
                            <td>
                                <select name="recipe[${index}][raw_material_id]" class="form-control" data-material-select>
                                    <option value="">Pilih bahan</option>
                                    ${wizard.querySelector('[data-material-select]')?.innerHTML ?? ''}
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0" class="form-control" name="recipe[${index}][qty_per_yield]" placeholder="Contoh: 50">
                            </td>
                            <td><span class="badge badge-light" data-unit-label>—</span></td>
                            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>&times;</button></td>`;
                    } else {
                        clone.dataset.rowIndex = index;
                        clone.querySelectorAll('select, input').forEach(el => {
                            el.name = el.name.replace(/recipe\[[0-9]+\]/, `recipe[${index}]`);
                            if (el.tagName === 'SELECT') {
                                el.selectedIndex = 0;
                            } else {
                                el.value = '';
                            }
                        });
                        clone.querySelector('[data-unit-label]').textContent = '—';
                    }
                    recipeTableBody.appendChild(clone);
                };

                const validateStep = (step) => {
                    if (step === 1) {
                        const stepContainer = steps.find(el => Number(el.dataset.step) === step);
                        const inputs = stepContainer.querySelectorAll('input, select');
                        for (const input of inputs) {
                            if (!input.checkValidity()) {
                                input.reportValidity();
                                return false;
                            }
                        }
                        return true;
                    }

                    if (step === 2) {
                        cleanRowHighlight();
                        if (recipeError) {
                            recipeError.classList.add('d-none');
                        }
                        let invalid = false;
                        recipeTableBody.querySelectorAll('tr').forEach(row => {
                            const select = row.querySelector('[data-material-select]');
                            const qtyInput = row.querySelector('input[name*="[qty_per_yield]"]');
                            const hasMaterial = !!select && select.value !== '';
                            const qtyValue = qtyInput ? parseFloat(qtyInput.value) : NaN;
                            const hasQty = !Number.isNaN(qtyValue) && qtyValue > 0;
                            const typedQty = qtyInput && qtyInput.value.trim() !== '';

                            if (hasMaterial || typedQty) {
                                if (!hasMaterial || !hasQty) {
                                    invalid = true;
                                    row.classList.add('table-danger');
                                }
                            }
                        });

                        if (invalid) {
                            if (recipeError) {
                                recipeError.textContent = 'Lengkapi bahan dan takaran yang terisi atau bersihkan barisnya terlebih dahulu.';
                                recipeError.classList.remove('d-none');
                            }
                            return false;
                        }
                        return true;
                    }

                    return true;
                };

                const showStep = (step) => {
                    if (step < 1 || step > steps.length) {
                        return;
                    }
                    currentStep = step;
                    steps.forEach(el => el.classList.toggle('active', Number(el.dataset.step) === currentStep));
                    stepButtons.forEach(btn => {
                        const target = Number(btn.dataset.stepTarget);
                        btn.classList.toggle('active', target === currentStep);
                        btn.classList.toggle('completed', target < currentStep);
                    });
                    prevBtn.disabled = currentStep === 1;
                    nextBtn.classList.toggle('d-none', currentStep === steps.length);
                    submitBtn.classList.toggle('d-none', currentStep !== steps.length);
                    if (currentStep === steps.length) {
                        renderPreview();
                    }
                };

                const renderPreview = () => {
                    const form = wizard.closest('form');
                    const name = form.querySelector('input[name="name"]').value || '—';
                    const categorySelect = form.querySelector('select[name="category_id"]');
                    const category = categorySelect?.options[categorySelect.selectedIndex]?.text?.trim() || '—';
                    const price = form.querySelector('input[name="price"]').value;
                    const previewTable = document.querySelector('#preview-table tbody');
                    const totalItemsBadge = document.querySelector('#preview-total-items');
                    previewTable.innerHTML = '';

                    let totalCost = 0;
                    let projectedUnits = null;
                    let itemCount = 0;

                    recipeTableBody.querySelectorAll('tr').forEach(row => {
                        const select = row.querySelector('[data-material-select]');
                        const option = select?.options[select.selectedIndex];
                        const qtyInput = row.querySelector('input[name*="[qty_per_yield]"]');
                        const qty = qtyInput ? parseFloat(qtyInput.value) : NaN;
                        if (!option || !option.value || Number.isNaN(qty) || qty <= 0) {
                            return;
                        }
                        const materialName = option.dataset.name || option.textContent.trim();
                        const unitLabel = option.dataset.unit || '-';
                        const avgCost = parseFloat(option.dataset.cost || '0');
                        const stockQty = parseFloat(option.dataset.stock || '0');
                        const yieldQty = 1; // default per-unit
                    const adjustedQty = qty; // waste tidak digunakan
                    totalCost += adjustedQty * avgCost;

                    const perUnitNeed = adjustedQty / yieldQty;
                        if (perUnitNeed > 0) {
                            const possible = Math.floor(stockQty / perUnitNeed);
                            projectedUnits = projectedUnits === null ? possible : Math.min(projectedUnits, possible);
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${materialName}</td><td>${qty.toFixed(1)} ${unitLabel}</td>`;
                        previewTable.appendChild(tr);
                        itemCount += 1;
                    });

                    if (itemCount === 0) {
                        const tr = document.createElement('tr');
                        tr.classList.add('text-muted');
                        tr.innerHTML = '<td colspan="2">Belum ada bahan dipilih.</td>';
                        previewTable.appendChild(tr);
                    }

                    const hpp = itemCount === 0 ? null : totalCost; // default yield 1

                    document.querySelector('#preview-name').textContent = name;
                    document.querySelector('#preview-category').textContent = category;
                    document.querySelector('#preview-price').textContent = formatCurrency(price || 0);
                    document.querySelector('#preview-hpp').textContent = hpp !== null ? formatCurrency(hpp) : '—';
                    document.querySelector('#preview-stock').textContent = projectedUnits !== null ? projectedUnits : '—';
                    totalItemsBadge.textContent = itemCount ? `${itemCount} bahan` : '0 bahan';
                };

                recipeTableBody.querySelectorAll('tr').forEach(setUnitLabel);

                recipeTableBody.addEventListener('change', (event) => {
                    if (event.target.matches('[data-material-select]')) {
                        setUnitLabel(event.target.closest('tr'));
                    }
                });

                recipeTableBody.addEventListener('click', (event) => {
                    if (event.target.closest('[data-remove-row]')) {
                        const rows = Array.from(recipeTableBody.querySelectorAll('tr'));
                        if (rows.length > 1) {
                            event.target.closest('tr').remove();
                        } else {
                            const row = rows[0];
                            row.querySelectorAll('select, input').forEach(el => {
                                if (el.tagName === 'SELECT') {
                                    el.selectedIndex = 0;
                                } else {
                                    el.value = '';
                                }
                            });
                            row.querySelector('[data-unit-label]').textContent = '—';
                        }
                        cleanRowHighlight();
                    }
                });

                wizard.querySelector('[data-add-row]').addEventListener('click', () => {
                    addRow();
                });

                nextBtn.addEventListener('click', () => {
                    if (!validateStep(currentStep)) {
                        return;
                    }
                    showStep(currentStep + 1);
                });

                prevBtn.addEventListener('click', () => {
                    showStep(currentStep - 1);
                });

                stepButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const target = Number(btn.dataset.stepTarget);
                        if (target === currentStep) {
                            return;
                        }
                        if (target > currentStep && !validateStep(currentStep)) {
                            return;
                        }
                        showStep(target);
                    });
                });

                ensureAtLeastOneRow();
            });
        </script>
    @endpush
@endonce
