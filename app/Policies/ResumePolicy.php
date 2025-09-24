<?php

namespace App\Policies;

use App\Models\Resume;
use App\Models\User;

class ResumePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own resumes
    }

    public function view(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can upload resumes
    }

    public function update(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function delete(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function restore(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }

    public function forceDelete(User $user, Resume $resume): bool
    {
        return $user->id === $resume->user_id;
    }
}