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

            'phone' => 'nullable|string|max:20',

            // ✅ ảnh bìa
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'description' => 'nullable|string',

            'open_time' => 'required|date_format:H:i:s',
            'close_time' => 'required|date_format:H:i:s',

            // ✅ gallery images
            'images' => 'nullable|array',

            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            // fields
            'fields' => 'required|array|min:1',

            'fields.*.name' => 'required|string',

            // prices
            'fields.*.prices' => 'required|array|min:1',

            'fields.*.prices.*.day_of_week'
                => 'required|integer|between:1,7',

            'fields.*.prices.*.start_time'
                => 'required|date_format:H:i:s',

            'fields.*.prices.*.end_time'
                => 'required|date_format:H:i:s',

            'fields.*.prices.*.price'
                => 'required|numeric|min:0',
        ];
    }
}