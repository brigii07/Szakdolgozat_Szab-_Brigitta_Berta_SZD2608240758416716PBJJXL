<?php

namespace App\Http\Requests\CareEpisode;

use App\Http\Requests\BaseRequest;

class OpenEpisodeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
        ];
    }
}
