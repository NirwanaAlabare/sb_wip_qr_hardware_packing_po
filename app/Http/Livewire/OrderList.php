<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\MasterPlan;
use App\Models\SignalBit\UserPassword;
use App\Models\SignalBit\TemporaryOutput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\SessionManager;
use Livewire\Component;
use DB;

class OrderList extends Component
{
    public $orders;
    public $search = '';
    public $date = '';
    public $baseUrl = '';

    public $orderFilters = '';
    public $filterLine = '';
    public $filterBuyer = '';
    public $filterWs = '';
    public $filterProductType = '';
    public $filterStyle = '';
    public $filterDate = '';

    public $listeners = ['setDate' => 'setDate'];

    public $temporaryOutput;

    public function mount(SessionManager $session)
    {
        $session->forget('orderInfo');
        $session->forget('orderWsDetails');

        $this->date = date('Y-m-d');
        $this->baseUrl = url('/');

        $this->filterLine = '';
        // $this->lines = UserPassword::where('Groupp', 'SEWING')->get();
        // $this->buyer = DB::table('mastersupplier')->where('tipe_sup', 'C')->get();
    }

    public function clearFilter()
    {
        $this->filterLine = '';
        $this->filterBuyer = '';
        $this->filterWs = '';
        $this->filterProductType = '';
        $this->filterStyle = '';

        $this->emit('clearFilterInput');
    }

    public function preSubmitFilter()
    {
        $this->emit('showFilterModal');
    }

    public function submitFilter() {
        $this->orders = $this->orders->filter(function ($item) {
            return $item['sewing_line'] == $this->filterLine && $item['ws_number'] == $this->filterWs && $item['buyer_name'] == $this->filterBuyer && $item['product_type'] == $this->filterProductType && $item['style_name'] == $this->filterStyle;
        })->values();
    }

    public function setDate($date)
    {
        $this->date = $date;

        $this->orders = $this->orders->filter(function ($item) {
            return $item['plan_date'] == $this->date;
        })->values();
    }

    public function render()
    {
        $lineFilter = "";
        if (Auth::user()) {
            if (Auth::user()->line_type == 'multi') {
                $userMultilines = Auth::user()->multiline()->get();
                $i = 0;
                foreach ($userMultilines as $userLine) {
                    $lineUsername = $userLine->line ? $userLine->line->username : '';

                    if ($lineUsername) {
                        if ($i == 0) {
                            $lineFilter .= "(";
                        }

                        if ($i != 0) {
                            $lineFilter .= " OR (master_plan.sewing_line = '".strtoupper($lineUsername)."' AND master_plan.tgl_plan between '".$userLine->tanggal_awal."' and '".$userLine->tanggal_akhir."') ";
                        } else {
                            $lineFilter .= " (master_plan.sewing_line = '".strtoupper($lineUsername)."' AND master_plan.tgl_plan between '".$userLine->tanggal_awal."' and '".$userLine->tanggal_akhir."') ";
                        }

                        if ($i == $userMultilines->count()-1) {
                            $lineFilter .= ")";
                        }
                    }

                    $i++;
                }
            } else {
                $lineFilter = "master_plan.sewing_line = '".strtoupper(Auth::user()->line->username)."'";
            }
        }

        $masterPlanBefore = MasterPlan::selectRaw("MAX(master_plan.id) id")->
            leftJoin("act_costing", "act_costing.id", "=", "master_plan.id_ws")->
            leftJoin("mastersupplier", "mastersupplier.Id_Supplier", "=", "act_costing.id_buyer")->
            whereRaw("
                (
                    ".$lineFilter."
                    ".($this->filterLine ? "AND master_plan.sewing_line = '".str_replace(" ", "_", strtoupper($this->filterLine))."'" : "")."
                    ".($this->filterBuyer ? "AND mastersupplier.supplier = '".$this->filterBuyer."'" : "")."
                    ".($this->filterWs ? "AND act_costing.kpno = '".$this->filterWs."'" : "")."
                    ".($this->filterStyle ? "AND act_costing.styleno = '".$this->filterStyle."'" : "")."
                )
            ")->
            where("master_plan.cancel", "N")->
            where("tgl_plan", "<=", $this->date)->
            whereRaw("
                (
                    act_costing.kpno LIKE '%".$this->search."%'
                    OR
                    mastersupplier.supplier LIKE '%".$this->search."%'
                    OR
                    act_costing.styleno LIKE '%".$this->search."%'
                    OR
                    master_plan.color LIKE '%".$this->search."%'
                    OR
                    master_plan.sewing_line LIKE '%".$this->search."%'
                    OR
                    REPLACE(master_plan.sewing_line, '_', ' ') LIKE '%".$this->search."%'
                )
            ")->
            groupBy("master_plan.sewing_line", "master_plan.id_ws", "master_plan.color")->
            orderBy("tgl_plan", "desc")->
            orderBy("sewing_line", "asc")->
            limit(Auth::user()->line_type == "multi" ? 33 : 3)->
            get();

        $additionalQuery = "";
        if ($masterPlanBefore) {
            $masterPlanBeforeIds = implode("' , '", $masterPlanBefore->pluck("id")->toArray());

            $additionalQuery = " OR master_plan.id IN ('".$masterPlanBeforeIds."') ";
        }

        $this->orderFilters = DB::table('master_plan')
            ->selectRaw("
                master_plan.id_ws as id_ws,
                master_plan.tgl_plan as plan_date,
                REPLACE(master_plan.sewing_line, '_', ' ') as sewing_line,
                act_costing.kpno as ws_number,
                mastersupplier.supplier as buyer_name,
                act_costing.styleno as style_name,
                CONCAT(masterproduct.product_group, ' - ', masterproduct.product_item) as product_type
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', 'N')
            ->where('master_plan.cancel', 'N')
            ->whereRaw("
                (
                    ".$lineFilter."
                    ".($this->filterLine ? "AND master_plan.sewing_line = '".str_replace(" ", "_", strtoupper($this->filterLine))."'" : "")."
                    ".($this->filterBuyer ? "AND mastersupplier.supplier = '".$this->filterBuyer."'" : "")."
                    ".($this->filterWs ? "AND act_costing.kpno = '".$this->filterWs."'" : "")."
                    ".($this->filterStyle ? "AND act_costing.styleno = '".$this->filterStyle."'" : "")."
                    AND master_plan.tgl_plan = '".$this->date."'
                    ".$additionalQuery."
                )
            ")
            ->orderBy('master_plan.tgl_plan', 'desc')
            ->orderBy('master_plan.sewing_line', 'asc')
            ->orderBy('mastersupplier.supplier', 'asc')
            ->orderBy('act_costing.kpno', 'asc')
            ->orderBy('product_type', 'asc')
            ->orderBy('act_costing.styleno', 'asc')
            ->get();

        $this->orders = DB::table('master_plan')
            ->selectRaw("
                MIN(master_plan.id) as id,
                master_plan.id_ws as id_ws,
                master_plan.tgl_plan as plan_date,
                REPLACE(master_plan.sewing_line, '_', ' ') as sewing_line,
                act_costing.kpno as ws_number,
                mastersupplier.supplier as buyer_name,
                act_costing.styleno as style_name,
                COALESCE(output.progress, 0) as progress,
                COALESCE(output_packing.progress, 0) as target,
                CONCAT(masterproduct.product_group, ' - ', masterproduct.product_item) as product_type
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->leftJoin(
                DB::raw("
                    (
                        select
                            master_plan.id as master_plan_id,
                            master_plan.tgl_plan,
                            master_plan.id_ws,
                            master_plan.sewing_line,
                            count(output_rfts_packing_po.id) as progress
                        from
                            master_plan
                        left join
                            output_rfts_packing_po on output_rfts_packing_po.master_plan_id = master_plan.id
                        where
                            (".$lineFilter." ".($this->filterLine ? "AND master_plan.sewing_line = '".str_replace(" ", "_", strtoupper($this->filterLine))."'" : "")." ) AND
                            (master_plan.tgl_plan = '".$this->date."' $additionalQuery) AND
                            master_plan.cancel = 'N'
                        group by
                            master_plan.sewing_line,
                            master_plan.id_ws,
                            master_plan.tgl_plan
                    ) output
                "),
                function ($join) {
                    $join->on("output.sewing_line", "=", "master_plan.sewing_line");
                    $join->on("output.id_ws", "=", "master_plan.id_ws");
                    $join->on("output.tgl_plan", "=", "master_plan.tgl_plan");
                }
            )
            ->leftJoin(
                DB::raw("
                    (
                        select
                            master_plan.id as master_plan_id,
                            master_plan.tgl_plan,
                            master_plan.id_ws,
                            master_plan.sewing_line,
                            count(output_rfts_packing.id) as progress
                        from
                            master_plan
                        left join
                            output_rfts_packing on output_rfts_packing.master_plan_id = master_plan.id
                        where
                            (".$lineFilter." ".($this->filterLine ? "AND master_plan.sewing_line = '".str_replace(" ", "_", strtoupper($this->filterLine))."'" : "")." ) AND
                            (master_plan.tgl_plan = '".$this->date."' $additionalQuery) AND
                            master_plan.cancel = 'N'
                        group by
                            master_plan.sewing_line,
                            master_plan.id_ws,
                            master_plan.tgl_plan
                    ) output_packing
                "),
                function ($join) {
                    $join->on("output_packing.sewing_line", "=", "master_plan.sewing_line");
                    $join->on("output_packing.id_ws", "=", "master_plan.id_ws");
                    $join->on("output_packing.tgl_plan", "=", "master_plan.tgl_plan");
                }
            )
            ->where('so_det.cancel', 'N')
            ->where('master_plan.cancel', 'N')
            ->whereRaw("
                (
                    ".$lineFilter."
                    ".($this->filterLine ? "AND master_plan.sewing_line = '".str_replace(" ", "_", strtoupper($this->filterLine))."'" : "")."
                    ".($this->filterBuyer ? "AND mastersupplier.supplier = '".$this->filterBuyer."'" : "")."
                    ".($this->filterWs ? "AND act_costing.kpno = '".$this->filterWs."'" : "")."
                    ".($this->filterStyle ? "AND act_costing.styleno = '".$this->filterStyle."'" : "")."
                    AND master_plan.tgl_plan = '".$this->date."'
                    ".$additionalQuery."
                )
            ")
            ->whereRaw("
                (
                    act_costing.kpno LIKE '%".$this->search."%'
                    OR
                    mastersupplier.supplier LIKE '%".$this->search."%'
                    OR
                    act_costing.styleno LIKE '%".$this->search."%'
                    OR
                    master_plan.color LIKE '%".$this->search."%'
                    OR
                    master_plan.sewing_line LIKE '%".$this->search."%'
                    OR
                    REPLACE(master_plan.sewing_line, '_', ' ') LIKE '%".$this->search."%'
                )
            ")
            ->groupBy(
                'master_plan.sewing_line',
                'master_plan.id_ws',
                'master_plan.tgl_plan',
                'act_costing.kpno',
                'mastersupplier.supplier',
                'act_costing.styleno',
                'product_type',
                'output.progress',
                'output_packing.progress',
                'so.id'
            )
            ->orderBy('master_plan.tgl_plan', 'desc')
            ->get();

        $this->temporaryOutput = TemporaryOutput::where("line_id", Auth::user()->line_id)->
            whereRaw('(DATE(temporary_output_packing.created_at) = "'.$this->date.'" OR DATE(temporary_output_packing.updated_at) = "'.$this->date.'")')->
            where('tipe_output', 'rft')->
            orWhere('tipe_output', 'rework')->
            count();

        return view('livewire.order-list');
    }
}
