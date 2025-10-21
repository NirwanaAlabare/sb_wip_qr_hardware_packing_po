<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\MasterPlan;
// use App\Models\SignalBit\ProductType;
// use App\Models\SignalBit\DefectType;
// use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\Defect as DefectModel;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Reject;
use App\Models\Nds\Numbering;
use App\Models\SignalBit\EndLineOutput;
use Carbon\Carbon;
use Validator;
use DB;

class Defect extends Component
{
    use WithFileUploads;

    public $orderInfo;
    public $orderWsDetailSizes;
    public $sizeInput;
    public $sizeInputText;
    public $numberingInput;
    public $noCutInput;
    public $defect;

    public $defectTypes;
    public $defectAreas;
    public $productTypes;

    public $defectType;
    public $defectArea;
    public $productType;

    public $defectTypeAdd;
    public $defectAreaAdd;
    public $productTypeAdd;

    public $productTypeImageAdd;
    public $defectAreaPositionX;
    public $defectAreaPositionY;

    public $rapidDefect;
    public $rapidDefectCount;

    protected $rules = [
        'sizeInput' => 'required',
        'noCutInput' => 'required',
        'numberingInput' => 'required',
        // 'productType' => 'required',
        'defectType' => 'required',
        'defectArea' => 'required',
        'defectAreaPositionX' => 'required',
        'defectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'sizeInput.required' => 'Harap scan qr.',
        'noCutInput.required' => 'Harap scan qr.',
        'numberingInput.required' => 'Harap scan qr.',
        // 'productType.required' => 'Harap tentukan tipe produk.',
        'defectType.required' => 'Harap tentukan jenis defect.',
        'defectArea.required' => 'Harap tentukan area defect.',
        'defectAreaPositionX.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        'defectAreaPositionY.required' => "Harap tentukan posisi defect area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'setDefectAreaPosition' => 'setDefectAreaPosition',
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'setAndSubmitInputDefect' => 'setAndSubmitInput',
        'toInputPanel' => 'resetError'
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->numberingInput = null;
        $this->numberingCode = null;

        $this->defectType = null;
        $this->defectArea = null;
        $this->productType = null;

        $this->defectAreaPositionX = null;
        $this->defectAreaPositionY = null;

        $this->rapidDefect = [];
        $this->rapidDefectCount = 0;
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

        if (DB::table('output_defects_packing')->where('kode_numbering', ($numberingInput ?? $this->numberingInput))->exists()) {
            $this->addError('numberingInput', 'Kode QR sudah discan di Defect.');
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
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->noCutInput = null;
        $this->numberingInput = null;

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);
        $this->selectedColor = $this->orderInfo->id;
        $this->selectedColorName = $this->orderInfo->color;

        $this->emit('setSelectedSizeSelect2', $this->selectedColor);

        if ($panel == 'defect') {
            $this->emit('qrInputFocus', 'defect');
        }
    }

    public function updateOutput()
    {
        $this->defect = collect(DB::select("select output_defects_packing.*, so_det.size, COUNT(output_defects_packing.id) output from `output_defects_packing` left join `so_det` on `so_det`.`id` = `output_defects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'defect' group by so_det.id"));
    }

    public function updatedproductTypeImageAdd()
    {
        $this->validate([
            'productTypeImageAdd' => 'image',
        ]);
    }

    // Legacy
        // public function submitProductType()
        // {
        //     if ($this->productTypeAdd && $this->productTypeImageAdd) {

        //         $productTypeImageAddName = md5($this->productTypeImageAdd . microtime()).'.'.$this->productTypeImageAdd->extension();
        //         $this->productTypeImageAdd->storeAs('public/images', $productTypeImageAddName);

        //         $createProductType = ProductType::create([
        //             'product_type' => $this->productTypeAdd,
        //             'image' => $productTypeImageAddName,
        //         ]);

        //         if ($createProductType) {
        //             $this->emit('alert', 'success', 'Product Time : '.$this->productTypeAdd.' berhasil ditambahkan.');

        //             $this->productTypeAdd = null;
        //             $this->productTypeImageAdd = null;
        //         } else {
        //             $this->emit('alert', 'error', 'Terjadi kesalahan.');
        //         }
        //     } else {
        //         $this->emit('alert', 'error', 'Harap tentukan nama tipe produk beserta gambarnya');
        //     }
        // }

        // public function submitDefectType()
        // {
        //     if ($this->defectTypeAdd) {
        //         $createDefectType = DefectType::create([
        //             'defect_type' => $this->defectTypeAdd
        //         ]);

        //         if ($createDefectType) {
        //             $this->emit('alert', 'success', 'Defect type : '.$this->defectTypeAdd.' berhasil ditambahkan.');

        //             $this->defectTypeAdd = '';
        //         } else {
        //             $this->emit('alert', 'error', 'Terjadi kesalahan.');
        //         }
        //     } else {
        //         $this->emit('alert', 'error', 'Harap tentukan nama defect type');
        //     }
        // }

        // public function submitDefectArea()
        // {
        //     if ($this->defectAreaAdd) {

        //         $createDefectArea = DefectArea::create([
        //             'defect_area' => $this->defectAreaAdd,
        //         ]);

        //         if ($createDefectArea) {
        //             $this->emit('alert', 'success', 'Defect area : '.$this->defectAreaAdd.' berhasil ditambahkan.');

        //             $this->defectAreaAdd = null;
        //         } else {
        //             $this->emit('alert', 'error', 'Terjadi kesalahan.');
        //         }
        //     } else {
        //         $this->emit('alert', 'error', 'Harap tentukan nama defect area');
        //     }
        // }

    public function clearInput()
    {
        $this->sizeInput = '';
    }

    public function selectDefectAreaPosition()
    {
        $masterPlan = DB::connection('mysql_sb')->table('master_plan')->select('gambar')->find($this->orderInfo->id);

        if ($masterPlan) {
            $this->emit('showSelectDefectArea', $masterPlan->gambar);
        } else {
            $this->emit('alert', 'error', 'Harap pilih tipe produk terlebih dahulu');
        }
    }

    public function setDefectAreaPosition($x, $y)
    {
        $this->defectAreaPositionX = $x;
        $this->defectAreaPositionY = $y;
    }

    public function preSubmitInput($value)
    {
        $this->emit('qrInputFocus', 'defect');

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
            'numberingInput.required' => 'Harap scan qr.'
        ]);

        if ($this->checkIfNumberingExists($numberingInput)) {
            return;
        }

        if ($validation->fails()) {
            $this->emit('qrInputFocus', 'defect');

            $validation->validate();
        } else {
            $endlineOutputData = DB::connection('mysql_sb')->table('output_rfts')->where("kode_numbering", $numberingInput)->first();

            if ($endlineOutputData) {
                if ($endlineOutputData && $this->orderWsDetailSizes->where('so_det_id', $this->sizeInput)->count() > 0) {
                    $this->emit('clearSelectDefectAreaPoint');

                    $this->defectType = null;
                    $this->defectArea = null;
                    $this->productType = null;
                    $this->defectAreaPositionX = null;
                    $this->defectAreaPositionY = null;

                    $this->validateOnly('sizeInput');

                    $this->numberingInput = $numberingInput;

                    $this->emit('showModal', 'defect', 'regular');
                } else {
                    $this->emit('qrInputFocus', 'defect');

                    $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
                }
            } else {
                $this->emit('alert', 'error', "Output dari <b>QC Finishing</b> tidak ditemukan.");
            }
        }
    }

    public function submitInput(SessionManager $session)
    {
        $validatedData = $this->validate();

        if ($this->checkIfNumberingExists($validatedData["numberingInput"])) {
            return;
        }

        $currentData = $this->orderWsDetailSizes->where('so_det_id', $validatedData["sizeInput"])->first();
        if ($currentData && $this->orderInfo && (trim($currentData['color']) == trim($this->orderInfo->color))) {
            $insertDefect = DefectModel::create([
                'master_plan_id' => $this->orderInfo->id,
                'no_cut_size' => $validatedData['noCutInput'],
                'kode_numbering' => $validatedData['numberingInput'],
                'so_det_id' => $validatedData['sizeInput'],
                // 'product_type_id' => $this->productType,
                'defect_type_id' => $validatedData['defectType'],
                'defect_area_id' => $validatedData['defectArea'],
                'defect_area_x' => $validatedData['defectAreaPositionX'],
                'defect_area_y' => $validatedData['defectAreaPositionY'],
                'status' => 'NORMAL',
                'created_by' => Auth::user()->line_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ($insertDefect) {
                $type = DB::table('output_defect_types')->select('defect_type')->find($this->defectType);
                $area = DB::table('output_defect_areas')->select('defect_area')->find($this->defectArea);
                $getSize = DB::table('so_det')
                    ->select('id', 'size')
                    ->where('id', $this->sizeInput)
                    ->first();

                $this->emit('alert', 'success', "1 output DEFECT berukuran ".$getSize->size." dengan jenis defect : ".$type->defect_type." dan area defect : ".$area->defect_area." berhasil terekam.");
                $this->emit('hideModal', 'defect', 'regular');

                $this->sizeInput = '';
                $this->sizeInputText = '';
                $this->numberingInput = '';
                $this->noCutInput = '';
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
            }

            $this->emit('qrInputFocus', 'defect');
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. QR tidak sesuai.");
        }
    }

    public function setAndSubmitInput($scannedNumbering, $scannedSize, $scannedSizeText) {
        $this->numberingInput = $scannedNumbering;
        $this->sizeInput = $scannedSize;
        $this->sizeInputText = $scannedSizeText;

        $this->preSubmitInput($scannedNumbering);
    }

    public function pushRapidDefect($numberingInput, $sizeInput, $sizeInputText) {
        $exist = false;
        foreach ($this->rapidDefect as $item) {
            if (($numberingInput && $item['numberingInput'] == $numberingInput)) {
                $exist = true;
            }
        }

        if (!$exist) {
            if ($numberingInput) {
                $this->rapidDefectCount += 1;

                array_push($this->rapidDefect, [
                    'numberingInput' => $numberingInput,
                ]);
            }
        }
    }

    public function preSubmitRapidInput()
    {
        $this->defectType = null;
        $this->defectArea = null;
        $this->productType = null;
        $this->defectAreaPositionX = null;
        $this->defectAreaPositionY = null;

        $this->emit('showModal', 'defect', 'rapid');
    }

    public function submitRapidInput() {
        $rapidDefectFiltered = [];
        $success = 0;
        $fail = 0;

        if ($this->rapidDefect && count($this->rapidDefect) > 0) {

            for ($i = 0; $i < count($this->rapidDefect); $i++) {
                // if (str_contains($this->rapidDefect[$i]['numberingInput'], 'WIP')) {
                //     $numberingData = DB::connection("mysql_nds")->table("stocker_numbering")->where("kode", $this->rapidDefect[$i]['numberingInput'])->first();
                // } else {
                //     $numberingCodes = explode('_', $this->rapidDefect[$i]['numberingInput']);

                //     if (count($numberingCodes) > 2) {
                //         $this->rapidDefect[$i]['numberingInput'] = substr($numberingCodes[0],0,4)."_".$numberingCodes[1]."_".$numberingCodes[2];
                //         $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidDefect[$i]['numberingInput'])->first();
                //     } else {
                //         $numberingData = DB::connection("mysql_nds")->table("month_count")->selectRaw("month_count.*, month_count.id_month_year no_cut_size")->where("id_month_year", $this->rapidDefect[$i]['numberingInput'])->first();
                //     }
                // }

                // One Straight Format
                $numberingData = DB::connection("mysql_nds")->table("year_sequence")->selectRaw("year_sequence.*, year_sequence.id_year_sequence no_cut_size")->where("id_year_sequence", $this->rapidDefect[$i]['numberingInput'])->first();

                $endlineOutputCount = DB::connection('mysql_sb')->table('output_rfts')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count();

                if (($endlineOutputCount > 0) && ((DB::connection('mysql_sb')->table('output_defects_packing')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_rfts_packing')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count() + DB::connection('mysql_sb')->table('output_rejects_packing')->where('kode_numbering', $this->rapidDefect[$i]['numberingInput'])->count()) < 1) && ($this->orderWsDetailSizes->where('so_det_id', $numberingData->so_det_id)->count() > 0)) {
                    array_push($rapidDefectFiltered, [
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $numberingData->so_det_id,
                        'no_cut_size' => $numberingData->no_cut_size,
                        'kode_numbering' => $this->rapidDefect[$i]['numberingInput'],
                        'defect_type_id' => $this->defectType,
                        'defect_area_id' => $this->defectArea,
                        'defect_area_x' => $this->defectAreaPositionX,
                        'defect_area_y' => $this->defectAreaPositionY,
                        'status' => 'NORMAL',
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

        $rapidDefectInsert = DefectModel::insert($rapidDefectFiltered);

        $this->emit('alert', 'success', $success." output berhasil terekam. ");
        $this->emit('alert', 'error', $fail." output gagal terekam.");
        $this->emit('hideModal', 'defect', 'rapid');

        $this->rapidDefect = [];
        $this->rapidDefectCount = 0;
        $this->defectType = '';
        $this->defectArea = '';
        $this->defectAreaPositionX = '';
        $this->defectAreaPositionY = '';
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

        // Defect types
        // $this->productTypes = DB::table('output_product_types')->orderBy('product_type')->get();

        // Defect types
        $this->defectTypes = DB::table('output_defect_types')->leftJoin(DB::raw("(select defect_type_id, count(id) total_defect from output_defects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by defect_type_id) as defects"), "defects.defect_type_id", "=", "output_defect_types.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DB::table('output_defect_areas')->leftJoin(DB::raw("(select defect_area_id, count(id) total_defect from output_defects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by defect_area_id) as defects"), "defects.defect_area_id", "=", "output_defect_areas.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_area')->get();

        // Defect
        $this->defect = collect(DB::select("select output_defects_packing.*, so_det.size, COUNT(output_defects_packing.id) output from `output_defects_packing` left join `so_det` on `so_det`.`id` = `output_defects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `defect_status` = 'defect' group by so_det.id"));

        return view('livewire.defect');
    }
}
