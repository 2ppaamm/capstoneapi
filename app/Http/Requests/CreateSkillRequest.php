<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateSkillRequest extends Request
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
        'skill' => 'required|max:255',
        'description'=>'required',
        'status_id' => 'required|exists:statuses,id',
//        'lesson_link'=> 'mimes:mov, avi,mpeg,quicktime,mp4|max:10240',
        'image' => 'mimes:jpeg,bmp,png'
        ];
    }

    public function response(array $errors)
    {
        return response()->json(['message' => $errors,'code'=>422], 422);
    }
}
