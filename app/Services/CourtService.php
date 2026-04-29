<?php

namespace App\Services;

use App\Contracts\CourtRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Court;

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
    public function __construct(CourtRepositoryInterface $courtRepository)
    {
        $this->courtRepository = $courtRepository;
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
    public function createCourt($ownerId, array $data)
    {
        return DB::transaction(function () use ($ownerId, $data) {

            $court = $this->createCourtBase($ownerId, $data);

            $this->createImages($court, $data['images'] ?? []);
            $this->createFields($court, $data['fields'] ?? []);

            return $court->load('fields.prices', 'images');
        });
    }

    private function createCourtBase($ownerId, $data)
    {
        return Court::create([
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
            'is_active' => true
        ]);
    }

    private function createImages($court, $images)
    {
        foreach ($images as $img) {
            $court->images()->create([
                'image_url' => $img
            ]);
        }
    }

    private function createFields($court, $fields)
    {
        if (empty($fields)) {
            throw new Exception("Phải có ít nhất 1 sân con");
        }

        foreach ($fields as $fieldData) {

            $this->validatePrices($fieldData['prices']);

            $field = $court->fields()->create([
                'name' => $fieldData['name'],
                'is_active' => true
            ]);

            foreach ($fieldData['prices'] as $price) {
                $field->prices()->create($price);
            }
        }
    }

    private function validatePrices($prices)
    {
        foreach ($prices as $i => $p1) {

            if ($p1['start_time'] >= $p1['end_time']) {
                throw new Exception("Giờ không hợp lệ");
            }

            foreach ($prices as $j => $p2) {
                if ($i == $j) continue;

                if (
                    $p1['start_time'] < $p2['end_time'] &&
                    $p1['end_time'] > $p2['start_time']
                ) {
                    throw new Exception("Khung giờ bị trùng");
                }
            }
        }
    }
// --- FIELD ---
    public function addField($ownerId, $courtId, array $data)
    {
        return DB::transaction(function () use ($ownerId, $courtId, $data) {

            $court = Court::findOrFail($courtId);

            // 🔐 check quyền
            if ($court->owner_id != $ownerId) {
                throw new Exception("Không có quyền thêm sân vào court này");
            }

            // validate giá
            $this->validatePrices($data['prices']);

            // tạo field
            $field = $court->fields()->create([
                'name' => $data['name'],
                'is_active' => true
            ]);

            // tạo price
            foreach ($data['prices'] as $price) {
                $field->prices()->create($price);
            }

            return $field->load('prices');
        });
    }
}