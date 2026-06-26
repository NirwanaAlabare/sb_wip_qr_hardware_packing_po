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

            <div class="production-input row g-4 py-3 px-2">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body p-4" wire:ignore.self>
                            @error('numberingInput')
                                <div class="alert alert-danger alert-dismissible fade show mb-3 rounded-0" role="alert">
                                    <strong>Error</strong> {{$message}}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @enderror

                            <input type="text" class="qty-input w-100" style="height: 340px;;" id="scannedItemReturn">
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <form wire:submit.prevent="save">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Kode QR</label>
                                        <input type="text" class="form-control" wire:model="kode_qr" readonly>

                                        <div class="invalid-feedback d-block">
                                            @error('kode_qr') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">PO</label>
                                        <input type="text" class="form-control" wire:model="po" readonly>
                                        <div class="invalid-feedback d-block">
                                            @error('po') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Worksheet Style</label>
                                        <input type="text" class="form-control" wire:model="worksheet_style" readonly>
                                        
                                        <div class="invalid-feedback d-block">
                                            @error('worksheet_style') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Color</label>
                                        <input type="text" class="form-control" wire:model="color" readonly>

                                        <div class="invalid-feedback d-block">
                                            @error('color') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Size</label>
                                        <input type="text" class="form-control" wire:model="size" readonly>

                                        <div class="invalid-feedback d-block">
                                            @error('size') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Packing Line</label>
                                        <input type="text" class="form-control" wire:model="packing_line" readonly>

                                        <div class="invalid-feedback d-block">
                                            @error('packing_line') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Line QC Finishing</label>

                                        <select class="form-select" wire:model="line_qc_finishing">
                                            <option value="">Pilih Line</option>

                                            @if($line_qc_finishing)
                                                <option value="{{ $line_qc_finishing }}">
                                                    {{ $line_qc_finishing }}
                                                </option>
                                            @endif

                                        </select>

                                        <div class="invalid-feedback d-block">
                                            @error('line_qc_finishing') {{ $message }} @enderror
                                        </div>
                                    </div>

                                    <input type="hidden" wire:model="ppic_master_id">
                                    <input type="hidden" wire:model="act_costing_id">
                                    <input type="hidden" wire:model="so_det_id">
                                    <input type="hidden" wire:model="kpno">
                                    <input type="hidden" wire:model="style">
                                    <input type="hidden" wire:model="qty_return">

                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success w-100">
                                            Simpan
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
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
                                        <button type="button" class="btn btn-sm btn-success" wire:click="openModal('{{ $row->tanggal }}')">
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
                                    <th>Kode QR</th>
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
                                        <td>{{ $detail->kode_numbering }}</td>
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
            $("#scannedItemReturn").focus();
        });

        $('#scannedItemReturn').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();

                let id = $(this).val();
                getScannedItem(id);

                $(this).val('');
            }
        });

        function getScannedItem(id) {
            $.ajax({
                url: "{{ route('get-scanned-item-return') }}",
                type: "GET",
                data: {
                    id: id
                },
                success: function(response) {

                    $('#scannedItemReturn').val('');

                    if (response) {

                        @this.set('ppic_master_id', response.ppic_master_id);
                        @this.set('act_costing_id', response.act_costing_id);
                        @this.set('so_det_id', response.so_det_id);

                        @this.set('kpno', response.kpno);
                        @this.set('style', response.style);

                        @this.set('qty_return', 1);

                        @this.set('kode_qr', response.kode_qr);
                        @this.set('po', response.po);
                        @this.set('worksheet_style', response.kpno + ' - ' + response.style);

                        @this.set('color', response.color);
                        @this.set('size', response.size);
                        @this.set('packing_line', response.packing_line);


                        $.ajax({
                            url: "{{ route('get-line-qc-finishing') }}",
                            type: "GET",
                            data: {
                                master_plan_id: response.master_plan_id
                            },
                            success: function(lineResponse) {

                                @this.set(
                                    'line_qc_finishing',
                                    lineResponse.line_qc_finishing
                                );

                            },
                            error: function(xhr) {
                                iziToast.warning({
                                    title: 'Warning!',
                                    message: xhr.responseJSON?.message,
                                    position: 'topCenter',
                                    transitionIn: 'slideInRight',
                                    timeout: 2000
                                });
                            }
                        });

                    }
                },
                error: function(xhr) {
                    iziToast.warning({
                        title: 'Warning!',
                        message: xhr.responseJSON?.message,
                        position: 'topCenter',
                        transitionIn: 'slideInRight',
                        timeout: 2000
                    });
                }
            });
        }

        document.addEventListener("livewire:load", function () {
            Livewire.hook('message.processed', (message, component) => {

            });
        });

        Livewire.on('resetSelect2', () => {
            $("#qty_return").val("");
        });

        Livewire.on('afterSave', () => {
            $("#scannedItemReturn").focus();
        });

        Livewire.on('reloadPage', () => {
            location.reload();
        });
        
        Livewire.on('openModal', () => {
            const modalEl = document.getElementById('returnModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

    </script>
@endpush
