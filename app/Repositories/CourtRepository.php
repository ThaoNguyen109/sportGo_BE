<?php

namespace App\Repositories;

use App\Contracts\CourtRepositoryInterface;
use App\Models\Court;
use App\Models\Field;
use Illuminate\Support\Collection;

/**
 * CourtRepository
 * 
 * Pattern: Repository Pattern (Structural - encapsulates data access logic)
 * Reason: Centralize all database queries, making them reusable and testable
 *         Service/Controller don't know HOW to query, just that it works
 * 
 * SOLID Principles Applied:
 * - S (Single Responsibility): ONLY handles data access - queries, creates, updates, deletes
 *   Controller/Service handle HTTP/business logic separately
 * - D (Dependency Inversion): Implements interface, Service uses interface, not this class
 * - O (Open/Closed): Can add caching layer here without changing Service
 *   Example: Can wrap results in Cache without Service knowing
 * 
 * Real-world analogy: 
 *   - Controller = Waiter takes order from customer
 *   - Service = Chef prepares the meal (logic)
 *   - Repository = Kitchen worker fetches ingredients (data)
 */
class CourtRepository implements CourtRepositoryInterface
{
    /**
     * Model instance
     * Using Model directly instead of Eloquent Builder for clarity
     */
    private Court $model;

    /**
     * Constructor with Dependency Injection
     * 
     * SOLID: Dependency Inversion - inject Model instead of instantiating inside
     * Benefit: Easy to mock for testing, swap implementations
     * 
     * @param Court $model The Court model
     */
    public function __construct(Court $model)
    {
        $this->model = $model;
    }

    /**
     * Find court by ID with eager loading
     * 
     * SOLID: Single Responsibility - only retrieves data
     * Pattern: Eager Loading (avoid N+1 queries)
     *   Without: Gets court (1 query) + gets owner (1 query) + gets fields (1 query) = 3 queries
     *   With: Gets all in 1-2 queries using with()
     * 
     * @param int $id Court ID
     * @return Court|null Court with all relationships loaded
     */
    public function findById(int $id)
    {
        // Eager load: owner, fields, and images
        // This prevents N+1 query problem
        return $this->model
            ->with([
                'owner:id,name,email,phone,avatar',  // Only select needed fields
                'fields:id,court_id,name,is_active',
                'images:id,court_id,image_url'
            ])
            ->where('status', '!=', 'rejected')  // Optional: exclude rejected courts
            ->find($id);
    }

    /**
     * Get all courts with pagination-ready structure
     * 
     * Future enhancement: Can add pagination, filtering, sorting
     * Pattern: Can be extended to support filtering/search
     * 
     * @return Collection
     */
    public function getAll()
    {
        return $this->model
            ->with(['owner:id,name,email', 'fields:id,court_id,name'])
            ->where('status', '!=', 'rejected')
            ->where('is_active', 1)
            ->get();
    }

    /**
     * Lấy danh sách sân kèm khoảng cách (Haversine formula trong MySQL).
     *
     * Công thức Haversine:
     *   d = 6371 * acos(
     *         cos(radians(lat1)) * cos(radians(lat2)) * cos(radians(lng2) - radians(lng1))
     *         + sin(radians(lat1)) * sin(radians(lat2))
     *       )
     * trong đó 6371 = bán kính trái đất (km)
     *
     * Dùng HAVING thay vì WHERE vì distance_km là computed column.
     *
     * @param float      $lat
     * @param float      $lng
     * @param float|null $maxKm  Nếu null, trả tất cả sân (không lọc bán kính)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithDistance(float $lat, float $lng, ?float $maxKm = null)
    {
        $haversine = '(6371 * acos(
            cos(radians(?)) * cos(radians(courts.latitude)) * cos(radians(courts.longitude) - radians(?))
            + sin(radians(?)) * sin(radians(courts.latitude))
        ))';

        $query = $this->model
            ->with(['owner:id,name,email', 'fields:id,court_id,name', 'images:id,court_id,image_url'])
            ->where('status', '!=', 'rejected')
            ->where('is_active', 1)
            ->selectRaw("courts.*, {$haversine} AS distance_km", [$lat, $lng, $lat]);

        if ($maxKm !== null) {
            // HAVING thay vì WHERE vì distance_km là alias của computed column
            $query->havingRaw("{$haversine} <= ?", [$lat, $lng, $lat, $maxKm]);
        }

        return $query->orderByRaw("{$haversine}", [$lat, $lng, $lat])->get();
    }

    /**
     * Create new court
     * 
     * SOLID: Single Responsibility - only creates, no validation (Service validates)
     * 
     * @param array $data Court data
     * @return Court|null
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update court
     * 
     * SOLID: Single Responsibility - only updates data
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->find($id)?->update($data) ?? false;
    }

    /**
     * Delete court
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    /**
     * Get field prices for a court
     *
     * SOLID: Single Responsibility - only retrieves field price data
     * Pattern: Eager Loading to avoid N+1 queries
     *   Gets court fields + their prices in optimized queries
     *
     * @param int $courtId Court ID
     * @return Collection Field prices with field and court info
     */
    public function getFieldPrices(int $courtId)
    {
        // First check if court exists and is not rejected
        $court = $this->model->where('id', $courtId)
                            ->where('status', '!=', 'rejected')
                            ->exists();

        if (!$court) {
            return collect(); // Return empty collection if court not found
        }

        // Get all active fields with their active prices
        return Field::where('court_id', $courtId)
            ->where('is_active', true)
            ->with([
                'prices' => function ($query) {
                    $query->orderBy('start_time');
                }
            ])
            ->get();
    }

    public function getActiveFields(int $courtId): mixed
    {
        return \App\Models\Field::where('court_id', $courtId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
