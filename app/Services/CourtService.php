<?php

namespace App\Services;

use App\Contracts\CourtRepositoryInterface;
use Exception;

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
}
