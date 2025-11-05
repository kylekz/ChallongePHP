<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Tournament State
 *
 * The current state/status of a tournament lifecycle.
 * Based on Challonge API v2.1 specification.
 *
 * Pre-start states:
 * - PENDING: Still adding participants, changing format, setting rules
 * - CHECKING_IN: Participant check-in period (if enabled)
 * - CHECKED_IN: After check-in results processed (if enabled)
 * - ACCEPTING_PREDICTIONS: Bracket prediction submission period (if enabled)
 *
 * In-progress states:
 * - GROUP_STAGES_UNDERWAY: Group stage being played (2-stage tournaments)
 * - GROUP_STAGES_FINALIZED: Group stage complete, final stage not started (2-stage)
 * - UNDERWAY: Final stage is underway
 * - AWAITING_REVIEW: All results reported, ready for review
 *
 * Final state:
 * - COMPLETE: Tournament ended
 */
enum TournamentState: string
{
    // Pre-start states
    case PENDING = 'pending';
    case CHECKING_IN = 'checking_in';
    case CHECKED_IN = 'checked_in';
    case ACCEPTING_PREDICTIONS = 'accepting_predictions';

    // In-progress states
    case GROUP_STAGES_UNDERWAY = 'group_stages_underway';
    case GROUP_STAGES_FINALIZED = 'group_stages_finalized';
    case UNDERWAY = 'underway';
    case AWAITING_REVIEW = 'awaiting_review';

    // Final state
    case COMPLETE = 'complete';
}
