<?php

namespace VsrStudio\TopupRank\Forms;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

use VsrStudio\TopupRank\Main;

class AdminForm {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function getForm(): SimpleForm {

        $form = new SimpleForm(function (
            Player $player,
            ?int $data
        ): void {

            if ($data === null) {
                return;
            }

            switch ($data) {

                case 0:
                    $this->showOrderList($player);
                    break;
            }
        });

        $form->setTitle(
            "§l§bTopup Rank Admin"
        );

        $form->setContent(
            "§fPilih menu yang tersedia."
        );

        $form->addButton(
            "§aDaftar Permintaan"
        );

        return $form;
    }

    /**
     * ORDER LIST
     */
    private function showOrderList(Player $player): void {

        $orders = $this->plugin
            ->getOrderManager()
            ->getOrders();

        $form = new SimpleForm(function (
            Player $player,
            ?int $data
        ) use (
            $orders
        ): void {

            if ($data === null) {
                return;
            }

            if (isset($orders[$data])) {

                $this->showOrderDetails(
                    $player,
                    $orders[$data]
                );
            }
        });

        $form->setTitle(
            "§l§eDaftar Permintaan"
        );

        if (count($orders) <= 0) {

            $form->setContent(
                "§cTidak ada permintaan topup."
            );

        } else {

            foreach ($orders as $order) {

                /*
                 * STATUS COLOR
                 */
                $statusColor = match (
                    strtolower($order["status"])
                ) {

                    "success" => "§a",

                    "rejected" => "§c",

                    default => "§6"
                };

                $form->addButton(

                    "§fID: §e" . $order["id"] . "\n" .

                    "§bPlayer: §f" .
                    $order["gamertag"] . "\n" .

                    "§aRank: §f" .
                    $order["rank"] . "\n" .

                    "§ePayment: §f" .
                    $order["method"] . "\n" .

                    "§dStatus: " .
                    $statusColor .
                    strtoupper($order["status"])
                );
            }
        }

        $player->sendForm($form);
    }

    /**
     * ORDER DETAIL
     */
    private function showOrderDetails(
        Player $player,
        array $order
    ): void {

        $form = new SimpleForm(function (
            Player $player,
            ?int $data
        ) use (
            $order
        ): void {

            if ($data === null) {
                return;
            }

            switch ($data) {

                /*
                 * APPROVE
                 */
                case 0:

                    /*
                     * ALREADY SUCCESS
                     */
                    if (
                        strtolower($order["status"]) ===
                        "success"
                    ) {

                        $player->sendMessage(
                            "§cOrder sudah disetujui."
                        );

                        return;
                    }

                    $this->plugin
                        ->getOrderManager()
                        ->approveOrder($order);

                    $this->plugin
                        ->getRankManager()
                        ->grantRank(
                            $order["gamertag"],
                            $order["rank"]
                        );

                    /*
                     * WEBHOOK UPDATE
                     */
                    $this->plugin
                        ->getDiscordWebhook()
                        ->sendStatusUpdate(
                            $order["id"],
                            $order["gamertag"],
                            $order["rank"],
                            "success"
                        );

                    $player->sendMessage(
                        "§aOrder berhasil disetujui."
                    );

                    break;

                /*
                 * REJECT
                 */
                case 1:

                    /*
                     * ALREADY REJECTED
                     */
                    if (
                        strtolower($order["status"]) ===
                        "rejected"
                    ) {

                        $player->sendMessage(
                            "§cOrder sudah ditolak."
                        );

                        return;
                    }

                    $this->plugin
                        ->getOrderManager()
                        ->rejectOrder($order);

                    /*
                     * WEBHOOK UPDATE
                     */
                    $this->plugin
                        ->getDiscordWebhook()
                        ->sendStatusUpdate(
                            $order["id"],
                            $order["gamertag"],
                            $order["rank"],
                            "rejected"
                        );

                    $player->sendMessage(
                        "§cOrder berhasil ditolak."
                    );

                    break;
            }
        });

        /*
         * STATUS COLOR
         */
        $statusColor = match (
            strtolower($order["status"])
        ) {

            "success" => "§a",

            "rejected" => "§c",

            default => "§6"
        };

        $form->setTitle(
            "§l§dDetail Order"
        );

        $form->setContent(

            "§bOrder ID: §f" .
            $order["id"] . "\n\n" .

            "§bGamertag: §f" .
            $order["gamertag"] . "\n" .

            "§bDiscord: §f" .
            $order["discord"] . "\n" .

            "§bRank: §f" .
            $order["rank"] . "\n" .

            "§bPayment: §f" .
            $order["method"] . "\n" .

            "§bStatus: " .
            $statusColor .
            strtoupper($order["status"]) . "\n" .

            "§bTime: §f" .
            $order["time"]
        );

        /*
         * BUTTONS
         */
        $form->addButton(
            "§aSetujui"
        );

        $form->addButton(
            "§cTolak"
        );

        $player->sendForm($form);
    }
}
