<?php

declare(strict_types=1);

namespace wavycraft\combatlogger;

use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase {

    protected static $instance;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}
