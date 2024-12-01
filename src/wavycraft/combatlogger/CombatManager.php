<?php

declare(strict_types=1);

namespace wavycraft\combatlogger;

use pocketmine\player\Player;

use pocketmine\Server;

use pocketmine\utils\SingletonTrait;

use function array_map;
use function count;
use function max;

use core\kitpvp\utils\KDRManager;

use core\utils\TextColor;

final class CombatManager {
    use SingletonTrait;

    private array $combatGroups = [];
    private const COMBAT_DURATION = 15;

    public function setCombatState(Player $playerA, Player $playerB) : void{
        $playerAUuid = $playerA->getUniqueId()->getBytes();
        $playerBUuid = $playerB->getUniqueId()->getBytes();

        $groupId = $this->getCombatGroupId($playerAUuid, $playerBUuid);
        if ($groupId === null) {
            $groupId = uniqid("combat_", true);
            $this->combatGroups[$groupId] = [];
        }

        $this->combatGroups[$groupId][$playerAUuid] = time() + self::COMBAT_DURATION;
        $this->combatGroups[$groupId][$playerBUuid] = time() + self::COMBAT_DURATION;
        foreach ([$playerA, $playerB] as $player) {
            $player->sendMessage(TextColor::RED . "You are in combat! You cannot log out for 15 seconds...");
        }
    }

    private function getCombatGroupId(string $uuidA, string $uuidB): ?string {
        foreach ($this->combatGroups as $groupId => $group) {
            if (isset($group[$uuidA]) || isset($group[$uuidB])) {
                return $groupId;
            }
        }
        return null;
    }

    public function resetCombatTimer(Player $player): void {
        $playerUuid = $player->getUniqueId()->getBytes();
        $groupId = $this->getCombatGroupId($playerUuid, $playerUuid);

        if ($groupId !== null) {
            foreach ($this->combatGroups[$groupId] as $uuid => &$combatEndTime) {
                $combatEndTime = time() + self::COMBAT_DURATION;
            }
        }
    }

    public function handlePlayerDisconnect(Player $player): void {
        $playerUuid = $player->getUniqueId()->getBytes();
        $groupId = $this->getCombatGroupId($playerUuid, $playerUuid);

        if ($groupId !== null) {
            unset($this->combatGroups[$groupId][$playerUuid]);
            foreach ($this->combatGroups[$groupId] as $remainingUuid => $combatEndTime) {
                $remainingPlayer = Server::getInstance()->getPlayerByRawUUID($remainingUuid);
                if ($remainingPlayer !== null && time() <= $combatEndTime) {
                    $remainingPlayer->sendMessage(TextColor::GREEN . "Your opponent has left the combat zone!");
                }
            }

            if (empty($this->combatGroups[$groupId])) {
                unset($this->combatGroups[$groupId]);
            }
        }
    }

    public function isInCombat(Player $player) : bool{
        $playerUuid = $player->getUniqueId()->getBytes();
        $groupId = $this->getCombatGroupId($playerUuid, $playerUuid);

        return $groupId !== null && count($this->combatGroups[$groupId]) > 0;
    }

    public function getRemainingCombatTime(Player $player) : int{
        $playerUuid = $player->getUniqueId()->getBytes();
        $groupId = $this->getCombatGroupId($playerUuid, $playerUuid);

        if ($groupId !== null) {
            $remainingTimes = array_map(fn($time) => $time - time(), $this->combatGroups[$groupId]);
            $remainingTime = max($remainingTimes);
            return $remainingTime > 0 ? $remainingTime : 0;
        }
        return 0;
    }
}
