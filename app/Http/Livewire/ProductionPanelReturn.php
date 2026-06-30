<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\ReturnPacking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionPanelReturn extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap'; 

    public $kode_qr;

    public $output_rfts_packing_po_id;
    public $master_plan_id;
    public $ppic_master_id;
    public $act_costing_id;
    public $so_det_id;
    public $po;
    public $worksheet_style;
    public $kpno;
    public $style;
    public $color;
    public $size;
    public $packing_line;
    public $line_qc_finishing;
    public $qty_return;
    public $startDate;
    public $endDate;
    public $searchSummary;
    public $selectedTanggal;

    protected $rules = [
        'kode_qr' => 'required',
        'po' => 'required',
        'worksheet_style' => 'required',
        'color' => 'required',
        'size' => 'required',
        'packing_line' => 'required',
        'line_qc_finishing' => 'required',
        'kpno' => 'required',
        'style' => 'required',
        'qty_return' => 'required|numeric|min:1',
    ];

    protected $messages = [
        'kode_qr.required' => 'Kode QR wajib ada',
        'po.required' => 'PO wajib ada',
        'worksheet_style.required' => 'Worksheet Style wajib ada',
        'color.required' => 'Color wajib ada',
        'size.required' => 'Size wajib ada',
        'packing_line.required' => 'Packing Line wajib ada',
        'line_qc_finishing.required' => 'Line QC Finishing wajib ada',
        'kpno.required' => 'KP No wajib ada',
        'style.required' => 'Style wajib ada',
        'qty_return.required' => 'QTY Return wajib diisi',
        'qty_return.numeric' => 'QTY harus angka',
        'qty_return.min' => 'QTY minimal 1',
    ];


    public function mount()
    {
        $this->kode_qr = '';
        $this->output_rfts_packing_po_id = '';
        $this->master_plan_id = '';
        $this->ppic_master_id = '';
        $this->act_costing_id = '';
        $this->so_det_id = '';
        $this->po = '';
        $this->worksheet_style = '';
        $this->kpno = '';
        $this->style = '';
        $this->color = '';
        $this->size = '';
        $this->packing_line = '';
        $this->line_qc_finishing = '';
        $this->qty_return = '';
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        ReturnPacking::create([
            'output_rfts_packing_po_id' => $this->output_rfts_packing_po_id,
            'master_plan_id' => $this->master_plan_id,
            'ppic_master_id' => $this->ppic_master_id,
            'act_costing_id' => $this->act_costing_id,
            'so_det_id' => $this->so_det_id,
            'po' => $this->po,
            'kpno' => $this->kpno,
            'style' => $this->style,
            'color' => $this->color,
            'size' => $this->size,
            'packing_line' => $this->packing_line,
            'qty_return' => $this->qty_return,
            'line_qc_finishing' => $this->line_qc_finishing,
            'kode_numbering' => $this->kode_qr,
            'created_by' => Auth::user()->id,
            'created_by_username' => Auth::user()->username,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);


        $this->reset([
            'kode_qr',
            'output_rfts_packing_po_id',
            'master_plan_id',
            'ppic_master_id',
            'act_costing_id',
            'so_det_id',
            'po',
            'worksheet_style',
            'kpno',
            'style',
            'color',
            'size',
            'packing_line',
            'line_qc_finishing',
            'qty_return',
        ]);

        $this->emit('resetSelect2');
        $this->emit('afterSave');
        $this->emit('alert', 'success', 'Data berhasil disimpan');
    }

    public function openModal($tanggal)
    {
        $this->selectedTanggal = $tanggal;
        $this->resetPage('modalDetailsPage'); 
        $this->emit('openModal');
    }

    public function getModalDetailsProperty()
    {
        if (!$this->selectedTanggal) {
            return new LengthAwarePaginator([], 0, 10, 1, [
                'pageName' => 'modalDetailsPage',
            ]);
        }

        return ReturnPacking::whereDate('created_at', Carbon::createFromFormat('d-m-Y', $this->selectedTanggal))
            ->paginate(10, ['*'], 'modalDetailsPage')
            ->through(function($item) {
                $item->tanggal = $item->created_at->format('d-m-Y');
                $item->kode_numbering = $item->kode_numbering;
                $item->packing_line = $item->packing_line;
                $item->po = $item->po;
                $item->worksheet = $item->kpno;
                $item->style = $item->style;
                $item->color = $item->color;
                $item->size = $item->size;
                $item->qty_return = $item->qty_return;
                $item->qty_cek_qc = 0;
                $item->qc_line = $item->line_qc_finishing;
                return $item;
            });
    }

    public function render()
    {
        $query = ReturnPacking::selectRaw("
            DATE_FORMAT(created_at, '%d-%m-%Y') as tanggal,
            SUM(qty_return) as qty_return
        ")->groupBy('tanggal');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if ($this->searchSummary) {
            $search = $this->searchSummary;
            $query->havingRaw("tanggal LIKE ?", ["%$search%"])
                  ->orHavingRaw("SUM(qty_return) LIKE ?", ["%$search%"]);
        }

        $summary = $query->paginate(10);

        return view('livewire.production-panel-return', [
            'summary' => $summary,
            'modalDetails' => $this->modalDetails,
        ]);
    }
}