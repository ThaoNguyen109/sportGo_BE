<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class FieldPriceService
{
    /**
     * Tạo mới toàn bộ khung giá
     */
    public function createPrices($field, array $prices): void
    {
        $this->validatePrices($prices);

        $data = array_map(function ($price) use ($field) {
            return [
                'field_id'    => $field->id,
                'day_of_week' => (int) ($price['day_of_week'] ?? 1),
                'start_time'  => $price['start_time'],
                'end_time'    => $price['end_time'],
                'price'       => $price['price'] ?? 0,
                'is_active'   => $price['is_active'] ?? true,
            ];
        }, $prices);

        $field->prices()->insert($data);
    }

    /**
     * Validate khung giờ (theo từng ngày)
     */
    private function validatePrices(array $prices): void
    {
        if (empty($prices)) {
            throw ValidationException::withMessages([
                'prices' => ['Phải có ít nhất 1 khung giờ']
            ]);
        }

        // 🔥 FIX: ép kiểu day_of_week khi group
        $grouped = collect($prices)->groupBy(function ($item) {
            return (int) ($item['day_of_week'] ?? 0);
        });

        foreach ($grouped as $day => $dayPrices) {

            $day = (int) $day;

            // validate day_of_week
            if ($day < 1 || $day > 7) {
                throw ValidationException::withMessages([
                    'prices' => ['day_of_week phải từ 1 đến 7']
                ]);
            }

            // sort theo start_time
            $sorted = $dayPrices->sortBy('start_time')->values();

            foreach ($sorted as $i => $price) {

                if (empty($price['start_time']) || empty($price['end_time'])) {
                    throw ValidationException::withMessages([
                        'prices' => ["Thiếu giờ ở ngày $day"]
                    ]);
                }

                if ($price['start_time'] >= $price['end_time']) {
                    throw ValidationException::withMessages([
                        'prices' => ["Giờ không hợp lệ ở ngày $day"]
                    ]);
                }

                if ($i > 0) {
                    $prev = $sorted[$i - 1];

                    if ($price['start_time'] < $prev['end_time']) {
                        throw ValidationException::withMessages([
                            'prices' => ["Trùng khung giờ ở ngày $day"]
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Đồng bộ khung giá (create/update/delete)
     */
    public function syncPrices($field, array $prices)
    {
        return DB::transaction(function () use ($field, $prices) {

            if (empty($prices)) {
                throw ValidationException::withMessages([
                    'prices' => ['Phải có ít nhất 1 khung giờ']
                ]);
            }

            $this->validatePrices($prices);

            $requestIds = collect($prices)
                ->pluck('id')
                ->filter()
                ->toArray();

            $existingIds = $field->prices()
                ->pluck('id')
                ->toArray();

            // DELETE
            $idsToDelete = array_diff($existingIds, $requestIds);
            if (!empty($idsToDelete)) {
                $field->prices()->whereIn('id', $idsToDelete)->delete();
            }

            // CREATE + UPDATE
            foreach ($prices as $priceData) {

                $data = [
                    'field_id'    => $field->id,
                    'day_of_week' => (int) ($priceData['day_of_week'] ?? 1),
                    'start_time'  => $priceData['start_time'],
                    'end_time'    => $priceData['end_time'],
                    'price'       => $priceData['price'] ?? 0,
                    'is_active'   => $priceData['is_active'] ?? true,
                ];

                if (isset($priceData['id'])) {
                    $field->prices()
                        ->where('id', $priceData['id'])
                        ->update($data);
                } else {
                    $field->prices()->create($data);
                }
            }

            return $field->load('prices');
        });
    }
}