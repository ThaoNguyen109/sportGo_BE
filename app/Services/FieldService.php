<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Field;
use App\Services\FieldPriceService;

class FieldService
{
    protected FieldPriceService $fieldPriceService;

    public function __construct(FieldPriceService $fieldPriceService)
    {
        $this->fieldPriceService = $fieldPriceService;
    }

    public function createFields($court, array $fields): void
    {
        if (empty($fields)) {
            throw ValidationException::withMessages([
                'fields' => ['Phải có ít nhất 1 sân con']
            ]);
        }

            foreach ($fields as $fieldData) {

                $this->validateFieldData($fieldData);

                $this->ensureFieldNotExists($court, $fieldData['name']);

                $field = $court->fields()->create([
                    'name' => $fieldData['name'],
                    'is_active' => true
                ]);

                $this->fieldPriceService->createPrices(
                    $field,
                    $fieldData['prices']
                );
            }
        
    }

    private function validateFieldData(array $fieldData): void
    {
        if (empty($fieldData['name'])) {
            throw ValidationException::withMessages([
                'name' => ['Tên sân không được để trống']
            ]);
        }

        if (empty($fieldData['prices'])) {
            throw ValidationException::withMessages([
                'prices' => ['Phải có giá cho sân']
            ]);
        }
    }

    private function ensureFieldNotExists($court, string $name): void
    {
        if ($court->fields()->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'fields' => ["Sân '{$name}' đã tồn tại"]
            ]);
        }
    }
    public function addField($court, array $data)
    {
        $this->validateFieldData($data);
        $this->ensureFieldNotExists($court, $data['name']);

        $field = $court->fields()->create([
            'name' => $data['name'],
            'is_active' => true
        ]);

        $this->fieldPriceService->createPrices($field, $data['prices']);

        return $field->load('prices');
    }
    
    public function updateField($field, array $data)
    {
        // 🧠 validate business (optional)
        if (isset($data['name']) && empty(trim($data['name']))) {
            throw ValidationException::withMessages([
                'name' => ['Tên sân không được để trống']
            ]);
        }

        // ✏️ update partial
        $field->update($data);

        return $field;
    }
}