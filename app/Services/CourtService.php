<?php

namespace App\Services;

    
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;

use App\Contracts\CourtRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Court;
use App\Models\Field;
use Illuminate\Validation\ValidationException;
use App\Services\FieldService;
use App\Services\CourtImageService;
use App\Services\FieldPriceService;
use App\Services\BookingService;
use App\Events\CourtCreatedEvent;

/**
 * CourtService
 * 
 * Pattern: Service Layer Pattern + Strategy Pattern (partial)
 * Reason: Encapsulate business logic separate from data access and HTTP handling
 *         Makes logic reusable (API, CLI command, queue job all can use this service)
 * 
 * SOLID Principles Applied:
 * - S (Single Responsibility): ONLY handles court business logic
 *   - Getting court details
 *   - Validation before returning
 *   - Formatting response
 *   Does NOT handle HTTP response or data access
 * 
 * - D (Dependency Inversion): Depends on CourtRepositoryInterface, not CourtRepository
 *   This means:
 *   1. Can easily switch to API provider without changing this Service
 *   2. Can add caching layer by wrapping repository
 *   3. Easy to test - inject mock repository
 * 
 * - O (Open/Closed): Adding new logic (e.g., filtering by rating) doesn't break existing code
 *   Just add a new method without modifying others
 * 
 * Real-world analogy: 
 *   A restaurant's "Ordering Service" that handles business rules
 *   - Can't order if restaurant is closed (business rule)
 *   - Can't order non-existent items (validation)
 *   - Doesn't care HOW food is made or WHERE ingredients come from
 */
class CourtService
{
    /**
     * Repository instance
     * Type-hinted to interface, not concrete class
     * This is DEPENDENCY INVERSION principle
     */
    private CourtRepositoryInterface $courtRepository;
    private FieldService $fieldService;
    private CourtImageService $imageService;
    private FieldPriceService $fieldPriceService;
    private BookingService $bookingService;

    /**
     * Constructor with Dependency Injection
     * 
     * SOLID D (Dependency Inversion): Inject interface, not concrete class
     * Benefit:
     * - Service doesn't know/care about repository implementation
     * - Can swap CourtRepository with CachedCourtRepository without changing Service
     * - Easy to mock for unit tests
     * 
     * @param CourtRepositoryInterface $courtRepository Data access layer
     */
    public function __construct(
        CourtRepositoryInterface $courtRepository,
        FieldService $fieldService,
        CourtImageService $imageService,
        FieldPriceService $fieldPriceService,
        BookingService $bookingService
    ) {
        $this->courtRepository = $courtRepository;
        $this->fieldService = $fieldService;
        $this->imageService = $imageService;
        $this->fieldPriceService = $fieldPriceService;
        $this->bookingService = $bookingService;
    }

    /**
     * Get court detail by ID
     * 
     * SOLID: Single Responsibility - handles only court retrieval business logic
     * This Service layer adds:
     * 1. Null checking with meaningful error
     * 2. Could add access control (e.g., can user view this court?)
     * 3. Could add data transformation/formatting
     * 4. Could add logging, monitoring
     * 
     * Future enhancements:
     * - Add permission check: if court is private, only owner or admin can view
     * - Add analytics: log view count
     * - Add caching: cache popular courts
     * - Add availability check: show available fields/time slots
     * 
     * @param int $id Court ID
     * @return array Court data with relationships
     * @throws Exception If court not found
     */
    public function getCourtDetail(int $id): array
    {
        // Get from repository
        $court = $this->courtRepository->findById($id);

        // Business rule: Validate court exists
        if (!$court) {
            throw new Exception("Sân vận động không tồn tại", 404);
        }

        // Business rule: Could add permission check here
        // Example: if (!$this->canViewCourt($court, auth()->user())) {...}

        // Format response (SOLID: Data transformation is business logic)
        return $this->formatCourtData($court);
    }

    /**
     * Get all courts
     *
     * SOLID: Single Responsibility - handles listing all courts
     *
     * @param array $filters  Có thể chứa: lat, lng, max_distance
     * @return array List of courts formatted for API response
     */
    public function getAllCourts(array $filters = []): array
    {
        $lat       = isset($filters['lat'])          ? (float) $filters['lat']          : null;
        $lng       = isset($filters['lng'])          ? (float) $filters['lng']          : null;
        $maxKm     = isset($filters['max_distance']) ? (float) $filters['max_distance'] : null;

        // Nếu có tọa độ người dùng → dùng query Haversine kèm khoảng cách
        if ($lat !== null && $lng !== null) {
            $courts = $this->courtRepository->getAllWithDistance($lat, $lng, $maxKm);

            return $courts->map(function ($court) use ($lat, $lng) {
                $item                 = $this->formatCourtListItem($court);
                $item['distance_km']  = $court->distance_km !== null
                                        ? round((float) $court->distance_km, 1)
                                        : null;
                return $item;
            })->values()->all();
        }

        // Không có tọa độ → trả danh sách thường, không có distance
        $courts = $this->courtRepository->getAll();

        return $courts->map(function ($court) {
            $item                = $this->formatCourtListItem($court);
            $item['distance_km'] = null;
            return $item;
        })->values()->all();
    }

    /**
     * Tính khoảng cách giữa 2 tọa độ (Haversine — PHP)
     * Dùng cho GET /courts/{id} đơn lẻ thay vì cần query DB.
     *
     * @param float $lat1  Vĩ độ điểm A
     * @param float $lng1  Kinh độ điểm A
     * @param float $lat2  Vĩ độ điểm B
     * @param float $lng2  Kinh độ điểm B
     * @return float khoảng cách (km), làm tròn 1 chữ số thập phân
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }

    /**
     * Format a single court item for the list view
     *
     * Lighter format than formatCourtData() — omits heavy nested data
     * to keep the list response lean and fast.
     *
     * @param object $court Court model instance
     * @return array
     */
    private function formatCourtListItem(object $court): array
    {
        return [
            'id'          => $court->id,
            'name'        => $court->name,
            'address'     => $court->address,
            'phone'       => $court->phone,
            'status'      => $court->status,
            'open_time'   => $court->open_time,
            'close_time'  => $court->close_time,
            'image'       => $court->image,
            'fields_count'=> $court->fields->count(),
            'owner'       => [
                'id'    => $court->owner?->id,
                'name'  => $court->owner?->name,
                'email' => $court->owner?->email,
            ],
            'created_at'  => $court->created_at,
        ];
    }

    /**
     * Get field prices for a court
     *
     * SOLID: Single Responsibility - handles field pricing business logic
     * This Service layer adds:
     * 1. Court existence validation
     * 2. Could add permission checks (future)
     * 3. Data formatting for API
     * 4. Could add caching (future)
     *
     * Future enhancements:
     * - Add permission check: can user view prices?
     * - Add availability check: show available time slots
     * - Add dynamic pricing: different prices for peak hours
     * - Add discount logic: member discounts, bulk booking
     *
     * @param int $courtId Court ID
     * @return array Formatted field prices data
     * @throws Exception If court not found
     */
    public function getFieldPrices(int $courtId): array
    {
        // Business rule: Validate court exists and is accessible
        $court = $this->courtRepository->findById($courtId);
        if (!$court) {
            throw new Exception("Sân vận động không tồn tại", 404);
        }

        // Business rule: Could add permission check here
        // Example: if court is private, only owner can view prices

        // Get field prices from repository
        $fieldsWithPrices = $this->courtRepository->getFieldPrices($courtId);

        // Format response (SOLID: Data transformation is business logic)
        return $this->formatFieldPricesData($fieldsWithPrices, $court);
    }

    /**
     * Format field prices data for API response
     *
     * SOLID: Single Responsibility - handles data formatting
     * Pattern: Data Transformer/Presenter
     * Reason: Centralize API response structure
     *
     * @param Collection $fieldsWithPrices Fields with their prices
     * @param object $court Court data
     * @return array Formatted field prices data
     */
    private function formatFieldPricesData($fieldsWithPrices, $court): array
    {
        return [
            'court' => [
                'id'      => $court->id,
                'name'    => $court->name,
                'address' => $court->address,
            ],
            'fields' => $fieldsWithPrices->map(function ($field) {
                // Group các khung giờ theo từng ngày trong tuần
                $byDay = $field->prices
                    ->groupBy('day_of_week')
                    ->map(function ($slots, $dayOfWeek) {
                        return [
                            'day_of_week' => (int) $dayOfWeek,
                            'day_name'    => $this->getDayName((int) $dayOfWeek),
                            'slots'       => $slots->map(function ($price) {
                                return [
                                    'id'         => $price->id,
                                    'start_time' => substr($price->start_time, 0, 5), // "08:00:00" → "08:00"
                                    'end_time'   => substr($price->end_time, 0, 5),
                                    'price'      => (float) $price->price,
                                    'is_active'  => (bool) $price->is_active,
                                ];
                            })->values(),
                        ];
                    })
                    ->sortKeys()   // sắp xếp theo thứ (1→7)
                    ->values();

                return [
                    'id'       => $field->id,
                    'name'     => $field->name,
                    'schedule' => $byDay,  // mỗi phần tử = 1 ngày, có danh sách slot+giá
                ];
            })->values(),
        ];
    }

    /**
     * Get day name from day of week number
     *
     * Pattern: Helper method
     * Reason: Convert numeric day to readable name
     * SOLID: Single Responsibility - utility function
     *
     * @param int $dayOfWeek 1=Monday, 7=Sunday
     * @return string Day name in Vietnamese
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật',
        ];

        return $days[$dayOfWeek] ?? 'Không xác định';
    }

    /**
     * Format court data for API response
     * 
     * SOLID: Single Responsibility - handles data formatting
     * Pattern: Data Transformer/Presenter
     * Reason: Centralize API response structure
     *         If frontend changes required fields, only change here
     * 
     * Benefits:
     * - API clients always get consistent structure
     * - Easy to add/remove fields without affecting database queries
     * - Could move to separate Presenter/Transformer class later (SOLID growth)
     * 
     * @param object $court Court model instance
     * @return array Formatted court data
     */
    private function formatCourtData(object $court): array
    {
        return [
            'id' => $court->id,
            'name' => $court->name,
            'address' => $court->address,
            'location' => [
                'latitude' => (float) $court->latitude,
                'longitude' => (float) $court->longitude,
            ],
            'contact' => [
                'phone' => $court->phone,
            ],
            'description' => $court->description,
            'business_hours' => [
                'open_time' => $court->open_time,
                'close_time' => $court->close_time,
            ],
            'status' => $court->status,
            'owner' => [
                'id' => $court->owner?->id,
                'name' => $court->owner?->name,
                'email' => $court->owner?->email,
                'phone' => $court->owner?->phone,
                'avatar' => $court->owner?->avatar,
            ],
            'fields' => $court->fields->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->name,
                'is_active' => (bool) $field->is_active,
            ])->values(),
            'images' => $court->images->map(fn($image) => [
                'id' => $image->id,
                'url' => $image->image_url,
            ])->values(),
            'created_at' => $court->created_at,
            'updated_at' => $court->updated_at,
        ];
    }

    /**
     * Lấy toàn bộ slot của tất cả field trong một court vào một ngày,
     * kèm trạng thái để frontend tô màu.
     *
     * @param int    $courtId
     * @param string $date
     * @return array
     * @throws Exception
     */
    public function getSlotsByCourt(int $courtId, string $date): array
    {
        $court = $this->courtRepository->findById($courtId);
        if (!$court) {
            throw new Exception('Sân không tồn tại.', 404);
        }

        $fields = $this->courtRepository->getActiveFields($courtId);
        if ($fields->isEmpty()) {
            throw new Exception('Sân không có field nào đang hoạt động.', 404);
        }

        $result = [];
        foreach ($fields as $field) {
            $slots = $this->bookingService->getAllSlotsWithStatus($field->id, $date);

            $result[] = [
                'field_id'   => $field->id,
                'field_name' => $field->name,
                'slots'      => $slots,
            ];
        }

        return [
            'court_id'   => $court->id,
            'court_name' => $court->name,
            'date'       => $date,
            'fields'     => $result,
        ];
    }
}
