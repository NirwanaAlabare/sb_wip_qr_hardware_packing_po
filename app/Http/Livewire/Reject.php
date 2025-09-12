<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use App\Models\SignalBit\Reject as RejectModel;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Defect;
use App\Models\SignalBit\DefectType;
use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\MasterPlan;
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
    public $sizeInput;
    public $sizeInputText;
    public $noCutInput;
    public $numberingInput;
    public $reject;

    public $rapidReject;
    public $rapidRejectCount;

    public $searchDefect;
    public $searchReject;
    public $defectImage;
    public $defectPositionX;
    public $defectPositionY;
    public $allDefectListFilter;
    public $allDefectImage;
    public $allDefectPosition;
    public $massQty;
    public $massSize;
    public $massDefectType;
    public $massDefectTypeName;
    public $massDefectArea;
    public $massDefectAreaName;
    public $massSelectedDefect;
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

        'rejectType' => 'required',
        'rejectArea' => 'required',
        'rejectAreaPositionX' => 'required',
        'rejectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',

        'rejectType.required' => 'Harap tentukan jenis reject.',
        'rejectArea.required' => 'Harap tentukan area reject.',
        'rejectAreaPositionX.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        'rejectAreaPositionY.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updateOutputReject' => 'updateOutput',
        'setAndSubmitInputReject' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError',

        'submitInputReject' => 'submitInput',
        'submitReject' => 'submitReject',
        'submitAllReject' => 'submitAllReject',
        'cancelReject' => 'cancelReject',
        'hideDefectAreaImageClear' => 'hideDefectAreaImage',
        'updateWsDetailSizes' => 'updateWsDetailSizes',

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
        if (DB::table('output_rfts_packing_po')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di RFT.');
            return true;
        }

        if (DB::table('output_rejects_packing')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di Reject.');
            return true;
        }

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

    public function updateOutput()
    {
        // Reject
        $this->reject = collect(DB::select("select output_rejects_packing.*, so_det.size, COUNT(output_rejects_packing.id) output from `output_rejects_packing` left join `so_det` on `so_det`.`id` = `output_rejects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));
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

    public function preSubmitInput($value)
    {
        $this->emit('qrInputFocus', 'reject');

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
            $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $numberingInput)->first();

            if ($numberingData) {
                $this->sizeInput = $numberingData->so_det_id;
                $this->sizeInputText = $numberingData->size;
                $this->noCutInput = $numberingData->no_cut_size;
                $this->numberingInput = $numberingInput;
            }
        }

        $scannedDefectData = Defect::where("kode_numbering", $numberingInput)->first();

        // check defect
        if ($scannedDefectData) {
            if ($scannedDefectData->defect_status == "defect") {
                $this->rejectType = $scannedDefectData->defect_type_id;
                $this->rejectArea = $scannedDefectData->defect_area_id;
                $this->rejectAreaPositionX = $scannedDefectData->defect_area_x;
                $this->rejectAreaPositionY = $scannedDefectData->defect_area_y;

                $this->numberingInput = $numberingInput;

                $this->emit('loadingStart');

                $this->emitSelf('submitInputReject');
            } else {
                $this->emit('qrInputFocus', 'reject');

                $this->emit('alert', 'warning', "Kode qr sudah discan di REWORK.");
            }
        } else {
            $validation = Validator::make([
                'sizeInput' => $this->sizeInput,
                'noCutInput' => $this->noCutInput,
                'numberingInput' => $numberingInput
            ], [
                'sizeInput' => 'required',
                'noCutInput' => 'required',
                'numberingInput' => 'required'
            ], [
                'sizeInput.required' => 'Harap scan qr.',
                'noCutInput.required' => 'Harap scan qr.',
                'numberingInput.required' => 'Harap scan qr.',
            ]);

            if ($this->checkIfNumberingExists($numberingInput)) {
                return;
            }

            if ($validation->fails()) {
                $this->emit('qrInputFocus', 'reject');

                $validation->validate();
            } else {
                $endlineOutputData = DB::connection('mysql_sb')->table('output_rfts')->where("kode_numbering", $numberingInput)->first();

                if ($endlineOutputData) {
                    if ($this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
                        $this->emit('clearSelectRejectAreaPoint');

                        $this->rejectType = null;
                        $this->rejectArea = null;
                        $this->rejectAreaPositionX = null;
                        $this->rejectAreaPositionY = null;

                        $this->numberingInput = $numberingInput;

                        $this->validateOnly('sizeInput');

                        $this->emit('showModal', 'reject', 'regular');
                    } else {
                        $this->emit('qrInputFocus', 'reject');

                        $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
                    }
                } else {
                    $this->emit('alert', 'error', "Output dari <b>QC</b> tidak ditemukan.");
                }
            }
        }
    }

    public function submitInput()
    {
        $this->emit('qrInputFocus', 'reject');

        if ($this->numberingInput) {
            // if (str_contains($this->numberingInput, 'WIP')) {
            //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->numberingInput)->first();
            // } else {
            //     $numberingCodes = explode('_', $this->numberingInput);

            //     if (count($numberingCodes) > 2) {
            //         $this->numberingInput = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
            //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->numberingInput)->first();
            //     } else {
            //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->numberingInput)->first();
            //     }
            // }

            // One Straight Format
            $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->numberingInput)->first();

            if ($numberingData) {
                $this->sizeInput = $numberingData->so_det_id;
                $this->sizeInputText = $numberingData->size;
                $this->noCutInput = $numberingData->no_cut_size;
            }
        }

        $validatedData = $this->validate();

        if ($this->checkIfNumberingExists()) {
            return;
        }

        if ($this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
            $continue = false;

            $scannedDefectData = Defect::selectRaw("output_defects_packing.*, master_plan.sewing_line, master_plan.tgl_plan, master_plan.color, output_defect_in_out.status in_out_status")->
                leftJoin("output_defect_in_out", function ($join) {
                    $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                    $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
                })->
                leftJoin("master_plan", "master_plan.id", "=", "output_defects_packing.master_plan_id")->
                where("output_defects_packing.kode_numbering", $this->numberingInput)->first();

            // check defect
            if ($scannedDefectData) {
                if ($scannedDefectData->master_plan_id == $this->orderInfo->id) {
                    if ($scannedDefectData->defect_status == "defect") {
                        $scannedDefectData->defect_status = "rejected";
                        $scannedDefectData->save();

                        $continue = true;
                    } else {
                        $continue = false;

                        $this->emit('alert', 'error', "Data DEFECT sudah : <b>'".$scannedDefectData->defect_status."'</b>)");
                    }
                } else {
                    $continue = false;

                    $this->emit('alert', 'error', "Data DEFECT berada di Plan lain (<b>ID :".$scannedDefectData->master_plan_id."/".$scannedDefectData->tgl_plan."/".$scannedDefectData->color."/".strtoupper(str_replace("_", " ", $scannedDefectData->sewing_line))."</b>)");
                }
            } else {
                $endlineOutputData = DB::connection('mysql_sb')->table('output_rfts')->where("kode_numbering", $this->numberingInput)->first();

                if ($endlineOutputData) {
                    $continue = true;
                } else {
                    $continue = false;

                    $this->emit('alert', 'error', "Data tidak ditemukan di QC");
                }
            }

            // continue
            if ($continue) {
                $insertReject = RejectModel::create([
                    'master_plan_id' => $this->orderInfo->id,
                    'so_det_id' => $this->sizeInput,
                    'no_cut_size' => $this->noCutInput,
                    'kode_numbering' => $this->numberingInput,
                    "defect_id" => $scannedDefectData ? $scannedDefectData->id : null,
                    'status' => 'NORMAL',
                    'reject_type_id' => $scannedDefectData ? $scannedDefectData->defect_type_id : $this->rejectType,
                    'reject_area_id' => $scannedDefectData ? $scannedDefectData->defect_area_id : $this->rejectArea,
                    'reject_area_x' => $scannedDefectData ? $scannedDefectData->defect_area_x : $this->rejectAreaPositionX,
                    'reject_area_y' => $scannedDefectData ? $scannedDefectData->defect_area_y : $this->rejectAreaPositionY,
                    'reject_status' => $scannedDefectData ? 'defect' : 'mati',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'created_by' => Auth::user()->username,
                ]);

                if ($insertReject) {
                    $this->emit('alert', 'success', "1 output berukuran ".$this->sizeInputText." berhasil terekam.");
                    $this->emit('hideModal', 'reject', 'regular');

                    $this->sizeInput = '';
                    $this->sizeInputText = '';
                    $this->noCutInput = '';
                    $this->numberingInput = '';
                } else {
                    $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
                }
            } else {
                $this->emit('alert', 'warning', "QR Sudah discan.");
            }
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        }

        $this->emit('qrInputFocus', 'reject');
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText) {
        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;

        $this->preSubmitInput($scannedNumbering);
    }

    public function pushRapidReject($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        if (count($this->rapidReject) < 100) {
            foreach ($this->rapidReject as $item) {
                if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                    $exist = true;
                }
            }

            if (!$exist) {
                if ($numberingInput) {
                    $this->rapidRejectCount += 1;

                    array_push($this->rapidReject, [
                        'numberingInput' => $numberingInput,
                    ]);
                }
            }
        } else {
            $this->emit('alert', 'error', "Anda sudah mencapai batas rapid scan. Harap klik selesai dahulu.");
        }
    }

    public function preSubmitRapidInput()
    {
        $this->rejectType = null;
        $this->rejectArea = null;
        $this->rejectAreaPositionX = null;
        $this->rejectAreaPositionY = null;

        $this->emit('showModal', 'reject', 'rapid');
    }

    public function submitRapidInput() {
        $rapidRejectFiltered = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidReject && count($this->rapidReject) > 0) {
            for ($i = 0; $i < count($this->rapidReject); $i++) {
                // if (str_contains($this->rapidReject[$i]['numberingInput'], 'WIP')) {
                //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->rapidReject[$i]['numberingInput'])->first();
                // } else {
                //     $numberingCodes = explode('_', $this->rapidReject[$i]['numberingInput']);

                //     if (count($numberingCodes) > 1) {
                //         $this->rapidReject[$i]['numberingInput'] = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidReject[$i]['numberingInput'])->first();
                //     } else {
                //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->rapidReject[$i]['numberingInput'])->first();
                //     }
                // }

                // One Straight Format
                $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidReject[$i]['numberingInput'])->first();

                if (((DB::connection('mysql_sb')->table('output_rejects_packing')->where('kode_numbering', $this->rapidReject[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_rfts_packing')->where('kode_numbering', $this->rapidReject[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_defects_packing')->where('kode_numbering', $this->rapidReject[$i]['numberingInput'])->count()) < 1) && ($this->orderWsDetailSizes->where('so_det_id', $numberingData->so_det_id)->count() > 0)) {
                    $scannedDefectData = Defect::where("defect_status", "defect")->where("kode_numbering", $this->rapidReject[$i]['numberingInput'])->first();

                    if ($scannedDefectData) {
                        $scannedDefectData->defect_status = 'rejected';
                        $scannedDefectData->save();
                    }

                    array_push($rapidRejectFiltered, [
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $numberingData->so_det_id,
                        'no_cut_size' => $numberingData->no_cut_size,
                        'kode_numbering' => $this->rapidReject[$i]['numberingInput'],
                        'defect_id' => $scannedDefectData ? $scannedDefectData->id : null,
                        'reject_type_id' => $scannedDefectData ? $scannedDefectData->defectType->defect_type_id : $this->rejectType,
                        'reject_area_id' => $scannedDefectData ? $scannedDefectData->defectArea->defect_area_id : $this->rejectArea,
                        'reject_area_x' => $scannedDefectData ? $scannedDefectData->defect_area_x : $this->rejectAreaPositionX,
                        'reject_area_y' => $scannedDefectData ? $scannedDefectData->defect_area_y : $this->rejectAreaPositionY,
                        'reject_status' => 'mati',
                        'status' => 'NORMAL',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'created_by' => Auth::user()->username,
                    ]);

                    $success += 1;
                } else {
                    $fail += 1;
                }
            }
        }

        $rapidRejectInsert = RejectModel::insert($rapidRejectFiltered);

        if ($success > 0) {
            $this->emit('alert', 'success', $success." output berhasil terekam. ");
        }

        if ($fail > 0) {
            $this->emit('alert', 'error', $fail." output gagal terekam.");
        }

        $this->rapidReject = [];
        $this->rapidRejectCount = 0;
    }

    public function closeInfo()
    {
        $this->info = false;
    }

    public function setDefectAreaPosition($x, $y)
    {
        $this->defectPositionX = $x;
        $this->defectPositionY = $y;
    }

    public function showDefectAreaImage($defectImage, $x, $y)
    {
        $this->defectImage = $defectImage;
        $this->defectPositionX = $x;
        $this->defectPositionY = $y;

        $this->emit('showDefectAreaImage', $this->defectImage, $this->defectPositionX, $this->defectPositionY);
    }

    public function hideDefectAreaImage()
    {
        $this->defectImage = null;
        $this->defectPositionX = null;
        $this->defectPositionY = null;
    }

    public function updatingSearchDefect()
    {
        $this->resetPage('defectsPage');
    }

    public function updatingSearchReject()
    {
        $this->resetPage('rejectsPage');
    }

    public function submitAllReject() {
        $availableReject = 0;
        $externalReject = 0;

        $allDefect = Defect::selectRaw('output_defects_packing.id id, output_defects_packing.master_plan_id master_plan_id, output_defects_packing.so_det_id so_det_id, output_defects_packing.kode_numbering, output_defects_packing.no_cut_size, output_defect_types.allocation, output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defects_packing.defect_area_x, output_defects_packing.defect_area_y, output_defect_in_out.status in_out_status')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            whereNull('output_defects_packing.kode_numbering')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            get();

        if ($allDefect->count() > 0) {
            $defectIds = [];
            foreach ($allDefect as $defect) {
                if ($defect->in_out_status != "defect") {
                    // create reject
                    $createReject = RejectModel::create([
                        "master_plan_id" => $defect->master_plan_id,
                        "so_det_id" => $defect->so_det_id,
                        "defect_id" => $defect->id,
                        "status" => "NORMAL",
                        "reject_status" => "defect",
                        'reject_type_id' => $defect->defect_type_id,
                        'reject_area_id' => $defect->defect_area_id,
                        'reject_area_x' => $defect->defect_area_x,
                        'reject_area_y' => $defect->defect_area_y,
                        "kode_numbering" => $defect->kode_numbering,
                        "no_cut_size" => $defect->no_cut_size,
                        'created_by' =>Auth::user()->username
                    ]);

                    // add defect ids
                    array_push($defectIds, $defect->id);

                    $availableReject += 1;
                } else {
                    $externalReject += 1;
                }
            }
            // update defect
            $defectSql = Defect::whereIn('id', $defectIds)->update([
                "defect_status" => "rejected"
            ]);

            if ($availableReject > 0) {
                $this->emit('alert', 'success', $availableReject." DEFECT berhasil di REJECT");
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT tidak berhasil di REJECT.");
            }

            if ($externalReject > 0) {
                $this->emit('alert', 'warning', $externalReject." DEFECT masih di proses MANDING/SPOTCLEANING.");
            }

        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function preSubmitMassReject($defectType, $defectArea, $defectTypeName, $defectAreaName) {
        $this->massQty = 1;
        $this->massSize = '';
        $this->massDefectType = $defectType;
        $this->massDefectTypeName = $defectTypeName;
        $this->massDefectArea = $defectArea;
        $this->massDefectAreaName = $defectAreaName;

        $this->emit('showModal', 'massReject');
    }

    public function submitMassReject() {
        $availableReject = 0;
        $externalReject = 0;

        $selectedDefect = Defect::selectRaw('output_defects_packing.id id, output_defects_packing.master_plan_id master_plan_id, output_defects_packing.so_det_id so_det_id, output_defects_packing.kode_numbering, output_defects_packing.no_cut_size, output_defect_types.allocation, output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defects_packing.defect_area_x, output_defects_packing.defect_area_y, so_det.size, output_defect_in_out.status in_out_status')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            whereNull('output_defects_packing.kode_numbering')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            where('output_defects_packing.defect_type_id', $this->massDefectType)->
            where('output_defects_packing.defect_area_id', $this->massDefectArea)->
            where('output_defects_packing.so_det_id', $this->massSize)->
            take($this->massQty)->get();

        if ($selectedDefect->count() > 0) {
            $defectIds = [];
            foreach ($selectedDefect as $defect) {
                // if ($defect->in_out_status != "defect") {
                    // create reject
                    $createReject = RejectModel::create([
                        "master_plan_id" => $defect->master_plan_id,
                        "so_det_id" => $defect->so_det_id,
                        "defect_id" => $defect->id,
                        "status" => "NORMAL",
                        "reject_status" => "defect",
                        'reject_type_id' => $defect->defect_type_id,
                        'reject_area_id' => $defect->defect_area_id,
                        'reject_area_x' => $defect->defect_area_x,
                        'reject_area_y' => $defect->defect_area_y,
                        "kode_numbering" => $defect->kode_numbering,
                        "no_cut_size" => $defect->no_cut_size,
                        'created_by' =>Auth::user()->username,
                    ]);

                    // add defect id array
                    array_push($defectIds, $defect->id);

                    $availableReject += 1;
                // } else {
                //     $externalReject += 1;
                // }
            }
            // update defect
            $defectSql = Defect::whereIn('id', $defectIds)->update([
                "defect_status" => "rejected"
            ]);

            if ($availableReject > 0) {
                $this->emit('alert', 'success', "DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massDefectTypeName." dan Area : ".$this->massDefectAreaName." berhasil di REJECT sebanyak ".$selectedDefect->count()." kali.");

                $this->emit('hideModal', 'massReject');
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massDefectTypeName." dan Area : ".$this->massDefectAreaName." tidak berhasil di REJECT.");
            }

            if ($externalReject > 0) {
                $this->emit('alert', 'warning', $externalReject." DEFECT masih ada yang di proses MENDING/SPOTCLEANING.");
            }
        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function submitReject($defectId) {
        $externalReject = 0;

        $thisDefectReject = RejectModel::where('defect_id', $defectId)->count();

        if ($thisDefectReject < 1) {
            // get defect
            $defect = Defect::where('id', $defectId);
            $getDefect = Defect::selectRaw('output_defects_packing.*, output_defect_in_out.status')->leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            where('output_defects_packing.id', $defectId)->
            whereNull('output_defects_packing.kode_numbering')->
            first();

            if ($getDefect->status != 'defect') {
                // remove from defect
                $updateDefect = $defect->update([
                    "defect_status" => "rejected"
                ]);

                // add to reject
                $createReject = RejectModel::create([
                    "master_plan_id" => $getDefect->master_plan_id,
                    "so_det_id" => $getDefect->so_det_id,
                    "defect_id" => $defectId,
                    "reject_status" => 'defect',
                    'reject_type_id' => $getDefect->defect_type_id,
                    'reject_area_id' => $getDefect->defect_area_id,
                    'reject_area_x' => $getDefect->defect_area_x,
                    'reject_area_y' => $getDefect->defect_area_y,
                    "kode_numbering" => $getDefect->kode_numbering,
                    "no_cut_size" => $getDefect->no_cut_size,
                    'created_by' =>Auth::user()->username,
                    "status" => "NORMAL"
                ]);

                if ($createReject && $updateDefect) {
                    $this->emit('alert', 'success', "DEFECT dengan ID : ".$defectId." berhasil di REJECT.");
                } else {
                    $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan ID : ".$defectId." tidak berhasil di REJECT.");
                }
            } else {
                $this->emit('alert', 'error', "DEFECT ini masih di proses MENDING/SPOTCLEANING. DEFECT dengan ID : ".$defectId." tidak berhasil di REJECT.");
            }
        } else {
            $this->emit('alert', 'warning', "Pencegahan data redundant. DEFECT dengan ID : ".$defectId." sudah ada di REJECT.");
        }
    }

    public function cancelReject($rejectId, $defectId) {
        // add to defect
        $defect = Defect::where('id', $defectId);
        $getDefect = $defect->first();
        $updateDefect = $defect->update([
            "defect_status" => "defect"
        ]);

        // delete from reject
        $deleteReject = RejectModel::where('id', $rejectId)->delete();

        if ($deleteReject && $updateDefect) {
            $this->emit('alert', 'success', "REJECT dengan REJECT ID : ".$rejectId." dan DEFECT ID : ".$defectId." berhasil di kembalikan ke DEFECT.");
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. REJECT dengan REJECT ID : ".$rejectId." dan DEFECT ID : ".$defectId." tidak berhasil dikembalikan ke DEFECT.");
        }
    }

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

        $this->allDefectImage = MasterPlan::select('gambar')->find($this->orderInfo->id);

        $this->allDefectPosition = DB::table('output_defects_packing')->where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            get();

        $allDefectList = DB::table('output_defects_packing')->selectRaw('output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_defects_packing.defect_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            whereRaw("
                (
                    output_defect_types.defect_type LIKE '%".$this->allDefectListFilter."%' OR
                    output_defect_areas.defect_area LIKE '%".$this->allDefectListFilter."%'
                )
            ")->
            groupBy('output_defects_packing.defect_type_id', 'output_defects_packing.defect_area_id', 'output_defect_types.defect_type', 'output_defect_areas.defect_area')->
            orderBy('output_defects_packing.updated_at', 'desc')->
            paginate(5, ['*'], 'allDefectListPage');

        $defects = DB::table('output_defects_packing')->selectRaw('output_defects_packing.*, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_defects_packing.defect_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            whereRaw("(
                output_defects_packing.id LIKE '%".$this->searchDefect."%' OR
                so_det.size LIKE '%".$this->searchDefect."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchDefect."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchDefect."%' OR
                output_defects_packing.defect_status LIKE '%".$this->searchDefect."%'
            )")->
            orderBy('output_defects_packing.updated_at', 'desc')->paginate(10, ['*'], 'defectsPage');

        $rejects = RejectModel::selectRaw('output_rejects_packing.*, so_det.size as so_det_size')->
            leftJoin('output_defects_packing', 'output_defects_packing.id', '=', 'output_rejects_packing.defect_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', DB::raw('COALESCE(output_defects_packing.defect_area_id, output_rejects_packing.reject_area_id)'))->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', DB::raw('COALESCE(output_defects_packing.defect_type_id, output_rejects_packing.reject_type_id)'))->
            leftJoin('so_det', 'so_det.id', '=', DB::raw('COALESCE(output_defects_packing.so_det_id, output_rejects_packing.so_det_id)'))->
            where('output_rejects_packing.master_plan_id', $this->orderInfo->id)->
            whereRaw("(
                output_rejects_packing.id LIKE '%".$this->searchReject."%' OR
                output_defects_packing.id LIKE '%".$this->searchReject."%' OR
                so_det.size LIKE '%".$this->searchReject."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchReject."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchReject."%' OR
                output_rejects_packing.reject_status LIKE '%".$this->searchReject."%'
            )")->
            orderBy('output_rejects_packing.updated_at', 'desc')->paginate(10, ['*'], 'rejectsPage');

        $this->massSelectedDefect = DB::table('output_defects_packing')->selectRaw('output_defects_packing.so_det_id, so_det.size as size, count(*) as total')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            where('output_defects_packing.defect_type_id', $this->massDefectType)->
            where('output_defects_packing.defect_area_id', $this->massDefectArea)->
            groupBy('output_defects_packing.so_det_id', 'so_det.size')->get();

        // Defect types
        $this->defectTypes = DB::table("output_defect_types")->leftJoin(DB::raw("(select reject_type_id, count(id) total_reject from output_rejects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by reject_type_id) as rejects"), "rejects.reject_type_id", "=", "output_defect_types.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DB::table("output_defect_areas")->leftJoin(DB::raw("(select reject_area_id, count(id) total_reject from output_rejects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by reject_area_id) as rejects"), "rejects.reject_area_id", "=", "output_defect_areas.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_area')->get();

        // Reject
        $this->reject = collect(DB::select("select output_rejects_packing.*, so_det.size, COUNT(output_rejects_packing.id) output from `output_rejects_packing` left join `so_det` on `so_det`.`id` = `output_rejects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));

        return view('livewire.reject', ['defects' => $defects, 'rejects' => $rejects, 'allDefectList' => $allDefectList]);
    }
}
