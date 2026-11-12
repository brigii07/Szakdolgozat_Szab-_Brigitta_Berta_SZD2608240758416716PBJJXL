<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function download(User $user, Document $document): bool
    {
        return in_array($user->role, ['doctor', 'assistant'], true);
    }
}
