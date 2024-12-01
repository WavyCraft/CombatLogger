<?php

namespace wavycraft\combatlog;

use pocketmine\scheduler\Task;

use pocketmine\player\Player;

use core\utils\TextColor;

class CombatTask extends Task {

    private Player $player;

    public function __construct(Player $player) {
        $this->player = $player;
    }

    public function onRun() : void{
        $combatManager = CombatManager::getInstance();

        if (!$combatManager->isInCombat($this->player)) {
            $combatManager->resetCombatTimer($this->player);
            $this->player->sendMessage(TextColor::GREEN . "Your combat timer has expired, You can now log out safely...");
            $this->getHandler()->cancel();
        } else {
            $remainingTime = $combatManager->getRemainingCombatTime($this->player);
            $this->player->sendMessage(TextColor::RED . "You are in combat! You must wait " . TextColor::YELLOW . $remainingTime . " seconds " . TextColor::RED . " before logging out safely...");
        }
    }
}