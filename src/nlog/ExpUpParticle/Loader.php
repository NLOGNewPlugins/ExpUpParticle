<?php

namespace nlog\ExpUpParticle;

use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;

class Loader extends PluginBase implements Listener {

    /** @var array */
    private $levels = [];

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $this->levels[$event->getPlayer()->getName()] = $event->getPlayer()->getXpLevel();
    }

    public function onQuit(PlayerQuitEvent $event) {
        unset($this->levels[$event->getPlayer()->getName()]);
    }

    /**
     * @param PlayerExperienceChangeEvent $event
     * @priority MONITOR
     */
    public function onExperienceChange(PlayerExperienceChangeEvent $event) {
        /** @var Player $player */
        if (
                !$event->isCancelled() &&
                ($player = $event->getEntity()) instanceof Player &&
                ($newLevel = $event->getNewLevel() ?? -1) > $event->getOldLevel() &&
                $newLevel > $this->levels[$player->getName()]
        ) {
            $this->levels[$player->getName()] = $newLevel;
            $this->getScheduler()->scheduleDelayedTask(new GenerateParticleTask($this, $player, $newLevel), 1);
        }
    }

}
