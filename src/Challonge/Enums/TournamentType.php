<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Tournament Type
 * 
 * Defines the bracket/format type for a tournament.
 * Based on Challonge API v2.1 specification.
 */
enum TournamentType: string
{
    case SINGLE_ELIMINATION = 'single elimination';
    case DOUBLE_ELIMINATION = 'double elimination';
    case ROUND_ROBIN = 'round robin';
    case SWISS = 'swiss';
    case FREE_FOR_ALL = 'free for all';
}
