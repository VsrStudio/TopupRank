<?php

namespace Vsrstudio\TopupRank;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Vsrstudio\TopupRank\Forms\TopupForm;
use Vsrstudio\TopupRank\Forms\AdminForm;
use Vsrstudio\TopupRank\Data\OrderManager;
use Vsrstudio\TopupRank\Data\TopupRankManager;
use Vsrstudio\TopupRank\Data\APIKeyManager;
use Vsrstudio\TopupRank\Data\LangManager;

class Main extends PluginBase {

    private OrderManager $orderManager;
    private TopupRankManager $rankManager;
    private APIKeyManager $apiKeyManager;
    private LangManager $langManager;
    private array $config;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig()->getAll();
        $langDir = $this->getDataFolder() . "lang/";
        $defaultLang = $this->getConfig()->get("default_language", "en");
        
        @mkdir($this->getDataFolder() . "lang/");
        $this->saveResource("lang/en.yml");
        $this->saveResource("lang/id.yml");

        $this->orderManager = new OrderManager($this);
        $this->rankManager = new TopupRankManager($this);
        $this->apiKeyManager = new APIKeyManager($this);
        $this->langManager = new LangManager($langDir, $defaultLang);

        $pluginName = $this->getDescription()->getName();
        $map = $this->getDescription()->getAuthors();
        $ver = $this->getDescription()->getVersion();

        if (isset($map[0])) {
            if ($pluginName !== "TopupRank" or $map[0] !== "VsrStudio" or $ver !== "1.2.0-BETA") {
                $this->getLogger()->emergency("§cPlugin info for $pluginName has been changed. Please restore the correct plugin.yml: author should be \"VsrStudio\", version should be \"1.0.1-BETA\", and plugin name should be \"TopupRank\".");
                $this->getServer()->shutdown();
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "topuprank") {
            if ($sender instanceof Player) {
                if (!$sender->hasPermission("topuprank.use")) {
                    $sender->sendMessage("§cAnda tidak memiliki izin untuk menggunakan perintah ini.");
                    return true;
                }

                $form = new TopupForm($this);
                $sender->sendForm($form->getForm());
            } else {
                $sender->sendMessage("Perintah ini hanya dapat digunakan oleh pemain.");
            }
            return true;
        }

        if ($command->getName() === "rankadmin") {
            if ($sender instanceof Player) {
                if (!$sender->hasPermission("topuprank.admin")) {
                    $sender->sendMessage("§cAnda tidak memiliki izin untuk menggunakan perintah ini.");
                    return true;
                }

                $form = new AdminForm($this);
                $sender->sendForm($form->getForm());
            } else {
                $sender->sendMessage("Perintah ini hanya dapat digunakan oleh pemain.");
            }
            return true;
        }

        if ($command->getName() === "createapikey") {
            if ($sender instanceof Player) {
                if (!$sender->hasPermission("topuprank.createapikey")) {
                    $sender->sendMessage("§cAnda tidak memiliki izin untuk menggunakan perintah ini.");
                    return true;
                }

                $apiKey = $this->apiKeyManager->createAPIKey();
                $sender->sendMessage("§aAPI Key baru telah dibuat: §b$apiKey");
            } else {
                $sender->sendMessage("Perintah ini hanya dapat digunakan oleh pemain.");
            }
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

    public function getAPIKeyManager(): APIKeyManager {
        return $this->apiKeyManager;
    }
}
