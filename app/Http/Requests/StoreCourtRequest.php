<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function rules()
{
    return [
        'name' => 'required|string|max:100',
        'address' => 'required|string',

        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'phone' => 'nullable|string',
        'image' => 'nullable|string',
        'description' => 'nullable|string',

        'open_time' => 'required',
        'close_time' => 'required',

        // images phụ
        'images' => 'nullable|array',
        'images.*' => 'string',

        // fields
        'fields' => 'required|array|min:1',
        'fields.*.name' => 'required|string',

        'fields.*.prices' => 'required|array|min:1',
        'fields.*.prices.*.start_time' => 'required',
        'fields.*.prices.*.end_time' => 'required',
        'fields.*.prices.*.price' => 'required|numeric|min:0',
    ];
}
}