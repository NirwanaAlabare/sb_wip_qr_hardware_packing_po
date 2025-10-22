<div>
    <div class="loading-container-fullscreen" wire:loading wire:target="toRft, toDefect, toDefectHistory, toReject, toRework, toProductionPanel, preSubmitUndo, submitUndo, updateOrder, setAndSubmitInput, submitInput, selectedPo, selectedPoId">
        <div class="loading-container">
            <div class="loading"></div>
        </div>
    </div>

    <div class="loading-container-fullscreen d-none" id="loading">
        <div class="loading-container">
            <div class="loading"></div>
        </div>
    </div>

    {{-- No Connection --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert" wire:offline>
        <strong>Koneksi Terputus.</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    {{-- Production Info --}}
    <div class="production-info row row-gap-1 align-items-center mb-3">
        <div class="col-md">
            <div class="mb-1">
                <label class="form-label mb-0">Buyer</label>
                <input type="text" class="form-control form-control-sm" id="buyer-name" value="{{ $orderInfo->buyer_name }}" readonly>
            </div>
        </div>
        <div class="col-md">
            <div class="mb-1">
                <label class="form-label mb-0">WS Number</label>
                <input type="text" class="form-control form-control-sm" id="ws-number" value="{{ $orderInfo->ws_number }}" readonly>
            </div>
        </div>
        <div class="col-md">
            <div class="mb-1">
                <label class="form-label mb-0">Product Type</label>
                <input type="text" class="form-control form-control-sm" id="product-type" value="{{ $orderInfo->product_type }}" readonly>
            </div>
        </div>
        <div class="col-md">
            <div class="mb-1">
                <label class="form-label mb-0">Style</label>
                <input type="text" class="form-control form-control-sm" id="style-name" value="{{ $orderInfo->style_name }}" readonly>
            </div>
        </div>
        <div class="col-md">
            <div class="mb-1" wire:ignore>
                <label class="form-label mb-0">Color</label>
                <select class="select2 form-select-sm" id="product-color" wire:model='selectedColor'>
                    @foreach ($orderWsDetails as $order)
                        <option value="{{ $order->id }}" data-color-name="{{ $order->color }}">{{ $order->id }} - {{ $order->color }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-1" wire:ignore>
                        <label class="form-label mb-0">PO</label>
                        <select class="select2 form-select-sm" id="product-po" >
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row row-gap-1">
                        <div class="col-md-4 mb-1" wire:ignore>
                            <label class="form-label mb-0">Size</label>
                            <select class="select2 form-select-sm" id="product-po-id">
                            </select>
                        </div>
                        <div class="col-md-4 mb-1" wire:ignore>
                            <label class="form-label mb-0">QTY PO</label>
                            <input class="form-control form-control-sm" id="product-po-qty" readonly />
                        </div>
                        <div class="col-md-4 mb-1" wire:ignore>
                            <label class="form-label mb-0">QTY OUTPUT</label>
                            <input class="form-control form-control-sm" id="product-po-output" readonly />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (Auth::user()->line_type == 'multi')
            <div class="d-flex justify-content-center">
                <span class="badge text-bg-success mt-1 mb-0">{{ strtoupper($orderInfo->sewing_line) }}</span>
            </div>
        @endif
    </div>

    {{-- Production Panels --}}
    <div class="production-panel row row-gap-3" id="production-panel">
        @if ($panels)
            <div class="row row-gap-3">
                <div class="col-md-6" id="rft-panel">
                    <div class="d-flex h-100">
                        <div class="card-custom bg-rft d-flex justify-content-between align-items-center w-75 h-100" {{-- onclick="toRft()" --}} wire:click='toRft'>
                            <div class="d-flex flex-column gap-3">
                                <p class="text-light"><i class="fa-regular fa-circle-check fa-2xl"></i></p>
                                <p class="text-light">RFT</p>
                            </div>
                            <p class="text-light fs-1">{{ $outputRft }}</p>
                        </div>
                        <div class="card-custom-footer bg-light w-25 h-100">
                            <div class="d-flex flex-column justify-content-center align-items-stretch h-100 gap-1">
                                <div class="filter multi-item upper h-50 bg-pale">
                                    <div class="d-flex flex-column justify-content-between w-100 h-100">
                                        <select class="form-select" style="border-radius: 0 15px 0 0" wire:model="selectedSize">
                                            <option value="all">All Sizes</option>
                                            @foreach ($orderWsDetailSizes as $order)
                                                <option value="{{ $order->so_det_id }}">{{ $order->size_dest }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-center fs-3 mt-auto mb-auto">{{ $outputFiltered }}</p>
                                    </div>
                                </div>
                                <button type="button" class="reset multi-item lower btn btn-pale h-50">
                                    <i class="fa-regular fa-rotate-left fa-2xl invisible"></i>
                                </button>
                                {{-- <button type="button" class="reset multi-item lower btn btn-pale h-50" wire:click="preSubmitUndo('rft')">
                                    <i class="fa-regular fa-rotate-left fa-2xl"></i>
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-md-6 d-none" id="defect-panel">
                    <div class="d-flex h-100">
                        <div class="card-custom bg-defect d-flex justify-content-between align-items-center w-75 h-100" {{-- onclick="toDefect()" --}} wire:click='toDefect'>
                            <div class="d-flex flex-column gap-3">
                                <p class="text-light"><i class="fa-regular fa-circle-exclamation fa-2xl"></i></p>
                                <p class="text-light">DEFECT</p>
                            </div>
                            <p class="text-light fs-1">{{-- $outputDefect --}}</p>
                        </div>
                        <div class="card-custom-footer bg-light w-25 h-100">
                            <div class="d-flex flex-column justify-content-center align-items-stretch h-100 gap-1">
                                <button class="history multi-item upper btn btn-pale h-50" {{-- onclick="toDefectHistory()" --}} wire:click='toDefectHistory'>
                                    <div class="d-flex flex-column justify-content-center align-items-center w-100 h-100">
                                        <p class="mb-1">HISTORY</p>
                                        <p class="mb-0"><i class="fa-regular fa-clock-rotate-left fa-xl"></i></p>
                                    </div>
                                </button>
                                <button type="button" class="reset multi-item lower btn btn-pale h-50">
                                    <i class="fa-regular fa-rotate-left fa-2xl invisible"></i>
                                </button>
                                {{-- <button type="button" class="reset multi-item lower btn btn-pale h-50" wire:click="preSubmitUndo('defect')">
                                    <div class="d-flex flex-column justify-content-center align-items-center w-100 h-100">
                                        <p class="mb-1">UNDO</p>
                                        <p class="mb-0"><i class="fa-regular fa-rotate-left fa-xl"></i></p>
                                    </div>
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
                -->
                <div class="col-md-6" id="reject-panel">
                    <div class="d-flex h-100">
                        <div class="card-custom bg-reject d-flex justify-content-between align-items-center w-75 h-100" {{-- onclick="toReject()" --}} wire:click='toReject'>
                            <div class="d-flex flex-column gap-3">
                                <p class="text-light"><i class="fa-regular fa-circle-xmark fa-2xl"></i></p>
                                <p class="text-light">REJECT</p>
                            </div>
                            <p class="text-light fs-1">{{ $outputReject }}</p>
                        </div>
                        <div class="card-custom-footer bg-light w-25 h-100">
                            <button type="button" class="reset single-item btn btn-pale w-100 h-100">
                                <i class="fa-regular fa-rotate-left fa-2xl invisible"></i>
                            </button>
                            {{-- <button class="reset single-item btn btn-pale w-100 h-100" wire:click="preSubmitUndo('reject')">
                                <i class="fa-regular fa-rotate-left fa-2xl"></i>
                            </button> --}}
                        </div>
                    </div>
                </div>
                <!--
                <div class="col-md-6 d-none" id="rework-panel">
                    <div class="d-flex h-100">
                        <div class="card-custom bg-rework d-flex justify-content-between align-items-center w-75 h-100" {{-- onclick="toRework()" --}} wire:click='toRework'>
                            <div class="d-flex flex-column gap-3">
                                <p class="text-light"><i class="fa-regular fa-arrows-rotate fa-2xl"></i></p>
                                <p class="text-light">REWORK</p>
                            </div>
                            <p class="text-light fs-1">{{-- $outputRework --}}</p>
                        </div>
                        <div class="card-custom-footer bg-light w-25 h-100">
                            <button type="button" class="reset single-item btn btn-pale w-100 h-100">
                                <i class="fa-regular fa-rotate-left fa-2xl invisible"></i>
                            </button>
                            {{-- <button class="reset single-item btn btn-pale w-100 h-100" wire:click="preSubmitUndo('rework')">
                                <i class="fa-regular fa-rotate-left fa-2xl"></i>
                            </button> --}}
                        </div>
                    </div>
                </div> -->
            </div>
        @endif

        {{-- Rft --}}
        {{-- @if ($rft) --}}
        <div class="{{ $rft ? '' : 'd-none' }}">
            @livewire('rft', ["orderWsDetailSizes" => $orderWsDetailSizes])
        </div>
        {{-- @endif --}}

        {{-- Defect --}}
        {{-- @if ($defect) --}}
        {{-- <div class="{{ $defect ? '' : 'd-none' }}">
            @livewire('defect', ["orderWsDetailSizes" => $orderWsDetailSizes])
        </div> --}}
        {{-- @endif --}}

        {{-- Defect History --}}
        {{-- @if ($defectHistory)
            @livewire('defect-history', ["orderWsDetailSizes" => $orderWsDetailSizes])
        @endif --}}

        {{-- Reject --}}
        {{-- @if ($reject) --}}
        <div class="{{ $reject ? '' : 'd-none' }}">
            @livewire('reject', ["orderWsDetailSizes" => $orderWsDetailSizes])
        </div>
        {{-- @endif --}}

        {{-- Rework --}}
        {{-- @if ($rework) --}}
        {{-- <div class="{{ $rework ? '' : 'd-none' }}">
            @livewire('rework', ["orderWsDetailSizes" => $orderWsDetailSizes])
        </div> --}}
        {{-- @endif --}}

        {{-- Undo --}}
        <div class="modal" tabindex="-1" id="undo-modal" wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">UNDO <span class="bg-{{ $undoType }} fs-5 px-3 py-1 mb-0 rounded text-center text-light fw-bold">{{ strtoupper($undoType) }}</span></h5>
                  <button type="button" class="btn btn-light border-none pt-1 close" data-dismiss="modal" aria-label="Close" wire:click="$emit('hideModal', 'undo')">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" class="form-control" name="undo" id="undo" value="{{ $undoType }}">
                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                @error('undoQty')
                                    <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                        <small>
                                            <strong>Error</strong> {{$message}}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </small>
                                    </div>
                                @enderror
                                <label class="form-label">QTY</label>
                                <input type="number" class="form-control @error('undoQty') is-invalid @enderror" name="undo-qty" id="undo-qty" value="1" wire:model='undoQty'>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                @error('undoSize')
                                    <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                        <small>
                                            <strong>Error</strong> {{$message}}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </small>
                                    </div>
                                @enderror
                                <label class="form-label">Size</label>
                                <select class="form-select @error('undoSize') is-invalid @enderror" name="undo-size" id="undo-size" wire:model='undoSize'>
                                    <option value="" selected disabled>Select Size</option>
                                    @if ($undoSizes)
                                        @foreach ($undoSizes as $size)
                                            <option value="{{ $size->so_det_id }}">{{ $size->size }} ({{ "qty : ".$size->total }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    @if ($undoType == 'defect' || $undoType == 'rework')
                        <div class="mb-3">
                            <label class="form-label">Defect Type <small>(not required)</small></label>
                            <select class="form-select" name="undo-defect-type" id="undo-defect-type" wire:model='undoDefectType'>
                                <option value="" selected>Select Defect Type</option>
                                @foreach ($undoDefectTypes as $defect)
                                    <option value="{{ $defect->id }}">{{ $defect->defect_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Defect Area <small>(not required)</small></label>
                            <select class="form-select" name="undo-defect-area" id="undo-defect-area" wire:model='undoDefectArea'>
                                <option value="" selected>Select Defect Area</option>
                                @foreach ($undoDefectAreas as $defect)
                                    <option value="{{ $defect->id }}">{{ $defect->defect_area }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                  {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="$emit('hideModal', 'undo')">Close</button> --}}
                  <button type="button" class="btn btn-dark" wire:click='submitUndo()'>UNDO</button>
                </div>
              </div>
            </div>
        </div>
    </div>

    {{-- Select Output Type --}}
    <div class="modal" tabindex="-1" id="select-output-type-modal" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-light">
                    <h5 class="modal-title">PILIH TIPE OUTPUT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="hideOutputTypeModal()"></button>
                </div>
                <div class="modal-body">
                    @error('numberingInput')
                        <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                            <strong>Error</strong> {{$message}}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @enderror
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-rft w-100 py-5" wire:click="setAndSubmitInput('rft')" onclick="hideOutputTypeModal();">
                                <h3><b>RFT</b></h3>
                            </button>
                        </div>
                        {{-- <div class="col-md-6">
                            <button class="btn btn-defect w-100 py-5" wire:click="setAndSubmitInput('defect')" onclick="hideOutputTypeModal();">
                                <h3><b>DEFECT</b></h3>
                            </button>
                        </div> --}}
                        <div class="col-md-6">
                            <button class="btn btn-reject w-100 py-5" wire:click="setAndSubmitInput('reject')" onclick="hideOutputTypeModal();">
                                <h3><b>REJECT</b></h3>
                            </button>
                        </div>
                        {{--
                        <div class="col-md-6">
                            <button class="btn btn-rework w-100 py-5" wire:click="setAndSubmitInput('rework')" onclick="hideOutputTypeModal();">
                                <h3><b>REWORK</b></h3>
                            </button>
                        </div>
                        --}}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" onclick="hideOutputTypeModal()">Batal</button>
                    {{-- <button type="button" class="btn btn-success" wire:click='submitDefectArea'>Tambahkan</button> --}}
                </div>
            </div>
        </div>
    </div>

    @if ($panels)
        <div class="w-100">
            <p class="mt-4 text-center opacity-50"><small><i>{{ date('Y') }} &copy; Nirwana Digital Solution</i></small></p>
        </div>
    @endif

    @if (!$panels)
        {{-- Back --}}
        @if (Auth::user()->line_type == "multi")
            <a wire:click="toProductionPanel" class="back bg-sb text-light text-center w-auto" id="back-button">
                <i class="fa-regular fa-reply"></i>
            </a>
        @else
            {{-- <a wire:click="toOrderList" class="back bg-success text-light text-center w-auto" id="back-button">
                <i class="fa-regular fa-reply"></i>
            </a> --}}
            <a href="{{ $this->baseUrl }}" class="back bg-success text-light text-center w-auto" id="back-button">
                <i class="fa-regular fa-reply"></i>
            </a>
        @endif
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            restrictYesterdayMasterPlan();

            getPo();
        });

        // window.addEventListener("focus", () => {
        //     document.getElementById("loading").classList.remove("d-none");

        //     $('#scannedItemRft').attr("disabled", true);
        //     $('#scannedDefectItem').attr("disabled", true);
        //     $('#scannedRejectItem').attr("disabled", true);
        //     $('#scannedReworkItem').attr("disabled", true);

        //     restrictYesterdayMasterPlan();

        //     // $('#defect-modal').modal("hide");
        //     // $('#reject-modal').modal("hide");

        //     Livewire.emit('updateOrder');
        // });

        // Pad 2 Digits
        function pad(n) {
            return n < 10 ? '0' + n : n
        }

        // Restrict Yesterday Master Plan
        function restrictYesterdayMasterPlan() {
            let date = new Date();
            let day = pad(date.getDate());
            let month = pad(date.getMonth() + 1);
            let year = date.getFullYear();

            // This arrangement can be altered based on how we want the date's format to appear.
            let currentDate = `${year}-${month}-${day}`;

            console.log(@this.orderDate, currentDate);

            if (@this.orderDate != currentDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Anda sedang mengakses Master Plan yang sudah berlalu',
                    html: `Master Plan yang anda akses berasal dari tanggal <br> <b>'`+ document.getElementById('tanggal').value +`'</b> <br> `,
                    showConfirmButton: true,
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#6531a0'
                }).then((result) => {
                    // window.location.href = '{{ route('index') }}';
                });
            }
        }

        var scannedQrCode = "";
        document.addEventListener("keydown", function(e) {
            let textInput = e.key || String.fromCharCode(e.keyCode);
            let targetName = e.target.localName;

            if (targetName != 'input') {
                if (textInput && textInput.length === 1) {
                    scannedQrCode = scannedQrCode+textInput;

                    if (scannedQrCode.length > 3) {
                        let i = 0;
                        let j = 1;
                        let k = 2;

                        if (scannedQrCode.includes('WIP')) {
                            @this.scannedNumberingCode = scannedQrCode;
                        } else {
                            // break decoded text
                            let breakDecodedText = scannedQrCode.split('-');

                            console.log(breakDecodedText);

                            // set kode_numbering
                            @this.scannedNumberingInput = breakDecodedText[i];

                            // set so_det_id
                            @this.scannedSizeInput = breakDecodedText[j];

                            // set size
                            @this.scannedSizeInputText = breakDecodedText[k];
                        }
                    }
                }

                if (@this.panels && textInput == "Enter") {
                    // open dialog
                    $("#select-output-type-modal").show();
                }
            }
        });

        function hideOutputTypeModal() {
            $("#select-output-type-modal").hide();
            scannedQrCode = '';
        }

        $('#product-color').on('change', function (e) {
            var selectedColor = $('#product-color').select2("val");
            var selectedColorName = $('#product-color').find(':selected').data('color-name');

            console.log(selectedColor);
            console.log(selectedColorName);

            @this.set('selectedColor', selectedColor);
            @this.set('selectedColorName', selectedColorName);

            @this.updateOrder();
        });

        Livewire.on('setSelectedSizeSelect2', (value) => {
            console.log($("#product-color").val(), value)
            if ($("#product-color").val() != value) {
                $("#product-color").val(value).trigger("change");
            }
        });

        Livewire.on('reloadPage', () => {
            location.reload();
        });

        Livewire.on('getPo', () => {
            getPo();
        });

        Livewire.on('getPoSize', () => {
            getPoSizes();
        });

        Livewire.on('getPoSizeQty', () => {
            getPoSizeQty();
        });

        function getPo() {
            $.ajax({
                type: "get",
                url: "{{ route('get-po') }}",
                data: {
                    ws_number: $("#ws-number").val(),
                    color: $('#product-color').find(':selected').data('color-name')
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        document.getElementById('product-po').innerHTML = '';
                        response.forEach(item => {
                            let newOption = document.createElement('option');
                            newOption.value = item.po;
                            newOption.text = item.po;

                            document.getElementById("product-po").appendChild(newOption);
                        });

                        if (response.length > 0) {
                            $("#product-po").val(response[0].po).trigger("change");
                        }
                    }

                    getPoSize();
                }
            });
        }

        function getPoSize() {
            document.getElementById("loading").classList.remove("d-none");

            $.ajax({
                type: "get",
                url: "{{ route('get-po-size') }}",
                data: {
                    po: $("#product-po").val(),
                    ws_number: $("#ws-number").val(),
                    color: $('#product-color').find(':selected').data('color-name')
                },
                dataType: "json",
                success: function (response) {
                    document.getElementById("loading").classList.add("d-none");

                    if (response) {
                        document.getElementById('product-po-id').innerHTML = '';
                        response.forEach(item => {
                            let newOption = document.createElement('option');
                            newOption.value = item.id;
                            newOption.text = item.size;

                            document.getElementById("product-po-id").appendChild(newOption);
                        });

                        if (response.length > 0) {
                            $("#product-po-id").val(response[0].id).trigger("change");
                        }
                    }

                    getPoSizeQty();
                }
            });
        }

        function getPoSizeQty() {
            $.ajax({
                type: "get",
                url: "{{ route('get-po-size-qty') }}",
                data: {
                    po: $("#product-po").val(),
                    po_id: $("#product-po-id").val(),
                },
                dataType: "json",
                success: function (response) {
                    if (response) {
                        document.getElementById('product-po-qty').value = response.qty_po;
                        document.getElementById('product-po-output').value = response.qty_output;
                    } else {
                        document.getElementById('product-po-qty').value = "";
                        document.getElementById('product-po-output').value = "";
                    }

                    Livewire.emit("qrInputFocus", (@this.rft ? 'rft' : (@this.reject ? 'reject' : '')));
                },
                error: function(jqXHR) {
                    document.getElementById('product-po-qty').value = "";
                    document.getElementById('product-po-output').value = "";
                }
            });
        }

        $('#product-po').on('change', function (e) {
            let selectedPo = $('#product-po').val();
            console.log("select po",selectedPo);

            @this.selectedPo = selectedPo;

            getPoSize();
        });

        $('#product-po-id').on('change', function (e) {
            let selectedPoId = $('#product-po-id').val();
            console.log("select po id",selectedPoId);

            @this.selectedPoId = selectedPoId;

            getPoSizeQty();
        });
    </script>
@endpush
