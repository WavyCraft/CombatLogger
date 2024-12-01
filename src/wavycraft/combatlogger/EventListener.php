<?php

declare(strict_types=1);

namespace wavycraft\combatlogger;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\player\Player;

class EventListener implements Listener {

    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        
        CombatManager::getInstance()->handlePlayerDisconnect($player);
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $victim = $event->getEntity();

        if ($damager instanceof Player && $victim instanceof Player) {
            $combatManager = CombatManager::getInstance();
            $combatManager->setCombatState($damager, $victim);
            $combatManager->resetCombatTimer($victim);
        }
    }
}
