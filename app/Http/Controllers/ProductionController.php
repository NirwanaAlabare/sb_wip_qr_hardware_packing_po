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
                act_costing.id as id_ws,
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

        if (Auth::user()->line_type == "multi") {
            $orderWsDetailsPo->push((object)[
                'po' => 'GUDANG_STOK',
            ]);
        }

        return json_encode($orderWsDetailsPo);
    }

    public function getPoSize(Request $request)
    {
        if ($request->po == "GUDANG_STOK") {
            $orderWsDetailsPoSize = DB::table("so_det")->selectRaw("
                    so_det.id as id,
                    '-' as po,
                    so_det.id as id_so_det,
                    so_det.color,
                    CONCAT(so_det.size, (CASE WHEN so_det.dest IS NOT NULL AND so_det.dest != '-' THEN CONCAT(' - ', so_det.dest) ELSE '' END)) as size,
                    '-' as qty_po,
                    COUNT(output_gudang_stok.id) as qty
                ")
                ->leftJoin('so', 'so.id', '=', 'so_det.id_so')
                ->leftJoin('act_costing', 'act_costing.id', '=', 'so.id_cost')
                ->leftJoin('output_gudang_stok', 'output_gudang_stok.so_det_id', '=', 'so_det.id')
                ->where('so_det.cancel', '!=', 'Y')
                ->where('act_costing.kpno', $request->ws_number)
                ->where('so_det.color', $request->color)
                ->groupBy('so_det.id')
                ->get();
        } else {
            $orderWsDetailsPoSize = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                    ppic_master_so.id,
                    ppic_master_so.po,
                    ppic_master_so.id_so_det,
                    so_det.color,
                    CONCAT(so_det.size, (CASE WHEN so_det.dest IS NOT NULL AND so_det.dest != '-' THEN CONCAT(' - ', so_det.dest) ELSE '' END)) as size,
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
        }

        return json_encode($orderWsDetailsPoSize);
    }

    public function getPoSizeQty(Request $request)
    {
        if ($request->po == "GUDANG_STOK") {
            $orderWsDetailsPoSizeQty = DB::table("so_det")->selectRaw("
                    '-' as po,
                    so_det.id as id_so_det,
                    so_det.size,
                    '-' as qty_po,
                    COUNT(output_gudang_stok.id) as qty_output
                ")
                ->leftJoin('output_gudang_stok', 'output_gudang_stok.so_det_id', '=', 'so_det.id')
                ->whereNotNull('output_gudang_stok.packing_po_id')
                ->where('so_det.cancel', '!=', 'Y')
                ->where('so_det.id', $request->po_id)
                ->groupBy('so_det.id')
                ->first();
        } else {
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
        }

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

    // Return
    public function getScannedItemReturn(Request $request)
    {
        $checkReturn = DB::table('output_rfts_packing_po_return')
            ->where('kode_numbering', $request->id)
            ->first();


        if ($checkReturn) {
            return response()->json([
                'message' => 'QR sudah pernah dilakukan return'
            ], 404);
        }
        
        $data = DB::select("
            SELECT
                ppic_master_so.id AS ppic_master_id,
                act_costing.id AS act_costing_id,
                so_det.id AS so_det_id,
                act_costing.kpno AS kpno,
                act_costing.styleno AS style,
                output_rfts_packing_po.kode_numbering as kode_qr,
                ppic_master_so.po,
                CONCAT(act_costing.kpno, ' - ', act_costing.styleno) AS worksheet_style,
                so_det.color,
                so_det.size,
                output_rfts_packing_po.created_by_line AS packing_line,
                output_rfts_packing_po.master_plan_id
            FROM
                output_rfts_packing_po
            LEFT JOIN laravel_nds.ppic_master_so ON ppic_master_so.id = output_rfts_packing_po.po_id
            LEFT JOIN so_det ON so_det.id = ppic_master_so.id_so_det
            LEFT JOIN so ON so.id = so_det.id_so
            LEFT JOIN act_costing ON act_costing.id = so.id_cost
            WHERE so_det.cancel != 'Y' AND
            output_rfts_packing_po.kode_numbering = ?
        ", [$request->id]);


        if (empty($data)) {
            return response()->json([
                'message' => 'Data QR tidak ditemukan'
            ], 404);
        }

        return response()->json($data[0]);
    }

    public function getLineQcFinishing(Request $request)
    {
        $data = DB::select("
            SELECT
                sewing_line as line_qc_finishing
            FROM master_plan
            WHERE id = ?
        ", [$request->master_plan_id]);


        if(empty($data)){
            return response()->json([
                'message' => 'Line QC Finishing tidak ditemukan'
            ], 404);
        }


        return response()->json($data[0]);
    }

    public function getPoReturn(Request $request)
    {
        $orderWsDetailsPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                ppic_master_so.id,
                ppic_master_so.po
            ")
            ->where('ppic_master_so.po', 'like', "%".($request->search ?? "")."%")
            ->groupBy('ppic_master_so.po')
            ->get();

        // if (Auth::user()->line_type == "multi") {
        //     $orderWsDetailsPo->push((object)[
        //         'po' => 'GUDANG_STOK',
        //     ]);
        // }

        return json_encode($orderWsDetailsPo);
    }

    public function getWsReturn(Request $request){
        $data = DB::table('act_costing')
            ->selectRaw("
                act_costing.id,
                CONCAT(act_costing.kpno, ' - ', act_costing.styleno) AS ws,
                act_costing.kpno,
                act_costing.styleno AS style
            ")
            ->leftJoin("so", "so.id_cost", "=", "act_costing.id")
            ->leftJoin("so_det", "so_det.id_so", "=", "so.id")
            ->leftJoin("laravel_nds.ppic_master_so", "ppic_master_so.id_so_det", "=", "so_det.id")
            ->where("so_det.cancel", "!=", "Y")
            ->where("ppic_master_so.po", $request->po)
            ->groupBy(DB::raw("CONCAT(act_costing.kpno, ' - ', act_costing.styleno)"))
            ->get();

        return json_encode($data);
    }

    public function getColorReturn(Request $request){
        $data = DB::table('so_det')
            ->selectRaw("
                so_det.id,
                so_det.color
            ")
            ->leftJoin("laravel_nds.ppic_master_so", "ppic_master_so.id_so_det", "=", "so_det.id")
            ->leftJoin("so", "so.id", "=", "so_det.id_so")
            ->leftJoin("act_costing", "act_costing.id", "=", "so.id_cost")
            ->where("so_det.cancel", "!=", "Y")
            ->where("ppic_master_so.po", $request->po)
            ->whereRaw("CONCAT(act_costing.kpno, ' - ', act_costing.styleno) = ?", [$request->ws])
            ->groupBy("so_det.color")
            ->get();

        return json_encode($data);
    }

    public function getSizeReturn(Request $request){
        $data = DB::table('so_det')
            ->selectRaw("
                so_det.size,
                ppic_master_so.qty_po AS qty_order
            ")
            ->leftJoin("laravel_nds.ppic_master_so", "ppic_master_so.id_so_det", "=", "so_det.id")
            ->leftJoin("so", "so.id", "=", "so_det.id_so")
            ->leftJoin("act_costing", "act_costing.id", "=", "so.id_cost")
            ->where("so_det.cancel", "!=", "Y")
            ->where("ppic_master_so.po", $request->po)
            ->whereRaw("CONCAT(act_costing.kpno, ' - ', act_costing.styleno) = ?", [$request->ws])
            ->where("so_det.color", $request->color)
            ->groupBy("so_det.size")
            ->get();

        return json_encode($data);
    }

    public function getPackingLineReturn(Request $request){
        $data = DB::table('output_rfts_packing_po')
            ->selectRaw("
                output_rfts_packing_po.created_by_line AS line,
                COUNT(output_rfts_packing_po.id) as tot_qty_in
            ")
            ->leftJoin("laravel_nds.ppic_master_so", "ppic_master_so.id", "=", "output_rfts_packing_po.po_id")
            ->leftJoin('so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
            ->leftJoin("so", "so.id", "=", "so_det.id_so")
            ->leftJoin("act_costing", "act_costing.id", "=", "so.id_cost")
            ->where("so_det.cancel", "!=", "Y")
            ->where("ppic_master_so.po", $request->po)
            ->whereRaw("CONCAT(act_costing.kpno, ' - ', act_costing.styleno) = ?", [$request->ws])
            ->where("so_det.color", $request->color)
            ->where("so_det.size", $request->size)
            ->groupBy("output_rfts_packing_po.created_by_line")
            ->get();

        return json_encode($data);
    }

    public function getQtyPackingLineReturn(Request $request){
        $data = DB::table('output_rfts_packing_po')
            ->selectRaw("
                COUNT(*) as qty_packing_line
            ")
            ->leftJoin("laravel_nds.ppic_master_so", "ppic_master_so.id", "=", "output_rfts_packing_po.po_id")
            ->leftJoin('so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
            ->leftJoin("so", "so.id", "=", "so_det.id_so")
            ->leftJoin("act_costing", "act_costing.id", "=", "so.id_cost")
            ->where("so_det.cancel", "!=", "Y")
            ->whereNull('output_rfts_packing_po.reject_id')
            ->where("ppic_master_so.po", $request->po)
            ->whereRaw("CONCAT(act_costing.kpno, ' - ', act_costing.styleno) = ?", [$request->ws])
            ->where("so_det.color", $request->color)
            ->where("so_det.size", $request->size)
            ->where("output_rfts_packing_po.created_by_line", $request->line)
            ->first();

        return json_encode($data);
    }

    public function return() {
        return view('production-panel-return');
    }
}
