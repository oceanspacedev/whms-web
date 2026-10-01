<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseOrderApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = PurchaseOrder::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search): void {
                $builder->where('no_po', 'like', "%{$search}%")
                    ->orWhere('no_sj_supplier', 'like', "%{$search}%")
                    ->orWhere('nama_supplier', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_penerimaan')) {
            $query->where('status_penerimaan', $request->string('status_penerimaan')->toString());
        }

        $perPage = min($request->integer('per_page', 20), 100);

        return PurchaseOrderResource::collection(
            $query->latest('tanggal_po')->paginate($perPage)
        );
    }

    public function findByNoPo(string $noPo): JsonResponse
    {
        return $this->findOrder('no_po', $noPo, 'Purchase Order');
    }

    public function findBySupplierSj(string $noSj): JsonResponse
    {
        return $this->findOrder('no_sj_supplier', $noSj, 'Surat Jalan supplier');
    }

    private function findOrder(string $column, string $value, string $label): JsonResponse
    {
        $order = PurchaseOrder::query()->where($column, trim($value))->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => "{$label} {$value} tidak ditemukan.",
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "{$label} ditemukan.",
            'data' => new PurchaseOrderResource($order),
        ]);
    }
}
