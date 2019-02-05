<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateHouseRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'course_id' => 'exists:courses,id',
            'house' => 'required',
            'description' =>'required',
            'currency' => 'required',
            'price' => 'required|integer',
            'start_date' =>'required|date',
            'end_date' => 'required|date|after:start_date',
            'image' => 'image'
        ];
    }

    public function response(array $errors) {
        return response()->json(['message' => $errors,'code'=>422], 422);
    }
}
