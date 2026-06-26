<?php

namespace App\Contracts;

/**
 * CourtRepositoryInterface
 * 
 * Pattern: Repository Pattern + Dependency Inversion (SOLID D)
 * Reason: Decouple business logic from data access layer
 *         Makes it easy to swap database or add caching without changing Service/Controller
 * 
 * SOLID Principles Applied:
 * - D (Dependency Inversion): High-level modules depend on interface, not concrete class
 *   Example: Service uses interface, so if we change from database to API, only Repository changes
 * - I (Interface Segregation): Only methods that are needed for court data access
 * - O (Open/Closed): Can add new implementations without changing existing code
 */
interface CourtRepositoryInterface
{
    /**
     * Find a court by ID with all related data
     * 
     * @param int $id Court ID
     * @return object|null Court object with owner, fields, images OR null
     */
    public function findById(int $id);

    /**
     * Get all courts (with pagination support for future)
     *
     * @return mixed Collection of courts
     */
    public function getAll();

    /**
     * Lấy tất cả các sân kèm khoảng cách tính theo công thức Haversine.
     * Kết quả được sắp xếp theo khoảng cách tăng dần.
     *
     * @param float      $lat        Vĩ độ người dùng
     * @param float      $lng        Kinh độ người dùng
     * @param float|null $maxKm      Nếu đặt, chỉ trả sân trong phạm vi này (km)
     * @return mixed Collection of courts with distance_km attribute
     */
    public function getAllWithDistance(float $lat, float $lng, ?float $maxKm = null);

    /**
     * Create a new court
     * 
     * @param array $data Court data
     * @return object Created court
     */
    public function create(array $data);

    /**
     * Update court
     * 
     * @param int $id Court ID
     * @param array $data Data to update
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete court
     *
     * @param int $id Court ID
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get field prices for a court
     *
     * @param int $courtId Court ID
     * @return mixed Collection of field prices with field info
     */
    public function getFieldPrices(int $courtId);

    /**
     * Lấy tất cả field đang active của một court.
     */
    public function getActiveFields(int $courtId): mixed;
}
