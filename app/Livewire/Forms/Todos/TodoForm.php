<?php

namespace App\Livewire\Forms\Todos;

use App\Data\Todos\TodoData;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TodoForm extends Form
{
    #[Validate]
    public string $title = '';

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('todos.fields.title'),
        ];
    }

    public function data(): TodoData
    {
        return TodoData::fromArray($this->validate());
    }
}
