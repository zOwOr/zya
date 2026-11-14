@once
<style>
    /* Redesign moderno unificado para filtros de Financieras (4 secciones) */
    .fin-section-header {
        margin-bottom: 1rem;
    }
    .fin-section-title {
        color: #0b4a86;
        font-weight: 700;
        font-size: 1.15rem;
        letter-spacing: -0.01em;
    }
    .fin-section-title.fin-section-title-danger {
        color: #b91c1c;
    }
    .fin-section-subtitle {
        color: #64748b;
        font-size: 0.8125rem;
        margin-bottom: 0;
    }

    .fin-filter-shell {
        background: #f4f7fb;
        border: 1px solid #dce5f0;
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 1.15rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }

    .fin-filter-label {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        min-height: 18px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #334155;
        margin-bottom: 6px;
        line-height: 1.25;
    }
    .fin-filter-label i {
        font-size: 12px;
    }
    .fin-ico-search { color: #0284c7; }
    .fin-ico-type   { color: #ea580c; }
    .fin-ico-bank   { color: #0284c7; }
    .fin-ico-branch { color: #16a34a; }
    .fin-ico-status { color: #dc2626; }
    .fin-ico-seller { color: #4f46e5; }
    .fin-ico-brand  { color: #64748b; }
    .fin-ico-model  { color: #d97706; }
    .fin-ico-supp   { color: #be123c; }
    .fin-ico-tag    { color: #0266c8; }

    .fin-filter-control,
    .fin-filter-shell .form-control.fin-filter-control,
    .fin-filter-shell .custom-select.fin-filter-control {
        height: 38px;
        min-height: 38px;
        line-height: 1.4;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #1e293b;
        font-size: 13px;
        font-weight: 500;
        padding: 0.375rem 0.75rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .fin-filter-shell select.fin-filter-control,
    .fin-filter-shell select.custom-select.fin-filter-control {
        padding-top: 0;
        padding-bottom: 0;
        padding-left: 0.75rem;
        padding-right: 2rem;
        line-height: 36px;
    }
    .fin-filter-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    .fin-filter-control:focus,
    .fin-filter-shell .form-control.fin-filter-control:focus,
    .fin-filter-shell .custom-select.fin-filter-control:focus {
        border-color: #0266c8;
        box-shadow: 0 0 0 3px rgba(2, 102, 200, 0.12);
        outline: none;
    }
    .fin-filter-control[readonly] {
        background-color: #f8fafc;
        color: #475569;
        border-style: dashed;
        cursor: default;
    }

    /* Grupo input + botón limpiar dentro de filtros */
    .fin-filter-input-group .fin-filter-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .fin-filter-input-group .fin-input-clear-btn {
        height: 38px;
        border: 1px solid #cbd5e1;
        border-left: none;
        border-radius: 0 8px 8px 0;
        background: #ffffff;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 11px;
        transition: all 0.15s ease;
    }
    .fin-filter-input-group .fin-input-clear-btn:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Integración Select2 múltiple/simple con controles modernos (v4.1.0-rc.0) */
    .fin-filter-shell .select2-container {
        width: 100% !important;
        display: block !important;
    }
    .fin-filter-shell .select2-multi-sm + .select2-container .select2-selection--multiple,
    .fin-filter-shell .select2-container--default .select2-selection--multiple {
        height: auto !important;
        min-height: 38px !important;
        line-height: 1.4 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 4px 10px !important;
        background-color: #ffffff !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .fin-filter-shell .select2-container--default.select2-container--focus .select2-selection--multiple,
    .fin-filter-shell .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #0266c8 !important;
        box-shadow: 0 0 0 3px rgba(2, 102, 200, 0.12) !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
        width: auto !important;
        max-width: 100% !important;
        min-height: 0 !important;
        line-height: 1.35 !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__rendered:empty {
        display: none !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__clear {
        float: none !important;
        order: 99 !important;
        margin: 0 0 0 auto !important;
        padding: 0 4px !important;
        height: 24px !important;
        line-height: 24px !important;
        color: #94a3b8 !important;
        font-size: 16px !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__clear:hover {
        color: #dc2626 !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__choice {
        float: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        background-color: #0266c8 !important;
        border: 1px solid #0266c8 !important;
        color: #ffffff !important;
        border-radius: 6px !important;
        padding: 1px 7px !important;
        margin: 0 !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        line-height: 1.35 !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        position: static !important;
        border: none !important;
        color: rgba(255, 255, 255, 0.85) !important;
        padding: 0 2px 0 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        background: transparent !important;
        color: #ffffff !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-search--inline {
        float: none !important;
        display: flex !important;
        align-items: center !important;
        flex: 1 1 50px !important;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 28px !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__rendered:not(:empty) ~ .select2-search--inline {
        height: 22px !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-selection__rendered:not(:empty) ~ .select2-search--inline .select2-search__field {
        height: 22px !important;
        min-height: 22px !important;
        line-height: 22px !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
        width: 100% !important;
        height: 28px !important;
        min-height: 28px !important;
        line-height: 28px !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        box-shadow: none !important;
        resize: none !important;
        overflow: hidden !important;
        white-space: nowrap !important;
    }
    .fin-filter-shell .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field::placeholder {
        color: #64748b !important;
        opacity: 1 !important;
    }
    .fin-filter-shell .form-control-sm + .select2-container .select2-selection--single,
    .fin-filter-shell .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0.375rem 0.75rem !important;
    }

    /* Tarjeta blanca interior (Periodo de búsqueda y barras secundarias) */
    .fin-filter-subcard {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        margin-top: 12px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .fin-filter-subcard.fin-filter-subcard-compact {
        padding: 12px 16px;
    }

    .fin-subcard-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }
    .fin-subcard-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #0f2942;
    }
    .fin-subcard-title i {
        font-size: 15px;
        color: #1e3a5f;
    }

    .fin-mode-inline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .fin-mode-inline-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin: 0;
        white-space: nowrap;
    }
    .fin-mode-select,
    .fin-filter-shell select.fin-mode-select.custom-select {
        width: auto;
        min-width: 175px;
        height: 34px !important;
        min-height: 34px !important;
        line-height: 32px !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        padding: 0 2rem 0 0.7rem !important;
        background-color: #f8fafc !important;
    }

    /* Botones táctiles de periodo (Mes actual / Mes anterior / Todo) */
    .fin-period-presets {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }
    .fin-period-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        height: 48px;
        padding: 0 16px;
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #0f2942;
        font-size: 14.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.16s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .fin-period-btn i {
        font-size: 18px;
        color: #1e3a5f;
        transition: color 0.16s ease;
    }
    .fin-period-btn:hover:not(.is-active) {
        border-color: #0266c8;
        background: #f0f7ff;
        color: #0266c8;
    }
    .fin-period-btn:hover:not(.is-active) i {
        color: #0266c8;
    }
    .fin-period-btn.is-active {
        background: #0266c8;
        border-color: #0266c8;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(2, 102, 200, 0.24);
    }
    .fin-period-btn.is-active i {
        color: #ffffff;
    }

    /* Barra inferior del periodo / acciones */
    .fin-period-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .fin-date-inputs-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        flex: 1 1 auto;
    }
    .fin-inline-field {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .fin-inline-field-label {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
    }
    .fin-date-input {
        width: 162px;
    }
    .fin-seller-inline {
        min-width: 220px;
        flex: 1 1 245px;
        max-width: 300px;
    }
    .fin-seller-inline .fin-filter-control {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }
    .fin-todos-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        border-radius: 8px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 12.5px;
        font-weight: 600;
    }

    /* Botones Filtrar / Limpiar */
    .fin-actions-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
    }
    .fin-filter-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        height: 38px;
        padding: 0 22px;
        min-width: 128px;
        border-radius: 8px;
        border: 1px solid #0266c8;
        background: #0266c8;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.16s ease;
        box-shadow: 0 3px 8px rgba(2, 102, 200, 0.22);
    }
    .fin-filter-submit:hover,
    .fin-filter-submit:focus {
        background: #0154a6;
        border-color: #0154a6;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .fin-filter-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 38px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.15s ease;
    }
    .fin-filter-reset:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    /* Resumen de filtros activos (Pills) */
    .fin-active-filters-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }
    .fin-active-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 500;
    }
    .fin-active-pill a {
        color: #dc2626;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        margin-left: 2px;
    }

    /* Grid superior de 5 columnas en escritorio para Ventas */
    .fin-grid-row {
        margin-left: -6px;
        margin-right: -6px;
    }
    .fin-grid-row > [class*="col-"] {
        padding-left: 6px;
        padding-right: 6px;
    }

    @media (max-width: 767.98px) {
        .fin-filter-shell {
            padding: 14px;
        }
        .fin-period-presets {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .fin-period-btn {
            height: 42px;
            font-size: 14px;
        }
        .fin-date-input {
            width: 100%;
        }
        .fin-inline-field,
        .fin-seller-inline,
        .fin-actions-wrap {
            width: 100%;
        }
        .fin-actions-wrap {
            justify-content: stretch;
            margin-left: 0;
        }
        .fin-filter-submit {
            flex: 1 1 auto;
        }
    }
</style>
@endonce
