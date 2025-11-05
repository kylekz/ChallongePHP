<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Match State
 * 
 * The current state/status of a match.
 * Based on Challonge API v2.1 specification.
 */
enum MatchState: string
{
    case PENDING = 'pending';
    case OPEN = 'open';
    case COMPLETE = 'complete';
}
