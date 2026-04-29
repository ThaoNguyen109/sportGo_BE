<?php
namespace App\Services;

use App\Models\Court;
use Exception;
use Illuminate\Support\Facades\DB;

class CourtService
{
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