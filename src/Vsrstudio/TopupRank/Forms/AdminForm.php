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

        $form = new SimpleForm(function (Player $player, ?int $data): void {

            if ($data === null) {
                return;
            }

            switch ($data) {

                case 0:
                    $this->showOrderList($player);
                    break;
            }
        });

        $form->setTitle("§l§bTopup Rank Admin");
        $form->setContent("§fPilih menu yang tersedia.");

        // API KEY DIHAPUS
        $form->addButton("§aDaftar Permintaan");

        return $form;
    }

    private function showOrderList(Player $player): void {

        $orders = $this->plugin->getOrderManager()->getOrders();

        $form = new SimpleForm(function (Player $player, ?int $data) use ($orders): void {

            if ($data === null) {
                return;
            }

            if (isset($orders[$data])) {
                $this->showOrderDetails($player, $orders[$data]);
            }
        });

        $form->setTitle("§l§eDaftar Permintaan");

        if (count($orders) === 0) {

            $form->setContent("§cTidak ada permintaan topup.");

        } else {

            foreach ($orders as $order) {

                $form->addButton(
                    "§bGamertag: §f" . $order["gamertag"] . "\n" .
                    "§aRank: §f" . $order["rank"] . "\n" .
                    "§eMetode: §f" . $order["method"]
                );
            }
        }

        $player->sendForm($form);
    }

    private function showOrderDetails(Player $player, array $order): void {

        $form = new SimpleForm(function (Player $player, ?int $data) use ($order): void {

            if ($data === null) {
                return;
            }

            switch ($data) {

                case 0:

                    $this->plugin->getOrderManager()->approveOrder($order);

                    $this->plugin->getRankManager()->grantRank(
                        $order["gamertag"],
                        $order["rank"]
                    );

                    $player->sendMessage(
                        "§aPermintaan berhasil disetujui."
                    );

                    break;

                case 1:

                    $this->plugin->getOrderManager()->rejectOrder($order);

                    $player->sendMessage(
                        "§cPermintaan berhasil ditolak."
                    );

                    break;
            }
        });

        $form->setTitle("§l§dDetail Permintaan");

        $form->setContent(
            "§bGamertag: §f" . $order["gamertag"] . "\n" .
            "§bNomor HP: §f" . $order["phone"] . "\n" .
            "§bRank: §f" . $order["rank"] . "\n" .
            "§bMetode: §f" . $order["method"] . "\n" .
            "§bWaktu: §f" . $order["time"]
        );

        $form->addButton("§aSetujui");
        $form->addButton("§cTolak");

        $player->sendForm($form);
    }
}
