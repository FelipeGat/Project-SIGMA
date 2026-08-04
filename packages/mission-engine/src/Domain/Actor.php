<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;

/** Quem originou a Mission — mesmo formato do campo `actor` do Envelope. */
final class Actor
{
    public function __construct(
        public readonly ActorType $type,
        public readonly string $id,
    ) {
        if (trim($id) === '') {
            throw new SigmaException('Actor.id não pode ser vazio.', 'mission.invalid_actor');
        }
    }
}
