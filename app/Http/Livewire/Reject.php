<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use App\Models\SignalBit\Reject as RejectModel;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\DefectType;
use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\OutputGudangStok;
use App\Models\Nds\Numbering;
use Carbon\Carbon;
use Validator;
use DB;

class Reject extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $orderInfo;
    public $orderWsDetailSizes;
    public $selectedPo;
    public $sizeInput;
    public $sizeInputText;
    public $noCutInput;
    public $numberingInput;
    public $reject;

    public $rapidReject;
    public $rapidRejectCount;

    public $searchRejectIn;
    public $searchReject;
    public $rejectImage;
    public $rejectPositionX;
    public $rejectPositionY;
    public $allRejectListFilter;
    public $allRejectImage;
    public $allRejectPosition;
    public $massQty;
    public $massSize;
    public $massRejectType;
    public $massRejectTypeName;
    public $massRejectArea;
    public $massRejectAreaName;
    public $massSelectedReject;
    public $info;

    public $defectTypes;
    public $defectAreas;
    public $rejectType;
    public $rejectArea;
    public $rejectAreaPositionX;
    public $rejectAreaPositionY;

    protected $rules = [
        'sizeInput' => 'required',
        'noCutInput' => 'required',
        'numberingInput' => 'required',

        // 'rejectType' => 'required',
        // 'rejectArea' => 'required',
        // 'rejectAreaPositionX' => 'required',
        // 'rejectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',

        // 'rejectType.required' => 'Harap tentukan jenis reject.',
        // 'rejectArea.required' => 'Harap tentukan area reject.',
        // 'rejectAreaPositionX.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        // 'rejectAreaPositionY.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updatePo' => 'updatePo',
        'updateOutputReject' => 'updateOutput',
        'setAndSubmitInputReject' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError',

        'submitInputReject' => 'submitInput',
        // 'submitReject' => 'submitReject',
        // 'submitAllReject' => 'submitAllReject',
        // 'cancelReject' => 'cancelReject',
        'hideDefectAreaImageClear' => 'hideDefectAreaImage',

        'setRejectAreaPosition' => 'setRejectAreaPosition',
        'clearInput' => 'clearInput'
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->sizeInput = null;

        $this->rapidReject = [];
        $this->rapidRejectCount = 0;

        $this->rejectType = null;
        $this->rejectArea = null;
        $this->rejectAreaPositionX = null;
        $this->rejectAreaPositionY = null;
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function resetError() {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    private function checkIfNumberingExists($numberingInput = null): bool
    {
        if (DB::table('output_rfts_packing_po')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->where("type", "rft")->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di RFT.');
            return true;
        }

        if (DB::table('output_rfts_packing_po')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->where("type", "reject")->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di Reject.');
            return true;
        }

        $this->emit('qrInputFocus', 'reject');

        return false;
    }

    public function loadRejectPage()
    {
        $this->emit('loadRejectPageJs');
    }

    public function updateWsDetailSizes($panel)
    {
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        if ($panel == 'reject') {
            $this->emit('qrInputFocus', 'reject');
        }
    }

    public function updatePo($po)
    {
        $this->selectedPo = $po;
    }

    public function updateOutput()
    {
        // Reject
        $this->reject = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `type` = 'reject' group by so_det.id"));
    }

    public function clearInput()
    {
        $this->sizeInput = null;
        $this->noCutInput = null;
        $this->numberingInput = null;
    }

    public function selectRejectAreaPosition()
    {
        $masterPlan = MasterPlan::select('gambar')->find($this->orderInfo->id);

        if ($masterPlan) {
            $this->emit('showSelectRejectArea', $masterPlan->gambar);
        } else {
            $this->emit('alert', 'error', 'Harap pilih tipe produk terlebih dahulu');
        }
    }

    public function setRejectAreaPosition($x, $y)
    {
        $this->rejectAreaPositionX = $x;
        $this->rejectAreaPositionY = $y;
    }

    // Deprecated
        // public function preSubmitInput($value)
        // {
        //     $this->emit('qrInputFocus', 'reject');

        //     $numberingInput = $value;

        //     if ($numberingInput) {
        //         // if (str_contains($numberingInput, 'WIP')) {
        //         //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $numberingInput)->first();
        //         // } else {
        //         //     $numberingCodes = explode('_', $numberingInput);

        //         //     if (count($numberingCodes) > 2) {
        //         //         $numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
        //         //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();
        //         //     } else {
        //         //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
        //         //     }
        //         // }

        //         // One Straight Format
        //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();

        //         if ($numberingData) {
        //             $this->sizeInput = $numberingData->so_det_id;
        //             $this->sizeInputText = $numberingData->size;
        //             $this->noCutInput = $numberingData->no_cut_size;
        //             $this->numberingInput = $numberingInput;
        //         }
        //     }

        //     $validation = Validator::make([
        //         'sizeInput' => $this->sizeInput,
        //         'noCutInput' => $this->noCutInput,
        //         'numberingInput' => $numberingInput
        //     ], [
        //         'sizeInput' => 'required',
        //         'noCutInput' => 'required',
        //         'numberingInput' => 'required'
        //     ], [
        //         'sizeInput.required' => 'Harap scan qr.',
        //         'noCutInput.required' => 'Harap scan qr.',
        //         'numberingInput.required' => 'Harap scan qr.',
        //     ]);

        //     if ($this->checkIfNumberingExists($numberingInput)) {
        //         $this->emit('qrInputFocus', 'reject');

        //         return;
        //     }

        //     if ($validation->fails()) {
        //         $this->emit('qrInputFocus', 'reject');

        //         $validation->validate();
        //     } else {
        //         // Check current reject
        //         $currentReject = null;
        //         $currentRejectType = null;

        //         $finishlineRejectData = DB::connection('mysql_sb')->table('output_rejects_packing')->where("kode_numbering", $numberingInput)->first();
        //         if ($finishlineRejectData) {
        //             $currentReject = $finishlineRejectData;

        //             $currentRejectType = 'packing';
        //         } else {
        //             $finishlineOutputData = DB::connection('mysql_sb')->table('output_rfts_packing')->where("kode_numbering", $numberingInput)->first();
        //             $finishlineDefectData = DB::connection('mysql_sb')->table('output_defects_packing')->where("kode_numbering", $numberingInput)->first();

        //             if (!$finishlineOutputData && !$finishlineDefectData) {
        //                 $endlineRejectData = DB::connection('mysql_sb')->table('output_rejects')->where("kode_numbering", $numberingInput)->first();

        //                 if ($endlineRejectData) {
        //                     $currentReject = $endlineRejectData;

        //                     $currentRejectType = 'qc';
        //                 }
        //             }
        //         }

        //         if ($currentReject) {
        //             if ($this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
        //                 $this->emit('clearSelectRejectAreaPoint');

        //                 $this->rejectType = null;
        //                 $this->rejectArea = null;
        //                 $this->rejectAreaPositionX = null;
        //                 $this->rejectAreaPositionY = null;

        //                 $this->numberingInput = $numberingInput;

        //                 $this->validateOnly('sizeInput');

        //                 $this->emit('showModal', 'reject', 'regular');
        //             } else {
        //                 $this->emit('qrInputFocus', 'reject');

        //                 $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        //             }
        //         } else {
        //             $this->emit('alert', 'error', "Reject dari <b>".($currentRejectType == 'packing' ? "QC Finishing" : strtoupper($currentRejectType))."</b> tidak ditemukan.");
        //         }
        //     }
        // }

    public function submitInput($value)
    {
        ini_set('memory_limit', '2048M');

        $this->emit('qrInputFocus', 'rft');

        $numberingInput = $value;

        if ($numberingInput) {
            // if (str_contains($numberingInput, 'WIP')) {
            //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $numberingInput)->first();
            // } else {
            //     $numberingCodes = explode('_', $numberingInput);

            //     if (count($numberingCodes) > 2) {
            //         $numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
            //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();
            //     } else {
            //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $numberingInput)->first();
            //     }
            // }

            // One Straight Format
            $numberingData = DB::connection("mysql_nds")->table("year_sequence")->
                selectRaw("year_sequence.*, so_det.dest, so_det.color, act_costing.id as id_ws, year_sequence.id_year_sequence no_cut_size")->
                leftJoin("signalbit_erp.so_det", "so_det.id", "=", "year_sequence.so_det_id")->
                leftJoin("signalbit_erp.so", "so.id", "=", "so_det.id_so")->
                leftJoin("signalbit_erp.act_costing", "act_costing.id", "=", "so.id_cost")->
                where("id_year_sequence", $numberingInput)->
                first();

            if ($numberingData) {
                $this->sizeInput = $numberingData->so_det_id;
                $this->sizeInputText = $numberingData->size.($numberingData->dest ? " - ".$numberingData->dest : "");
                $this->noCutInput = $numberingData->no_cut_size;
                $this->numberingInput = $numberingInput;

                if (!$this->sizeInput) {
                    return $this->emit('alert', 'error', "QR belum terdaftar.");
                }

                $validatedData = $this->validate();

                if ($this->checkIfNumberingExists($numberingInput)) {
                    return;
                }

                $currentReject = null;
                $currentRejectType = null;

                $finishlineRejectData = DB::connection('mysql_sb')->table('output_rejects_packing')->where("kode_numbering", $this->numberingInput)->first();
                if ($finishlineRejectData) {
                    $currentReject = $finishlineRejectData;

                    $currentRejectType = 'packing';
                } else {
                    $finishlineOutputData = DB::connection('mysql_sb')->table('output_rfts_packing')->where("kode_numbering", $this->numberingInput)->first();
                    $finishlineDefectData = DB::connection('mysql_sb')->table('output_defects_packing')->where("kode_numbering", $this->numberingInput)->first();

                    if (!$finishlineOutputData && !$finishlineDefectData) {
                        $endlineRejectData = DB::connection('mysql_sb')->table('output_rejects')->where("kode_numbering", $this->numberingInput)->first();

                        if ($endlineRejectData) {
                            $currentReject = $endlineRejectData;

                            $currentRejectType = 'qc';
                        }
                    }
                }

                if ($currentReject) {
                    $currentData = $this->orderWsDetailSizes->where('id_ws', $numberingData->id_ws)->where('color', $numberingData->color)->where('size', $numberingData->size)->first();
                    if ($currentData && $this->orderInfo && ($currentData['color'] == $this->orderInfo->color)) {
                        $currentSizeInput = $this->sizeInput;
                        $currentSizeInputText = $this->sizeInputText;
                        // $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                        //         ppic_master_so.id
                        //     ")
                        //     ->where('ppic_master_so.po', $this->selectedPo)
                        //     ->where('ppic_master_so.id_so_det', $this->sizeInput)
                        //     ->first();
                        $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                                ppic_master_so.id,
                                ppic_master_so.po,
                                ppic_master_so.id_so_det,
                                so_det.size,
                                ppic_master_so.qty_po,
                                COUNT(output_rfts_packing_po.id) as qty_output
                            ")
                            ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
                            ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
                            ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
                            ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
                            ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
                            ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
                            ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
                            ->where('so_det.cancel', '!=', 'Y')
                            ->where('ppic_master_so.po', $this->selectedPo) // By Size & Color
                            ->where('so_det.color', $numberingData->color) // By Size & Color
                            ->where('so_det.size', $numberingData->size) // By Size & Color
                            ->groupBy('ppic_master_so.id')
                            ->first();

                        if ($this->selectedPo == "GUDANG_STOK" || $currentPo) {
                            if ($this->selectedPo == "GUDANG_STOK" || $currentPo->qty_output < $currentPo->qty_po) {

                                // Modify based on selected PO
                                if ($currentPo && $currentPo->id_so_det != $numberingData->so_det_id) {
                                    $id = (int) $currentPo->id_so_det;
                                    $num = addslashes($numberingInput);
                                    $numId = (int) $numberingData->id;

                                    // SB Data Update
                                    $sql = "
                                        UPDATE output_rfts              SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_defects           SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_rejects           SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_rfts_packing      SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_defects_packing   SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_rejects_packing   SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE output_reject_in         SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                    ";
                                    DB::unprepared($sql);

                                    // NDS Data Update
                                    DB::connection('mysql_nds')->unprepared("
                                        UPDATE output_rfts_packing SET so_det_id = {$id} WHERE kode_numbering = '{$num}';
                                        UPDATE year_sequence       SET so_det_id = {$id} WHERE id = {$numId};
                                    ");
                                }

                                $insertReject = Rft::create([
                                    'master_plan_id' => $this->orderInfo->id,
                                    'so_det_id' => $currentPo ? $currentPo->id_so_det : $currentSizeInput,
                                    'no_cut_size' => $this->noCutInput,
                                    'po_id' => $currentPo ? $currentPo->id : NULL,
                                    'kode_numbering' => $numberingInput,
                                    'status' => $currentReject ? $currentReject->reject_status : "NORMAL",
                                    'alokasi' => $currentPo ? "po" : "gudang stok",
                                    'reject_id' => $currentReject ? $currentReject->id : NULL,
                                    'type' => 'reject',
                                    'department' => $currentRejectType,
                                    'created_by' => Auth::user()->id,
                                    'created_by_username' => Auth::user()->username,
                                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]);

                                if ($insertReject) {
                                    if ($this->selectedPo == "GUDANG_STOK") {
                                        OutputGudangStok::create([
                                            'kode_numbering' => $numberingInput,
                                            'so_det_id' => $currentPo ? $currentPo->id_so_det : $currentSizeInput,
                                            'packing_po_id' => $insertReject->id,
                                            'type' => 'reject',
                                            'created_by' => Auth::user()->id,
                                            'created_by_username' => Auth::user()->username,
                                            'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                                        ]);
                                    }

                                    $this->emit('alert', 'success', "1 output berukuran ".$currentSizeInputText." berhasil terekam.");

                                    $this->sizeInput = '';
                                    $this->sizeInputText = '';
                                    $this->noCutInput = '';
                                    $this->numberingInput = '';

                                    $this->emit('getPoSizeQty');
                                } else {
                                    $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
                                }
                            } else {
                                $this->emit('alert', 'error', "QTY <b>Output</b> tidak dapat melebihi QTY <b>PO</b>.");
                            }
                        } else {
                            $this->emit('alert', 'error', "PO tidak ditemukan untuk size <b>".$currentSizeInputText."</b> (ID SO : <b>".$currentSizeInput."</b>)");
                        }
                    } else {
                        $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
                    }
                } else {
                    $this->emit('alert', 'error', "Output dari <b>QC Finishing</b> tidak ditemukan.");
                }
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
            }
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        }
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText) {
        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;

        $this->submitInput($scannedNumbering);
    }

    public function pushRapidReject($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        foreach ($this->rapidReject as $item) {
            if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                $exist = true;
            }
        }

        if (!$exist) {
            $this->rapidRejectCount += 1;

            if ($numberingInput) {
                array_push($this->rapidReject, [
                    'numberingInput' => $numberingInput,
                ]);
            }
        }
    }

    public function submitRapidInput() {
        ini_set('memory_limit', '2048M');

        $rapidRejectFiltered = [];
        $rapidRejectFilteredNds = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidReject && count($this->rapidReject) > 0) {

            for ($i = 0; $i < count($this->rapidReject); $i++) {
                // if (str_contains($this->rapidReject[$i]['numberingInput'], 'WIP')) {
                //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->rapidReject[$i]['numberingInput'])->first();
                // } else {
                //     $numberingCodes = explode('_', $this->rapidReject[$i]['numberingInput']);

                //     if (count($numberingCodes) > 2) {
                //         $this->rapidReject[$i]['numberingInput'] = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidReject[$i]['numberingInput'])->first();
                //     } else {
                //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->rapidReject[$i]['numberingInput'])->first();
                //     }
                // }

                // One Straight Format
                $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidReject[$i]['numberingInput'])->first();

                $currentReject = null;
                $currentRejectType = null;

                $finishlineRejectData = DB::connection('mysql_sb')->table('output_rejects_packing')->where("kode_numbering", $this->numberingInput)->first();
                if ($finishlineRejectData) {
                    $currentReject = $finishlineRejectData;

                    $currentRejectType = 'packing';
                } else {
                    $finishlineOutputData = DB::connection('mysql_sb')->table('output_rfts_packing')->where("kode_numbering", $this->numberingInput)->first();
                    $finishlineDefectData = DB::connection('mysql_sb')->table('output_defects_packing')->where("kode_numbering", $this->numberingInput)->first();

                    if (!$finishlineOutputData && !$finishlineDefectData) {
                        $endlineRejectData = DB::connection('mysql_sb')->table('output_rejects')->where("kode_numbering", $this->numberingInput)->first();

                        if ($endlineRejectData) {
                            $currentReject = $endlineRejectData;

                            $currentRejectType = 'qc';
                        }
                    }
                }

                if ($currentReject) {
                    $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                                ppic_master_so.id,
                                ppic_master_so.po,
                                ppic_master_so.id_so_det,
                                so_det.size,
                                ppic_master_so.qty_po,
                                COUNT(output_rfts_packing_po.id) as qty_output
                            ")
                            ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
                            ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
                            ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
                            ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
                            ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
                            ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
                            ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
                            ->where('so_det.cancel', '!=', 'Y')
                            ->where('ppic_master_so.po', $this->selectedPo) // By Size & Color
                            ->where('so_det.color', $numberingData->color) // By Size & Color
                            ->where('so_det.size', $numberingData->size) // By Size & Color
                            ->groupBy('ppic_master_so.id')
                            ->first();

                    if ($currentPo) {
                        array_push($rapidRejectFiltered, [
                            'master_plan_id' => $this->orderInfo->id,
                            'so_det_id' => $currentPo ? $currentPo->id_so_det : $numberingData->so_det_id,
                            'no_cut_size' => $this->noCutInput,
                            'po_id' => $currentPo ? $currentPo->id : NULL,
                            'kode_numbering' => $this->rapidReject[$i]['numberingInput'],
                            'status' => 'NORMAL',
                            'alokasi' => $currentPo ? "po" : "gudang stok",
                            'reject_id' => $currentReject ? $currentReject->id : NULL,
                            'type' => 'reject',
                            'department' => $currentRejectType,
                            'created_by' => Auth::user()->id,
                            'created_by_username' => Auth::user()->username,
                            'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        $success += 1;
                    } else {
                        $fail += 1;
                    }
                } else {
                    $fail += 1;
                }
            }
        }

        $rapidRejectInsert = Rft::insert($rapidRejectFiltered);

        $this->emit('alert', 'success', $success." output berhasil terekam. ");
        $this->emit('alert', 'error', $fail." output gagal terekam.");

        $this->rapidReject = [];
        $this->rapidRejectCount = 0;
    }

    public function closeInfo()
    {
        $this->info = false;
    }

    public function setDefectAreaPosition($x, $y)
    {
        $this->rejectPositionX = $x;
        $this->rejectPositionY = $y;
    }

    public function showDefectAreaImage($rejectImage, $x, $y)
    {
        $this->rejectImage = $rejectImage;
        $this->rejectPositionX = $x;
        $this->rejectPositionY = $y;

        $this->emit('showDefectAreaImage', $this->rejectImage, $this->rejectPositionX, $this->rejectPositionY);
    }

    public function hideDefectAreaImage()
    {
        $this->rejectImage = null;
        $this->rejectPositionX = null;
        $this->rejectPositionY = null;
    }

    public function updatingSearchRejectIn()
    {
        $this->resetPage('rejectInPage');
    }

    public function updatingSearchReject()
    {
        $this->resetPage('rejectsPage');
    }

    // Deprecated :
        // public function submitAllReject() {
        //     $availableReject = 0;
        //     $externalReject = 0;

        //     $allDefect = Defect::selectRaw('output_defects_packing.id id, output_defects_packing.master_plan_id master_plan_id, output_defects_packing.so_det_id so_det_id, output_defects_packing.kode_numbering, output_defects_packing.no_cut_size, output_defect_types.allocation, output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defects_packing.defect_area_x, output_defects_packing.defect_area_y, output_defect_in_out.status in_out_status')->
        //         leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
        //         leftJoin("output_defect_in_out", function ($join) {
        //             $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
        //             $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
        //         })->
        //         leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
        //         whereNull('output_defects_packing.kode_numbering')->
        //         where('output_defects_packing.defect_status', 'defect')->
        //         where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
        //         get();

        //     if ($allDefect->count() > 0) {
        //         $defectIds = [];
        //         foreach ($allDefect as $defect) {
        //             if ($defect->in_out_status != "defect") {
        //                 // create reject
        //                 $createReject = RejectModel::create([
        //                     "master_plan_id" => $defect->master_plan_id,
        //                     "so_det_id" => $defect->so_det_id,
        //                     "defect_id" => $defect->id,
        //                     "status" => "NORMAL",
        //                     "reject_status" => "defect",
        //                     'reject_type_id' => $defect->defect_type_id,
        //                     'reject_area_id' => $defect->defect_area_id,
        //                     'reject_area_x' => $defect->defect_area_x,
        //                     'reject_area_y' => $defect->defect_area_y,
        //                     "kode_numbering" => $defect->kode_numbering,
        //                     "no_cut_size" => $defect->no_cut_size,
        //                     'created_by' =>Auth::user()->username
        //                 ]);

        //                 // add defect ids
        //                 array_push($defectIds, $defect->id);

        //                 $availableReject += 1;
        //             } else {
        //                 $externalReject += 1;
        //             }
        //         }

        //         if ($availableReject > 0) {
        //             $this->emit('alert', 'success', $availableReject." DEFECT berhasil di REJECT");
        //         } else {
        //             $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT tidak berhasil di REJECT.");
        //         }

        //         if ($externalReject > 0) {
        //             $this->emit('alert', 'warning', $externalReject." DEFECT masih di proses MANDING/SPOTCLEANING.");
        //         }

        //     } else {
        //         $this->emit('alert', 'warning', "Data tidak ditemukan.");
        //     }
        // }

        // public function preSubmitMassReject($defectType, $defectArea, $defectTypeName, $defectAreaName) {
        //     $this->massQty = 1;
        //     $this->massSize = '';
        //     $this->massRejectType = $defectType;
        //     $this->massRejectTypeName = $defectTypeName;
        //     $this->massRejectArea = $defectArea;
        //     $this->massRejectAreaName = $defectAreaName;

        //     $this->emit('showModal', 'massReject');
        // }

        // public function submitMassReject() {
        //     $availableReject = 0;
        //     $externalReject = 0;

        //     $selectedDefect = Defect::selectRaw('output_defects_packing.id id, output_defects_packing.master_plan_id master_plan_id, output_defects_packing.so_det_id so_det_id, output_defects_packing.kode_numbering, output_defects_packing.no_cut_size, output_defect_types.allocation, output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defects_packing.defect_area_x, output_defects_packing.defect_area_y, so_det.size, output_defect_in_out.status in_out_status')->
        //         leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
        //         leftJoin("output_defect_in_out", function ($join) {
        //             $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
        //             $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
        //         })->
        //         leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
        //         whereNull('output_defects_packing.kode_numbering')->
        //         where('output_defects_packing.defect_status', 'defect')->
        //         where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
        //         where('output_defects_packing.defect_type_id', $this->massRejectType)->
        //         where('output_defects_packing.defect_area_id', $this->massRejectArea)->
        //         where('output_defects_packing.so_det_id', $this->massSize)->
        //         take($this->massQty)->get();

        //     if ($selectedDefect->count() > 0) {
        //         $defectIds = [];
        //         foreach ($selectedDefect as $defect) {
        //             // if ($defect->in_out_status != "defect") {
        //                 // create reject
        //                 $createReject = RejectModel::create([
        //                     "master_plan_id" => $defect->master_plan_id,
        //                     "so_det_id" => $defect->so_det_id,
        //                     "defect_id" => $defect->id,
        //                     "status" => "NORMAL",
        //                     "reject_status" => "defect",
        //                     'reject_type_id' => $defect->defect_type_id,
        //                     'reject_area_id' => $defect->defect_area_id,
        //                     'reject_area_x' => $defect->defect_area_x,
        //                     'reject_area_y' => $defect->defect_area_y,
        //                     "kode_numbering" => $defect->kode_numbering,
        //                     "no_cut_size" => $defect->no_cut_size,
        //                     'created_by' =>Auth::user()->username,
        //                 ]);

        //                 // add defect id array
        //                 array_push($defectIds, $defect->id);

        //                 $availableReject += 1;
        //             // } else {
        //             //     $externalReject += 1;
        //             // }
        //         }
        //         // update defect
        //         $defectSql = Defect::whereIn('id', $defectIds)->update([
        //             "defect_status" => "rejected"
        //         ]);

        //         if ($availableReject > 0) {
        //             $this->emit('alert', 'success', "DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massRejectTypeName." dan Area : ".$this->massRejectAreaName." berhasil di REJECT sebanyak ".$selectedDefect->count()." kali.");

        //             $this->emit('hideModal', 'massReject');
        //         } else {
        //             $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massRejectTypeName." dan Area : ".$this->massRejectAreaName." tidak berhasil di REJECT.");
        //         }

        //         if ($externalReject > 0) {
        //             $this->emit('alert', 'warning', $externalReject." DEFECT masih ada yang di proses MENDING/SPOTCLEANING.");
        //         }
        //     } else {
        //         $this->emit('alert', 'warning', "Data tidak ditemukan.");
        //     }
        // }

        // public function submitReject($defectId) {
        //     $externalReject = 0;

        //     $thisDefectReject = RejectModel::where('defect_id', $defectId)->count();

        //     if ($thisDefectReject < 1) {
        //         // get defect
        //         $defect = Defect::where('id', $defectId);
        //         $getDefect = Defect::selectRaw('output_defects_packing.*, output_defect_in_out.status')->leftJoin("output_defect_in_out", function ($join) {
        //             $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
        //             $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
        //         })->
        //         where('output_defects_packing.id', $defectId)->
        //         whereNull('output_defects_packing.kode_numbering')->
        //         first();

        //         if ($getDefect->status != 'defect') {
        //             // remove from defect
        //             $updateDefect = $defect->update([
        //                 "defect_status" => "rejected"
        //             ]);

        //             // add to reject
        //             $createReject = RejectModel::create([
        //                 "master_plan_id" => $getDefect->master_plan_id,
        //                 "so_det_id" => $getDefect->so_det_id,
        //                 "defect_id" => $defectId,
        //                 "reject_status" => 'defect',
        //                 'reject_type_id' => $getDefect->defect_type_id,
        //                 'reject_area_id' => $getDefect->defect_area_id,
        //                 'reject_area_x' => $getDefect->defect_area_x,
        //                 'reject_area_y' => $getDefect->defect_area_y,
        //                 "kode_numbering" => $getDefect->kode_numbering,
        //                 "no_cut_size" => $getDefect->no_cut_size,
        //                 'created_by' =>Auth::user()->username,
        //                 "status" => "NORMAL"
        //             ]);

        //             if ($createReject && $updateDefect) {
        //                 $this->emit('alert', 'success', "DEFECT dengan ID : ".$defectId." berhasil di REJECT.");
        //             } else {
        //                 $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan ID : ".$defectId." tidak berhasil di REJECT.");
        //             }
        //         } else {
        //             $this->emit('alert', 'error', "DEFECT ini masih di proses MENDING/SPOTCLEANING. DEFECT dengan ID : ".$defectId." tidak berhasil di REJECT.");
        //         }
        //     } else {
        //         $this->emit('alert', 'warning', "Pencegahan data redundant. DEFECT dengan ID : ".$defectId." sudah ada di REJECT.");
        //     }
        // }

        // public function cancelReject($rejectId) {
        //     // delete from reject
        //     $deleteReject = RejectModel::where('id', $rejectId)->delete();

        //     if ($deleteReject) {
        //         $this->emit('alert', 'success', "REJECT dengan REJECT ID : ".$rejectId." berhasil di hapus ke DEFECT.");
        //     } else {
        //         $this->emit('alert', 'error', "Terjadi kesalahan. REJECT dengan REJECT ID : ".$rejectId." tidak berhasil dihapus.");
        //     }
        // }

    public function render(SessionManager $session)
    {
        $this->emit('loadRejectPageJs');

        // if (isset($this->errorBag->messages()['numberingInput']) && collect($this->errorBag->messages()['numberingInput'])->contains(function ($message) {return Str::contains($message, 'Kode QR sudah discan');})) {
        //     foreach ($this->errorBag->messages()['numberingInput'] as $message) {
        //         $this->emit('alert', 'warning', $message);
        //     }
        // } else if ((isset($this->errorBag->messages()['numberingInput']) && collect($this->errorBag->messages()['numberingInput'])->contains("Harap scan qr.")) || (isset($this->errorBag->messages()['sizeInput']) && collect($this->errorBag->messages()['sizeInput'])->contains("Harap scan qr."))) {
        //     $this->emit('alert', 'error', "Harap scan QR.");
        // }
        if (isset($this->errorBag->messages()['numberingInput'])) {
            foreach ($this->errorBag->messages()['numberingInput'] as $message) {
                $this->emit('alert', 'error', $message);
            }
        }

        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        $this->allRejectImage = MasterPlan::select('gambar')->find($this->orderInfo->id);

        $this->allRejectPosition = Rft::select("reject_area_x", "reject_area_y")->
            leftJoin("output_rejects", "output_rejects.id", "=", "output_rfts_packing_po.reject_id")->
            leftJoin("output_defect_types", "output_defect_types.id", "=", "output_rejects.reject_type_id")->
            leftJoin("output_defect_areas", "output_defect_areas.id", "=", "output_rejects.reject_area_id")->
            where('output_rfts_packing_po.type', 'reject')->
            where('output_rfts_packing_po.master_plan_id', $this->orderInfo->id)->
            get();

        // Reject List
        $allRejectList = Rft::selectRaw('output_rfts_packing_po.department as output_type, COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id) as reject_type_id, COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id) as reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rfts_packing_po.master_plan_id')->
            leftJoin('output_rejects', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            leftJoin('output_rejects_packing', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id)'))->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id)'))->
            where("output_rfts_packing_po.type", "reject")->
            where('master_plan.id', $this->orderInfo->id)->
            whereRaw("
                (
                    output_rfts_packing_po.department LIKE '%".$this->allRejectListFilter."%' OR
                    output_defect_types.defect_type LIKE '%".$this->allRejectListFilter."%' OR
                    output_defect_areas.defect_area LIKE '%".$this->allRejectListFilter."%'
                )
            ")->
            groupBy('output_rejects_packing.reject_type_id', 'output_rejects_packing.reject_area_id', 'output_defect_types.defect_type', 'output_defect_areas.defect_area')->
            orderBy('total', 'desc')->
            paginate(5, ['*'], 'allRejectListPage');

        // Reject IN
        $rejectsQc = DB::table('output_rejects')->selectRaw('output_rejects.id, master_plan.sewing_line, output_rejects.kode_numbering, output_rejects.updated_at, output_rejects.created_at, output_rejects.reject_area_x, output_rejects.reject_area_y, output_rejects.reject_status, "qc" as output_type, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects.master_plan_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_rejects.so_det_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("(
                'qc' LIKE '%".$this->searchRejectIn."%' OR
                output_rejects.id LIKE '%".$this->searchRejectIn."%' OR
                so_det.size LIKE '%".$this->searchRejectIn."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchRejectIn."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchRejectIn."%' OR
                output_rejects.reject_status LIKE '%".$this->searchRejectIn."%'
            )")->
            orderBy('output_rejects.updated_at', 'desc');
        $rejectsPacking = DB::table('output_rejects_packing')->selectRaw('output_rejects_packing.id, master_plan.sewing_line, output_rejects_packing.kode_numbering, output_rejects_packing.updated_at, output_rejects_packing.created_at, output_rejects_packing.reject_area_x, output_rejects_packing.reject_area_y, output_rejects_packing.reject_status, "packing" as output_type, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("(
                'finishing' LIKE '%".$this->searchRejectIn."%' OR
                output_rejects_packing.id LIKE '%".$this->searchRejectIn."%' OR
                so_det.size LIKE '%".$this->searchRejectIn."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchRejectIn."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchRejectIn."%' OR
                output_rejects_packing.reject_status LIKE '%".$this->searchRejectIn."%'
            )")->
            orderBy('output_rejects_packing.updated_at', 'desc');
        $rejectIn = $rejectsQc->union($rejectsPacking)->paginate(10, ['*'], 'rejectInPage');

        $rejects = Rft::selectRaw('output_rfts_packing_po.*, ppic_master_so.po, COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id) as reject_type_id, COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id) as reject_area_id, COALESCE(output_rejects_packing.reject_area_x, output_rejects.reject_area_x) as reject_area_x, COALESCE(output_rejects_packing.reject_area_y, output_rejects.reject_area_y) as reject_area_y, output_defect_types.defect_type, output_defect_areas.defect_area, UPPER(output_rfts_packing_po.status) as reject_status, so_det.size as so_det_size')->
            leftJoin('output_rejects', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            leftJoin('output_rejects_packing', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id)'))->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id)'))->
            leftJoin('so_det', 'so_det.id', '=', 'output_rfts_packing_po.so_det_id')->
            leftJoin('laravel_nds.ppic_master_so', 'ppic_master_so.id', '=', 'output_rfts_packing_po.po_id')->
            where('output_rfts_packing_po.type', 'reject')->
            where('output_rfts_packing_po.master_plan_id', $this->orderInfo->id)->
            whereRaw("(
                output_rfts_packing_po.department LIKE '%".$this->searchReject."%' OR
                output_rfts_packing_po.id LIKE '%".$this->searchReject."%' OR
                so_det.size LIKE '%".$this->searchReject."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchReject."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchReject."%' OR
                output_rfts_packing_po.status LIKE '%".$this->searchReject."%'
            )")->
            orderBy('output_rfts_packing_po.updated_at', 'desc')->paginate(10, ['*'], 'rejectsPage');

        $this->massSelectedDefect = DB::table('output_defects_packing')->selectRaw('output_defects_packing.so_det_id, so_det.size as size, count(*) as total')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            where('output_defects_packing.defect_type_id', $this->massRejectType)->
            where('output_defects_packing.defect_area_id', $this->massRejectArea)->
            groupBy('output_defects_packing.so_det_id', 'so_det.size')->get();

        // Defect types
        $this->defectTypes = DB::table("output_defect_types")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DB::table("output_defect_areas")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_area')->get();

        // Reject
        $this->reject = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and type = 'reject' group by so_det.id"));

        return view('livewire.reject', ['rejectIn' => $rejectIn, 'rejects' => $rejects, 'allRejectList' => $allRejectList]);
    }
}
