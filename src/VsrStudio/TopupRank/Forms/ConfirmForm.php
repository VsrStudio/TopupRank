<?php

namespace VsrStudio\TopupRank\Forms;

use jojoe77777\FormAPI\ModalForm;
use pocketmine\player\Player;

class ConfirmForm {

    public function getForm(string $rank, string $method, callable $onConfirm): ModalForm {
        $form = new ModalForm(function (Player $player, $data) use ($onConfirm) {
            if ($data) {
                $onConfirm($player);
            } else {
                $player->sendMessage("Anda membatalkan permintaan top-up.");
            }
        });

        $form->setTitle("Konfirmasi Top-up");
        $form->setContent("Apakah Anda yakin ingin membeli rank $rank dengan metode pembayaran $method?");
        $form->setButton1("Ya");
        $form->setButton2("Tidak");

        return $form;
    }
}
