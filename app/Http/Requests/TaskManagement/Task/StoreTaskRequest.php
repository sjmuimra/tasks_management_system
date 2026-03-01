<?php

namespace App\Http\Requests\TaskManagement\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status'      => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'deadline'    => ['nullable', 'date', 'after:now'],
            'project_id'  => ['nullable', 'integer', 'exists:projects,id'],
        ];
    }
}
