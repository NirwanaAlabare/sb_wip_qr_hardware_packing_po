<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\OutputGudangStok;
use Livewire\Component;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\Rft as RftModel;
// use App\Models\SignalBit\Rework;
// use App\Models\SignalBit\Defect;
// use App\Models\SignalBit\Reject;
// use App\Models\SignalBit\EndlineOutput;
use App\Models\Nds\Numbering;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;

class Rft extends Component
{
    public $orderInfo;
    public $orderWsDetailSizes;

    public $selectedPo;
    public $sizeInput;
    public $sizeInputText;
    public $noCutInput;
    public $numberingInput;
    public $rapidRft;
    public $rapidRftCount;
    public $rft;

    protected $rules = [
        'sizeInput' => 'required',
        'noCutInput' => 'required',
        'numberingInput' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.'
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updatePo' => 'updatePo',
        'setAndSubmitInputRft' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError'
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;
        $this->rapidRft = [];
        $this->rapidRftCount = 0;
        $this->submitting = false;
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

        // if (DB::table('output_defects_packing_po')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
        //     $this->addError('numberingInput', 'Kode QR sudah discan di Defect.');
        //     return true;
        // }

        // if (DB::table('output_rejects_packing_po')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
        //     $this->addError('numberingInput', 'Kode QR sudah discan di Reject.');
        //     return true;
        // }

        $this->emit('qrInputFocus', 'reject');

        return false;
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

        if ($panel == 'rft') {
            $this->emit('qrInputFocus', 'rft');
        }
    }

    public function updatePo($po)
    {
        $this->selectedPo = $po;
    }

    public function updateOutput()
    {
        $this->rft = DB::connection('mysql_sb')->table('output_rfts_packing_po')->
            leftJoin("so_det", "so_det.id", "=", "output_rfts_packing_po.so_det_id")->
            where('master_plan_id', $this->orderInfo->id)->
            where('status', 'NORMAL')->
            get();
    }

    public function clearInput()
    {
        $this->sizeInput = null;
    }

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

                $validatedData = $this->validate();

                if ($this->checkIfNumberingExists($numberingInput)) {
                    return;
                }

                $finishlineOutputData = DB::connection('mysql_sb')->table('output_rfts_packing')->where("kode_numbering", $numberingInput)->first();
                // $finishlineOutputData = true;

                if ($finishlineOutputData) {
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

                                $insertRft = RftModel::create([
                                    'master_plan_id' => $this->orderInfo->id,
                                    'so_det_id' => $currentPo ? $currentPo->id_so_det : $currentSizeInput,
                                    'no_cut_size' => $this->noCutInput,
                                    'po_id' => $currentPo ? $currentPo->id : NULL,
                                    'kode_numbering' => $numberingInput,
                                    'status' => 'NORMAL',
                                    'alokasi' => $currentPo ? "po" : "gudang stok",
                                    'rft_id' => $finishlineOutputData ? $finishlineOutputData->id : NULL,
                                    'type' => 'rft',
                                    'department' => 'packing',
                                    'created_by' => Auth::user()->id,
                                    'created_by_username' => Auth::user()->username,
                                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]);

                                if ($insertRft) {
                                    if ($this->selectedPo == "GUDANG_STOK") {
                                        OutputGudangStok::create([
                                            'kode_numbering' => $numberingInput,
                                            'so_det_id' => $currentPo ? $currentPo->id_so_det : $currentSizeInput,
                                            'packing_po_id' => $insertRft->id,
                                            'type' => 'rft',
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

    public function pushRapidRft($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;

        foreach ($this->rapidRft as $item) {
            if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                $exist = true;
            }
        }

        if (!$exist) {
            $this->rapidRftCount += 1;

            if ($numberingInput) {
                array_push($this->rapidRft, [
                    'numberingInput' => $numberingInput,
                ]);
            }
        }
    }

    public function submitRapidInput() {
        ini_set('memory_limit', '2048M');

        $rapidRftFiltered = [];
        $rapidRftFilteredNds = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidRft && count($this->rapidRft) > 0) {

            for ($i = 0; $i < count($this->rapidRft); $i++) {
                // if (str_contains($this->rapidRft[$i]['numberingInput'], 'WIP')) {
                //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->rapidRft[$i]['numberingInput'])->first();
                // } else {
                //     $numberingCodes = explode('_', $this->rapidRft[$i]['numberingInput']);

                //     if (count($numberingCodes) > 2) {
                //         $this->rapidRft[$i]['numberingInput'] = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidRft[$i]['numberingInput'])->first();
                //     } else {
                //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->rapidRft[$i]['numberingInput'])->first();
                //     }
                // }

                // One Straight Format
                $numberingData = DB::connection("mysql_nds")->table("year_sequence")->
                    selectRaw("year_sequence.*, so_det.dest, so_det.color, act_costing.id as id_ws, year_sequence.id_year_sequence no_cut_size")->
                    leftJoin("signalbit_erp.so_det", "so_det.id", "=", "year_sequence.so_det_id")->
                    leftJoin("signalbit_erp.so", "so.id", "=", "so_det.id_so")->
                    leftJoin("signalbit_erp.act_costing", "act_costing.id", "=", "so.id_cost")->
                    where("id_year_sequence", $this->rapidRft[$i]['numberingInput'])->
                    first();

                $finishlineOutputData = DB::connection('mysql_sb')->table('output_rfts_packing')->where("kode_numbering", $this->rapidRft[$i]['numberingInput'])->first();

                if ($finishlineOutputData > 0) {

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
                        array_push($rapidRftFiltered, [
                            'master_plan_id' => $this->orderInfo->id,
                            'so_det_id' => $numberingData->so_det_id,
                            'po_id' => $currentPo ? $currentPo->id : NULL,
                            'no_cut_size' => $numberingData->no_cut_size,
                            'kode_numbering' => $this->rapidRft[$i]['numberingInput'],
                            'status' => 'NORMAL',
                            'alokasi' => $currentPo ? 'po' : 'gudang stok',
                            'rft_id' => $finishlineOutputData ? $finishlineOutputData->id : NULL,
                            'type' => 'rft',
                            'department' => 'packing',
                            'created_by' => Auth::user()->line_id,
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

        $rapidRftInsert = RftModel::insert($rapidRftFiltered);

        $this->emit('alert', 'success', $success." output berhasil terekam. ");
        $this->emit('alert', 'error', $fail." output gagal terekam.");

        $this->rapidRft = [];
        $this->rapidRftCount = 0;
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText) {
        ini_set('memory_limit', '2048M');

        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;

        $this->submitInput($scannedNumbering);
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

        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        // Rft
        $this->rft = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));

        return view('livewire.rft');
    }
}
