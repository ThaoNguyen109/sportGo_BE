<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Services\CourtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerCourtController extends Controller
{
    public function __construct(
        private CourtService $courtService
    ) {}

    public function createCourt(StoreCourtRequest $request): JsonResponse
    {
        $court = $this->courtService->createCourt(
            auth()->id(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Tạo sân thành công, chờ duyệt',
            'data' => $court
        ], 201);
    }


    public function addField($courtId, Request $request): JsonResponse
    {
        $field = $this->courtService->addField(
            auth()->id(),
            $courtId,
            $request->all()
        );

        return response()->json([
            'message' => 'Thêm sân con thành công',
            'data' => $field
        ], 201);
    }
}