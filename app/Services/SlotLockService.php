<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Quản lý tạm giữ slot bằng Redis.
 *
 * Redis key: slot_lock:{field_id}:{date}:{start_time}-{end_time}
 * Redis value: user_id
 * TTL: 10 phút (600 giây) — tự động hết hạn, không cần cronjob
 */
class SlotLockService
{
    private const LOCK_TTL   = 600; // 10 phút
    private const KEY_PREFIX = 'slot_lock';

    /**
     * Tạm giữ một slot cho user.
     * Dùng SET NX EX — atomic, không bị race condition.
     *
     * @return bool true nếu giữ thành công, false nếu đã bị giữ
     */
    public function acquireLock(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime,
        int $userId
    ): bool {
        $key    = $this->buildKey($fieldId, $date, $startTime, $endTime);
        $result = Redis::set($key, $userId, 'EX', self::LOCK_TTL, 'NX');

        return $result === true
            || $result === 'OK'
            || (is_object($result) && method_exists($result, '__toString') && (string) $result === 'OK');
    }

    /**
     * Tạm giữ nhiều slot cùng lúc — all or nothing.
     * Nếu 1 slot thất bại → rollback toàn bộ slot đã lock.
     */
    public function acquireMultipleLocks(int $userId, array $slots): array
    {
        $lockedSlots = [];

        foreach ($slots as $slot) {
            $acquired = $this->acquireLock(
                $slot['field_id'],
                $slot['date'],
                $slot['start_time'],
                $slot['end_time'],
                $userId
            );

            if (!$acquired) {
                // Rollback các slot đã lock trước đó
                foreach ($lockedSlots as $locked) {
                    $this->releaseLock(
                        $locked['field_id'],
                        $locked['date'],
                        $locked['start_time'],
                        $locked['end_time'],
                        $userId
                    );
                }
                return ['success' => false, 'failed_slot' => $slot];
            }

            $lockedSlots[] = $slot;
        }

        return ['success' => true, 'failed_slot' => null];
    }

    /**
     * Giải phóng slot sau thanh toán hoặc hủy.
     * Dùng Lua script — chỉ xóa nếu đúng chủ sở hữu, tránh xóa nhầm.
     */
    public function releaseLock(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime,
        int $userId
    ): bool {
        $key    = $this->buildKey($fieldId, $date, $startTime, $endTime);
        $script = <<<LUA
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        LUA;

        $result = Redis::eval($script, 1, $key, (string) $userId);
        return $result === 1;
    }

    /**
     * Giải phóng nhiều slot cùng lúc.
     */
    public function releaseMultipleLocks(int $userId, array $slots): void
    {
        foreach ($slots as $slot) {
            $this->releaseLock(
                $slot['field_id'],
                $slot['date'],
                $slot['start_time'],
                $slot['end_time'],
                $userId
            );
        }
    }

    /**
     * Kiểm tra slot có đang bị tạm giữ không.
     */
    public function isLocked(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        $key = $this->buildKey($fieldId, $date, $startTime, $endTime);
        return Redis::exists($key) > 0;
    }

    /**
     * Lấy thông tin lock: ai đang giữ, còn bao nhiêu giây.
     * Trả về null nếu slot không bị lock.
     */
    public function getLockInfo(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime
    ): ?array {
        $key    = $this->buildKey($fieldId, $date, $startTime, $endTime);
        $userId = Redis::get($key);

        if (!$userId) return null;

        return [
            'locked_by_user_id'  => (int) $userId,
            'expires_in_seconds' => max(0, Redis::ttl($key)),
        ];
    }

    // Tạo Redis key chuẩn hóa
    // VD: slot_lock:3:2026-05-10:08:00-10:00
    private function buildKey(
        int $fieldId,
        string $date,
        string $startTime,
        string $endTime
    ): string {
        return sprintf('%s:%d:%s:%s-%s',
            self::KEY_PREFIX, $fieldId, $date, $startTime, $endTime
        );
    }
}