<?php

namespace App\Policies;

use App\Models\Consumer;
use App\Models\User;

/**
 * Class ConsumerPolicy
 * @package App\Policies
 */
class ConsumerPolicy
{
    /**
     * @param User $user
     * @return bool
     */
    public function index(User $user)
    {
        return true;
    }

    /**
     * @param User $user
     * @param Consumer $consumer
     * @return bool
     */
    public function view(User $user, Consumer $consumer)
    {
        return $user->isAdmin() || $consumer->user->equals($user);
    }

    /**
     * @param User $user
     * @param Consumer $consumer
     * @return bool
     */
    public function explore(User $user, Consumer $consumer)
    {
        return $user->isAdmin() || $consumer->user->equals($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return true;
    }
}