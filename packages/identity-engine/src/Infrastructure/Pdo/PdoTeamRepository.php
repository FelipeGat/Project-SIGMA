<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Infrastructure\Pdo;

use Sigma\IdentityEngine\Application\TeamRepository;
use Sigma\IdentityEngine\Domain\CompanyId;
use Sigma\IdentityEngine\Domain\Team;
use Sigma\IdentityEngine\Domain\TeamId;
use Sigma\IdentityEngine\Domain\TeamType;
use Sigma\IdentityEngine\Domain\UserId;

final class PdoTeamRepository implements TeamRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Team $team): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO teams (id, company_id, name, type, created_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), type = VALUES(type)',
        );
        $statement->execute([
            $team->id()->toString(),
            $team->companyId()->toString(),
            $team->name(),
            $team->type()->value,
        ]);

        $this->pdo->prepare('DELETE FROM team_memberships WHERE team_id = ?')->execute([$team->id()->toString()]);

        $insertMember = $this->pdo->prepare('INSERT INTO team_memberships (team_id, user_id) VALUES (?, ?)');
        foreach ($team->members() as $memberId) {
            $insertMember->execute([$team->id()->toString(), $memberId->toString()]);
        }
    }

    public function find(TeamId $id): ?Team
    {
        $statement = $this->pdo->prepare('SELECT id, company_id, name, type FROM teams WHERE id = ?');
        $statement->execute([$id->toString()]);

        return $this->hydrate($statement->fetch(\PDO::FETCH_ASSOC));
    }

    public function findByCompany(CompanyId $companyId): array
    {
        $statement = $this->pdo->prepare('SELECT id, company_id, name, type FROM teams WHERE company_id = ?');
        $statement->execute([$companyId->toString()]);

        $teams = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $teams[] = $this->hydrate($row);
        }

        return $teams;
    }

    /** @param array<string, mixed>|false $row */
    private function hydrate($row): ?Team
    {
        if ($row === false) {
            return null;
        }

        $team = new Team(
            TeamId::fromString($row['id']),
            CompanyId::fromString($row['company_id']),
            $row['name'],
            TeamType::from($row['type']),
        );

        $members = $this->pdo->prepare('SELECT user_id FROM team_memberships WHERE team_id = ?');
        $members->execute([$row['id']]);
        foreach ($members->fetchAll(\PDO::FETCH_COLUMN) as $userId) {
            $team->addMember(UserId::fromString($userId));
        }

        return $team;
    }
}
