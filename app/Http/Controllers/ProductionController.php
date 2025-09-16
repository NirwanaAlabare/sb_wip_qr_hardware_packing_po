<?php

namespace App\Http\Controllers;

use App\Models\SignalBit\MasterPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DB;

class ProductionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $orderInfo = MasterPlan::selectRaw("
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
                CONCAT(masterproduct.product_group, ' - ', masterproduct.product_item) as product_type
            ")
            ->leftJoin('act_costing', 'act_costing.id', '=', 'master_plan.id_ws')
            ->leftJoin('so', 'so.id_cost', '=', 'act_costing.id')
            ->leftJoin('so_det', 'so_det.id_so', '=', 'so.id')
            ->leftJoin('mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', 'N')
            ->where('master_plan.id', $id)
            ->first();

        $orderWsDetails = MasterPlan::selectRaw("
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
            ->where('master_plan.sewing_line', str_replace(" ", "_", $orderInfo->sewing_line))
            ->where('act_costing.kpno', $orderInfo->ws_number)
            ->where('master_plan.tgl_plan', $orderInfo->tgl_plan)
            ->groupBy(
                'master_plan.id',
                'master_plan.tgl_plan',
                'master_plan.color',
                'mastersupplier.supplier',
                'act_costing.styleno',
                'mastersupplier.supplier'
            )->get();

        return view('production-panel', ['orderInfo' => $orderInfo, 'orderWsDetails' => $orderWsDetails]);
    }

    public function getPo(Request $request)
    {
        $orderWsDetailsPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                ppic_master_so.po as po
            ")
            ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
            ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
            ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
            ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', '!=', 'Y')
            ->where('act_costing.kpno', $request->ws_number)
            ->where('so_det.color', $request->color)
            ->groupBy('ppic_master_so.po')
            ->get();

        return json_encode($orderWsDetailsPo);
    }

    public function getPoSize(Request $request)
    {
        $orderWsDetailsPoSize = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                ppic_master_so.id,
                ppic_master_so.po,
                ppic_master_so.id_so_det,
                COALESCE(so_det.size, (CASE WHEN so_det.dest IS NOT NULL AND so_det.dest != '-' THEN so_det.dest ELSE '' END)) as size,
                ppic_master_so.qty_po,
                COUNT(output_rfts_packing_po.id) as qty
            ")
            ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
            ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
            ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
            ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
            ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
            ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
            ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
            ->where('so_det.cancel', '!=', 'Y')
            ->where('ppic_master_so.po', $request->po)
            ->where('act_costing.kpno', $request->ws_number)
            ->where('so_det.color', $request->color)
            ->groupBy('ppic_master_so.id')
            ->orderBy('so_det.id')
            ->get();

        return json_encode($orderWsDetailsPoSize);
    }

    public function getPoSizeQty(Request $request)
    {
        $orderWsDetailsPoSizeQty = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
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
            ->where('ppic_master_so.id', $request->po_id)
            ->groupBy('ppic_master_so.po', 'ppic_master_so.id_so_det', 'so_det.size')
            ->first();

        return json_encode($orderWsDetailsPoSizeQty);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LineProductions  $lineProductions
     * @return \Illuminate\Http\Response
     */
    public function show(LineProductions $lineProductions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LineProductions  $lineProductions
     * @return \Illuminate\Http\Response
     */
    public function edit(LineProductions $lineProductions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LineProductions  $lineProductions
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LineProductions $lineProductions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LineProductions  $lineProductions
     * @return \Illuminate\Http\Response
     */
    public function destroy(LineProductions $lineProductions)
    {
        //
    }

    public function universal() {
        return view('production-panel-universal');
    }

    public function temporary() {
        return view('production-panel-temporary');
    }
}
