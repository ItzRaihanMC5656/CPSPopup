<?php

namespace Inaayat\CPSPopup;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\utils\Config;
use Inaayat\CPSPopup\CPSListener;
use Inaayat\CPSPopup\CPSTask;

class Main extends PluginBase implements Listener {

    private $clicks;
    public $config;

    public function onEnable() : void
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents(new CPSListener($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new CPSTask($this), 10);
    }

    public function getCPS(Player $player): int{
        if(!isset($this->clicks[$player->getName()])){
            return 0;
        }
        $time = $this->clicks[$player->getName()][0];
        $clicks = $this->clicks[$player->getName()][1];
        if($time !== time()){
            unset($this->clicks[$player->getName()]);
            return 0;
        }
        return $clicks;
    }

    public function addCPS(Player $player): void {
        if(!isset($this->clicks[$player->getName()])){
            $this->clicks[$player->getName()] = [time(), 0];
        }
        $time = $this->clicks[$player->getName()][0];
        $clicks = $this->clicks[$player->getName()][1];
        if($time !== time()){
            $time = time();
            $clicks = 0;
        }
        $clicks++;
        $this->clicks[$player->getName()] = [$time, $clicks];
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();
        if($packet instanceof InventoryTransactionPacket){
            $transactionType = $packet->trData->getTypeId();
            if($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM || $transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
                $this->addCPS($player);
            }
        }
    }
}
