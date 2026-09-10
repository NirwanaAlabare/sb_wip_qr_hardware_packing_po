@extends('layouts.index', ['orderDate' => $orderInfo->tgl_plan])

@section('content')
    {{-- Production Panel Livewire --}}
    @livewire('production-panel', ['orderInfo' => $orderInfo, 'orderWsDetails' => $orderWsDetails])

    {{-- Select Defect Area --}}
    {{-- <div class="select-defect-area" id="select-defect-area">
        <div class="defect-area-position-container">
            <div class="d-flex">
                <div class="d-flex justify-content-center align-items-center">
                    <label class="text-light bg-dark" style="padding: .375rem .75rem;height: 100%">X </label>
                    <input type="text" class="form-control rounded-0" id="defect-area-position-x" readonly>
                </div>
                <div class="d-flex justify-content-center align-items-center">
                    <label class="text-light bg-dark h-100" style="padding: .375rem .75rem;height: 100%">Y </label>
                    <input type="text" class="form-control rounded-0" id="defect-area-position-y" readonly>
                </div>
            </div>
            <div class="d-flex">
                <button class="btn btn-success rounded-0" id="defect-area-confirm">
                    <i class="fa-regular fa-check"></i>
                </button>
                <button class="btn btn-danger rounded-0" id="defect-area-cancel">
                    <i class="fa-regular fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="defect-area-img-container" id="defect-area-img-container">
            <div class="defect-area-img-point" id="defect-area-img-point"></div>
            <img src="" alt="" class="img-fluid defect-area-img" id="defect-area-img">
        </div>
    </div> --}}

    {{-- Show Defect Area --}}
    <div class="show-defect-area" id="show-defect-area">
        <div class="position-relative d-flex flex-column justify-content-center align-items-center">
            <button type="button" class="btn btn-lg btn-light rounded-0 hide-defect-area-img" onclick="onHideDefectAreaImage()">
                <i class="fa-regular fa-xmark fa-lg"></i>
            </button>
            <div class="defect-area-img-container mx-auto">
                <div class="defect-area-img-point" id="defect-area-img-point-show"></div>
                <img src="" alt="" class="img-fluid defect-area-img" id="defect-area-img-show">
            </div>
        </div>
    </div>

    {{-- Select Reject Area --}}
    <div class="select-defect-area" id="select-reject-area">
        <div class="defect-area-position-container">
            <div class="d-flex">
                <div class="d-flex justify-content-center align-items-center">
                    <label class="text-light bg-dark" style="padding: .375rem .75rem;height: 100%">X </label>
                    <input type="text" class="form-control rounded-0" id="reject-area-position-x" readonly>
                </div>
                <div class="d-flex justify-content-center align-items-center">
                    <label class="text-light bg-dark h-100" style="padding: .375rem .75rem;height: 100%">Y </label>
                    <input type="text" class="form-control rounded-0" id="reject-area-position-y" readonly>
                </div>
            </div>
            <div class="d-flex">
                <button class="btn btn-success rounded-0" id="reject-area-confirm">
                    <i class="fa-regular fa-check"></i>
                </button>
                <button class="btn btn-danger rounded-0" id="reject-area-cancel">
                    <i class="fa-regular fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="defect-area-img-container" id="reject-area-img-container">
            <div class="defect-area-img-point" id="reject-area-img-point"></div>
            <img src="" alt="" class="img-fluid defect-area-img" id="reject-area-img">
        </div>
    </div>

    {{-- Show Reject Area --}}
    <div class="show-defect-area" id="show-reject-area">
        <div class="position-relative d-flex flex-column justify-content-center align-items-center">
            <button type="button" class="btn btn-lg btn-light rounded-0 hide-defect-area-img" onclick="onHideRejectAreaImage()">
                <i class="fa-regular fa-xmark fa-lg"></i>
            </button>
            <div class="defect-area-img-container mx-auto">
                <div class="defect-area-img-point" id="reject-area-img-point-show"></div>
                <img src="" alt="" class="img-fluid defect-area-img" id="reject-area-img-show">
            </div>
        </div>
    </div>
@endsection

@section('custom-script')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            $('.select2').select2({
                theme: "bootstrap-5",
                width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
                placeholder: $( this ).data( 'placeholder' ),
            });
        });

        Livewire.on('alert', (type, message) => {
            showNotification(type, message);
        });

        Livewire.on('differentLine', (lineActual, linePlan) => {
            Swal.fire({
                icon: 'warning',
                title: 'Line dari master plan berbeda',
                html: `Line saat ini : '`+lineActual+`' <br> Line master plan : '`+linePlan+`' <br> Akan kembali ke order list`,
                showConfirmButton: true,
                confirmButtonText: 'Oke',
                confirmButtonColor: '#ff971f',
            }).then(() => {
                window.location.href = '{{ route('index') }}'
            });
        });

        Livewire.on('showModal', (type, additional) => {
            // if (type == 'defect') {
            //     if (additional) {
            //         showDefectModal(additional);
            //     } else {
            //         showDefectModal();
            //     }
            // }
            // else
            if (type == 'reject')
            {
                if (additional) {
                    showRejectModal(additional);
                } else {
                    showRejectModal();
                }
            }
            // else if (type == 'undo') {
            //     showUndoModal();
            // } else if (type == 'addProductType') {
            //     showAddProductTypeModal();
            // } else if (type == 'addDefectType') {
            //     showAddDefectTypeModal();
            // } else if (type == 'addDefectArea') {
            //     showAddDefectAreaModal();
            // } else if (type == 'massRework') {
            //     showMassReworkModal();
            // } else if (type == 'rapidRft') {
            //     showRapidRftModal();
            // } else if (type == 'rapidDefect') {
            //     showRapidDefectModal();
            // } else if (type == 'rapidReject') {
            //     showRapidRejectModal();
            // } else if (type == 'rapidRework') {
            //     showRapidReworkModal();
            // }

            if (type == 'undo') {
                showUndoModal();
            } else if (type == 'rapidRft') {
                showRapidRftModal();
            }
        });

        Livewire.on('hideModal', (type, additional) => {
            // if (type == 'defect') {
            //     if (additional) {
            //         hideDefectModal(additional);
            //     } else {
            //         hideDefectModal();
            //     }
            // } else
            if (type == 'reject') {
                if (additional) {
                    hideRejectModal(additional);
                } else {
                    hideRejectModal();
                }
            }
            // else if (type == 'undo') {
            //     hideUndoModal();
            // } else if (type == 'addDefectType') {
            //     hideAddDefectTypeModal();
            // } else if (type == 'addDefectArea') {
            //     hideAddDefectAreaModal();
            // } else if (type == 'massRework') {
            //     hideMassReworkModal();
            // }

            if (type == 'undo') {
                hideUndoModal();
            }
        });

        function temporaryPackingActivation(type) {
            const targetValue = "TEMPORARY_PACKING";

            if (type === 'reject') {
                // 1. Disable option target
                $('#product-po option[value="' + targetValue + '"]').prop('disabled', true);

                // 2. Jika opsi yang di-disable sedang terpilih, alihkan ke opsi pertama yang aktif
                if ($('#product-po').val() === targetValue || $('#product-po').val() === null) {
                    // Cari value dari option pertama yang tidak disabled
                    const firstValidValue = $('#product-po option:not(:disabled):first').val();

                    // Set nilainya ke opsi pertama tersebut
                    $('#product-po').val(firstValidValue).trigger("change");
                }

            } else {
                // Enable kembali option target
                $('#product-po option[value="' + targetValue + '"]').prop('disabled', false);
            }

            // 3. Trigger change & refresh Select2
            $('#product-po').trigger('change').trigger('change.select2');
        }

        Livewire.on('fromInputPanel', (type) => {
            temporaryPackingActivation(type);

            $('#input-type').hide();
        });

        Livewire.on('toInputPanel', (type) => {
            temporaryPackingActivation(type);

            if (type == 'defect-history') {
                type = 'defect';
            }
            $('#input-type').removeClass();
            $('#input-type').addClass('bg-'+type+' w-100 fs-6 py-1 mb-0 rounded text-center text-light fw-bold');
            $('#input-type').html(type.toUpperCase());
            $('#input-type').show();
        });

        // // REWORK ABOUT
        // Livewire.on('preSubmitAllRework', () => {
        //     Swal.fire({
        //         icon: 'info',
        //         title: 'REWORK semua DEFECT',
        //         html: `Yakin akan me-REWORK semua DEFECT?`,
        //         showConfirmButton: true,
        //         showDenyButton: true,
        //         confirmButtonText: 'Rework',
        //         confirmButtonColor: '#447efa',
        //         denyButtonText: 'Batal',
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Livewire.emit('submitAllRework');
        //         } else if (result.isDenied) {
        //             Swal.fire({
        //                 icon: 'info',
        //                 title: 'Submit REWORK dibatalkan',
        //                 confirmButtonText: 'Ok',
        //                 confirmButtonColor: '#447efa',
        //             });
        //         }
        //     });
        // });

        // Livewire.on('preSubmitRework', (defectId, defectSize, defectType, defectArea, defectImage, defectX, defectY) => {
        //     Swal.fire({
        //         icon: 'info',
        //         title: 'REWORK defect ini?',
        //         html: `<table class="table text-start w-auto mx-auto">
        //                     <tr>
        //                         <td>ID<td>
        //                         <td>:<td>
        //                         <td>`+defectId+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Size<td>
        //                         <td>:<td>
        //                         <td>`+defectSize+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Type<td>
        //                         <td>:<td>
        //                         <td>`+defectType+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Area<td>
        //                         <td>:<td>
        //                         <td>`+defectArea+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Image<td>
        //                         <td>:<td>
        //                         <td>
        //                             <button type="button" class="btn btn-dark" onclick="onShowDefectAreaImage('`+defectImage+`', '`+defectX+`', '`+defectY+`')">
        //                                 <i class="fa-regular fa-image"></i>
        //                             </button>
        //                         <td>
        //                     <tr>
        //                 </table>`,
        //         showConfirmButton: true,
        //         showDenyButton: true,
        //         confirmButtonText: 'Rework',
        //         confirmButtonColor: '#447efa',
        //         denyButtonText: 'Batal',
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Livewire.emit('submitRework', defectId);
        //         } else if (result.isDenied) {
        //             Swal.fire({
        //                 icon: 'info',
        //                 title: 'Submit REWORK dibatalkan',
        //                 confirmButtonText: 'Ok',
        //                 confirmButtonColor: '#447efa',
        //             });
        //         }
        //     });
        // });

        // Livewire.on('preCancelRework', (reworkId, defectId, defectSize, defectType, defectArea, defectImage, defectX, defectY) => {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Kembalikan REWORK ini ke DEFECT?',
        //         html: `<table class="table text-start w-auto mx-auto">
        //                     <tr>
        //                         <td>Rework ID<td>
        //                         <td>:<td>
        //                         <td>`+reworkId+`<td>
        //                     <tr>
        //                         <tr>
        //                         <td>Defect ID<td>
        //                         <td>:<td>
        //                         <td>`+defectId+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Size<td>
        //                         <td>:<td>
        //                         <td>`+defectSize+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Type<td>
        //                         <td>:<td>
        //                         <td>`+defectType+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Area<td>
        //                         <td>:<td>
        //                         <td>`+defectArea+`<td>
        //                     <tr>
        //                     <tr>
        //                         <td>Defect Image<td>
        //                         <td>:<td>
        //                         <td>
        //                             <button type="button" class="btn btn-dark" onclick="onShowDefectAreaImage('`+defectImage+`', '`+defectX+`', '`+defectY+`')">
        //                                 <i class="fa-regular fa-image"></i>
        //                             </button>
        //                         <td>
        //                     <tr>
        //                 </table>`,
        //         showConfirmButton: true,
        //         showDenyButton: true,
        //         confirmButtonText: 'Defect',
        //         confirmButtonColor: '#ff971f',
        //         denyButtonText: 'Batal',
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Livewire.emit('cancelRework', reworkId, defectId);
        //         } else if (result.isDenied) {
        //             Swal.fire({
        //                 icon: 'info',
        //                 title: 'Pengembalian REWORK KE DEFECT dibatalkan',
        //                 confirmButtonText: 'Ok',
        //                 confirmButtonColor: '#447efa',
        //             });
        //         }
        //     });
        // });

        // REJECT ABOUT
        Livewire.on('preSubmitAllReject', () => {
            Swal.fire({
                icon: 'info',
                title: 'REJECT semua DEFECT',
                html: `Yakin akan me-REJECT semua DEFECT?`,
                showConfirmButton: true,
                showDenyButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#fa4456',
                denyButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('submitAllReject');
                } else if (result.isDenied) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Submit REJECT dibatalkan',
                        confirmButtonText: 'Ok',
                        confirmButtonColor: '#fa4456',
                    });
                }
            });
        });

        Livewire.on('preSubmitReject', (defectId, defectSize, defectType, defectArea, defectImage, defectX, defectY) => {
            Swal.fire({
                icon: 'info',
                title: 'REJECT defect ini?',
                html: `<table class="table text-start w-auto mx-auto">
                            <tr>
                                <td>ID<td>
                                <td>:<td>
                                <td>`+defectId+`<td>
                            <tr>
                            <tr>
                                <td>Size<td>
                                <td>:<td>
                                <td>`+defectSize+`<td>
                            <tr>
                            <tr>
                                <td>Defect Type<td>
                                <td>:<td>
                                <td>`+defectType+`<td>
                            <tr>
                            <tr>
                                <td>Defect Area<td>
                                <td>:<td>
                                <td>`+defectArea+`<td>
                            <tr>
                            <tr>
                                <td>Defect Image<td>
                                <td>:<td>
                                <td>
                                    <button type="button" class="btn btn-dark" onclick="onShowDefectAreaImage('`+defectImage+`', '`+defectX+`', '`+defectY+`')">
                                        <i class="fa-regular fa-image"></i>
                                    </button>
                                <td>
                            <tr>
                        </table>`,
                showConfirmButton: true,
                showDenyButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#fa4456',
                denyButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('submitReject', defectId);
                } else if (result.isDenied) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Submit REJECT dibatalkan',
                        confirmButtonText: 'Ok',
                        confirmButtonColor: '#fa4456',
                    });
                }
            });
        });

        Livewire.on('preCancelReject', (rejectId, defectId, defectSize, defectType, defectArea, defectImage, defectX, defectY) => {
            Swal.fire({
                icon: 'warning',
                title: 'Kembalikan REJECT ini ke DEFECT?',
                html: `<table class="table text-start w-auto mx-auto">
                            <tr>
                                <td>Reject ID<td>
                                <td>:<td>
                                <td>`+rejectId+`<td>
                            <tr>
                                <tr>
                                <td>Defect ID<td>
                                <td>:<td>
                                <td>`+defectId+`<td>
                            <tr>
                            <tr>
                                <td>Size<td>
                                <td>:<td>
                                <td>`+defectSize+`<td>
                            <tr>
                            <tr>
                                <td>Defect Type<td>
                                <td>:<td>
                                <td>`+defectType+`<td>
                            <tr>
                            <tr>
                                <td>Defect Area<td>
                                <td>:<td>
                                <td>`+defectArea+`<td>
                            <tr>
                            <tr>
                                <td>Defect Image<td>
                                <td>:<td>
                                <td>
                                    <button type="button" class="btn btn-dark" onclick="onShowDefectAreaImage('`+defectImage+`', '`+defectX+`', '`+defectY+`')">
                                        <i class="fa-regular fa-image"></i>
                                    </button>
                                <td>
                            <tr>
                        </table>`,
                showConfirmButton: true,
                showDenyButton: true,
                confirmButtonText: 'Defect',
                confirmButtonColor: '#ff971f',
                denyButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit('cancelReject', rejectId, defectId);
                } else if (result.isDenied) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Pengembalian REJECT KE DEFECT dibatalkan',
                        confirmButtonText: 'Ok',
                        confirmButtonColor: '#447efa',
                    });
                }
            });
        });

        // // Select Defect Area Position
        // Livewire.on('showSelectDefectArea', async function (defectAreaImage) {
        //     showSelectDefectArea(defectAreaImage);
        // });

        // if (document.getElementById('select-defect-area')) {
        //     let defectAreaImageContainer = document.getElementById('defect-area-img-container');
        //     let defectAreaImage = document.getElementById('defect-area-img');
        //     let defectAreaImagePoint = document.getElementById('defect-area-img-point');
        //     let defectAreaPositionX = document.getElementById('defect-area-position-x');
        //     let defectAreaPositionY = document.getElementById('defect-area-position-y');
        //     let defectAreaConfirm = document.getElementById('defect-area-confirm');
        //     let defectAreaCancel = document.getElementById('defect-area-cancel');

        //     let localMousePos = { x: undefined, y: undefined };
        //     let globalMousePos = { x: undefined, y: undefined };

        //     defectAreaImageContainer.addEventListener('mousemove', (event) => {
        //         let rect = defectAreaImage.getBoundingClientRect();

        //         const localX = parseFloat((event.clientX - rect.left))/parseFloat(rect.width) * 100;
        //         const localY = parseFloat((event.clientY - rect.top))/parseFloat(rect.height) * 100;

        //         localMousePos = { x: localX, y: localY };

        //         defectAreaImageContainer.addEventListener('click', (event) => {
        //             defectAreaImagePoint.style.width = 0.03 * rect.width+'px';
        //             defectAreaImagePoint.style.height = defectAreaImagePoint.style.width;
        //             defectAreaImagePoint.style.left =  'calc('+localMousePos.x+'% - '+0.015 * rect.width+'px)';
        //             defectAreaImagePoint.style.top =  'calc('+localMousePos.y+'% - '+0.015 * rect.width+'px)';
        //             defectAreaImagePoint.style.display = 'block';

        //             defectAreaPositionX.value = localMousePos.x;
        //             defectAreaPositionY.value = localMousePos.y;
        //         });
        //     });

        //     defectAreaConfirm.addEventListener('click', () => {
        //         Livewire.emit('setDefectAreaPosition', defectAreaPositionX.value, defectAreaPositionY.value);

        //         hideSelectDefectArea();
        //     });

        //     defectAreaCancel.addEventListener('click', () => {
        //         defectAreaImagePoint.style.left = '0px';
        //         defectAreaImagePoint.style.top = '0px';
        //         defectAreaImagePoint.style.display = 'none';

        //         defectAreaPositionX.value = null;
        //         defectAreaPositionY.value = null;

        //         Livewire.emit('setDefectAreaPosition', defectAreaPositionX.value, defectAreaPositionY.value);

        //         hideSelectDefectArea();
        //     });
        // }

        // Livewire.on('clearSelectDefectAreaPoint', () => {
        //     let defectAreaImagePoint = document.getElementById('defect-area-img-point');
        //     let defectAreaPositionX = document.getElementById('defect-area-position-x');
        //     let defectAreaPositionY = document.getElementById('defect-area-position-y');

        //     defectAreaImagePoint.style.left = '0px';
        //     defectAreaImagePoint.style.top = '0px';
        //     defectAreaImagePoint.style.display = 'none';

        //     defectAreaPositionX.value = null;
        //     defectAreaPositionY.value = null;

        //     Livewire.emit('setDefectAreaPosition', defectAreaPositionX.value, defectAreaPositionY.value);
        // });

        // Select Reject Area Position
        Livewire.on('showSelectRejectArea', async function (rejectAreaImage) {
            showSelectRejectArea(rejectAreaImage);
        });

        if (document.getElementById('select-reject-area')) {
            let rejectAreaImageContainer = document.getElementById('reject-area-img-container');
            let rejectAreaImage = document.getElementById('reject-area-img');
            let rejectAreaImagePoint = document.getElementById('reject-area-img-point');
            let rejectAreaPositionX = document.getElementById('reject-area-position-x');
            let rejectAreaPositionY = document.getElementById('reject-area-position-y');
            let rejectAreaConfirm = document.getElementById('reject-area-confirm');
            let rejectAreaCancel = document.getElementById('reject-area-cancel');

            let localMousePos = { x: undefined, y: undefined };
            let globalMousePos = { x: undefined, y: undefined };

            rejectAreaImageContainer.addEventListener('mousemove', (event) => {
                let rect = rejectAreaImage.getBoundingClientRect();

                const localX = parseFloat((event.clientX - rect.left))/parseFloat(rect.width) * 100;
                const localY = parseFloat((event.clientY - rect.top))/parseFloat(rect.height) * 100;

                localMousePos = { x: localX, y: localY };

                rejectAreaImageContainer.addEventListener('click', (event) => {
                    rejectAreaImagePoint.style.width = 0.03 * rect.width+'px';
                    rejectAreaImagePoint.style.height = rejectAreaImagePoint.style.width;
                    rejectAreaImagePoint.style.left =  'calc('+localMousePos.x+'% - '+0.015 * rect.width+'px)';
                    rejectAreaImagePoint.style.top =  'calc('+localMousePos.y+'% - '+0.015 * rect.width+'px)';
                    rejectAreaImagePoint.style.display = 'block';

                    rejectAreaPositionX.value = localMousePos.x;
                    rejectAreaPositionY.value = localMousePos.y;
                });
            });

            rejectAreaConfirm.addEventListener('click', () => {
                Livewire.emit('setRejectAreaPosition', rejectAreaPositionX.value, rejectAreaPositionY.value);

                hideSelectRejectArea();
            });

            rejectAreaCancel.addEventListener('click', () => {
                rejectAreaImagePoint.style.left = '0px';
                rejectAreaImagePoint.style.top = '0px';
                rejectAreaImagePoint.style.display = 'none';

                rejectAreaPositionX.value = null;
                rejectAreaPositionY.value = null;

                Livewire.emit('setRejectAreaPosition', rejectAreaPositionX.value, rejectAreaPositionY.value);

                hideSelectRejectArea();
            });
        }

        Livewire.on('clearSelectRejectAreaPoint', () => {
            let rejectAreaImagePoint = document.getElementById('reject-area-img-point');
            let rejectAreaPositionX = document.getElementById('reject-area-position-x');
            let rejectAreaPositionY = document.getElementById('reject-area-position-y');

            rejectAreaImagePoint.style.left = '0px';
            rejectAreaImagePoint.style.top = '0px';
            rejectAreaImagePoint.style.display = 'none';

            rejectAreaPositionX.value = null;
            rejectAreaPositionY.value = null;

            Livewire.emit('setRejectAreaPosition', rejectAreaPositionX.value, rejectAreaPositionY.value);
        });

        function onShowDefectAreaImage(defectAreaImage, x, y) {
            Livewire.emit('showDefectAreaImage', defectAreaImage, x, y);
        }

        Livewire.on('showDefectAreaImage', async function (defectAreaImage, x, y) {
            await showDefectAreaImage(defectAreaImage);

            let defectAreaImageElement = document.getElementById('defect-area-img-show');
            let defectAreaImagePointElement = document.getElementById('defect-area-img-point-show');

            defectAreaImageElement.style.display = 'block'

            let rect = await defectAreaImageElement.getBoundingClientRect();

            let pointWidth = null;
            if (rect.width == 0) {
                pointWidth = 35;
            } else {
                pointWidth = 0.03 * rect.width;
            }

            defectAreaImagePointElement.style.width = pointWidth+'px';
            defectAreaImagePointElement.style.height = defectAreaImagePointElement.style.width;
            defectAreaImagePointElement.style.left = 'calc('+x+'% - '+0.5 * pointWidth+'px)';
            defectAreaImagePointElement.style.top = 'calc('+y+'% - '+0.5 * pointWidth+'px)';
            defectAreaImagePointElement.style.display = 'block';
        });

        function onHideDefectAreaImage() {
            hideDefectAreaImage();

            Livewire.emit('hideDefectAreaImageClear');
        }

        // Livewire.on('loadReworkPageJs', () => {
        //     if (document.getElementById('all-defect-area-img')) {
        //         let defectAreaImage = document.getElementById('all-defect-area-img');
        //         let defectAreaImagePoint = document.getElementsByClassName('all-defect-area-img-point');

        //         let rect = defectAreaImage.getBoundingClientRect();

        //         for(i = 0; i < defectAreaImagePoint.length; i++) {
        //             defectAreaImagePoint[i].style.width = 0.03 * rect.width+'px';
        //             defectAreaImagePoint[i].style.height = defectAreaImagePoint[i].style.width;
        //             defectAreaImagePoint[i].style.left =  'calc('+defectAreaImagePoint[i].getAttribute('data-x')+'% - '+0.015 * rect.width+'px)';
        //             defectAreaImagePoint[i].style.top =  'calc('+defectAreaImagePoint[i].getAttribute('data-y')+'% - '+0.015 * rect.width+'px)';
        //         }
        //     }
        // });

        Livewire.on('loadRejectPageJs', () => {
            if (document.getElementById('all-defect-area-img')) {
                let defectAreaImage = document.getElementById('all-defect-area-img');
                let defectAreaImagePoint = document.getElementsByClassName('all-defect-area-img-point');

                let rect = defectAreaImage.getBoundingClientRect();

                for(i = 0; i < defectAreaImagePoint.length; i++) {
                    defectAreaImagePoint[i].style.width = 0.03 * rect.width+'px';
                    defectAreaImagePoint[i].style.height = defectAreaImagePoint[i].style.width;
                    defectAreaImagePoint[i].style.left =  'calc('+defectAreaImagePoint[i].getAttribute('data-x')+'% - '+0.015 * rect.width+'px)';
                    defectAreaImagePoint[i].style.top =  'calc('+defectAreaImagePoint[i].getAttribute('data-y')+'% - '+0.015 * rect.width+'px)';
                }
            }
        });

        Livewire.on('loadingStart', () => {
            if (document.getElementById('loading-rft')) {
                $('#loading-rft').removeClass('hidden');
                $('#content-rft').addClass('hidden');
            }
            // if (document.getElementById('loading-defect')) {
            //     $('#loading-defect').removeClass('hidden');
            //     $('#content-defect').addClass('hidden');
            // }
            // if (document.getElementById('loading-defect-history')) {
            //     $('#loading-defect-history').removeClass('hidden');
            //     $('#content-defect-history').addClass('hidden');
            // }
            if (document.getElementById('loading-reject')) {
                $('#loading-reject').removeClass('hidden');
                $('#content-reject').addClass('hidden');
            }
            // if (document.getElementById('loading-rework')) {
            //     $('#loading-rework').removeClass('hidden');
            //     $('#content-rework').addClass('hidden');
            // }
            if (document.getElementById('loading-profile')) {
                $('#loading-profile').removeClass('hidden');
                $('#content-profile').addClass('hidden');
            }
            if (document.getElementById('loading-history')) {
                $('#loading-history').removeClass('hidden');
                $('#content-history').addClass('hidden');
            }
            if (document.getElementById('loading-undo')) {
                $('#loading-undo').removeClass('hidden');
                $('#content-undo').addClass('hidden');
            }
        });

        // var intervalTrigger = setInterval(() => {
        //     triggerDashboard('{{ Auth::user()->username }}', '{{ date('Y-m-d') }}');
        // }, 10000);

        // function triggerDashboard(line, tanggal) {
        //     console.log("Sending Trigger...");

        //     $.ajax({
        //         url: "http://10.10.5.62:8000/nds_wip/public/index.php/api/trigger-wip-line/dashboard-line/wip-line-sign",
        //         type: "post",
        //         data: {
        //             line_id: line,
        //             tanggal: tanggal,
        //         },
        //         dataType: "json",
        //         success: function (response) {
        //             console.log(response);

        //             console.log("Trigger Sent!");
        //         },
        //         error: function (jqXHR) {
        //             console.error(jqXHR);

        //             console.error("Trigger Fail!");
        //         }
        //     });
        // }

        Livewire.on('triggerDashboard', (line, tanggal) => {
            // triggerDashboard(line, tanggal);
            console.log("trigger paused by interval");
        });
    </script>
@endsection
