<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Services\OwnerCourtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Services\FileUploadService;

class OwnerCourtController extends Controller
{
    public function __construct(
        private OwnerCourtService $courtService,
        private FileUploadService $fileUploadService
    ) {
        $this->courtService = $courtService;
        $this->fileUploadService = $fileUploadService;
        }

    /**
     * Tạo sân + fields + images
     */
    public function createCourt(StoreCourtRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            /**
             * Upload ảnh bìa
             */
            if ($request->hasFile('image')) {

                $data['image'] = $this->fileUploadService
                    ->upload(
                        $request->file('image'),
                        'courts/covers'
                    );
            }

            /**
             * Upload gallery images
             */
            if ($request->hasFile('images')) {

                $gallery = [];

                foreach ($request->file('images') as $image) {

                    $gallery[] = $this->fileUploadService
                        ->upload(
                            $image,
                            'courts/gallery'
                        );
                }

                $data['images'] = $gallery;
            }

            $court = $this->courtService->createFullCourt(
                auth()->id(),
                $data
            );

            return response()->json([
                'message' => 'Tạo sân thành công, chờ duyệt',
                'data' => $court
            ], 201);

        } catch (ValidationException $e) {

            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // lấy danh sách sân của owner

    public function getMyCourts(Request $request): JsonResponse
    {
        try {

            $courts = $this->courtService->getMyCourts(
                auth()->id(),
                $request
            );

            return response()->json([
                'message' => 'Lấy danh sách sân thành công',
                'data' => $courts
            ]);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // lấy chi tiết sân của owner (có cả sân con và giá)
    public function getMyCourtDetail($courtId): JsonResponse
    {
        try {

            $court = $this->courtService->getMyCourtDetail(
                auth()->id(),
                $courtId
            );

            return response()->json([
                'message' => 'Lấy chi tiết sân thành công',
                'data' => $court
            ]);

        } catch (AuthorizationException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 403);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Không tìm thấy sân'
            ], 404);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        // Cập nhật thông tin sân (không cập nhật hình ảnh, sân con, giá, )
    public function updateCourt(
        Request $request,
        $courtId
    ): JsonResponse {

        try {

            $user = auth()->user();

            $validated = $request->validate([

                'name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string',

                'latitude' => 'sometimes|nullable|numeric',
                'longitude' => 'sometimes|nullable|numeric',

                'phone' => 'sometimes|nullable|string',

                // ✅ upload file
                'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',

                'description' => 'sometimes|nullable|string',

                'open_time' => 'sometimes',
                'close_time' => 'sometimes',
            ]);

            /**
             * Upload ảnh mới
             */
            if ($request->hasFile('image')) {

                // lấy ảnh cũ
                $oldImage = Court::where('owner_id', $user->id)
                    ->where('id', $courtId)
                    ->value('image');

                // xoá ảnh cũ
                if ($oldImage) {
                    $this->fileUploadService
                        ->delete($oldImage);
                }

                // upload ảnh mới
                $validated['image'] = $this->fileUploadService
                    ->upload(
                        $request->file('image'),
                        'courts/covers'
                    );
            }

            $court = $this->courtService->updateCourt(
                $user->id,
                $courtId,
                $validated
            );

            return response()->json([
                'message' => 'Cập nhật sân thành công',
                'data' => $court
            ]);

        } catch (AuthorizationException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 403);

        } catch (ValidationException $e) {

            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        /**
         * Thêm sân con
         */
        public function addField($courtId, Request $request): JsonResponse
        {
            try {
                $field = $this->courtService->addField(
                    auth()->id(),
                    $courtId,
                    $request->all()
                );

                return response()->json([
                    'message' => 'Thêm sân con thành công',
                    'data' => $field
                ], 201);

            } catch (AuthorizationException $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 403);

            } catch (ValidationException $e) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $e->errors()
                ], 422);

            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Lỗi server',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // Cập nhật sân con (chỉ cập nhật tên và trạng thái hoạt động)
    public function updateField(Request $request, $fieldId): JsonResponse
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $field = $this->courtService->updateField(
                $user->id,
                $fieldId,
                $validated
            );

            return response()->json([
                'message' => 'Cập nhật sân con thành công',
                'data' => $field
            ]);

        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateFieldPrices(Request $request, $fieldId): JsonResponse
{
    try {
        $user = auth()->user();

        $validated = $request->validate([
            'prices' => 'required|array|min:1',

            'prices.*.id' => 'nullable|integer|exists:field_prices,id',

            'prices.*.start_time' => 'required|date_format:H:i',
            'prices.*.end_time' => 'required|date_format:H:i|after:prices.*.start_time',

            'prices.*.price' => 'required|numeric|min:0',

            'prices.*.day_of_week' => 'required|integer|between:1,7',

            'prices.*.is_active' => 'nullable|boolean',
        ]);

        $result = $this->courtService->updateFieldPrices(
            $user->id,
            $fieldId,
            $validated['prices']
        );

        return response()->json([
            'message' => 'Cập nhật giá thành công',
            'data' => $result
        ]);

    } catch (AuthorizationException $e) {

        return response()->json([
            'message' => $e->getMessage()
        ], 403);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function uploadCourtImage(
    Request $request,
    $courtId
): JsonResponse {

    try {

        $user = auth()->user();

        $validated = $request->validate([

            'image'
                => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // upload file
        $path = $this->fileUploadService
            ->upload(
                $request->file('image'),
                'courts/gallery'
            );

        // create DB
        $image = $this->courtService
            ->addCourtImage(
                $user->id,
                $courtId,
                $path
            );

        return response()->json([
            'message' => 'Upload ảnh thành công',
            'data' => $image
        ], 201);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function deleteCourtImage(
    $courtId,
    $imageId
): JsonResponse {

    try {

        $user = auth()->user();

        $this->courtService->deleteCourtImage(
            $user->id,
            $courtId,
            $imageId
        );

        return response()->json([
            'message' => 'Xóa ảnh thành công'
        ]);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}
}