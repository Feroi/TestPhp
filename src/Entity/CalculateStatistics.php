<?php

namespace App\Entity;
use App\Interfaces\ScoreDataIndexerInterface;


class CalculateStatistics implements ScoreDataIndexerInterface
{

    private $users;

    public function getUsers(): array {
        return $this->users;
    }

    public function setUsers(array $users): void {
        $this->users = $users;
    }

    /**
     *
     * @param int $rangeStart
     * @param int $rangeEnd
     * @return int
     */
    public function getCountOfUsersWithinScoreRange(int $rangeStart,int $rangeEnd): int 
    {

        $total = 0;
        foreach ($this->getUsers() as $user) {
            if (filter_var($user["Score"], FILTER_VALIDATE_INT, ['options' => ['min_range' => $rangeStart, 'max_range' => $rangeEnd]])) {
                $total++;
            }
        }


        return $total;
    }

    /**
     *
     * @param string $region
     * @param string $gender
     * @param bool $legalAge
     * @param bool $score
     * @return int
     */
    public function getCountOfUsersByCondition(string $region, string $gender, bool $legalAge, bool $score): int 
    {
        $total = 0;

        foreach ($this->getUsers() as $user) {
            if (in_array($region, $user) && in_array($gender, $user)) {
                if ($legalAge) {
                    if ($user["Age"] >= 21) {
                        if ($score && $user["Score"] > 0) {
                            $total++;
                        }
                    }
                } else {
                    if ($user["Age"] < 21) {
                        if (!$score & $user["Score"] < 0) {
                            $total++;
                        }
                    }
                }
            }
        }
        
        return $total;
    }

}
