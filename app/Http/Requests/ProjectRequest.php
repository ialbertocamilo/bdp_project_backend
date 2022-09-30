<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "description" => "string",
            "name" => "string|nullable",
            "step_name" => 'required|min:3|max:50',
            "substep_name" => 'required|min:3|max:50',
            "percentage" => 'required',
            "content" => 'required',
            "project_type_id" => 'required',
        ];
    }
}
