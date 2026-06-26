<?php

namespace App\Services;

use App\Events\CourtCreatedEvent;
use App\Models\Court;
use App\Models\Field;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * OwnerCourtService
 *
 * Responsibility:
 * - Handle all COURT OWNER actions
 * - Create/update court
 * - Manage fields
 * - Manage prices
 * - Manage images
 *
 * SOLID:
 * - S: Only handles owner business logic
 * - D: Depends on abstractions/services
 *
 * Pattern:
 * - Service Layer
 * - Aggregate Orchestration
 */
class OwnerCourtService
{
    private FieldService $fieldService;
    private CourtImageService $imageService;
    private FieldPriceService $fieldPriceService;

    public function __construct(
        FieldService $fieldService,
        CourtImageService $imageService,
        FieldPriceService $fieldPriceService
    ) {
        $this->fieldService = $fieldService;
        $this->imageService = $imageService;
        $this->fieldPriceService = $fieldPriceService;
    }

    /**
     * Create full court with:
     * - court
     * - fields
     * - prices
     * - images
     */
     /*--Của Thảo--*/
    //--- COURT OWNER ACTIONS ---

    // Tạo sân mới kèm theo fields và prices

    public function createFullCourt(int $ownerId, array $data)
    {
        return DB::transaction(function () use ($ownerId, $data) {

            $court = $this->createCourt($ownerId, $data);

            $this->handleImages($court, $data);
            $this->handleFields($court, $data);

            // 🔥 FIRE EVENT (QUAN TRỌNG)
            event(new CourtCreatedEvent(
                $ownerId,
                $court->id,
                $court->name
            ));

            return $court->load([
                'fields.prices',
                'images'
            ]);
        });
    }
    // Tạo sân mới (chỉ court, không kèm fields và prices)
    public function createCourt($ownerId, array $data)
    {
        return $court = Court::create([
                'owner_id' => $ownerId, 
                'name' => $data['name'], 
                'address' => $data['address'], 
                'latitude' => $data['latitude'] ?? null, 
                'longitude' => $data['longitude'] ?? null, 
                'phone' => $data['phone'] ?? null, 
                'image' => $data['image'] ?? null, 
                'description' => $data['description'] ?? null, 
                'open_time' => $data['open_time'], 
                'close_time' => $data['close_time'], 
                'status' => 'pending',
                'is_active' => false
            ]);

            return $court;
    }

    public function getMyCourts($ownerId, $request)
    {
        $query = Court::with([
            'images'
        ])
        ->where('owner_id', $ownerId);

        // 🔍 filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 🔍 search theo tên
        if ($request->has('search')) {
            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }

        return $query
            ->latest()
            ->paginate(10);
    }

    // lấy chi tiết sân của owner (có cả sân con và giá)
    public function getMyCourtDetail($ownerId, $courtId)
    {
        $court = Court::query()
            ->select([
                'id',
                'owner_id',
                'name',
                'address',
                'latitude',
                'longitude',
                'phone',
                'image',
                'description',
                'open_time',
                'close_time',
                'status',
                'is_active',
                'created_at'
            ])

            // 🔐 chỉ lấy sân của owner luôn
            ->where('owner_id', $ownerId)

            // 📸 images
            ->with([
                'images:id,court_id,image_url',

                // 🏟️ fields
                'fields' => function ($query) {
                    $query->select([
                        'id',
                        'court_id',
                        'name',
                        'is_active'
                    ])
                    ->with([

                        // 💰 prices
                        'prices' => function ($q) {
                            $q->select([
                                'id',
                                'field_id',
                                'day_of_week',
                                'start_time',
                                'end_time',
                                'price',
                                'is_active'
                            ])
                            ->orderBy('day_of_week')
                            ->orderBy('start_time');
                        }

                    ]);
                }
            ])

            // 📊 đếm số sân con
            ->withCount('fields')

            ->findOrFail($courtId);

        return $court;
    }
    // Cập nhật thông tin sân (không cập nhật hình ảnh, sân con, giá, )

    public function updateCourt(
        $ownerId,
        $courtId,
        array $data
    )
    {
        return DB::transaction(function () use (
            $ownerId,
            $courtId,
            $data
        ) {

            $court = Court::findOrFail($courtId);

            // 🔐 check quyền
            if ($court->owner_id != $ownerId) {
                throw new HttpException(
                    403,
                    "Không có quyền sửa sân này"
                );
            }

            // 🧠 validate business
            if (
                isset($data['open_time'], $data['close_time']) &&
                $data['open_time'] >= $data['close_time']
            ) {
                throw new HttpException(
                    422,
                    "Giờ mở phải nhỏ hơn giờ đóng"
                );
            }

            // ✅ whitelist fields được phép update
            $allowedData = collect($data)->only([
                'name',
                'address',
                'latitude',
                'longitude',
                'phone',
                'image',
                'description',
                'open_time',
                'close_time',
            ]);

            $court->update($allowedData->toArray());

            return $court->fresh();
        });
    }

    // Tạo ảnh cho sân
    private function handleImages($court, array $data): void
    {
        $this->imageService->createImages(
            $court,
            $data['images'] ?? []
        );
    }
    // Tạo fields và prices cho sân
    private function handleFields($court, array $data): void
    {
        $this->fieldService->createFields(
            $court,
            $data['fields'] ?? []
        );
    }
    // Tạo field mới cho sân
    public function addField($ownerId, $courtId, array $data)
    {
        $court = Court::findOrFail($courtId);

        if ($court->owner_id != $ownerId) {
            throw new Exception(403, "Không có quyền thêm sân");
        }

        return $this->fieldService->addField($court, $data);
    }
    // Cập nhật thông tin sân con
    public function updateField($ownerId, $fieldId, array $data)
    {
        return DB::transaction(function () use ($ownerId, $fieldId, $data) {

            $field = Field::with('court')->findOrFail($fieldId);

            // 🔐 check quyền qua Court
            if ($field->court->owner_id != $ownerId) {
                throw new HttpException(403, "Không có quyền sửa sân con này");
            }

            return $this->fieldService->updateField($field, $data);
        });
    }

    // Cập nhật giá cho field
    public function updateFieldPrices($ownerId, $fieldId, array $prices)
    {
        return DB::transaction(function () use ($ownerId, $fieldId, $prices) {

            $field = Field::with('court')->findOrFail($fieldId);

            // 🔐 check quyền qua Court (aggregate root)
            if ($field->court->owner_id != $ownerId) {
                throw new HttpException(403, "Không có quyền");
            }

            return $this->fieldPriceService->syncPrices($field, $prices);
        });
    }

   public function addCourtImage( int $ownerId, int $courtId, string $path )
    {
        $court = Court::findOrFail($courtId);

        // 🔐 check quyền
        if ($court->owner_id != $ownerId) {

            throw new HttpException(
                403,
                "Không có quyền thêm ảnh"
            );
        }

        return $this->imageService
            ->createImage($court, $path);
    }
    public function deleteCourtImage( int $ownerId, int $courtId, int $imageId): void 
    {

        $court = Court::findOrFail($courtId);

        // 🔐 check quyền
        if ($court->owner_id != $ownerId) {

            throw new HttpException(
                403,
                "Không có quyền xóa ảnh"
            );
        }

        $this->imageService
            ->deleteImage($court, $imageId);
    }
}