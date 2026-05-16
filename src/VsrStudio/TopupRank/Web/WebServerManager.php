<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\router\Router;
use Hebbinkpro\WebServer\WebServer;

use VsrStudio\TopupRank\Main;

final class WebServerManager {

    private Main $plugin;

    private ?WebServer $webServer = null;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function start(): void {

        if (!class_exists(WebServer::class)) {

            $this->plugin->getLogger()->error(
                "pmmp-webserver not installed."
            );

            return;
        }

        $router = new Router();

        /*
         * PASS STRING ONLY
         */
        $ordersFile =
            $this->plugin->getDataFolder() .
            "orders.json";

        /*
         * HOME PAGE
         */
        $router->get("/", static function (
            HttpRequest $request,
            HttpResponse $response
        ): void {

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>TopupRank</title>

<style>

body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    margin:0;
}

.card{
    background:#1e293b;
    padding:30px;
    border-radius:15px;
    width:400px;
}

h1{
    text-align:center;
}

input{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    margin-top:15px;
    background:#334155;
    color:white;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    background:#3b82f6;
    color:white;
    cursor:pointer;
}

</style>

</head>

<body>

<div class="card">

<h1>Cek Pesanan</h1>

<form action="/check" method="GET">

<input
    type="text"
    name="order"
    placeholder="Masukkan ID Order"
    required
>

<button type="submit">
    Cek Sekarang
</button>

</form>

</div>

</body>

</html>
HTML;

            $response->send($html);
        });

        /*
         * CHECK PAGE
         */
        $router->get("/check", static function (
            HttpRequest $request,
            HttpResponse $response,
            string $ordersFile
        ): void {

            $orderId = trim(
                $request
                    ->getURL()
                    ->getQueryParam("order") ?? ""
            );

            if ($orderId === "") {

                $response->send("
                    <h1>ID order kosong</h1>
                ");

                return;
            }

            /*
             * LOAD ORDERS
             */
            if (!file_exists($ordersFile)) {

                $response->send("
                    <h1>orders.json tidak ditemukan</h1>
                ");

                return;
            }

            $orders = json_decode(
                file_get_contents($ordersFile),
                true
            ) ?? [];

            $order = null;

            foreach ($orders as $data) {

                if (
                    strtolower($data["id"]) ===
                    strtolower($orderId)
                ) {

                    $order = $data;

                    break;
                }
            }

            /*
             * ORDER NOT FOUND
             */
            if ($order === null) {

                $safeOrderId =
                    htmlspecialchars($orderId);

                $response->send("
                <!DOCTYPE html>

                <html>

                <body style='
                    background:#111827;
                    color:white;
                    font-family:Arial;
                    padding:30px;
                '>

                    <h1>Order Tidak Ditemukan</h1>

                    <p>ID: {$safeOrderId}</p>

                    <a href='/' style='color:#60a5fa;'>
                        Kembali
                    </a>

                </body>

                </html>
                ");

                return;
            }

            $statusColor = match (
                strtolower($order["status"])
            ) {

                "success" => "#22c55e",

                "rejected" => "#ef4444",

                default => "#facc15"
            };

            $id =
                htmlspecialchars($order["id"]);

            $gamertag =
                htmlspecialchars($order["gamertag"]);

            $rank =
                htmlspecialchars($order["rank"]);

            $discord =
                htmlspecialchars($order["discord"]);

            $method =
                htmlspecialchars($order["method"]);

            $status =
                strtoupper(
                    htmlspecialchars(
                        $order["status"]
                    )
                );

            $time =
                htmlspecialchars($order["time"]);

            $response->send("
            <!DOCTYPE html>

            <html>

            <head>

                <title>Status Pesanan</title>

                <style>

                    body{
                        background:#0f172a;
                        color:white;
                        font-family:Arial;
                        padding:40px;
                    }

                    .card{
                        max-width:500px;
                        margin:auto;
                        background:#1e293b;
                        padding:30px;
                        border-radius:15px;
                    }

                    .item{
                        margin-bottom:12px;
                    }

                </style>

            </head>

            <body>

                <div class='card'>

                    <h1>Status Pesanan</h1>

                    <div class='item'>
                        <b>ID:</b> {$id}
                    </div>

                    <div class='item'>
                        <b>Gamertag:</b> {$gamertag}
                    </div>

                    <div class='item'>
                        <b>Rank:</b> {$rank}
                    </div>

                    <div class='item'>
                        <b>Discord:</b> {$discord}
                    </div>

                    <div class='item'>
                        <b>Metode:</b> {$method}
                    </div>

                    <div class='item'>
                        <b>Status:</b>

                        <span style='
                            color:{$statusColor};
                            font-weight:bold;
                        '>
                            {$status}
                        </span>
                    </div>

                    <div class='item'>
                        <b>Waktu:</b> {$time}
                    </div>

                    <br>

                    <a href='/' style='color:#60a5fa;'>
                        Kembali
                    </a>

                </div>

            </body>

            </html>
            ");

        }, $ordersFile);

        $host = (string) $this->plugin
            ->getConfig()
            ->get("web-host", "0.0.0.0");

        $port = (int) $this->plugin
            ->getConfig()
            ->get("web-port", 8080);

        $serverInfo = new HttpServerInfo(
            $host,
            $port,
            $router
        );

        $this->webServer = new WebServer(
            $this->plugin,
            $serverInfo
        );

        try {

            $this->webServer->start();

            $this->plugin
                ->getLogger()
                ->info(
                    "WebServer running at {$host}:{$port}"
                );

        } catch (\Throwable $e) {

            $this->plugin
                ->getLogger()
                ->error(
                    $e->getMessage()
                );
        }
    }

    public function stop(): void {

        if (
            $this->webServer !== null &&
            $this->webServer->isStarted()
        ) {

            $this->webServer->close();
        }
    }
}
