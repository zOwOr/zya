@extends('dashboard.body.main')

@section('specificpagestyles')
<style id="barcodePrintPageStyle">
    @media print {
        @page { size: letter portrait; margin: 10mm; }
    }
</style>
<style>
    .labels-grid-sheet {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .labels-grid-thermal {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .barcode-sticker {
        background: #ffffff;
        border: 1px dashed #94a3b8;
        border-radius: 6px;
        padding: 6px 8px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .barcode-sticker .sticker-branch {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #475569;
        line-height: 1.1;
        margin-bottom: 1px;
    }

    .barcode-sticker .sticker-name {
        font-size: 11px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.15;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 3px;
    }

    .barcode-sticker .sticker-svg {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }

    .barcode-sticker .sticker-svg svg {
        max-width: 100%;
        height: 42px;
        display: block;
    }

    .barcode-sticker .sticker-meta {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2px;
        font-size: 10px;
        font-weight: 700;
        color: #0f172a;
        gap: 6px;
    }

    .barcode-sticker .sticker-code {
        font-family: monospace;
        letter-spacing: 0.5px;
    }

    .barcode-sticker .sticker-price {
        color: #047857;
    }

    .preset-thermal_50x25 .barcode-sticker {
        width: 50mm;
        height: 25mm;
    }

    .preset-thermal_58x30 .barcode-sticker {
        width: 58mm;
        height: 30mm;
    }

    .preset-thermal_40x25 .barcode-sticker {
        width: 40mm;
        height: 25mm;
    }

    .preset-thermal_40x25 .sticker-svg svg {
        height: 32px;
    }

    @media print {
        body, html {
            background: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .iq-sidebar,
        .iq-top-navbar,
        .iq-footer,
        .no-print {
            display: none !important;
        }
        .wrapper,
        .content-page,
        .container-fluid,
        #labelsPrintWrapper {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-height: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
        .barcode-sticker {
            border-color: #cbd5e1;
            border-radius: 0;
        }
        .labels-grid-thermal {
            display: block !important;
        }
        .labels-grid-thermal .barcode-sticker {
            border: 0 !important;
            padding: 1mm 2mm !important;
            margin: 0 !important;
            page-break-after: always;
            break-after: page;
        }
        .labels-grid-thermal .barcode-sticker:last-child {
            page-break-after: auto;
            break-after: auto;
        }
    }
</style>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row no-print mb-3">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="fa-solid fa-barcode text-primary mr-2"></i>Imprimir Etiquetas de Código de Barras
                        </h4>
                        <small class="text-muted">
                            Producto: <strong>{{ $product->product_name }}</strong>
                            | Código: <code>{{ $barcodeCode }}</code>
                            | Sucursal: <strong>{{ $product->branch?->name ?? 'General' }}</strong>
                            | Stock actual: <span class="badge {{ $stockQty > 0 ? 'badge-success' : 'badge-danger' }}">{{ $stockQty }}</span>
                        </small>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <a href="{{ route('products.show', $product->id) }}" class="btn btn-outline-info btn-sm">
                            <i class="ri-eye-line mr-1"></i> Ver Producto
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Catálogo
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-5 col-md-6 mb-3">
                            <label class="font-weight-bold d-block">Origen de cantidad a imprimir</label>
                            <div class="btn-group btn-group-toggle w-100 mb-2" data-toggle="buttons">
                                <label class="btn btn-outline-primary {{ $initialMode === 'stock' ? 'active' : '' }}" id="lblModeStock">
                                    <input type="radio" name="qty_mode" value="stock" id="modeStockRadio" {{ $initialMode === 'stock' ? 'checked' : '' }}>
                                    <i class="fa-solid fa-boxes-stacked mr-1"></i> Stock del producto ({{ $stockQty }})
                                </label>
                                <label class="btn btn-outline-primary {{ $initialMode === 'manual' ? 'active' : '' }}" id="lblModeManual">
                                    <input type="radio" name="qty_mode" value="manual" id="modeManualRadio" {{ $initialMode === 'manual' ? 'checked' : '' }}>
                                    <i class="fa-solid fa-pen mr-1"></i> Cantidad Manual
                                </label>
                            </div>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">Etiquetas:</span>
                                </div>
                                <input type="number"
                                       id="labelQuantityInput"
                                       class="form-control font-weight-bold"
                                       min="1"
                                       max="500"
                                       value="{{ $initialQuantity }}"
                                       {{ $initialMode === 'stock' ? 'readonly' : '' }}>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary quick-qty-btn" data-qty="1">1</button>
                                    <button type="button" class="btn btn-outline-secondary quick-qty-btn" data-qty="5">5</button>
                                    <button type="button" class="btn btn-outline-secondary quick-qty-btn" data-qty="10">10</button>
                                    <button type="button" class="btn btn-outline-secondary quick-qty-btn" data-qty="25">25</button>
                                </div>
                            </div>

                            @if ($stockQty <= 0)
                                <div id="zeroStockAlert" class="alert alert-warning py-1 px-2 mt-2 mb-0 small {{ $initialMode === 'stock' ? '' : 'd-none' }}">
                                    El producto tiene stock <strong>0</strong>. Cambia a <strong>Cantidad Manual</strong> para imprimir etiquetas de prueba o reposición.
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-4 col-md-6 mb-3">
                            <label for="labelPresetSelect" class="font-weight-bold">Formato de papel / etiquetadora</label>
                            <select id="labelPresetSelect" class="form-control mb-2">
                                <option value="sheet" {{ $initialPreset === 'sheet' ? 'selected' : '' }}>Hoja Carta / A4 (Cuadrícula 3 columnas)</option>
                                <option value="thermal_50x25" {{ $initialPreset === 'thermal_50x25' ? 'selected' : '' }}>Etiqueta Térmica 50mm x 25mm</option>
                                <option value="thermal_58x30" {{ $initialPreset === 'thermal_58x30' ? 'selected' : '' }}>Etiqueta Térmica 58mm x 30mm</option>
                                <option value="thermal_40x25" {{ $initialPreset === 'thermal_40x25' ? 'selected' : '' }}>Etiqueta Compacta 40mm x 25mm</option>
                            </select>

                            <div class="d-flex flex-wrap" style="gap: 12px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input label-toggle" id="toggleShowName" checked>
                                    <label class="custom-control-label small" for="toggleShowName">Nombre</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input label-toggle" id="toggleShowPrice" checked>
                                    <label class="custom-control-label small" for="toggleShowPrice">Precio (${{ number_format((float) $product->selling_price, 2) }})</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input label-toggle" id="toggleShowBranch">
                                    <label class="custom-control-label small" for="toggleShowBranch">Sucursal</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-12 d-flex flex-column justify-content-end mb-3">
                            <div class="text-muted small mb-2">
                                Vista previa: <strong id="previewCountLabel">{{ $initialQuantity }}</strong> etiqueta(s)
                            </div>
                            <button type="button" id="btnPrintLabels" class="btn btn-primary btn-lg btn-block font-weight-bold shadow-sm">
                                <i class="fa-solid fa-print mr-1"></i> Imprimir Etiquetas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="labelsPrintWrapper">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div id="labelsPrintArea" class="labels-grid-sheet preset-{{ $initialPreset }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="singleBarcodeLabelTemplate">
    <div class="barcode-sticker">
        <div class="sticker-branch d-none">{{ $product->branch?->name ?? 'General' }}</div>
        <div class="sticker-name" title="{{ $product->product_name }}">{{ $product->product_name }}</div>
        <div class="sticker-svg">{!! $barcodeSvg !!}</div>
        <div class="sticker-meta">
            <span class="sticker-code">{{ $barcodeCode }}</span>
            <span class="sticker-price">${{ number_format((float) $product->selling_price, 2) }}</span>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const STOCK_QTY = @json($stockQty);
    let currentMode = @json($initialMode);

    const qtyInput = document.getElementById('labelQuantityInput');
    const presetSelect = document.getElementById('labelPresetSelect');
    const labelsContainer = document.getElementById('labelsPrintArea');
    const labelTpl = document.getElementById('singleBarcodeLabelTemplate');
    const pageStyleEl = document.getElementById('barcodePrintPageStyle');
    const previewCountLabel = document.getElementById('previewCountLabel');
    const zeroStockAlert = document.getElementById('zeroStockAlert');
    const btnPrint = document.getElementById('btnPrintLabels');

    const toggleName = document.getElementById('toggleShowName');
    const togglePrice = document.getElementById('toggleShowPrice');
    const toggleBranch = document.getElementById('toggleShowBranch');

    const PRESET_PAGE_CSS = {
        sheet: '@media print { @page { size: letter portrait; margin: 10mm; } }',
        thermal_50x25: '@media print { @page { size: 50mm 25mm; margin: 0.8mm; } }',
        thermal_58x30: '@media print { @page { size: 58mm 30mm; margin: 1mm; } }',
        thermal_40x25: '@media print { @page { size: 40mm 25mm; margin: 0.8mm; } }'
    };

    function getEffectiveQuantity() {
        if (currentMode === 'stock') {
            return Math.min(500, Math.max(0, STOCK_QTY));
        }
        const parsed = parseInt(qtyInput.value, 10);
        return isNaN(parsed) ? 0 : Math.min(500, Math.max(0, parsed));
    }

    function applyModeUI(mode) {
        currentMode = mode;
        document.getElementById('lblModeStock')?.classList.toggle('active', mode === 'stock');
        document.getElementById('lblModeManual')?.classList.toggle('active', mode === 'manual');

        if (mode === 'stock') {
            qtyInput.value = STOCK_QTY;
            qtyInput.readOnly = true;
            if (zeroStockAlert) zeroStockAlert.classList.toggle('d-none', STOCK_QTY > 0);
        } else {
            qtyInput.readOnly = false;
            if (parseInt(qtyInput.value, 10) <= 0 || isNaN(parseInt(qtyInput.value, 10))) {
                qtyInput.value = 1;
            }
            if (zeroStockAlert) zeroStockAlert.classList.add('d-none');
        }
        renderLabels();
    }

    function renderLabels() {
        const count = getEffectiveQuantity();
        const preset = presetSelect.value || 'sheet';
        const isThermal = preset.startsWith('thermal_');

        if (pageStyleEl) {
            pageStyleEl.textContent = PRESET_PAGE_CSS[preset] || PRESET_PAGE_CSS.sheet;
        }

        labelsContainer.className = (isThermal ? 'labels-grid-thermal ' : 'labels-grid-sheet ') + 'preset-' + preset;
        labelsContainer.innerHTML = '';
        previewCountLabel.textContent = count;

        if (count <= 0) {
            btnPrint.disabled = true;
            labelsContainer.innerHTML = '<div class="alert alert-warning w-100 mb-0">No hay etiquetas para mostrar con cantidad 0. Selecciona "Cantidad Manual" o ajusta el stock.</div>';
            return;
        }

        btnPrint.disabled = false;
        const fragment = document.createDocumentFragment();
        const baseNode = labelTpl.content.firstElementChild;

        for (let i = 0; i < count; i++) {
            const clone = baseNode.cloneNode(true);
            clone.querySelector('.sticker-name')?.classList.toggle('d-none', !toggleName.checked);
            clone.querySelector('.sticker-price')?.classList.toggle('d-none', !togglePrice.checked);
            clone.querySelector('.sticker-branch')?.classList.toggle('d-none', !toggleBranch.checked);
            fragment.appendChild(clone);
        }
        labelsContainer.appendChild(fragment);
    }

    document.getElementById('modeStockRadio')?.addEventListener('change', () => applyModeUI('stock'));
    document.getElementById('modeManualRadio')?.addEventListener('change', () => applyModeUI('manual'));
    document.getElementById('lblModeStock')?.addEventListener('click', () => applyModeUI('stock'));
    document.getElementById('lblModeManual')?.addEventListener('click', () => applyModeUI('manual'));

    document.querySelectorAll('.quick-qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentMode = 'manual';
            const manualRadio = document.getElementById('modeManualRadio');
            if (manualRadio) manualRadio.checked = true;
            qtyInput.value = this.dataset.qty;
            applyModeUI('manual');
        });
    });

    qtyInput.addEventListener('input', function () {
        if (currentMode === 'manual') {
            renderLabels();
        }
    });

    presetSelect.addEventListener('change', renderLabels);
    document.querySelectorAll('.label-toggle').forEach(cb => cb.addEventListener('change', renderLabels));

    btnPrint.addEventListener('click', function () {
        if (getEffectiveQuantity() <= 0) return;
        window.print();
    });

    renderLabels();
});
</script>
@endsection

