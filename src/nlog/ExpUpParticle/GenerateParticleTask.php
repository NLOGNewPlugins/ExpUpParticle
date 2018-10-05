<?php
/**
 * Created by PhpStorm.
 * User: nlog
 * Date: 2018-10-04
 * Time: 오후 8:42
 */

namespace nlog\ExpUpParticle;


use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Random;

class GenerateParticleTask extends Task {

    public static function getRandom() {
        return (new Random())->nextFloat() * ((mt_rand(0, 1) === 0 ? -1 : 1));
    }

    public static function getRandomPlus(float $random) {
        return ($random > 0) ? -1 : 1;
    }

    /** @var Loader */
    private $plugin;

    /** @var Player */
    private $player;

    /** @var int */
    private $nextLevel;

    /** @var int */
    private $taskStep;

    /** @var RemoveEntityPacket */
    private $rpk;

    /** @var Player[] */
    private $viewers;

    /** @var RemoveEntityPacket */
    private $rpk1;

    /** @var Player[] */
    private $viewers1;

    public function __construct(Loader $plugin, Player $player, int $nextLevel) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->nextLevel = $nextLevel;
        $this->taskStep = -1;
    }

    public function onRun(int $currentTick) {
        if (!$this->player->isOnline()) {
            return;
        }
        $random = new Random();
        if (++$this->taskStep === 0) {
            // 잘 쓸게요 데베형
            $pk = new AddEntityPacket();
            $this->rpk = new RemoveEntityPacket();
            $this->rpk->entityUniqueId = $pk->entityRuntimeId = Entity::$entityCount++;
            $pk->type = Entity::CHICKEN;
            $pk->position = $this->player->add(0, 2.7, 0);
            $pk->metadata = [
                    Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "§a§lLevel UP !!!"],
                    Entity::DATA_ALWAYS_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
                    Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
                    Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT]
            ];
            $this->viewers = $this->player->getViewers();
            $this->viewers[] = $this->player;
            Server::getInstance()->broadcastPacket($this->viewers, $pk);
        } elseif ($this->taskStep < 17) {
            $particle = new CriticalParticle($pos = $this->player->asPosition());
            for ($i = 0; $i < 2; $i++) {
                $particle->setComponents(
                        $pos->x + (($r = self::getRandom()) * 2 + self::getRandomPlus($r)) * 2,
                        $pos->y + (($r = self::getRandom()) * 2 + self::getRandomPlus($r)) + 1,
                        $pos->z + (($r = self::getRandom()) * 2 + self::getRandomPlus($r)) * 2
                );
                $pos->getLevel()->addParticle($particle);
            }
        } elseif ($this->taskStep === 17) {
            // 잘 쓸게요 데베형
            $this->viewers1 = $this->player->getViewers();

            $pk = new AddEntityPacket();
            $this->rpk1 = new RemoveEntityPacket();
            $this->rpk1->entityUniqueId = $pk->entityRuntimeId = Entity::$entityCount++;
            $pk->type = Entity::CHICKEN;
            $pk->position = $this->player->add(0, 2.0, 0);
            $pk->metadata = [
                    Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "§b§l~~ {$this->nextLevel} ~~"],
                    Entity::DATA_ALWAYS_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
                    Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
                    Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT]
            ];
            $this->viewers1 = $this->player->getViewers();
            $this->viewers1[] = $this->player;
            Server::getInstance()->broadcastPacket($this->viewers1, $pk);
        } elseif ($this->taskStep < 40 && $this->taskStep % 3 === 0) {
            $this->player->getLevel()->addParticle(new DestroyBlockParticle(
                    $this->player->add(
                            (($r = self::getRandom()) * 2 + self::getRandomPlus($r)) * 3,
                            0.3,
                            (($r = self::getRandom()) * 2 + self::getRandomPlus($r)) * 3
                    ),
                    BlockFactory::get(Block::DIAMOND_BLOCK)
            ));
        } elseif ($this->taskStep >= 50) {
            foreach ($this->viewers as $k => $viewer) {
                if ($viewer->isOnline()) {
                    $viewer->sendDataPacket($this->rpk);
                }
            }

            foreach ($this->viewers1 as $k => $viewer) {
                if ($viewer->isOnline()) {
                    $viewer->sendDataPacket($this->rpk1);
                }
            }

            return;
        }

        $this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
    }

}