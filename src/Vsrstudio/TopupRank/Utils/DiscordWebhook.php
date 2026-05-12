<?php

namespace VsrStudio\TopupRank\Utils;

use pocketmine\plugin\Plugin;

use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Embed;

class DiscordWebhook {

    private Plugin $plugin;
    private string $webhookUrl;

    public function __construct(Plugin $plugin, string $webhookUrl) {
        $this->plugin = $plugin;
        $this->webhookUrl = $webhookUrl;
    }

    public function sendTopup(
        string $player,
        string $rank,
        string $phone,
        string $method
    ): void {

        if ($this->webhookUrl === "") {
            return;
        }

        $webhook = new Webhook($this->webhookUrl);

        $embed = new Embed();

        $embed->setTitle("Topup Rank");
        $embed->setDescription("Player make rank purchases.");
        $embed->setColor(0x00FF00);

        $embed->addField("Player", $player, true);
        $embed->addField("Rank", $rank, true);
        $embed->addField("Phone", $phone, false);
        $embed->addField("Payment", $method, true);
        $embed->addField("Time", date("Y-m-d H:i:s"), false);

        $message = new Message();
        $message->setUsername("TopupRank");
        $message->addEmbed($embed);

        $webhook->send($message);
    }
}
