<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Race State
 * 
 * The current state/status of a race.
 * Based on Challonge API v2.1 specification.
 */
enum RaceState: string
{
    case PENDING = 'pending';
    case UNDERWAY = 'underway';
    case COMPLETED = 'completed';
}
