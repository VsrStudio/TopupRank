<?php

namespace VsrStudio\TopupRank\Forms;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use VsrStudio\TopupRank\Main;

class TopupForm {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getForm(): SimpleForm {
        $lang = $this->plugin->getLangManager();

        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;
            
            $ranks = $this->plugin->getPluginConfig()["ranks"];
            $rankNames = array_keys($ranks);
            $selectedRank = $rankNames[$data] ?? null;

            if ($selectedRank !== null) {
                $this->showCustomForm($player, $selectedRank);
            }
        });

        $form->setTitle($lang->translate("title"));
        $form->setContent($lang->translate("contact_info") . "\n\n" . $lang->translate("choose_rank"));

        foreach ($this->plugin->getPluginConfig()["ranks"] as $rank => $details) {
            $form->addButton($lang->translate("rank_description", [
                "rank" => $rank,
                "price" => $details["price"],
                "description" => $details["description"]
            ]), 0, $details["image"]);
        }

        return $form;
    }

    private function showCustomForm(Player $player, string $rank): void {
        $lang = $this->plugin->getLangManager();

        $form = new CustomForm(function (Player $player, $data) use ($rank, $lang) {
            if ($data === null) return;

            [$gamertag, $phone, $method] = $data;

            if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
                $player->sendMessage($lang->translate("invalid_phone"));
                return;
            }

            if (empty($gamertag)) {
                $player->sendMessage($lang->translate("invalid_gamertag"));
                return;
            }

            $this->plugin->getOrderManager()->addOrder($gamertag, $rank, $phone, $method);
            $this->plugin->getDiscordWebhook()->sendTopup($player->getName(),
                                                          $rank,
                                                          $phone,
                                                          $method);
            $player->sendMessage($lang->translate("rank_purchased", ["rank" => $rank]));

            foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
                if ($onlinePlayer->hasPermission("topuprank.admin")) {
                    $onlinePlayer->sendMessage($lang->translate("notify_admin", [
                        "player" => $player->getName(),
                        "rank" => $rank,
                        "method" => $method
                    ]));
                }
            }
        });

        $form->setTitle($lang->translate("form_title"));
        $form->addInput($lang->translate("enter_gamertag"), "Contoh: Steve", $player->getName());
        $form->addInput($lang->translate("enter_phone"), "Contoh: 08123456789");
        $form->addDropdown($lang->translate("select_payment"), $this->plugin->getPluginConfig()["payment_methods"]);

        $player->sendForm($form);
    }
}
