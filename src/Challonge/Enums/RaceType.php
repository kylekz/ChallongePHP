<?php

declare(strict_types=1);

namespace Reflex\Challonge\Enums;

/**
 * Race Type
 * 
 * The type of racing tournament.
 * Based on Challonge API v2.1 specification.
 */
enum RaceType: string
{
    case TIME_TRIAL = 'time_trial';
    case GRAND_PRIX = 'grand_prix';
}
