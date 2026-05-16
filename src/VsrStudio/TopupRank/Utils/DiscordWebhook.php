<?php

namespace VsrStudio\TopupRank\Utils;

use pocketmine\plugin\Plugin;

use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Embed;

class DiscordWebhook {

    private Plugin $plugin;

    private string $webhookUrl;

    public function __construct(
        Plugin $plugin,
        string $webhookUrl
    ) {

        $this->plugin = $plugin;

        $this->webhookUrl = $webhookUrl;
    }

    /**
     * SEND TOPUP WEBHOOK
     */
    public function sendTopup(
        string $orderId,
        string $player,
        string $rank,
        string $discord,
        string $method,
        string $status = "pending"
    ): void {

        /*
         * WEBHOOK EMPTY
         */
        if ($this->webhookUrl === "") {

            $this->plugin->getLogger()->warning(
                "Discord webhook empty URL."
            );

            return;
        }

        try {

            $webhook = new Webhook(
                $this->webhookUrl
            );

            $embed = new Embed();

            /*
             * STATUS COLOR
             */
            $color = match (strtolower($status)) {

                "success" => 0x00FF00,

                "rejected" => 0xFF0000,

                default => 0xFFFF00
            };

            /*
             * STATUS TEXT
             */
            $statusText = strtoupper($status);

            $embed->setTitle(
                "Topup Rank"
            );

            $embed->setDescription(
                "New rank topup order."
            );

            $embed->setColor($color);

            /*
             * FIELDS
             */
            $embed->addField(
                "Order ID",
                $orderId,
                true
            );

            $embed->addField(
                "Player",
                $player,
                true
            );

            $embed->addField(
                "Rank",
                $rank,
                true
            );

            $embed->addField(
                "Discord",
                $discord,
                false
            );

            $embed->addField(
                "Payment",
                $method,
                true
            );

            $embed->addField(
                "Status",
                $statusText,
                true
            );

            $embed->addField(
                "Time",
                date("Y-m-d H:i:s"),
                false
            );

            /*
             * FOOTER
             */
            $embed->setFooter(
                "TopupRank"
            );

            /*
             * MESSAGE
             */
            $message = new Message();

            $message->setUsername(
                "TopupRank"
            );

            $message->setContent(
                "New topup order received."
            );

            $message->addEmbed(
                $embed
            );

            /*
             * SEND
             */
            $webhook->send(
                $message
            );

        } catch (\Throwable $e) {

            $this->plugin->getLogger()->error(
                "Failed to send Discord webhook: " .
                $e->getMessage()
            );
        }
    }

    /**
     * UPDATE STATUS WEBHOOK
     */
    public function sendStatusUpdate(
        string $orderId,
        string $player,
        string $rank,
        string $status
    ): void {

        if ($this->webhookUrl === "") {
            return;
        }

        try {

            $webhook = new Webhook(
                $this->webhookUrl
            );

            $embed = new Embed();

            $color = match (strtolower($status)) {

                "success" => 0x00FF00,

                "rejected" => 0xFF0000,

                default => 0xFFFF00
            };

            $embed->setTitle(
                "Order Status Updated"
            );

            $embed->setDescription(
                "Topup rank status has changed."
            );

            $embed->setColor($color);

            $embed->addField(
                "Order ID",
                $orderId,
                true
            );

            $embed->addField(
                "Player",
                $player,
                true
            );

            $embed->addField(
                "Rank",
                $rank,
                true
            );

            $embed->addField(
                "Status",
                strtoupper($status),
                true
            );

            $embed->addField(
                "Updated",
                date("Y-m-d H:i:s"),
                false
            );

            $embed->setFooter(
                "TopupRank"
            );

            $message = new Message();

            $message->setUsername(
                "TopupRank"
            );

            $message->addEmbed(
                $embed
            );

            $webhook->send(
                $message
            );

        } catch (\Throwable $e) {

            $this->plugin->getLogger()->error(
                "Webhook status update failed: " .
                $e->getMessage()
            );
        }
    }
}
