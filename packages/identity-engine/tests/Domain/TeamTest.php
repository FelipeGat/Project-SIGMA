<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Team;
use Sigma\IdentityEngine\Domain\TeamId;
use Sigma\IdentityEngine\Domain\TeamType;
use Sigma\IdentityEngine\Domain\UserId;

final class TeamTest extends TestCase
{
    public function test_a_team_has_no_members_by_default(): void
    {
        $team = new Team(TeamId::generate(), CompanyId::generate(), 'Suporte', TeamType::System);

        self::assertFalse($team->hasMember(UserId::generate()));
    }

    public function test_add_member_makes_has_member_true(): void
    {
        $team = new Team(TeamId::generate(), CompanyId::generate(), 'Suporte', TeamType::System);
        $user = UserId::generate();

        $team->addMember($user);

        self::assertTrue($team->hasMember($user));
    }

    public function test_adding_the_same_member_twice_is_idempotent(): void
    {
        $team = new Team(TeamId::generate(), CompanyId::generate(), 'Suporte', TeamType::System);
        $user = UserId::generate();

        $team->addMember($user);
        $team->addMember($user);

        self::assertTrue($team->hasMember($user));
    }

    public function test_remove_member_makes_has_member_false(): void
    {
        $team = new Team(TeamId::generate(), CompanyId::generate(), 'Suporte', TeamType::System);
        $user = UserId::generate();

        $team->addMember($user);
        $team->removeMember($user);

        self::assertFalse($team->hasMember($user));
    }

    public function test_type_is_preserved(): void
    {
        $team = new Team(TeamId::generate(), CompanyId::generate(), 'Comercial', TeamType::Business);

        self::assertSame(TeamType::Business, $team->type());
    }
}
