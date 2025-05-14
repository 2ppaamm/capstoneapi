<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $track = $this->skill->tracks->first();
        $fieldId = $track?->field_id; // Safely access related track
		$videos = $fieldId  ? \App\Video::where('field_id', $fieldId)
		        ->where('status_id', 3)
		        ->orderBy('order')
		        ->get()
		        ->map(fn($v) => [
		            'id' => $v->id,
		            'title' => $v->video_title,
		            'description' => $v->description,
		            'link' => $v->video_link,
		            'order' => $v->order,
		        ])
		        ->values()
		: [];

        return [
            'id' => $this->id,
            'question' => $this->question,
            'question_image' => $this->question_image,
            'type_id' => $this->type_id,
            'difficulty_id'=>$this->difficulty_id,
            'answer0' => $this->answer0,
            'answer1' => $this->answer1,
            'answer2' => $this->answer2,
            'answer3' => $this->answer3,
            'correct_answer' => $this->correct_answer,
            'skill' => [
                'id' => $this->skill->id,
                'skill' => $this->skill->skill,
                'lesson_link' => $this->skill->lesson_link,
                'tracks' => $track ? [[
                    'track' => $track->track,
                    'field_id' => $track->field_id,
                    'level' => $track->level->description ?? '',
                ]] : [],
            ],

			'videos' => $videos,
        ];
    }
}