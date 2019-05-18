<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVideoRequest extends FormRequest
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
        'video_link'=>'required',
        'description' => 'required',
        'status_id' => 'exists:statuses,id',
        'user_id'=> 'exists:users, id'
        ];
    }

    public function response(array $errors)
    {
        return response()->json(['message' => $errors,'code'=>422], 422);
    }
}
