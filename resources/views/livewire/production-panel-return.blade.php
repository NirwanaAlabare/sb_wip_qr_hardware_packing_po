@push('styles')
    <style>
        #product-po + .select2-container .select2-selection__placeholder {
            color: #000 !important;
        }
    </style>
@endpush

<div class="p-3">
    <div class="col-md-12">
        <div class="card h-100">

            <div class="card-header d-flex justify-content-between align-items-center bg-reject text-light">
                <p class="mb-0 fs-5">PACKING LINE RETURN</p>
            </div>

            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">PO</label>
                            <div wire:ignore>
                                <select id="product-po" class="select2 form-select-sm"></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPo') {{ $message }} @enderror
                            </div>
                        </div>
    
                        <div class="col-md-6">
                            <label class="form-label">Worksheet - Style</label>
                            <div wire:ignore>
                                <select class="select2 form-select-sm" id="product-po-ws" ></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPoWs') {{ $message }} @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Color</label>
                            <div wire:ignore>
                                <select class="select2 form-select-sm" id="product-po-color" ></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPoColor') {{ $message }} @enderror
                            </div>
                        </div>
    
                        <div class="col-md-3">
                            <label class="form-label">Size</label>
                            <div wire:ignore>
                                <select class="select2 form-select-sm" id="product-po-size" ></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPoSize') {{ $message }} @enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">QTY Order</label>
                            <input type="text" class="form-control" id="qty_order" readonly>
                        </div>
    
                        <div class="col-md-3">
                            <label class="form-label">TOT QTY IN</label>
                            <input type="number" class="form-control" id="tot_qty_in" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Packing Line</label>
                            <div wire:ignore>
                                <select class="select2 form-select-sm" id="product-po-packing-line" ></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPoPackingLine') {{ $message }} @enderror
                            </div>
                        </div>
    
                        <div class="col-md-3">
                            <label class="form-label">QTY Packing Line</label>
                            <input type="number" id="product-po-qty-packing-line" class="form-control" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">QTY Return</label>
                            <input type="number" id="qty_return" class="form-control @error('qtyReturn') is-invalid @enderror" wire:model.defer="qtyReturn">
                            @error('qtyReturn') 
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div> 
                            @enderror
                        </div>
    
                        <div class="col-md-3">
                            <label class="form-label">Line QC Finishing</label>
                            <div wire:ignore>
                                <select class="select2 form-select-sm" id="product-po-finishing-line" ></select>
                            </div>

                            <div class="invalid-feedback d-block">
                                @error('selectedPoFinishingLine') {{ $message }} @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="row justify-content-center">
                            <div class="col-12 col-md-6">
                                <button type="submit" class="btn btn-success w-100">
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12 mt-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-reject text-light">
                <p class="mb-0 fs-5">SUMMARY</p>
                <div class="d-flex justify-content-end align-items-center gap-1">
                </div>
            </div>
            <div class="card-body table-responsive">
                {{-- <div class="d-flex justify-content-center align-items-center">
                    <input type="text" class="form-control mb-3 rounded-0" id="search-summary" name="search-summary" wire:model='searchSummary' placeholder="Search here...">
                </div> --}}
                <div class="d-flex justify-content-between align-items-end gap-2 mb-3">
                    <div class="d-flex gap-3">
                        <div>
                            <label class="form-label mb-1">Tanggal Awal</label>
                            <input 
                                type="date" 
                                class="form-control rounded-0"
                                wire:model="startDate"
                            >
                        </div>
                        <div>
                            <label class="form-label mb-1">Tanggal Akhir</label>
                            <input 
                                type="date" 
                                class="form-control rounded-0"
                                wire:model="endDate"
                            >
                        </div>

                    </div>

                    <div style="width: 300px;">
                        <input 
                            type="text" 
                            class="form-control rounded-0" 
                            wire:model="searchSummary" 
                            placeholder="Search here..."
                        >
                    </div>
                </div>
                <table class="table table-bordered text-center align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Tanggal</th>
                            <th>QTY Return</th>
                            <th>QC Check</th>
                            <th>BLC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($summary->count() < 1)
                            <tr>
                                <td colspan='5'>Summary tidak ditemukan</td>
                            </tr>
                        @else
                            @foreach ($summary as $row)
                                <tr>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click="openModal('{{ $row->tanggal }}')">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </td>
                                    <td>{{ $row->tanggal }}</td>
                                    <td>{{ $row->qty_return }}</td>
                                    <td>0</td>
                                    <td>0</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <div class="mt-2">
                    {{ $summary->links() }}
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Summary - {{ $selectedTanggal }} </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Packing Line</th>
                                    <th>PO</th>
                                    <th>Worksheet</th>
                                    <th>Style</th>
                                    <th>Color</th>
                                    <th>Size</th>
                                    <th>Qty Return</th>
                                    <th>Qty Cek QC</th>
                                    <th>QC Line</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($modalDetails ?? [] as $detail)
                                    <tr>
                                        <td>{{ $detail->tanggal }}</td>
                                        <td>{{ $detail->packing_line }}</td>
                                        <td>{{ $detail->po }}</td>
                                        <td>{{ $detail->worksheet }}</td>
                                        <td>{{ $detail->style }}</td>
                                        <td>{{ $detail->color }}</td>
                                        <td>{{ $detail->size }}</td>
                                        <td>{{ $detail->qty_return }}</td>
                                        <td>{{ $detail->qty_cek_qc }}</td>
                                        <td>{{ $detail->qc_line }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">Data tidak ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        @if($modalDetails)
                            <div class="mt-2">
                                {{ $modalDetails->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // getPo();
            resetWs();
            resetColor();
            resetSize();
            resetLine();
        });

        function initSelect2() {
            $('#product-po').select2({
                theme: "bootstrap-5",
                placeholder: '-- Pilih PO --',
                allowClear: false,
                minimumInputLength: 1,
                width: '100%',
                ajax: {
                    url: "{{ route('get-po-return') }}",
                    dataType: 'json',
                    delay: 3000,
                    data: function(params) {
                        return {
                            search: params.term,
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(item => ({
                                id: item.po,
                                text: item.po,
                                qty_order: item.qty_order,
                                po: item.po,
                                po_id: item.id
                            }))
                        };
                    }
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            initSelect2();
        });

        document.addEventListener("livewire:load", function () {
            Livewire.hook('message.processed', (message, component) => {
                initSelect2();
            });
        });

        Livewire.on('resetSelect2', () => {
            resetPo();
            resetWs();
            resetColor();
            resetSize();
            resetLine();

            $("#qty_return").val("");
        });

        Livewire.on('afterSave', () => {
            getPo();
            resetWs();
            resetColor();
            resetSize();
            resetLine();
        });

        Livewire.on('reloadPage', () => {
            location.reload();
        });
        
        Livewire.on('openModal', () => {
            const modalEl = document.getElementById('returnModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        document.addEventListener("livewire:load", () => {
            const selectIds = [
                'product-po',
                'product-po-ws',
                'product-po-color',
                'product-po-size',
                'product-po-packing-line',
                'product-po-finishing-line'
            ];

            Livewire.hook('message.processed', (message, component) => {
                selectIds.forEach(id => {
                    const el = $('#' + id);

                    const errorDiv = el.closest('.col-md-6, .col-md-3').find('.invalid-feedback');
                    if (errorDiv.text().trim() !== '') {
                        el.addClass('is-invalid');
                    } else {
                        el.removeClass('is-invalid');
                    }
                });
            });
        });

        function getPo() {
            $.ajax({
                type: "get",
                url: "{{ route('get-po-return') }}",
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let poSelect = $('#product-po');
                        poSelect.empty(); 

                        poSelect.append('<option value="" selected disabled>-- Pilih PO --</option>');

                        if (response) {
                            response.forEach(item => {
                                poSelect.append(`<option value="${item.po}" data-id="${item.id}" data-qty_order="${item.qty_order}">${item.po}</option>`);
                            });
                        }

                        poSelect.val(null);
                    }
                }
            });
        }
        
        function getWs(po) {
            $.ajax({
                type: "get",
                url: "{{ route('get-ws-return') }}",
                data: {
                    po: po,
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let wsSelect = $('#product-po-ws');
                        wsSelect.empty(); 

                        wsSelect.append('<option value="" selected disabled>-- Pilih WS --</option>');

                        if (response) {
                            response.forEach(item => {
                                wsSelect.append(`<option value="${item.ws}" data-id="${item.id}" data-kpno="${item.kpno}" data-style="${item.style}">${item.ws}</option>`);
                            });
                        }

                        wsSelect.val(null);
                    }
                }
            });
        }

        function getColor(po, ws) {
            $.ajax({
                type: "get",
                url: "{{ route('get-color-return') }}",
                data: {
                    po: po,
                    ws: ws,
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let colorSelect = $('#product-po-color');
                        colorSelect.empty(); 

                        colorSelect.append('<option value="" selected disabled>-- Pilih Color --</option>');

                        if (response) {
                            response.forEach(item => {
                                colorSelect.append(`<option value="${item.color}" data-id="${item.id}">${item.color}</option>`);
                            });
                        }

                        colorSelect.val(null);
                    }

                }
            });
        }

        function getSize(po, ws, color) {
            $.ajax({
                type: "get",
                url: "{{ route('get-size-return') }}",
                data: {
                    po: po,
                    ws: ws,
                    color: color,
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let sizeSelect = $('#product-po-size');
                        sizeSelect.empty(); 

                        sizeSelect.append('<option value="" selected disabled>-- Pilih Size --</option>');

                        if (response) {
                            response.forEach(item => {
                                sizeSelect.append(`<option value="${item.size}">${item.size}</option>`);
                            });
                        }

                        sizeSelect.val(null);
                    }

                }
            });
        }

        function getPackingLine(po, ws, color, size) {
            $.ajax({
                type: "get",
                url: "{{ route('get-packing-line-return') }}",
                data: {
                    po: po,
                    ws: ws,
                    color: color,
                    size: size,
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let packingLineSelect = $('#product-po-packing-line');
                        packingLineSelect.empty(); 

                        packingLineSelect.append('<option value="" selected disabled>-- Pilih Packing Line --</option>');

                        if (response) {
                            response.forEach(item => {
                                packingLineSelect.append(`<option value="${item.line}">${item.line}</option>`);
                            });
                        }

                        packingLineSelect.val(null);

                        // QTY
                        let totalQty = 0;
                        response.forEach(item => {
                            totalQty += parseInt(item.tot_qty_in);
                        });
                        $('#tot_qty_in').val(totalQty);
                    }

                }
            });
        }

        function getLineQcFinishing(po, ws, color, size) {
            $.ajax({
                type: "get",
                url: "{{ route('get-packing-line-return') }}",
                data: {
                    po: po,
                    ws: ws,
                    color: color,
                    size: size,
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        let finishingLineSelect = $('#product-po-finishing-line');
                        finishingLineSelect.empty(); 

                        finishingLineSelect.append('<option value="" selected disabled>-- Pilih Line QC Finishing --</option>');

                        if (response) {
                            response.forEach(item => {
                                finishingLineSelect.append(`<option value="${item.line}">${item.line}</option>`);
                            });
                        }

                        finishingLineSelect.val(null);
                    }

                }
            });
        }

        function getQtyPackingLine(po, ws, color, size, line) {
            $.ajax({
                type: "get",
                url: "{{ route('get-qty-packing-line-return') }}",
                data: {
                    po: po,
                    ws: ws,
                    color: color,
                    size: size,
                    line: line
                },
                dataType: "json",
                success: function (response) {
                    $('#product-po-qty-packing-line').val(response.qty_packing_line ?? 0);

                    @this.set('qtyPackingLine', response.qty_packing_line);
                    
                }
            });
        }

        $('#product-po').on('select2:select', function (e) {
            let data = e.params.data;

            let selectedPo = data.po;
            let selectedPoId = data.po_id;
            let qtyOrder = data.qty_order;

            $('#qty_order').val(qtyOrder ?? 0);

            @this.set('selectedPo', selectedPo);
            @this.set('selectedPoId', selectedPoId);

            resetWs();
            resetColor();
            resetSize();
            resetLine();

            getWs(selectedPo);
        });

        // $('#product-po').on('change', function (e) {
        //     let selectedPo = $(this).val();
        //     let selectedPoId = $(this).find(':selected').data('id');
        //     let qtyOrder = $(this).find(':selected').data('qty_order');

        //     $('#qty_order').val(qtyOrder ?? 0);

        //     @this.set('selectedPo', selectedPo);
        //     @this.set('selectedPoId', selectedPoId);

        //     // @this.selectedPo = selectedPo;
        //     // @this.selectedPoId = selectedPoId;

        //     resetWs();
        //     resetColor();
        //     resetSize();
        //     resetLine();

        //     getWs(selectedPo);
        // });

        $('#product-po-ws').on('change', function (e) {
            let selectedPo = $('#product-po').val();
            let selectedPoWs = $(this).val();
            let actCostingId = $(this).find(':selected').data('id');
            let kpno = $(this).find(':selected').data('kpno');
            let style = $(this).find(':selected').data('style');

            @this.selectedPoWs = selectedPoWs;
            @this.actCostingId = actCostingId;
            @this.kpno = kpno;
            @this.style = style;

            resetColor();
            resetSize();
            resetLine();

            getColor(selectedPo, selectedPoWs);
        });

        $('#product-po-color').on('change', function (e) {
            let selectedPo = $('#product-po').val();
            let selectedPoWs = $('#product-po-ws').val();
            let selectedPoColor = $(this).val();
            let soDetId = $(this).find(':selected').data('id');

            @this.selectedPoColor = selectedPoColor;
            @this.soDetId = soDetId;

            resetSize();
            resetLine();

            getSize(selectedPo, selectedPoWs, selectedPoColor);
        });

        $('#product-po-size').on('change', function (e) {
            let selectedPo = $('#product-po').val();
            let selectedPoWs = $('#product-po-ws').val();
            let selectedPoColor = $('#product-po-color').val();
            let selectedPoSize = $(this).val();

            @this.selectedPoSize = selectedPoSize;

            resetLine();

            getPackingLine(selectedPo, selectedPoWs, selectedPoColor, selectedPoSize);
            getLineQcFinishing(selectedPo, selectedPoWs, selectedPoColor, selectedPoSize);
        });
        
        $('#product-po-packing-line').on('change', function (e) {
            let selectedPo = $('#product-po').val();
            let selectedPoWs = $('#product-po-ws').val();
            let selectedPoColor = $('#product-po-color').val();
            let selectedPoSize = $('#product-po-size').val();
            let selectedPoPackingLine = $(this).val();

            @this.selectedPoPackingLine = selectedPoPackingLine;

            getQtyPackingLine(selectedPo, selectedPoWs, selectedPoColor, selectedPoSize, selectedPoPackingLine);
        });

        $('#product-po-finishing-line').on('change', function (e) {
            let selectedPoFinishingLine = $(this).val();

            @this.selectedPoFinishingLine = selectedPoFinishingLine;

        });

        function resetPo() {
            let poSelect = $('#product-po');
            poSelect.empty();
            poSelect.append('<option value="" selected disabled>-- Pilih PO --</option>');
            poSelect.val(null).trigger('change.select2');

            $("#qty_order").val('');
        }

        function resetWs() {
            let wsSelect = $('#product-po-ws');
            wsSelect.empty();
            wsSelect.append('<option value="" selected disabled>-- Pilih WS --</option>');
            wsSelect.val(null).trigger('change.select2');
        }

        function resetColor() {
            let colorSelect = $('#product-po-color');
            colorSelect.empty();
            colorSelect.append('<option value="" selected disabled>-- Pilih Color --</option>');
            colorSelect.val(null).trigger('change.select2');
        }

        function resetSize() {
            let sizeSelect = $('#product-po-size');
            sizeSelect.empty();
            sizeSelect.append('<option value="" selected disabled>-- Pilih Size --</option>');
            sizeSelect.val(null).trigger('change.select2');

            $("#tot_qty_in").val('');
        }

        function resetLine() {
            let packingLineSelect = $('#product-po-packing-line');
            packingLineSelect.empty();
            packingLineSelect.append('<option value="" selected disabled>-- Pilih Packing Line --</option>');
            packingLineSelect.val(null).trigger('change.select2');

            let finishingLineSelect = $('#product-po-finishing-line');
            finishingLineSelect.empty();
            finishingLineSelect.append('<option value="" selected disabled>-- Pilih Line QC Finishing --</option>');
            finishingLineSelect.val(null).trigger('change.select2');

            $("#product-po-qty-packing-line").val('');
        }

    </script>
@endpush
