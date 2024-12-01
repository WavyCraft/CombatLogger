<?php

namespace wavycraft\combatlog;

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

    private array $combatLog = [];

    private const COMBAT_DURATION = 15;

    public function setCombatState(Player $victim, Player $damager) : void{
        $uuid = $victim->getUniqueId()->getBytes();
        $damagerName = $damager->getName();

        if (!isset($this->combatLog[$uuid])) {
            $this->combatLog[$uuid] = [];
        }

        $this->combatLog[$uuid][$damagerName] = time() + self::COMBAT_DURATION;

        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new CombatTask($victim), 20);
        $victim->sendMessage(TextColor::RED . "You are in combat! You cannot log out for 15 seconds...");
    }

    public function resetCombatTimer(Player $player) : void{
        $uuid = $player->getUniqueId()->getBytes();
        if (isset($this->combatLog[$uuid])) {
            foreach ($this->combatLog[$uuid] as $opponentName => &$combatEndTime) {
                $combatEndTime = time() + self::COMBAT_DURATION;
            }
        }
    }

    public function handlePlayerDisconnect(Player $player) : void{
        $uuid = $player->getUniqueId()->getBytes();
        if (isset($this->combatLog[$uuid])) {
            foreach ($this->combatLog[$uuid] as $opponentName => $combatEndTime) {
                if (time() <= $combatEndTime) {
                    $opponent = Server::getInstance()->getPlayerExact($opponentName);
                    if ($opponent !== null) {
                        $opponent->sendMessage(TextColor::GREEN . "You have been credited for killing " . TextColor::YELLOW . $player->getName() . TextColor:.GREEN . "!");
                        KDRManager::getInstance()->addKill($opponent);
                    }
                }
            }
            unset($this->combatLog[$uuid]);
        }
    }

    public function isInCombat(Player $player) : bool{
        $uuid = $player->getUniqueId()->getBytes();
        return isset($this->combatLog[$uuid]) && count($this->combatLog[$uuid]) > 0;
    }

    public function getRemainingCombatTime(Player $player) : int{
        $uuid = $player->getUniqueId()->getBytes();
        if (isset($this->combatLog[$uuid])) {
            $remainingTimes = array_map(fn($time) => $time - time(), $this->combatLog[$uuid]);
            $remainingTime = max($remainingTimes);
            return $remainingTime > 0 ? $remainingTime : 0;
        }
        return 0;
    }
}