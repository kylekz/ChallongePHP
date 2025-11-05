<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

/**
 * Participant Standing Data
 *
 * Wraps a Participant with calculated standings information including
 * group stage results and final bracket results.
 */
final class ParticipantStanding
{
    /**
     * @param Participant $participant The tournament participant
     * @param array<int, array{matches: mixed, results: mixed}> $groups Group stage standings
     * @param array{matches: mixed, results: mixed} $final Final bracket standings
     */
    public function __construct(
        public readonly Participant $participant,
        public readonly array $groups,
        public readonly array $final,
    ) {
    }
}
