<?php

namespace VsrStudio\TopupRank\Data;

use pocketmine\plugin\Plugin;

class OrderManager {

    private Plugin $plugin;

    private string $filePath;

    private array $orders = [];

    public function __construct(Plugin $plugin) {

        $this->plugin = $plugin;

        $this->filePath =
            $plugin->getDataFolder() . "orders.json";

        if (!file_exists($this->filePath)) {

            $this->save();

        } else {

            $this->orders = json_decode(
                file_get_contents($this->filePath),
                true
            ) ?? [];
        }
    }

    /**
     * ADD ORDER
     */
    public function addOrder(
        string $gamertag,
        string $rank,
        string $discord,
        string $method
    ): string {

        $orderId = $this->generateOrderId();

        $this->orders[] = [

            "id" => $orderId,

            "gamertag" => $gamertag,

            "rank" => $rank,

            "discord" => $discord,

            "method" => $method,

            "status" => "pending",

            "time" => date("Y-m-d H:i:s")
        ];

        $this->save();

        return $orderId;
    }

    /**
     * GET ORDERS
     */
    public function getOrders(): array {
        return $this->orders;
    }

    /**
     * APPROVE
     */
    public function approveOrder(array $order): void {

        foreach ($this->orders as $key => $data) {

            if ($data["id"] === $order["id"]) {

                $this->orders[$key]["status"] = "success";
            }
        }

        $this->save();

        $this->logOrder($order, "success");
    }

    /**
     * REJECT
     */
    public function rejectOrder(array $order): void {

        foreach ($this->orders as $key => $data) {

            if ($data["id"] === $order["id"]) {

                $this->orders[$key]["status"] = "rejected";
            }
        }

        $this->save();

        $this->logOrder($order, "rejected");
    }

    /**
     * GENERATE ID
     */
    private function generateOrderId(): string {

        $count = count($this->orders) + 1;

        return "TR-" . str_pad(
            (string) $count,
            3,
            "0",
            STR_PAD_LEFT
        );
    }

    /**
     * LOG
     */
    private function logOrder(
        array $order,
        string $status
    ): void {

        $logFile =
            $this->plugin->getDataFolder() .
            "order_log.txt";

        $logEntry =
            "[" . date("Y-m-d H:i:s") . "] " .
            "ID: " . $order["id"] . ", " .
            "Gamertag: " . $order["gamertag"] . ", " .
            "Rank: " . $order["rank"] . ", " .
            "Metode: " . $order["method"] . ", " .
            "Status: " . $status . "\n";

        file_put_contents(
            $logFile,
            $logEntry,
            FILE_APPEND
        );
    }

    /**
     * SAVE
     */
    private function save(): void {

        file_put_contents(
            $this->filePath,
            json_encode(
                $this->orders,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE
            )
        );
    }
}
