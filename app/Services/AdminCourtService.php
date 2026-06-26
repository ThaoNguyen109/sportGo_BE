<?php
namespace App\Services;

use App\Models\Court;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Events\CourtApprovedEvent;
use App\Events\CourtRejectedEvent;


class AdminCourtService
{
    public function getPendingCourts()
    {
        return Court::with(['owner', 'images'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ✔ Approve
    public function approveCourt($id)
    {
        return DB::transaction(function () use ($id) {

            $court = Court::findOrFail($id);

            // ❗ chỉ cho duyệt khi đang pending
            if ($court->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Chỉ có thể duyệt sân đang chờ duyệt']
                ]);
            }

            $court->update([
                'status' => 'approved',
                'is_active' => true
            ]);

            // 🔥 FIRE EVENT (sau khi update)
            event(new CourtApprovedEvent(
                $court->owner_id,
                $court->id,
                $court->name
            )); // 🔥 QUAN TRỌNG

            return $court;
        });
    }

    // ❌ Reject
    public function rejectCourt($id, $reason = null)
    {
        return DB::transaction(function () use ($id, $reason) {

            $court = Court::findOrFail($id);

            if ($court->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Chỉ có thể từ chối sân đang chờ duyệt']
                ]);
            }

            // 🔥 validate reason (nên có)
            if (!$reason) {
                throw ValidationException::withMessages([
                    'reason' => ['Phải nhập lý do từ chối']
                ]);
            }

            $court->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'is_active' => false
            ]);

            // 🔥 FIRE EVENT (KHÔNG cần afterCommit nếu Event implements ShouldDispatchAfterCommit)
            event(new CourtRejectedEvent(
                $court->owner_id,
                $court->id,
                $court->name,
                $reason
            ));

            return $court;
        });
    }

    public function toggleActive($id)
    {
        return DB::transaction(function () use ($id) {

            $court = Court::findOrFail($id);

            if ($court->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => ['Chỉ có thể bật/tắt sân đã được duyệt']
                ]);
            }

            // 🔁 đảo trạng thái
            $court->update([
                'is_active' => !$court->is_active
            ]);

            return $court;
        });
    }

    public function getAllCourts($request)
    {
        $query = Court::with(['owner', 'images']);

        // 🔍 filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 🔍 filter is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 🔍 filter owner
        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        // 🔍 search name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 📅 sort mới nhất
        $query->orderByDesc('created_at');

        // 📄 pagination
        return $query->paginate(10);
    }

    public function getCourtDetail($id)
    {
        return Court::with([
            'owner',
            'images',
            'fields.prices'
        ])->findOrFail($id);
    }

    public function getCourtStats()
    {
        return [
            'total' => Court::count(),
            'pending' => Court::where('status', 'pending')->count(),
            'approved' => Court::where('status', 'approved')->count(),
            'rejected' => Court::where('status', 'rejected')->count(),
            'active' => Court::where('is_active', true)->count(),
            'inactive' => Court::where('is_active', false)->count(),    
            'top_owners' => Court::select('owner_id', DB::raw('COUNT(*) as total'))
            ->groupBy('owner_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()

                ];
    }
}