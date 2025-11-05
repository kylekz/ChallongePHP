<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Tournament State
 * 
 * The current state/status of a tournament.
 * Based on Challonge API v2.1 specification.
 */
enum TournamentState: string
{
    case PENDING = 'pending';
    case UNDERWAY = 'underway';
    case AWAITING_REVIEW = 'awaiting_review';
    case COMPLETE = 'complete';
}
