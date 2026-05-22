<?php

namespace VsrStudio\TopupRank;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use VsrStudio\TopupRank\Forms\TopupForm;
use VsrStudio\TopupRank\Forms\AdminForm;

use VsrStudio\TopupRank\Data\OrderManager;
use VsrStudio\TopupRank\Data\TopupRankManager;
use VsrStudio\TopupRank\Data\LangManager;

use VsrStudio\TopupRank\Utils\DiscordWebhook;
use VsrStudio\TopupRank\Web\WebServerManager;

class Main extends PluginBase {

    private OrderManager $orderManager;
    private TopupRankManager $rankManager;
    private LangManager $langManager;
    private DiscordWebhook $discordWebhook;

    private WebServerManager $webServerManager;

    private array $config;

    public function onEnable(): void {

        $this->saveDefaultConfig();

        $this->config = $this->getConfig()->getAll();

        @mkdir($this->getDataFolder() . "lang/");

        $this->saveResource("lang/en.yml");
        $this->saveResource("lang/id.yml");

        $langDir = $this->getDataFolder() . "lang/";

        $defaultLang = $this->getConfig()->get(
            "default_language",
            "id"
        );

        $this->orderManager = new OrderManager($this);

        $this->rankManager = new TopupRankManager($this);

        $this->langManager = new LangManager(
            $langDir,
            $defaultLang
        );

        $this->discordWebhook = new DiscordWebhook(
            $this,
            $this->getConfig()->get(
                "discord-webhook",
                ""
            )
        );

        /*
         * START WEB SERVER
         */
        $this->webServerManager = new WebServerManager(
            $this
        );

        $this->webServerManager->start();

        $this->getLogger()->info(
            "TopupRank enabled."
        );
    }

    public function onDisable(): void {

        $this->webServerManager->stop();
    }

    public function onCommand(
        CommandSender $sender,
        Command $command,
        string $label,
        array $args
    ): bool {

        switch ($command->getName()) {

            case "topuprank":

                if (!$sender instanceof Player) {

                    $sender->sendMessage(
                        "Command hanya untuk player."
                    );

                    return true;
                }

                $sender->sendForm(
                    (new TopupForm($this))->getForm()
                );

                return true;

            case "rankadmin":

                if (!$sender instanceof Player) {

                    $sender->sendMessage(
                        "Command hanya untuk player."
                    );

                    return true;
                }

                $sender->sendForm(
                    (new AdminForm($this))->getForm()
                );

                return true;
        }

        return false;
    }

    public function getOrderManager(): OrderManager {
        return $this->orderManager;
    }

    public function getRankManager(): TopupRankManager {
        return $this->rankManager;
    }

    public function getPluginConfig(): array {
        return $this->config;
    }

    public function getLangManager(): LangManager {
        return $this->langManager;
    }

    public function getWebServerManager(): WebServerManager {
        return $this->webServerManager;
    }

    public function getDiscordWebhook(): DiscordWebhook {
        return $this->discordWebhook;
    }
}
