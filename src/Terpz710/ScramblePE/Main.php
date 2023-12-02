<?php

namespace Terpz710\ScramblePE;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{
    public static ?string $word = null;

    public function onEnable(): void
    {
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            new Config($this->getDataFolder() . "config.yml", Config::YAML, [
                "server_msg" => "The word is {word}",
                "win_msg" => "The player {player} found the word {word} !",
                "win_commands" => [
                    "addmoney {player} 10",
                    "give {player} diamond"
                ],
                "word_list" => [
                    "BlueGamesNetwork",
                    "Diamond",
                    "Minecraft"
                ],
                "time" => 300
            ]);
        }

        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            public function __construct(public Main $main)
            {
            }

            public function onRun(): void
            {
                $word = $this->main->getConfig()->get("word_list")[array_rand($this->main->getConfig()->get("word_list"))];
                Main::$word = $word;
                $this->main->getServer()->broadcastMessage(str_replace("{word}", str_shuffle($word), $this->main->getConfig()->get("server_msg")));
            }
        }, 20 * $this->getConfig()->get("time"));

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onChat(PlayerChatEvent $event)
    {
        if (!is_null(Main::$word)) {
            if ($event->getMessage() === Main::$word) {
                $this->getServer()->broadcastMessage(str_replace(["{word}", "{player}"], [Main::$word, $event->getPlayer()->getName()], $this->getConfig()->get("win_msg")));
                foreach ($this->getConfig()->get("win_commands") as $cmd) {
                    $this->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), str_replace("{player}", $event->getPlayer()->getName(), $cmd));
                }
                Main::$word = null;
            }
        }
    }
}