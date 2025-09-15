<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use App\Models\Nds\Numbering;
use App\Models\Nds\OutputPacking;
use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Defect;
use App\Models\SignalBit\Rework as ReworkModel;
use Carbon\Carbon;
use DB;

class Rework extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // filters
    public $orderInfo;
    public $orderWsDetailSizes;
    public $searchDefect;
    public $searchRework;

    // defect position
    public $defectImage;
    public $defectPositionX;
    public $defectPositionY;

    // defect list
    public $allDefectListFilter;
    public $allDefectImage;
    public $allDefectPosition;
    // public $allDefectList;

    // mass rework
    public $massQty;
    public $massSize;
    public $massDefectType;
    public $massDefectTypeName;
    public $massDefectArea;
    public $massDefectAreaName;
    public $massSelectedDefect;

    public $info;

    public $rework;
    public $sizeInputText;
    public $noCutInput;
    public $numberingInput;

    public $rapidRework;
    public $rapidReworkCount;

    protected $rules = [
        'sizeInput' => 'required',
        'noCutInput' => 'required',
        'numberingInput' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',
    ];

    protected $listeners = [
        'submitRework' => 'submitRework',
        'submitAllRework' => 'submitAllRework',
        'cancelRework' => 'cancelRework',
        'hideDefectAreaImageClear' => 'hideDefectAreaImage',
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'setAndSubmitInputRework' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError'
    ];

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

    public function updateWsDetailSizes($panel)
    {
        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        if ($panel == 'rework') {
            $this->emit('qrInputFocus', 'rework');
        }
    }

    public function updateOutput()
    {
        $this->rework = collect(DB::select("select output_defects_packing.*, so_det.size, COUNT(output_defects_packing.id) output from `output_defects_packing` left join `so_det` on `so_det`.`id` = `output_defects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'reworked' group by so_det.id"));
    }

    public function loadReworkPage()
    {
        $this->emit('loadReworkPageJs');
    }

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);

        $this->massSize = '';

        $this->info = true;

        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        $this->rapidRework = [];
        $this->rapidReworkCount = 0;
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

    public function updatingSearchRework()
    {
        $this->resetPage('reworksPage');
    }

    public function submitAllRework() {
        $availableRework = 0;
        $externalRework = 0;

        $allDefect = DB::connection('mysql_sb')->table('output_defects_packing')->selectRaw('output_defects_packing.id id, output_defects_packing.master_plan_id master_plan_id, output_defects_packing.kode_numbering, output_defects_packing.no_cut_size, output_defects_packing.so_det_id so_det_id, output_defect_in_out.status in_out_status')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->get();

        if ($allDefect->count() > 0) {
            $defectIds = [];
            $rftArray = [];
            $rftArrayNds = [];
            foreach ($allDefect as $defect) {
                if ($defect->in_out_status != "defect") {
                    // create rework
                    $createRework = ReworkModel::create([
                        "defect_id" => $defect->id,
                        "status" => "NORMAL",
                        'created_by' => Auth::user()->username
                    ]);

                    // add defect ids
                    array_push($defectIds, $defect->id);

                    // add rft array
                    array_push($rftArray, [
                        'master_plan_id' => $defect->master_plan_id,
                        'no_cut_size' => $defect->no_cut_size,
                        'kode_numbering' => $defect->kode_numbering,
                        'so_det_id' => $defect->so_det_id,
                        'status' => "REWORK",
                        'rework_id' => $createRework->id,
                        'created_by' => Auth::user()->line_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    // add rft array nds
                    array_push($rftArrayNds, [
                        'sewing_line' => $this->orderInfo->sewing_line,
                        'master_plan_id' => $defect->master_plan_id,
                        'no_cut_size' => $defect->no_cut_size,
                        'kode_numbering' => $defect->kode_numbering,
                        'so_det_id' => $defect->so_det_id,
                        'status' => "REWORK",
                        'rework_id' => $createRework->id,
                        'created_by' => Auth::user()->line_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    $availableRework += 1;
                } else {
                    $externalRework += 1;
                }
            }

            // update defect
            $updateDefect = Defect::whereIn("id", $defectIds)->update([
                "defect_status" => "reworked"
            ]);

            // create rft
            $createRft = Rft::insert($rftArray);

            // create rft nds
            $createRftNds = OutputPacking::insert($rftArrayNds);

            if ($availableRework > 0) {
                $this->emit('alert', 'success', $availableRework." DEFECT berhasil di REWORK");

                // $this->emit('triggerDashboard', Auth::user()->line->username, Carbon::now()->format('Y-m-d'));
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT tidak berhasil di REWORK.");
            }

            if ($externalRework > 0) {
                $this->emit('alert', 'warning', $externalRework." DEFECT masih di proses MENDING/SPOTCLEANING.");
            }
        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function preSubmitMassRework($defectType, $defectArea, $defectTypeName, $defectAreaName) {
        $this->massQty = 1;
        $this->massSize = '';
        $this->massDefectType = $defectType;
        $this->massDefectTypeName = $defectTypeName;
        $this->massDefectArea = $defectArea;
        $this->massDefectAreaName = $defectAreaName;

        $this->emit('showModal', 'massRework');
    }

    public function submitMassRework() {
        $availableRework = 0;
        $externalRework = 0;

        $selectedDefect = DB::table('output_defects_packing')->
            selectRaw('output_defects_packing.*, so_det.size as size, output_defect_in_out.status in_out_status')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            where('output_defects_packing.defect_type_id', $this->massDefectType)->
            where('output_defects_packing.defect_area_id', $this->massDefectArea)->
            where('output_defects_packing.so_det_id', $this->massSize)->
            take($this->massQty)->get();

        if ($selectedDefect->count() > 0) {
            foreach ($selectedDefect as $defect) {
                if ($defect->in_out_status != "defect") {
                    // create rework
                    $createRework = ReworkModel::create([
                        "defect_id" => $defect->id,
                        "status" => "NORMAL"
                    ]);

                    // update defect
                    $defectSql = Defect::where('id', $defect->id)->update([
                        "defect_status" => "reworked"
                    ]);

                    // create rft
                    $createRft = Rft::create([
                        'master_plan_id' => $defect->master_plan_id,
                        'no_cut_size' => $defect->no_cut_size,
                        'kode_numbering' => $defect->kode_numbering,
                        'so_det_id' => $defect->so_det_id,
                        'status' => 'REWORK',
                        'rework_id' => $createRework->id,
                        'created_by' => Auth::user()->line_id,
                    ]);

                    // create rft nds
                    $createRftNds = OutputPacking::create([
                        'sewing_line' => $this->orderInfo->sewing_line,
                        'master_plan_id' => $defect->master_plan_id,
                        'no_cut_size' => $defect->no_cut_size,
                        'kode_numbering' => $defect->kode_numbering,
                        'so_det_id' => $defect->so_det_id,
                        'status' => 'REWORK',
                        'rework_id' => $createRework->id,
                        'created_by' => Auth::user()->line_id,
                    ]);
                    $availableRework++;
                } else {
                    $externalRework++;
                }
            }

            if ($availableRework > 0) {
                $this->emit('alert', 'success', "DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massDefectTypeName." dan Area : ".$this->massDefectAreaName." berhasil di REWORK sebanyak ".$selectedDefect->count()." kali.");

                $this->emit('hideModal', 'massRework');
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan Ukuran : ".$selectedDefect[0]->size.", Tipe : ".$this->massDefectTypeName." dan Area : ".$this->massDefectAreaName." tidak berhasil di REWORK.");
            }

            if ($externalRework > 0) {
                $this->emit('alert', 'warning', $externalRework." DEFECT masih ada yang di proses MENDING/SPOTCLEANING.");
            }
        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function submitRework($defectId) {
        $thisDefectRework = DB::connection('mysql_sb')->table('output_reworks_packing')->where('defect_id', $defectId)->count();

        if ($thisDefectRework < 1) {
            $defect = Defect::where('id', $defectId);
            $getDefect = Defect::selectRaw('output_defects_packing.*, output_defect_in_out.status in_out_status')->
                leftJoin("output_defect_in_out", function ($join) {
                    $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                    $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
                })->
                where('output_defects_packing.id', $defectId)->
                first();

            if ($getDefect->in_out_status != 'defect') {
                // add to rework
                $createRework = ReworkModel::create([
                    "defect_id" => $defectId,
                    "status" => "NORMAL"
                ]);

                // remove from defect
                $defect = Defect::where('id', $defectId)->first();
                $defect->defect_status = "reworked";
                $defect->save();

                // add to rft
                $createRft = Rft::create([
                    'master_plan_id' => $defect->master_plan_id,
                    'no_cut_size' => $defect->no_cut_size,
                    'kode_numbering' => $defect->kode_numbering,
                    'so_det_id' => $defect->so_det_id,
                    "status" => "REWORK",
                    "rework_id" => $createRework->id,
                    'created_by' => Auth::user()->line_id,
                ]);

                // add to rft nds
                $rftNds = OutputPacking::where("kode_numbering", $defect->kode_numbering)->orWhere("rework_id", $createRework->id)->first();
                if (!$rftNds) {
                    $createRftNds = OutputPacking::create([
                        'sewing_line' => $this->orderInfo->sewing_line,
                        'master_plan_id' => $defect->master_plan_id,
                        'no_cut_size' => $defect->no_cut_size,
                        'kode_numbering' => $defect->kode_numbering,
                        'so_det_id' => $defect->so_det_id,
                        "status" => "REWORK",
                        "rework_id" => $createRework->id,
                        'created_by' => Auth::user()->line_id,
                    ]);
                }

                if ($createRework && $createRft) {
                    $this->emit('alert', 'success', "DEFECT dengan ID : ".$defectId." berhasil di REWORK.");
                } else {
                    $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan ID : ".$defectId." tidak berhasil di REWORK.");
                }
            } else {
                $this->emit('alert', 'error', "DEFECT ini masih di proses MENDING/SPOTCLEANING. DEFECT dengan ID : ".$defectId." tidak berhasil di REWORK.");
            }
        } else {
            $this->emit('alert', 'warning', "Pencegahan data redundant. DEFECT dengan ID : ".$defectId." sudah ada di REWORK.");
        }
    }

    public function cancelRework($reworkId, $defectId) {
        // delete from rework
        $deleteRework = ReworkModel::where('id', $reworkId)->delete();

        // add to defect
        $defect = Defect::where('id', $defectId)->first();
        $defect->defect_status = 'defect';
        $defect->save();

        // delete from rft
        $deleteRft = Rft::where('rework_id', $reworkId)->delete();

        // delete from rft nds
        $deleteRftNds = OutputPacking::where('rework_id', $reworkId)->delete();

        if ($deleteRework && $defect && $deleteRft) {
            $this->emit('alert', 'success', "REWORK dengan REWORK ID : ".$reworkId." dan DEFECT ID : ".$defectId." berhasil di kembalikan ke DEFECT.");
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. REWORK dengan REWORK ID : ".$reworkId." dan DEFECT ID : ".$defectId." tidak berhasil dikembalikan ke DEFECT.");
        }
    }

    public function submitInput($value)
    {
        $this->emit('qrInputFocus', 'rework');

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

        $validatedData = $this->validate();

        if ($this->checkIfNumberingExists($numberingInput)) {
            return;
        }

        $scannedDefectData = Defect::selectRaw("output_defects_packing.*, output_defects_packing.master_plan_id, master_plan.sewing_line, master_plan.tgl_plan, master_plan.color, output_defect_types.allocation, output_defect_in_out.id in_out_id, output_defect_in_out.status as in_out_status")->
            leftJoin("output_defect_in_out", function ($join) {
                $join->on("output_defect_in_out.defect_id", "=", "output_defects_packing.id");
                $join->on("output_defect_in_out.output_type", "=", DB::raw("'packing'"));
            })->
            leftJoin("master_plan", "master_plan.id", "=", "output_defects_packing.master_plan_id")->
            leftJoin("output_defect_types", "output_defect_types.id", "=", "output_defects_packing.defect_type_id")->
            where("output_defects_packing.defect_status", "defect")->
            where("output_defects_packing.kode_numbering", $numberingInput)->
            first();

        if ($scannedDefectData && $this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
            if ($scannedDefectData->master_plan_id == $this->orderInfo->id) {
                // check external allocation
                if (($scannedDefectData->allocation != "SEWING" && $scannedDefectData->in_out_id != null) || $scannedDefectData->allocation == "SEWING") {
                    if ($scannedDefectData->in_out_status != "defect") {
                        // add to rework
                        $createRework = ReworkModel::create([
                            "defect_id" => $scannedDefectData->id,
                            "status" => "NORMAL"
                        ]);

                        // remove from defect
                        $scannedDefectData->defect_status = "reworked";
                        $scannedDefectData->save();

                        // add to rft
                        $createRft = Rft::create([
                            'master_plan_id' => $scannedDefectData->master_plan_id,
                            'no_cut_size' => $scannedDefectData->no_cut_size,
                            'kode_numbering' => $scannedDefectData->kode_numbering,
                            'so_det_id' => $scannedDefectData->so_det_id,
                            'status' => 'REWORK',
                            'rework_id' => $createRework->id,
                            'created_by' => Auth::user()->username
                        ]);

                        // add to rft nds
                        $rftNds = OutputPacking::where("kode_numbering", $scannedDefectData->kode_numbering)->orWhere("rework_id", $createRework->id)->first();
                        if (!$rftNds) {
                            $createRftNds = OutputPacking::create([
                                'sewing_line' => $this->orderInfo->sewing_line,
                                'master_plan_id' => $scannedDefectData->master_plan_id,
                                'no_cut_size' => $scannedDefectData->no_cut_size,
                                'kode_numbering' => $scannedDefectData->kode_numbering,
                                'so_det_id' => $scannedDefectData->so_det_id,
                                'status' => 'REWORK',
                                'rework_id' => $createRework->id,
                                'created_by' => Auth::user()->username
                            ]);
                        }

                        $this->sizeInput = '';
                        $this->sizeInputText = '';
                        $this->noCutInput = '';
                        $this->numberingInput = '';

                        if ($createRework && $createRft) {
                            $this->emit('alert', 'success', "DEFECT dengan Kode : ".$scannedDefectData->kode_numbering." berhasil di REWORK.");
                        } else {
                            $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT dengan Kode : ".$scannedDefectData->kode_numbering." tidak berhasil di REWORK.");
                        }
                    } else {
                        $this->emit('alert', 'error', "DEFECT dengan Kode : ".$scannedDefectData->kode_numbering." masih ada di <b>".$scannedDefectData->allocation."</b>.");
                    }
                } else {
                    $this->emit('alert', 'error', "DEFECT belum dialokasi ke <b>".$scannedDefectData->allocation."</b>.");
                }
            } else {
                $this->emit('alert', 'error', "Data DEFECT berada di Plan lain (<b>ID :".$scannedDefectData->master_plan_id."/".$scannedDefectData->tgl_plan."/".$scannedDefectData->color."/".strtoupper(str_replace("_", " ", $scannedDefectData->sewing_line))."</b>)");
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

    public function pushRapidRework($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        foreach ($this->rapidRework as $item) {
            if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                $exist = true;
            }
        }

        if (!$exist) {
            $this->rapidReworkCount += 1;

            if ($numberingInput) {
                array_push($this->rapidRework, [
                    'numberingInput' => $numberingInput,
                ]);
            }
        }
    }

    public function submitRapidInput() {
        $defectIds = [];
        $rftData = [];
        $rftDataNds = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidRework && count($this->rapidRework) > 0) {
            for ($i = 0; $i < count($this->rapidRework); $i++) {
                $scannedDefectData = DB::connection('mysql_sb')->table('output_defects_packing')->where("defect_status", "defect")->where("kode_numbering", $this->rapidRework[$i]['numberingInput'])->first();

                if (($scannedDefectData) && ($this->orderWsDetailSizes->where('so_det_id', $scannedDefectData->so_det_id)->count() > 0)) {
                    $createRework = ReworkModel::create([
                        'defect_id' => $scannedDefectData->id,
                        'status' => 'NORMAL',
                        'created_by' => Auth::user()->line_id,
                    ]);

                    array_push($defectIds, $scannedDefectData->id);

                    array_push($rftData, [
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $scannedDefectData->so_det_id,
                        'no_cut_size' => $scannedDefectData->no_cut_size,
                        'kode_numbering' => $scannedDefectData->kode_numbering,
                        'rework_id' => $createRework->id,
                        'status' => 'REWORK',
                        'created_by' => Auth::user()->line_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    array_push($rftDataNds, [
                        'sewing_line' => $this->orderInfo->sewing_line,
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $scannedDefectData->so_det_id,
                        'no_cut_size' => $scannedDefectData->no_cut_size,
                        'kode_numbering' => $scannedDefectData->kode_numbering,
                        'rework_id' => $createRework->id,
                        'status' => 'REWORK',
                        'created_by' => Auth::user()->line_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    $success += 1;
                } else {
                    $fail += 1;
                }
            }
        }

        $rapidDefectUpdate = Defect::whereIn('id', $defectIds)->update(["defect_status" => "reworked"]);
        $rapidRftInsert = Rft::insert($rftData);
        $rapidRftInsertNds = OutputPacking::insert($rftDataNds);

        $this->emit('alert', 'success', $success." output berhasil terekam. ");
        $this->emit('alert', 'error', $fail." output gagal terekam.");

        $this->rapidRework = [];
        $this->rapidReworkCount = 0;
    }

    public function render(SessionManager $session)
    {
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

        $this->emit('loadReworkPageJs');

        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        $this->allDefectImage = MasterPlan::select('gambar')->find($this->orderInfo->id);

        $this->allDefectPosition = DB::connection('mysql_sb')->table('output_defects_packing')->where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            get();

        $allDefectList = DB::connection('mysql_sb')->table('output_defects_packing')->selectRaw('output_defects_packing.defect_type_id, output_defects_packing.defect_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
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

        $reworks = DB::table('output_reworks_packing')->selectRaw('output_defects_packing.*, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('output_defects_packing', 'output_defects_packing.id', '=', 'output_reworks_packing.defect_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_defects_packing.defect_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            where('output_defects_packing.defect_status', 'reworked')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            whereRaw("(
                output_reworks_packing.id LIKE '%".$this->searchRework."%' OR
                output_defects_packing.id LIKE '%".$this->searchRework."%' OR
                so_det.size LIKE '%".$this->searchRework."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchRework."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchRework."%' OR
                output_defects_packing.defect_status LIKE '%".$this->searchRework."%'
            )")->
            orderBy('output_reworks_packing.updated_at', 'desc')->paginate(10, ['*'], 'reworksPage');

        $this->massSelectedDefect = DB::connection('mysql_sb')->table('output_defects_packing')->selectRaw('output_defects_packing.so_det_id, so_det.size as size, count(*) as total')->
            leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            where('output_defects_packing.defect_status', 'defect')->
            where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            where('output_defects_packing.defect_type_id', $this->massDefectType)->
            where('output_defects_packing.defect_area_id', $this->massDefectArea)->
            groupBy('output_defects_packing.so_det_id', 'so_det.size')->get();

        $this->rework = collect(DB::select("select output_defects_packing.*, so_det.size, COUNT(output_defects_packing.id) output from `output_defects_packing` left join `so_det` on `so_det`.`id` = `output_defects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'reworked' group by so_det.id"));

        return view('livewire.rework' , ['defects' => $defects, 'reworks' => $reworks, 'allDefectList' => $allDefectList]);
    }
}
