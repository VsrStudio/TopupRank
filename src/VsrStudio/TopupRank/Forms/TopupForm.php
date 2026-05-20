<?php

namespace VsrStudio\TopupRank\Forms;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

use pocketmine\player\Player;

use VsrStudio\TopupRank\Main;

class TopupForm {

    private Main $plugin;
    private array $cooldown = [];

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    private function hasCooldown(Player $player) : bool{

    $seconds =
        (int)(
            $this->plugin
                ->getConfig()
                ->getNested(
                    "cooldown.form",
                    30
                )
        );

    $name =
        strtolower(
            $player->getName()
        );

    $time =
        time();

    if(isset($this->cooldown[$name])){

        $remaining =
            $this->cooldown[$name] - $time;

        if($remaining > 0){

            $player->sendMessage(
                "§cWait {$remaining} seconds before placing another order."
            );

            return true;
        }
    }

    $this->cooldown[$name] =
        $time + $seconds;

    return false;
    }

    public function getForm() : SimpleForm{

        $lang =
            $this->plugin
                ->getLangManager();

        $form = new SimpleForm(
            function(
                Player $player,
                ?int $data
            ) : void {

                if($data === null){
                    return;
                }

                $ranks =
                    $this->plugin
                        ->getPluginConfig()["ranks"] ?? [];

                $rankNames =
                    array_keys($ranks);

                $selectedRank =
                    $rankNames[$data] ?? null;

                if($selectedRank !== null){

                    $this->showRankInfo(
                        $player,
                        $selectedRank
                    );
                }
            }
        );

        $form->setTitle(
            $lang->translate("title")
        );

        $form->setContent(
            $lang->translate("contact_info") .
            "\n\n" .
            $lang->translate("choose_rank")
        );

        foreach(
            $this->plugin
                ->getPluginConfig()["ranks"]
            as $rank => $details
        ){

            $buttonName =
                $details["name-button"] ?? $rank;

            $price =
                $details["price"] ?? "0";

            $image =
                $details["image-form"] ?? "";

            /*
             * SUPPORT PATH & URL
             */
            $imageType =
                str_starts_with($image, "http://") ||
                str_starts_with($image, "https://")
                    ? SimpleForm::IMAGE_TYPE_URL
                    : SimpleForm::IMAGE_TYPE_PATH;

            $form->addButton(
                "{$buttonName}\n§fRp {$price}",
                $image !== ""
                    ? $imageType
                    : -1,
                $image
            );
        }

        return $form;
    }

    private function showRankInfo(
        Player $player,
        string $rank
    ) : void {

        $rankData =
            $this->plugin
                ->getPluginConfig()["ranks"][$rank] ?? [];

        $title =
            $rankData["title"] ?? $rank;

        $content =
            $rankData["content"] ?? "";

        $image =
            $details["image-form"] ?? "";

        /*
         * SUPPORT PATH & URL
         */
        $imageType =
            str_starts_with($image, "http://") ||
            str_starts_with($image, "https://")
                ? SimpleForm::IMAGE_TYPE_URL
                : SimpleForm::IMAGE_TYPE_PATH;

        $form = new SimpleForm(
            function(
                Player $player,
                ?int $data
            ) use (
                $rank
            ) : void {

                if($data === null){
                    return;
                }

                switch($data){

                    /*
                     * BUY
                     */
                    case 0:

                        $this->showCustomForm(
                            $player,
                            $rank
                        );

                    break;

                    /*
                     * RETURN
                     */
                    case 1:

                        $player->sendForm(
                            $this->getForm()
                        );

                    break;
                }
            }
        );

        $form->setTitle($title);

        $form->setContent($content);

        /*
         * BUY BUTTON ABOVE
         */
        $form->addButton(
            "§aBuy",
            $image !== ""
                ? $imageType
                : -1,
            $image
        );

        $form->addButton(
            "§cReturn",
            SimpleForm::IMAGE_TYPE_PATH,
            "textures/ui/cancel"
        );

        $player->sendForm($form);
    }

    private function showCustomForm(
        Player $player,
        string $rank
    ) : void {

        $lang =
            $this->plugin
                ->getLangManager();

        $paymentMethods =
            $this->plugin
                ->getPluginConfig()["payment_methods"] ?? [];

        $form = new CustomForm(
            function(
                Player $player,
                ?array $data
            ) use (
                $rank,
                $paymentMethods,
                $lang
            ) : void {

                if($data === null){
                    return;
                }

                $gamertag =
                    trim($data[0]);

                $discord =
                    trim($data[1]);

                /*
                 * REMOVE @
                 */
                $discord =
                    ltrim($discord, "@");

                $methodIndex =
                    (int)$data[2];

                $method =
                    $paymentMethods[$methodIndex]
                    ?? "Unknown";

                if($gamertag === ""){

                    $player->sendMessage(
                        $lang->translate(
                            "invalid_gamertag"
                        )
                    );

                    return;
                }

                if($discord === ""){

                    $player->sendMessage(
                        "§cDiscord username cannot be empty."
                    );

                    return;
                }
                if($this->hasCooldown($player)){
                    return;
                }

                /*
                 * ADD @ AGAIN
                 */
                $discord =
                    "@" . $discord;

                $orderId =
                    $this->plugin
                        ->getOrderManager()
                        ->addOrder(
                            $gamertag,
                            $rank,
                            $discord,
                            $method
                        );

                /*
                 * DISCORD WEBHOOK
                 */
                $this->plugin
                    ->getDiscordWebhook()
                    ->sendTopup(
                        $orderId,
                        $player->getName(),
                        $rank,
                        $discord,
                        $method,
                        "pending"
                    );

                $player->sendMessage(
                    "§aOrder successfully created.\n" .
                    "§fID Order: §e{$orderId}\n" .
                    "§fStatus: §6PENDING"
                );

                /*
                 * NOTIFY ADMIN
                 */
                foreach(
                    $this->plugin
                        ->getServer()
                        ->getOnlinePlayers()
                    as $onlinePlayer
                ){

                    if(
                        $onlinePlayer->hasPermission(
                            "topuprank.admin"
                        )
                    ){

                        $onlinePlayer->sendMessage(
                            "§aTopup new!\n" .
                            "§fID: §e{$orderId}\n" .
                            "§fPlayer: §b{$gamertag}\n" .
                            "§fRank: §a{$rank}\n" .
                            "§fMethod: §e{$method}"
                        );
                    }
                }
            }
        );

        $form->setTitle(
            "§l§bTopup Rank"
        );

        $form->addInput(
            "Enter Gamertag",
            "Example: Steve",
            $player->getName()
        );

        /*
         * @ TIDAK BISA DIHAPUS
         */
        $form->addInput(
            "Enter Discord Username",
            "Example: username",
            "@"
        );

        $form->addDropdown(
            "Select Payment Method",
            $paymentMethods
        );

        $player->sendForm($form);
    }
}
