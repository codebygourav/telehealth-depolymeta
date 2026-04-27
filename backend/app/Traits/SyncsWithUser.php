<?php

namespace App\Traits;

use App\Models\User;

trait SyncsWithUser
{
    /**
     * Sync user details whenever the model is updated.
     */
    public function syncWithUser(array $updateData): void
    {
        if (!$this->user) {
            return;
        }

        $userUpdateData = [];

        // Sync Name
        if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
            $userUpdateData['name'] = trim(
                ($updateData['first_name'] ?? $this->first_name) . ' ' .
                ($updateData['last_name'] ?? $this->last_name)
            );
        }

        // Sync Email
        if (isset($updateData['email'])) {
            $userUpdateData['email'] = $updateData['email'];
        }

        // Sync Phone (Patient uses mobile_no, Doctor and User use phone)
        if (isset($updateData['mobile_no'])) {
            $userUpdateData['phone'] = $updateData['mobile_no'];
        } elseif (isset($updateData['phone'])) {
            $userUpdateData['phone'] = $updateData['phone'];
        }

        // Sync Password
        if (isset($updateData['password'])) {
            $userUpdateData['password'] = bcrypt($updateData['password']);
        }

        if (!empty($userUpdateData)) {
            $this->user->update($userUpdateData);
        }
    }
}
