<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\Rft;
// use App\Models\SignalBit\Defect;
// use App\Models\SignalBit\DefectType;
// use App\Models\SignalBit\DefectArea;
// use App\Models\SignalBit\Reject;
// use App\Models\SignalBit\Rework;
use App\Models\SignalBit\Undo;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class ProductionPanel extends Component
{
    public $baseUrl;

    // Data
    public $orderDate;
    public $orderInfo;
    public $orderWsDetails;
    public $orderWsDetailSizes;
    public $outputRft;
    // public $outputDefect;
    // public $outputReject;
    // public $outputRework;
    public $outputFiltered;

    // Filter
    public $selectedColor;
    public $selectedColorName;
    public $selectedSize;

    // Panel views
    public $panels;
    public $rft;
    // public $defect;
    // public $defectHistory;
    // public $reject;
    // public $rework;

    // Undo
    public $undoSizes;
    public $undoType;
    public $undoQty;
    public $undoSize;
    // public $undoDefectType;
    // public $undoDefectArea;

    // Input
    public $scannedNumberingCode;
    public $scannedNumberingInput;
    public $scannedSizeInput;
    public $scannedSizeInputText;

    public $selectedPo;
    public $selectedPoId;

    // Rules
    protected $rules = [
        'undoType' => 'required',
        'undoQty' => 'required|numeric|min:1',
        'undoSize' => 'required',
    ];

    protected $messages = [
        'undoType.required' => 'Terjadi kesalahan, tipe undo output tidak terbaca.',
        'undoQty.required' => 'Harap tentukan kuantitas undo output.',
        'undoQty.numeric' => 'Harap isi kuantitas undo output dengan angka.',
        'undoQty.min' => 'Kuantitas undo output tidak bisa kurang dari 1.',
        'undoSize.required' => 'Harap tentukan ukuran undo output.',
    ];

    // Event listeners
    protected $listeners = [
        'toProductionPanel' => 'toProductionPanel',
        'toRft' => 'toRft',
        // 'toDefect' => 'toDefect',
        // 'toDefectHistory' => 'toDefectHistory',
        // 'toReject' => 'toReject',
        // 'toRework' => 'toRework',
        'countRft' => 'countRft',
        // 'countDefect' => 'countDefect',
        // 'countReject' => 'countReject',
        // 'countRework' => 'countRework',
        'preSubmitUndo' => 'preSubmitUndo',
        'updateOrder' => 'updateOrder',
    ];

    public function mount(SessionManager $session, $orderInfo, $orderWsDetails)
    {
        $this->baseUrl = url('/');

        $this->orderInfo = $orderInfo;
        $this->orderWsDetails = $orderWsDetails;

        // Put data on session
        $session->put("orderInfo", $orderInfo);
        $session->put("orderWsDetails", $orderWsDetails);

        // Default value
        $this->selectedColor = $this->orderWsDetails[0]->id;
        $this->selectedColorName = $this->orderWsDetails[0]->color;
        $this->selectedSize = 'all';
        $this->selectedPo = '';
        $this->selectedPoId = '';
        $this->panels = false;
        $this->rft = true;
        // $this->defect = false;
        // $this->defectHistory = false;
        // $this->reject = false;
        // $this->rework = false;
        $this->outputRft = 0;
        // $this->outputDefect = 0;
        // $this->outputReject = 0;
        // $this->outputRework = 0;
        $this->outputFiltered = 0;
        $this->undoType = "";
        $this->undoQty = 1;
        $this->undoSize = "";
        // $this->undoDefectType = "";
        // $this->undoDefectArea = "";

        $this->orderWsDetailSizes = DB::table('master_plan')->selectRaw("
                MIN(so_det.id) as so_det_id,
                so_det.color as color,
                so_det.size as size,
                so_det.dest as dest,
                CONCAT(so_det.size, (CASE WHEN so_det.dest != '-' OR so_det.dest IS NULL THEN CONCAT('-', so_det.dest) ELSE '' END)) as size_dest
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->where('master_plan.sewing_line', str_replace(" ", "_", $this->orderInfo->sewing_line))
            ->where('act_costing.kpno', $this->orderInfo->ws_number)
            ->where('so_det.color', $this->selectedColorName)
            ->where('master_plan.cancel', 'N')
            ->groupBy('so_det.id', 'so_det.dest', 'so_det.size', 'so_det.color')
            ->orderBy('so_det_id')
            ->get();

        $session->put("orderWsDetailSizes", $this->orderWsDetailSizes);
    }

    public function toRft()
    {
        $this->panels = false;
        $this->rft = !($this->rft);
        $this->emit('toInputPanel', 'rft');
    }

    // public function toDefect()
    // {
    //     $this->panels = false;
    //     $this->defect = !($this->defect);
    //     $this->emit('toInputPanel', 'defect');
    // }

    // public function toDefectHistory()
    // {
    //     $this->panels = false;
    //     $this->defectHistory = !($this->defectHistory);
    //     $this->emit('toInputPanel', 'defect-history');
    // }

    // public function toReject()
    // {
    //     $this->panels = false;
    //     $this->reject = !($this->reject);
    //     $this->emit('toInputPanel', 'reject');
    // }

    // public function toRework()
    // {
    //     $this->panels = false;
    //     $this->rework = !($this->rework);
    //     $this->emit('toInputPanel', 'rework');
    // }

    public function toProductionPanel()
    {
        $this->emit('fromInputPanel');
        $this->panels = true;
        $this->rft = false;
        // $this->defect = false;
        // $this->defectHistory = false;
        // $this->reject = false;
        // $this->rework = false;
    }

    public function preSubmitUndo($undoType)
    {
        $this->undoQty = 1;
        $this->undoSize = '';
        $this->undoType = $undoType;

        $this->emit('showModal', 'undo');
    }

    public function submitUndo()
    {
        $validatedData = $this->validate();

        $size = DB::select(DB::raw("SELECT * FROM so_det WHERE id = '".$this->undoSize."'"));
        // $defectType = DB::table('output_defect_types')->select('defect_type')->find($this->undoDefectType);
        // $defectArea = DB::table('output_defect_areas')->select('defect_area')->find($this->undoDefectArea);

        switch ($this->undoType) {
            case 'rft' :
                // Undo RFT
                $rftSql = Rft::where('master_plan_id', $this->orderInfo->id)->
                    where('so_det_id', $this->undoSize)->
                    where('created_by', Auth::user()->username)->
                    where('status', 'NORMAL')->
                    orderBy('updated_at', 'DESC')->
                    orderBy('created_at', 'DESC')->
                    take($this->undoQty);

                $getRfts = $rftSql->get();

                foreach ($getRfts as $getRft) {
                    $addUndoHistory = Undo::create([
                        'master_plan_id' => $getRft->master_plan_id,
                        'so_det_id' => $getRft->so_det_id,
                        'po_id' => $getRft->po_id,
                        'output_rft_id' => $getRft->id,
                        'alokasi' => $getRft->alokasi,
                        'keterangan' => 'rft',
                        'created_by' => $getRft->created_by,
                        'created_by_username' => $getRft->created_by_username,
                        'created_by_line' => $getRft->created_by_line,
                        'created_at' => $getRft->created_at,
                        'updated_at' => $getRft->updated_at,
                        'undo_by' => Auth::user()->id
                    ]);
                }

                $deleteRft = $rftSql->delete();

                if ($deleteRft)  {
                    $this->emit('alert', 'success', 'Output RFT dengan ukuran '.$size[0]->size.' berhasil di UNDO sebanyak '.$deleteRft.' kali.');

                    $this->emit('hideModal', 'undo');
                } else {
                    $this->emit('alert', 'error', 'Output RFT dengan ukuran '.$size[0]->size.' gagal di UNDO.');
                }

                break;
            // case 'defect' :
            //     // Undo DEFECT
            //     $defectQuery = DB::table('output_defects_packing')->selectRaw('output_defects_packing.id as defect_id, output_defects_packing.*')->
            //         leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_defects_packing.defect_area_id')->
            //         leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            //         where('master_plan_id', $this->orderInfo->id)->
            //         where('so_det_id', $this->undoSize)->
            //         where('defect_status', 'defect');
            //     if ($this->undoDefectType) {
            //         $defectQuery->where('output_defects_packing.defect_type_id', $this->undoDefectType);
            //     };
            //     if ($this->undoDefectArea) {
            //         $defectQuery->where('output_defects_packing.defect_area_id', $this->undoDefectArea);
            //     };
            //     $defectQuery->orderBy('output_defects_packing.updated_at', 'DESC')->
            //         orderBy('output_defects_packing.created_at', 'DESC')->
            //         take($this->undoQty);

            //     $getDefects = $defectQuery->get();

            //     foreach ($getDefects as $getDefect) {
            //         $addUndoHistory = Undo::create([
            //             'master_plan_id' => $getDefect->master_plan_id,
            //             'so_det_id' => $getDefect->so_det_id,
            //             'output_defect_id' => $getDefect->defect_id,
            //             'keterangan' => 'defect',
            //         ]);

            //         $deleteDefect = Defect::find($getDefect->id)->delete();
            //     }

            //     $defectTypeText = $defectType ? ' dengan defect type = '.$defectType->defect_type : '';
            //     $defectAreaText = $defectArea ? 'dengan defect area = '.$defectArea->defect_area.' ' : '';

            //     if ($getDefects->count() > 0) {
            //         $this->emit('alert', 'success', 'Output DEFECT dengan ukuran '.$size[0]->size.''.$defectTypeText.' '.$defectAreaText.'berhasil di UNDO sebanyak '.$getDefects->count().' kali.');

            //         $this->emit('hideModal', 'undo');
            //     } else {
            //         $this->emit('alert', 'error', 'Output DEFECT dengan ukuran '.$size[0]->size.''.$defectTypeText.' '.$defectAreaText.'gagal di UNDO.');
            //     }

            //     break;
            // case 'reject' :
            //     // Undo REJECT
            //     $rejectSql = Reject::where('master_plan_id', $this->orderInfo->id)->
            //         where('so_det_id', $this->undoSize)->
            //         orderBy('updated_at', 'DESC')->
            //         orderBy('created_at', 'DESC')->
            //         take($this->undoQty);

            //     $getRejects = $rejectSql->get();

            //     foreach ($getRejects as $reject) {
            //         $addUndoHistory = Undo::create([
            //             'master_plan_id' => $reject->master_plan_id,
            //             'so_det_id' => $reject->so_det_id,
            //             'output_reject_id' => $reject->id,
            //             'keterangan' => 'reject',
            //         ]);
            //     }

            //     $deleteReject = $rejectSql->delete();

            //     if ($deleteReject) {
            //         $this->emit('alert', 'success', 'Output REJECT dengan ukuran '.$size[0]->size.' berhasil di UNDO sebanyak '.$deleteReject.' kali.');

            //         $this->emit('hideModal', 'undo');
            //     } else {
            //         $this->emit('alert', 'error', 'Output REJECT dengan ukuran '.$size[0]->size.' gagal di UNDO.');
            //     }

            //     break;
            // case 'rework' :
            //     // Undo REWORK
            //     $defectQuery = DB::table('output_defects_packing')->selectRaw('output_defects_packing.id as defect_id, output_defects_packing.*')->
            //         leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_defects_packing.defect_area_id')->
            //         leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_defects_packing.defect_type_id')->
            //         where('master_plan_id', $this->orderInfo->id)->
            //         where('so_det_id', $this->undoSize)->
            //         where('defect_status', 'reworked');
            //     if ($this->undoDefectType) {
            //         $defectQuery->where('output_defects_packing.defect_type_id', $this->undoDefectType);
            //     }
            //     if ($this->undoDefectArea) {
            //         $defectQuery->where('output_defects_packing.defect_area_id', $this->undoDefectArea);
            //     }
            //     $getDefects = $defectQuery->orderBy('output_defects_packing.updated_at', 'DESC')->
            //         orderBy('output_defects_packing.created_at', 'DESC')->
            //         limit($this->undoQty)->
            //         get();

            //     // update defect & delete rework
            //     foreach ($getDefects as $defect) {
            //         Undo::create(['master_plan_id' => $defect->master_plan_id, 'so_det_id' => $defect->so_det_id, 'output_rework_id' => $defect->rework->id, 'keterangan' => 'rework',]);
            //         Defect::where('id', $defect->defect_id)->update(['defect_status' => 'defect']);
            //         Rft::leftJoin('output_reworks', 'output_reworks.id', '=', 'output_rfts.rework_id')->where('output_reworks.defect_id', $defect->defect_id)->delete();
            //         Rework::where('defect_id', $defect->defect_id)->delete();
            //     }

            //     $defectTypeText = $defectType ? ' dengan defect type = '.$defectType->defect_type : '';
            //     $defectAreaText = $defectArea ? 'dengan defect area = '.$defectArea->defect_area.' ' : '';

            //     if ($getDefects->count() > 0) {
            //         $this->emit('alert', 'success', 'Output REWORK dengan ukuran '.$size[0]->size.''.$defectTypeText.' '.$defectAreaText.'berhasil di UNDO sebanyak '.$getDefects->count().' kali.');

            //         $this->emit('hideModal', 'undo');
            //     } else {
            //         $this->emit('alert', 'error', 'Output REWORK dengan ukuran '.$size[0]->size.''.$defectTypeText.' '.$defectAreaText.'gagal di UNDO.');
            //     }

            //     break;
        }
    }

    public function updateOrder() {
        $this->emit('loadingStart');

        $this->selectedSize = 'all';

        $this->orderInfo = MasterPlan::selectRaw("
                master_plan.id as id,
                master_plan.tgl_plan as tgl_plan,
                REPLACE(master_plan.sewing_line, '_', ' ') as sewing_line,
                act_costing.kpno as ws_number,
                act_costing.styleno as style_name,
                mastersupplier.supplier as buyer_name,
                so_det.styleno_prod as reff_number,
                master_plan.color as color,
                so_det.size as size,
                so.qty as qty_order,
                master_plan.sewing_line as sewing_line,
                CONCAT(masterproduct.product_group, ' - ', masterproduct.product_item) as product_type
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', 'N')
            ->where('master_plan.id', $this->selectedColor)
            ->first();

        if (!$this->orderInfo) {
            return $this->emit('reloadPage');
        }

        $this->orderWsDetails = DB::table("master_plan")->selectRaw("
                master_plan.id as id,
                master_plan.tgl_plan as tgl_plan,
                master_plan.color as color,
                mastersupplier.supplier as buyer_name,
                act_costing.styleno as style_name,
                mastersupplier.supplier as buyer_name
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', '!=', 'Y')
            ->where('master_plan.cancel', '!=', 'Y')
            ->where('master_plan.sewing_line', str_replace(" ", "_", $this->orderInfo->sewing_line))
            ->where('act_costing.kpno', $this->orderInfo->ws_number)
            ->where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)
            ->groupBy(
                'master_plan.id',
                'master_plan.tgl_plan',
                'master_plan.color',
                'mastersupplier.supplier',
                'act_costing.styleno',
                'mastersupplier.supplier'
            )->get();

        $this->orderWsDetailSizes = DB::table('master_plan')->selectRaw("
                MIN(so_det.id) as so_det_id,
                so_det.color as color,
                so_det.size as size,
                so_det.dest as dest,
                CONCAT(so_det.size, (CASE WHEN so_det.dest != '-' OR so_det.dest IS NULL THEN CONCAT('-', so_det.dest) ELSE '' END)) as size_dest
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->where('master_plan.sewing_line', str_replace(" ", "_", $this->orderInfo->sewing_line))
            ->where('act_costing.kpno', $this->orderInfo->ws_number)
            ->where('so_det.color', $this->selectedColorName)
            ->groupBy('so_det.id', 'so_det.dest', 'so_det.size', 'so_det.color')
            ->orderBy('so_det_id')
            ->get();

        $this->orderDate = $this->orderInfo->tgl_plan;
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        session()->put("orderInfo", $this->orderInfo);
        session()->put("orderWsDetails", $this->orderWsDetails);
        session()->put("orderWsDetailSizes", $this->orderWsDetailSizes);

        if ($this->rft) {
            $this->emit('updateWsDetailSizes', 'rft');
        }
        // else if ($this->defect)
        // {
        //     $this->emit('updateWsDetailSizes', 'defect');
        // } else if ($this->rework)
        // {
        //     $this->emit('updateWsDetailSizes', 'rework');
        // } else if ($this->reject)
        // {
        //     $this->emit('updateWsDetailSizes', 'reject');
        // }
        else {
            $this->emit('updateWsDetailSizes', 'panel');
        }

        $this->emit('getPo');
    }

    public function updatedSelectedPo()
    {
        $this->emit('updatePo', $this->selectedPo);
    }

    public function setAndSubmitInput($type) {
        $this->emit('loadingStart');

        if ($this->scannedNumberingCode) {
            if (str_contains($this->scannedNumberingCode, 'WIP')) {
                $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->scannedNumberingCode)->first();
            } else {
                $numberingCodes = explode('_', $this->scannedNumberingCode);

                if (count($numberingCodes) > 2) {
                    $this->scannedNumberingCode = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                    $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->scannedNumberingCode)->first();
                } else {
                    $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->scannedNumberingCode)->first();
                }
            }

            if ($numberingData) {
                $this->scannedSizeInput = $numberingData->so_det_id;
                $this->scannedSizeInputText = $numberingData->size;
                $this->scannedNumberingInput = $numberingData->no_cut_size;
            }
        }

        if ($type == "rft") {
            $this->toRft();
            $this->emit('setAndSubmitInputRft', $this->scannedNumberingInput, $this->scannedSizeInput, $this->scannedSizeInputText, $this->scannedNumberingCode);
        }

        // if ($type == "defect") {
        //     $this->toDefect();
        //     $this->emit('setAndSubmitInputDefect', $this->scannedNumberingInput, $this->scannedSizeInput, $this->scannedSizeInputText, $this->scannedNumberingCode);
        // }

        // if ($type == "reject") {
        //     $this->toReject();
        //     $this->emit('setAndSubmitInputReject', $this->scannedNumberingInput, $this->scannedSizeInput, $this->scannedSizeInputText, $this->scannedNumberingCode);
        // }

        // if ($type == "rework") {
        //     $this->toRework();
        //     $this->emit('setAndSubmitInputRework', $this->scannedNumberingInput, $this->scannedSizeInput, $this->scannedSizeInputText, $this->scannedNumberingCode);
        // }
    }

    public function render(SessionManager $session)
    {
        // Keep this data with session
        $this->orderInfo = $session->get("orderInfo", $this->orderInfo);
        $this->orderWsDetails = $session->get("orderWsDetails", $this->orderWsDetails);
        $this->orderWsDetailSizes = $session->get("orderWsDetailSizes", $this->orderWsDetailSizes);

        $this->orderDate = $this->orderInfo->tgl_plan;
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        // Get total output
        $this->outputRft = DB::table('output_rfts_packing_po')->
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'NORMAL')->
            count();
        // $this->outputDefect = DB::table('output_defects_packing')->
        //     where('master_plan_id', $this->orderInfo->id)->
        //     where('defect_status', 'defect')->
        //     count();
        // $this->outputReject = DB::table('output_rejects_packing')->
        //     where('master_plan_id', $this->orderInfo->id)->
        //     count();
        // $this->outputRework = DB::table('output_defects_packing')->
        //     where('master_plan_id', $this->orderInfo->id)->
        //     where('defect_status', 'reworked')->
        //     count();
        $sqlFiltered = DB::table('output_rfts_packing_po')->select('id')->where('master_plan_id', $this->orderInfo->id)->where('status', 'NORMAL');
        $this->outputFiltered = $this->selectedSize == 'all' ? $sqlFiltered->count() : $sqlFiltered->where('so_det_id', $this->selectedSize)->count();

        // Undo size data
        switch ($this->undoType) {
            case 'rft' :
                $this->undoSizes = DB::table('output_rfts_packing_po')->selectRaw('so_det.id as so_det_id, so_det.size, count(*) as total')->
                    leftJoin('so_det', 'so_det.id', '=', 'output_rfts_packing_po.so_det_id')->
                    where('master_plan_id', $this->orderInfo->id)->
                    where('status', 'NORMAL')->
                    orderBy('updated_at', 'DESC')->
                    orderBy('created_at', 'DESC')->
                    groupBy('so_det.id', 'so_det.size')->
                    get();
                break;
            // case 'defect' :
            //     $this->undoSizes = DB::table('output_defects_packing')->selectRaw('so_det.id as so_det_id, so_det.size, count(*) as total')->
            //         leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            //         where('master_plan_id', $this->orderInfo->id)->
            //         where('status', 'NORMAL')->
            //         where('defect_status', 'defect')->
            //         orderBy('updated_at', 'DESC')->
            //         orderBy('created_at', 'DESC')->
            //         groupBy('so_det.id', 'so_det.size')->
            //         get();
            //     break;
            // case 'reject' :
            //     $this->undoSizes = DB::table('output_rejects_packing')->selectRaw('so_det.id as so_det_id, so_det.size, count(*) as total')->
            //         leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
            //         where('master_plan_id', $this->orderInfo->id)->
            //         where('status', 'NORMAL')->
            //         orderBy('updated_at', 'DESC')->
            //         orderBy('created_at', 'DESC')->
            //         groupBy('so_det.id', 'so_det.size')->
            //         get();
            //     break;
            // case 'rework' :
            //     $this->undoSizes = DB::table('output_defects_packing')->selectRaw('so_det.id as so_det_id, so_det.size, count(*) as total')->
            //         leftJoin('output_defects_packing', 'output_defects_packing.id', '=', 'output_reworks_packing.defect_id')->
            //         leftJoin('so_det', 'so_det.id', '=', 'output_defects_packing.so_det_id')->
            //         where('output_defects_packing.master_plan_id', $this->orderInfo->id)->
            //         where('output_reworks_packing.status', 'NORMAL')->
            //         orderBy('output_reworks_packing.updated_at', 'DESC')->
            //         orderBy('output_reworks_packing.created_at', 'DESC')->
            //         groupBy('so_det.id', 'so_det.size')->
            //         get();
            //     break;
        }

        // Defect
        // $undoDefectTypes = DB::table('output_defect_types')->get();
        // $undoDefectAreas = DB::table('output_defect_areas')->get();

        return view('livewire.production-panel'/*, ['undoDefectTypes' => $undoDefectTypes, 'undoDefectAreas' => $undoDefectAreas]*/);
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
