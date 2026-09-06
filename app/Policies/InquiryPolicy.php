<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inquiries.view');
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.view') && $this->withinScope($user, $inquiry);
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.manage') && $this->withinScope($user, $inquiry);
    }

    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.manage') && $this->withinScope($user, $inquiry);
    }

    /**
     * Branch staff see their branch's enquiries plus general ones.
     */
    private function withinScope(User $user, Inquiry $inquiry): bool
    {
        if ($user->branch_id === null) {
            return true;
        }

        return $inquiry->branch_id === null || $user->branch_id === $inquiry->branch_id;
    }
}
