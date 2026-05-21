<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\router\Router;
use Hebbinkpro\WebServer\WebServer;

use VsrStudio\TopupRank\Main;
use VsrStudio\TopupRank\Web\AdminWebPanel;

final class WebServerManager {

    private Main $plugin;
    private array $cooldown = [];

    private ?WebServer $webServer = null;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function start() : void{

        if(!class_exists(WebServer::class)){

            $this->plugin
                ->getLogger()
                ->error(
                    "pmmp-webserver not found."
                );

            return;
        }

        $router = new Router();

        $config =
            $this->plugin
                ->getPluginConfig();

        $ranks =
            $config["ranks"] ?? [];

        $paymentMethods =
            $config["payment_methods"] ?? [];

        $ordersFile =
            $this->plugin
                ->getDataFolder() .
                "orders.json";

        $adminPanel = new AdminWebPanel(
            $this->plugin,  
            $ordersFile
        );

        /*
 * ADMIN LOGIN
 */
$router->match(
    ["GET", "POST"],
    "/admin",
    function(
        HttpRequest $request,
        HttpResponse $response
    ) use (
        $adminPanel
    ) : void {

        $adminPanel->handleLogin(
            $request,
            $response
        );
    }
);

/*
 * APPROVE
 */
$router->get(
    "/admin/approve",
    function(
        HttpRequest $request,
        HttpResponse $response
    ) use (
        $adminPanel
    ) : void {

        $adminPanel->approve(
            $request,
            $response
        );
    }
);

/*
 * REJECT
 */
$router->get(
    "/admin/reject",
    function(
        HttpRequest $request,
        HttpResponse $response
    ) use (
        $adminPanel
    ) : void {

        $adminPanel->reject(
            $request,
            $response
        );
    }
);

        /*
         * HOME
         * FORM UTAMA
         */
        $router->get("/", static function(
            HttpRequest $request,
            HttpResponse $response
        ) use (
            $ranks
        ) : void {

            $rankCards = "";

            foreach($ranks as $rank => $data){

                $buttonName =
                    $data["name-button"] ?? $rank;

                $price =
                    $data["price"] ?? "0";

                $image =
                    $data["image-web"] ?? "";

                $safeRank =
                    urlencode($rank);

                $safeButton =
                    htmlspecialchars($buttonName);

                $safePrice =
                    htmlspecialchars((string)$price);

                $imageHtml = "";

                if($image !== ""){

                    $safeImage =
                        htmlspecialchars($image);

                    $imageHtml = "
                    <img
                        src='{$safeImage}'
                        class='rank-image'
                    >";
                }

                $rankCards .= "
                <a
                    href='/rank?name={$safeRank}'
                    class='rank-card'
                >

                    {$imageHtml}

                    <div class='rank-info'>

                        <div class='rank-name'>
                            {$safeButton}
                        </div>

                        <div class='rank-price'>
                            Rp {$safePrice}
                        </div>

                    </div>

                </a>
                ";
            }

            $html = "
<!DOCTYPE html>

<html>

<head>

<meta charset='UTF-8'>

<meta
    name='viewport'
    content='width=device-width, initial-scale=1.0'
>

<title>TopupRank</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:
        linear-gradient(
            180deg,
            #0f172a,
            #020617
        );
    font-family:Arial;
    color:white;
    min-height:100vh;
}

.header{
    padding:40px 20px;
    text-align:center;
}

.header h1{
    font-size:42px;
    font-weight:bold;
}

.header p{
    margin-top:10px;
    color:#94a3b8;
}

.container{
    max-width:1100px;
    margin:auto;
    padding:20px;
}

.rank-grid{
    display:grid;
    grid-template-columns:
        repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
}

.rank-card{
    background:#1e293b;
    border-radius:20px;
    overflow:hidden;
    text-decoration:none;
    color:white;
    transition:0.2s;
    border:1px solid #334155;
}

.rank-card:hover{
    transform:translateY(-5px);
    border-color:#3b82f6;
}

.rank-image{
    width:100%;
    height:180px;
    object-fit:cover;
    background:#0f172a;
}

.rank-info{
    padding:20px;
}

.rank-name{
    font-size:22px;
    font-weight:bold;
}

.rank-price{
    margin-top:10px;
    color:#60a5fa;
    font-size:18px;
}

.bottom-card{
    margin-top:40px;
    background:#1e293b;
    padding:25px;
    border-radius:20px;
    border:1px solid #334155;
}

.check-form{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-top:15px;
}

.check-form input{
    flex:1;
    min-width:200px;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#334155;
    color:white;
}

.check-form button{
    padding:14px 24px;
    border:none;
    border-radius:12px;
    background:#3b82f6;
    color:white;
    cursor:pointer;
    font-weight:bold;
}

</style>

</head>

<body>

<div class='header'>

    <h1>Topup Rank</h1>

    <p>
        Choose the best rank for your server
    </p>

</div>

<div class='container'>

    <div class='rank-grid'>

        {$rankCards}

    </div>

    <div class='bottom-card'>

        <h2>Check Order</h2>

        <form
            action='/check'
            method='GET'
            class='check-form'
        >

            <input
                type='text'
                name='order'
                placeholder='Insert ID Order'
                required
            >

            <button type='submit'>
                Check Status
            </button>

        </form>

    </div>

</div>

</body>

</html>
";

            $response->send($html);
        });

        /*
         * DETAIL RANK
         * TITLE + CONTENT
         * BUY + RETURN
         */
        $router->get("/rank", static function(
            HttpRequest $request,
            HttpResponse $response
        ) use (
            $ranks
        ) : void {

            $rank =
                $request
                    ->getURL()
                    ->getQueryParam("name") ?? "";

            if(
                !isset($ranks[$rank])
            ){

                $response->send("
                    <h1>Rank not found</h1>
                ");

                return;
            }

            $data =
                $ranks[$rank];

            $title =
                htmlspecialchars(
                    $data["title"] ?? $rank
                );

            $content =
                nl2br(
                    htmlspecialchars(
                        $data["content"] ?? ""
                    )
                );

            $price =
                htmlspecialchars(
                    (string)(
                        $data["price"] ?? "0"
                    )
                );

            $image =
                htmlspecialchars(
                    (string)($data["image-web"] ?? "")
                );

            $safeRank =
                urlencode($rank);

            $html = "
<!DOCTYPE html>

<html>

<head>

<meta charset='UTF-8'>

<meta
    name='viewport'
    content='width=device-width, initial-scale=1.0'
>

<title>{$rank}</title>

<style>

body{
    background:
        linear-gradient(
            180deg,
            #0f172a,
            #020617
        );
    font-family:Arial;
    color:white;
    margin:0;
    padding:30px;
}

.card{
    max-width:750px;
    margin:auto;
    background:#1e293b;
    border-radius:25px;
    overflow:hidden;
    border:1px solid #334155;
}

.image{
    width:100%;
    height:300px;
    object-fit:cover;
    background:#0f172a;
}

.content{
    padding:30px;
}

.title{
    font-size:34px;
    font-weight:bold;
}

.price{
    margin-top:10px;
    color:#60a5fa;
    font-size:22px;
}

.desc{
    margin-top:25px;
    color:#cbd5e1;
    line-height:1.8;
}

.buttons{
    display:flex;
    gap:15px;
    margin-top:35px;
    flex-wrap:wrap;
}

.button{
    flex:1;
    min-width:200px;
    text-align:center;
    padding:15px;
    border-radius:15px;
    text-decoration:none;
    font-weight:bold;
}

.buy{
    background:#22c55e;
    color:white;
}

.return{
    background:#ef4444;
    color:white;
}

</style>

</head>

<body>

<div class='card'>

    <img
        src='{$image}'
        class='image'
    >

    <div class='content'>

        <div class='title'>
            {$title}
        </div>

        <div class='price'>
            Rp {$price}
        </div>

        <div class='desc'>
            {$content}
        </div>

        <div class='buttons'>

            <a
                href='/buy?rank={$safeRank}'
                class='button buy'
            >
                Buy
            </a>

            <a
                href='/'
                class='button return'
            >
                Return
            </a>

        </div>

    </div>

</div>

</body>

</html>
";

            $response->send($html);
        });

        /*
         * SHOW CUSTOM FORM WEBSITE
         */
        $router->get("/buy", static function(
            HttpRequest $request,
            HttpResponse $response
        ) use (
            $ranks,
            $paymentMethods
        ) : void {

            $rank =
                $request
                    ->getURL()
                    ->getQueryParam("rank") ?? "";

            if(
                !isset($ranks[$rank])
            ){

                $response->send(
                    "<h1>Rank not found</h1>"
                );

                return;
            }

            $paymentOptions = "";

            foreach($paymentMethods as $method){

                $safeMethod =
                    htmlspecialchars($method);

                $paymentOptions .= "
                <option value='{$safeMethod}'>
                    {$safeMethod}
                </option>
                ";
            }

            $safeRank =
                htmlspecialchars($rank);

            $html = "
<!DOCTYPE html>

<html>

<head>

<meta charset='UTF-8'>

<meta
    name='viewport'
    content='width=device-width, initial-scale=1.0'
>

<title>Buy {$safeRank}</title>

<style>

body{
    background:
        linear-gradient(
            180deg,
            #0f172a,
            #020617
        );
    font-family:Arial;
    color:white;
    margin:0;
    padding:30px;
}

.card{
    max-width:650px;
    margin:auto;
    background:#1e293b;
    padding:35px;
    border-radius:25px;
    border:1px solid #334155;
}

.title{
    font-size:32px;
    font-weight:bold;
}

.rank{
    margin-top:10px;
    color:#60a5fa;
}

.input-group{
    margin-top:20px;
}

.input-group label{
    display:block;
    margin-bottom:10px;
}

.input-group input,
.input-group select{
    width:100%;
    padding:15px;
    border:none;
    border-radius:15px;
    background:#334155;
    color:white;
}

.discord-box{
    display:flex;
    align-items:center;
    background:#334155;
    border-radius:15px;
    overflow:hidden;
}

.discord-prefix{
    padding:15px;
    background:#475569;
}

.discord-input{
    flex:1;
    border:none;
    background:transparent;
    color:white;
    padding:15px;
    outline:none;
}

.submit{
    width:100%;
    padding:16px;
    border:none;
    border-radius:15px;
    background:#22c55e;
    color:white;
    margin-top:30px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
}

</style>

</head>

<body>

<div class='card'>

    <div class='title'>
        Topup Rank
    </div>

    <div class='rank'>
        Rank: {$safeRank}
    </div>

    <form action='/submit' method='POST'>

        <input
            type='hidden'
            name='rank'
            value='{$safeRank}'
        >

        <div class='input-group'>

            <label>
                Gamertag
            </label>

            <input
                type='text'
                name='gamertag'
                placeholder='Steve'
                required
            >

        </div>

        <div class='input-group'>

            <label>
                Discord Username
            </label>

            <div class='discord-box'>

                <div class='discord-prefix'>
                    @
                </div>

                <input
                    type='text'
                    name='discord'
                    class='discord-input'
                    placeholder='username'
                    required
                >

            </div>

        </div>

        <div class='input-group'>

            <label>
                Payment Method
            </label>

            <select name='method'>

                {$paymentOptions}

            </select>

        </div>

        <button
            type='submit'
            class='submit'
        >
            Create Order
        </button>

    </form>

</div>

</body>

</html>
";

            $response->send($html);
        });

        /*
         * SUBMIT ORDER
         */
        $router->post("/submit", static function(
            HttpRequest $request,
            HttpResponse $response
        ) use (
            $ordersFile
        ) : void {

            $ip =
                method_exists($request, "getIp")
                    ? $request->getIp()
                    : "unknown";

            $cooldown =
                $this->hasCooldown($ip);

            if($cooldown !== false){

                $response->send("
                <h1>Too Fast</h1>

                <p>
                    Wait {$cooldown} seconds.
                </p>
                ");

                return;
            }

            parse_str(
                $request->getBody(),
                $body
            );

            $gamertag =
                trim($body["gamertag"] ?? "");

            $discord =
                trim($body["discord"] ?? "");

            $rank =
                trim($body["rank"] ?? "");

            $method =
                trim($body["method"] ?? "");

            if(
                $gamertag === "" ||
                $discord === "" ||
                $rank === "" ||
                $method === ""
            ){

                $response->send(
                    "<h1>Incomplete data</h1>"
                );

                return;
            }

            $discord =
                "@" .
                ltrim($discord, "@");

            $orders = [];

            if(file_exists($ordersFile)){

                $orders = json_decode(
                    file_get_contents($ordersFile),
                    true
                ) ?? [];
            }

            $orders =
                $orderStorage
                ->getOrders();
            $count =
                count($orders) + 1;

            $orderId =
                "TR-" .
                str_pad(
                    (string)$count,
                    3,
                    "0",
                    STR_PAD_LEFT
                );

            $orders[] = [

                "id" => $orderId,
                "gamertag" => $gamertag,
                "rank" => $rank,
                "discord" => $discord,
                "method" => $method,
                "status" => "pending",
                "time" => date("Y-m-d H:i:s")
            ];

            file_put_contents(
                $ordersFile,
                json_encode(
                    $orders,
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE
                )
            );

            $safeOrderId =
                htmlspecialchars($orderId);

            $safeDiscord =
                htmlspecialchars($discord);

            $response->send("
<!DOCTYPE html>

<html>

<head>

<style>

body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.card{
    background:#1e293b;
    padding:35px;
    border-radius:25px;
    width:500px;
    text-align:center;
}

.status{
    color:#facc15;
    font-weight:bold;
}

.button{
    display:block;
    margin-top:25px;
    background:#3b82f6;
    color:white;
    text-decoration:none;
    padding:15px;
    border-radius:15px;
}

</style>

</head>

<body>

<div class='card'>

    <h1>Order Successfully Created</h1>

    <p>
        ID Order:
        <b>{$safeOrderId}</b>
    </p>

    <p>
        Discord:
        <b>{$safeDiscord}</b>
    </p>

    <p class='status'>
        PENDING
    </p>

    <a href='/' class='button'>
        Return
    </a>

</div>

</body>

</html>
");
        });

        /*
         * CHECK STATUS
         */
        $router->get("/check", static function(
            HttpRequest $request,
            HttpResponse $response
        ) use (
            $ordersFile
        ) : void {

            $orderId = trim(
                $request
                    ->getURL()
                    ->getQueryParam("order") ?? ""
            );

            $orders = [];

            if(file_exists($ordersFile)){

                $orders = json_decode(
                    file_get_contents($ordersFile),
                    true
                ) ?? [];
            }

            $order = null;

            foreach($orders as $data){

                if(
                    strtolower($data["id"]) ===
                    strtolower($orderId)
                ){

                    $order = $data;

                    break;
                }
            }

            if($order === null){

                $response->send("
                    <h1>Order tidak ditemukan</h1>
                ");

                return;
            }

            $statusColor = match(
                strtolower($order["status"])
            ){

                "success" => "#22c55e",

                "rejected" => "#ef4444",

                default => "#facc15"
            };

            $response->send("
<!DOCTYPE html>

<html>

<head>

<style>

body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    padding:40px;
}

.card{
    max-width:600px;
    margin:auto;
    background:#1e293b;
    padding:35px;
    border-radius:25px;
}

.item{
    margin-top:15px;
}

</style>

</head>

<body>

<div class='card'>

    <h1>Status Pesanan</h1>

    <div class='item'>
        <b>ID:</b>
        {$order["id"]}
    </div>

    <div class='item'>
        <b>Gamertag:</b>
        {$order["gamertag"]}
    </div>

    <div class='item'>
        <b>Rank:</b>
        {$order["rank"]}
    </div>

    <div class='item'>
        <b>Discord:</b>
        {$order["discord"]}
    </div>

    <div class='item'>
        <b>Metode:</b>
        {$order["method"]}
    </div>

    <div class='item'>
        <b>Status:</b>

        <span style='
            color:{$statusColor};
            font-weight:bold;
        '>

            " . strtoupper($order["status"]) . "

        </span>
    </div>

    <div class='item'>
        <b>Time:</b>
        {$order["time"]}
    </div>

    <a
        href='/'
        style='
            display:block;
            margin-top:30px;
            color:#60a5fa;
        '
    >
        Return
    </a>

</div>

</body>

</html>
");
        });

        $host = (string)
            $this->plugin
                ->getConfig()
                ->get(
                    "web-host",
                    "0.0.0.0"
                );

        $port = (int)
            $this->plugin
                ->getConfig()
                ->get(
                    "web-port",
                    8080
                );

        $serverInfo = new HttpServerInfo(
            $host,
            $port,
            $router
        );

        $this->webServer = new WebServer(
            $this->plugin,
            $serverInfo
        );

        try{

            $this->webServer->start();

            $this->plugin
                ->getLogger()
                ->info(
                    "WebServer running at {$host}:{$port}"
                );

        }catch(\Throwable $e){

            $this->plugin
                ->getLogger()
                ->error(
                    $e->getMessage()
                );
        }
    }

    private function hasCooldown(string $ip) : int|false{

    $seconds =
        (int)(
            $this->plugin
                ->getConfig()
                ->getNested(
                    "cooldown.website",
                    60
                )
        );

    $time =
        time();

    if(isset($this->cooldown[$ip])){

        $remaining =
            $this->cooldown[$ip] - $time;

        if($remaining > 0){
            return $remaining;
        }
    }

    $this->cooldown[$ip] =
        $time + $seconds;

    return false;
    }

    public function stop() : void{

        if(
            $this->webServer !== null &&
            $this->webServer->isStarted()
        ){

            $this->webServer->close();
        }
    }
}
