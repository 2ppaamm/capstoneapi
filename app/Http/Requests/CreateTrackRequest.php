<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateTrackRequest extends Request
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
        'track' => 'required|max:255',
        'description' => 'required|max:255',
        'field_id' => 'required',
        'level_id' => 'required',
        'status_id' => 'required',
        'skill_ids.*' => 'exists:skills,id' // check each item in the array 

        ];
    }

    public function response(array $errors)
    {
        return response()->json(['message' => $errors,'code'=>422], 422);
    }

}