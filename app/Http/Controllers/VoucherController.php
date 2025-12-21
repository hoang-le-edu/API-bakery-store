<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends BaseController
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Display a listing of vouchers for admin.
     * GET /api/admin/vouchers
     */
    public function index()
    {
        $vouchers = Voucher::with('creator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($voucher) {
                $used = $this->voucherService->calculateUsedVouchers($voucher);
                return [
                    'id' => $voucher->id,
                    'vourcher_code' => $voucher->vourcher_code,
                    'status' => $voucher->status,
                    'start_date' => $voucher->start_date,
                    'end_date' => $voucher->end_date,
                    'discount_type' => $voucher->discount_type,
                    'discount_amount' => $voucher->discount_amount,
                    'discount_percent' => $voucher->discount_percent,
                    'limit' => $voucher->limit,
                    'used' => $used,
                    'remaining' => max(0, $voucher->limit - $used),
                    'minimum' => $voucher->minimum,
                    'limit_per_order' => $voucher->limit_per_order,
                    'apply_type' => $voucher->apply_type,
                    'created_by' => $voucher->creator,
                    'created_at' => $voucher->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ], 200);
    }

    /**
     * Store a newly created voucher in storage (Admin)
     * POST /api/admin/vouchers
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vourcher_code' => 'required|string|unique:vouchers|max:255',
            'status' => 'required|in:active,inactive',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'discount_type' => 'required|in:percent,fixed',
            'discount_amount' => 'required_if:discount_type,fixed|numeric|min:0',
            'discount_percent' => 'required_if:discount_type,percent|numeric|min:0|max:100',
            'limit' => 'required|integer|min:1',
            'apply_type' => 'required|in:shipping_fee,discount',
            'limit_per_order' => 'nullable|numeric|min:0',
            'minimum' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Set default values
        if ($validated['discount_type'] === 'fixed') {
            $validated['discount_percent'] = 0;
        } else {
            $validated['discount_amount'] = 0;
        }

        $validated['config'] = json_encode([
            'description' => $validated['description'] ?? ''
        ]);

        // Get current user as creator
        $user = $this->checkFirebaseUser($request);
        if ($user) {
            $validated['created_by'] = $user->id;
        }

        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully.',
            'data' => $voucher
        ], 201);
    }
    /**
     * @OA\Get(
     *     path="/api/vouchers/loadCustomerVoucher",
     *     tags={"Vouchers"},
     *     summary="Get available vouchers for customer",
     *     description="Get all active vouchers for a specific branch/team, grouped by apply_type (discount/shipping_fee)",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="team_id",
     *         in="query",
     *         required=true,
     *         description="Team/Branch ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vouchers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="discount", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="voucher_code", type="string"),
     *                     @OA\Property(property="discount_type", type="string", enum={"percent", "fixed"}),
     *                     @OA\Property(property="discount_amount", type="number"),
     *                     @OA\Property(property="discount_percent", type="number"),
     *                     @OA\Property(property="minimum", type="number"),
     *                     @OA\Property(property="remaining", type="integer")
     *                 )),
     *                 @OA\Property(property="shipping_fee", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     * Load vouchers based on current date and team_id.
     */
    public function loadVouchersByDateAndTeam(Request $request)
    {
        // Validate the request to ensure team_id is provided
        $validated = $request->validate([
            'team_id' => 'required|uuid|exists:teams,id',
        ]);

        // Get the current date
        $currentDate = now()->toDateString();

        // Fetch vouchers that are active, within the current date range, and associated with the provided team_id
        $vouchers = Voucher::where('status', 'active')
            ->whereDate('start_date', '<=', $currentDate)
            ->whereDate('end_date', '>=', $currentDate)
            ->whereHas('teams', function ($query) use ($validated) {
                $query->where('teams.id', $validated['team_id']);
            })
            ->get();

        $return_data = [];
        foreach ($vouchers as $voucher) {
            $return_data[$voucher['apply_type']][] = [
                'id' => $voucher['id'],
                'voucher_code' => $voucher['vourcher_code'],
                'discount_type' => $voucher['discount_type'],
                'discount_amount' => $voucher['discount_amount'],
                'discount_percent' => $voucher['discount_percent'],
                'limit' => $voucher['limit'],
                'limit_per_order' => $voucher['limit_per_order'],
                'minimum' => $voucher['minimum'],
                'apply_type' => $voucher['apply_type'],
                'remaining' => $voucher['limit'] - $this->calculateUsedVouchers($voucher),
            ];
        }
        return response()->json([
            'success' => true,
            'data' => $return_data
        ], 200);
    }
    public function loadVouchersByDateAndUser(Request $request)
    {
        // Get the current date
        $currentDate = now()->toDateString();
        $curren_user = auth()->user();
        // Fetch vouchers that are active, within the current date range, and associated with the provided team_id
        $vouchers = Voucher::where('status', 'active')
            ->where('apply_type', 'discount')
            ->whereDate('start_date', '<=', $currentDate)
            ->whereDate('end_date', '>=', $currentDate)
            ->whereHas('teams', function ($query) use ($curren_user) {
                $query->where('teams.id', $curren_user->team_id)
                    ->orWhere('teams.id', '1');
            })
            ->get();

        $return_data = [];
        foreach ($vouchers as $voucher) {
            $return_data[] = [
                'id' => $voucher['id'],
                'voucher_code' => $voucher['vourcher_code'],
                'discount_type' => $voucher['discount_type'],
                'discount_amount' => $voucher['discount_amount'],
                'discount_percent' => $voucher['discount_percent'],
                'limit' => $voucher['limit'],
                'limit_per_order' => $voucher['limit_per_order'],
                'minimum' => $voucher['minimum'],
                'apply_type' => $voucher['apply_type'],
                'remaining' => $voucher['limit'] - $this->calculateUsedVouchers($voucher),
            ];
        }
        return response()->json([
            'success' => true,
            'data' => $return_data
        ], 200);
    }

    /**
     * Calculate how many times a voucher has been used
     * Count only non-Draft and non-Cancelled orders
     */
    public function calculateUsedVouchers($voucher)
    {
        // If $voucher is array (from query), convert to model
        if (is_array($voucher)) {
            $voucherId = $voucher['id'];
            $voucher = Voucher::find($voucherId);

            if (!$voucher) {
                return 0;
            }
        }

        // Use VoucherService for consistent calculation
        return $this->voucherService->calculateUsedVouchers($voucher);
    }

    /**
     * Display the specified voucher (Admin)
     * GET /api/admin/vouchers/{id}
     */
    public function show(string $id)
    {
        $voucher = Voucher::with('creator:id,name,email')->findOrFail($id);
        $used = $this->voucherService->calculateUsedVouchers($voucher);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $voucher->id,
                'vourcher_code' => $voucher->vourcher_code,
                'status' => $voucher->status,
                'start_date' => $voucher->start_date,
                'end_date' => $voucher->end_date,
                'discount_type' => $voucher->discount_type,
                'discount_amount' => $voucher->discount_amount,
                'discount_percent' => $voucher->discount_percent,
                'limit' => $voucher->limit,
                'used' => $used,
                'remaining' => max(0, $voucher->limit - $used),
                'minimum' => $voucher->minimum,
                'limit_per_order' => $voucher->limit_per_order,
                'apply_type' => $voucher->apply_type,
                'config' => $voucher->config,
                'created_by' => $voucher->creator,
                'created_at' => $voucher->created_at,
                'updated_at' => $voucher->updated_at,
            ]
        ], 200);
    }

    /**
     * Update the specified voucher in storage (Admin)
     * PUT /api/admin/vouchers/{id}
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'vourcher_code' => 'nullable|string|unique:vouchers,vourcher_code,' . $id . '|max:255',
            'status' => 'nullable|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'limit' => 'nullable|integer|min:1',
            'apply_type' => 'nullable|in:shipping_fee,discount',
            'limit_per_order' => 'nullable|numeric|min:0',
            'minimum' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            $voucher = Voucher::findOrFail($id);

            // Update config if description provided
            if (isset($validated['description'])) {
                $config = json_decode($voucher->config ?? '{}', true);
                $config['description'] = $validated['description'];
                $validated['config'] = json_encode($config);
                unset($validated['description']);
            }

            $voucher->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Voucher updated successfully.',
                'data' => $voucher
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified voucher from storage (Admin)
     * DELETE /api/admin/vouchers/{id}
     */
    public function destroy(string $id)
    {
        try {
            $voucher = Voucher::findOrFail($id);
            $voucher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Voucher deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle voucher status between active/inactive (Admin)
     * PATCH /api/admin/vouchers/{id}/toggle-status
     */
    public function toggleStatus(string $id)
    {
        try {
            $voucher = Voucher::findOrFail($id);
            $newStatus = $voucher->status === 'active' ? 'inactive' : 'active';
            $voucher->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Voucher status updated successfully.',
                'data' => [
                    'id' => $voucher->id,
                    'status' => $voucher->status
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
