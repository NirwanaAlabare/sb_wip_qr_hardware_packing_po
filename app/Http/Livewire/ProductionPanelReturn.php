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

    public $selectedPo;
    public $selectedPoId;
    public $selectedPoWs;
    public $selectedPoColor;
    public $selectedPoSize;
    public $selectedPoPackingLine;
    public $selectedPoFinishingLine;
    public $kpno;
    public $style;
    public $soDetId;
    public $actCostingId;
    public $qtyReturn;
    public $qtyPackingLine;

    public $startDate;
    public $endDate;
    public $searchSummary;

    public $selectedTanggal;

    protected $rules = [
        'selectedPo' => 'required',
        'selectedPoWs' => 'required',
        'selectedPoColor' => 'required',
        'selectedPoSize' => 'required',
        'selectedPoPackingLine' => 'required',
        'selectedPoFinishingLine' => 'required',
        'kpno' => 'required',
        'style' => 'required',
        'qtyReturn' => 'required|numeric|min:1|lte:qtyPackingLine',
    ];

    protected $messages = [
        'selectedPo.required' => 'PO wajib dipilih',
        'selectedPoWs.required' => 'WS wajib dipilih',
        'selectedPoColor.required' => 'Color wajib dipilih',
        'selectedPoSize.required' => 'Size wajib dipilih',
        'selectedPoPackingLine.required' => 'Packing Line wajib dipilih',
        'selectedPoFinishingLine.required' => 'Line QC Finishing wajib dipilih',
        'kpno.required' => 'KP No wajib ada',
        'style.required' => 'Style wajib ada',
        'qtyReturn.required' => 'QTY Return wajib diisi',
        'qtyReturn.numeric' => 'QTY harus angka',
        'qtyReturn.min' => 'QTY minimal 1',
        'qtyReturn.lte' => 'QTY Return tidak boleh lebih dari qty packing line',
    ];

    public function mount()
    {
        $this->selectedPo = '';
        $this->selectedPoId = '';
        $this->selectedPoWs = '';
        $this->selectedPoColor = '';
        $this->selectedPoSize = '';
        $this->selectedPoPackingLine = '';
        $this->selectedPoFinishingLine = '';
        $this->kpno = '';
        $this->style = '';
        $this->actCostingId = '';
        $this->soDetId = '';
        $this->qtyReturn = '';
        $this->qtyPackingLine = '';

        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate   = Carbon::today()->format('Y-m-d');
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
            'ppic_master_id' => $this->selectedPoId,
            'act_costing_id' => $this->actCostingId,
            'so_det_id' => $this->soDetId,
            'po' => $this->selectedPo,
            'kpno' => $this->kpno,
            'style' => $this->style,
            'color' => $this->selectedPoColor,
            'size' => $this->selectedPoSize,
            'packing_line' => $this->selectedPoPackingLine,
            'qty_return' => $this->qtyReturn,
            'line_qc_finishing' => $this->selectedPoFinishingLine,
            'created_by' => Auth::user()->id,
            'created_by_username' => Auth::user()->username,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->reset([
            'selectedPo',
            'selectedPoId',
            'selectedPoWs',
            'actCostingId',
            'kpno',
            'style',
            'selectedPoColor',
            'soDetId',
            'selectedPoSize',
            'selectedPoPackingLine',
            'qtyReturn',
            'selectedPoFinishingLine',
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