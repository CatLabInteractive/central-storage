<?php

namespace App\Policies;

use App\Models\Consumer;
use App\Models\Processor;
use App\Models\User;

/**
 * Class ConsumerPolicy
 * @package App\Policies
 */
class ProcessorPolicy
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
     * @param Processor $processor
     * @return bool
     */
    public function edit(User $user, Processor $processor)
    {
        return $user->isAdmin() || $processor->consumer->user->equals($user);
    }

    /**
     * @param User $user
     * @param Consumer $consumer
     * @return bool
     */
    public function create(User $user, Consumer $consumer)
    {
        return $user->isAdmin() || $consumer->user->equals($user);
    }
}