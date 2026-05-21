<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;
use Hebbinkpro\WebServer\router\Router;

use VsrStudio\TopupRank\Main;

final class AdminPanel {

    private Main $plugin;
    private OrderStorage $storage;

    public function __construct(
        Main $plugin,
        OrderStorage $storage
    ){
        $this->plugin = $plugin;
        $this->storage = $storage;
    }

    public function register(
        Router $router
    ) : void {

        /*
         * LOGIN PAGE
         */
        $router->get("/admin", function(
            HttpRequest $request,
            HttpResponse $response
        ) : void {

            $response->send($this->getLoginPage());
        });

        /*
         * LOGIN PROCESS
         */
        $router->post("/admin/login", function(
            HttpRequest $request,
            HttpResponse $response
        ) : void {

            parse_str(
                $request->getBody(),
                $body
            );

            $username =
                trim($body["username"] ?? "");

            $password =
                trim($body["password"] ?? "");

            $config =
                $this->plugin
                    ->getConfig()
                    ->get("admin-panel", []);

            $adminUser =
                $config["username"] ?? "admin";

            $adminPass =
                $config["password"] ?? "admin123";

            if(
                $username !== $adminUser ||
                $password !== $adminPass
            ){

                $response->send("
                <h1>Login gagal</h1>
                ");

                return;
            }

            $response->send(
                $this->getDashboard()
            );
        });

        /*
         * LIVE API
         */
        $router->get("/admin/orders", function(
            HttpRequest $request,
            HttpResponse $response
        ) : void {

            header("Content-Type: application/json");

            $response->send(
                json_encode(
                    $this->storage->getOrders(),
                    JSON_PRETTY_PRINT
                )
            );
        });

        /*
         * APPROVE
         */
        $router->get("/admin/approve", function(
            HttpRequest $request,
            HttpResponse $response
        ) : void {

            $id =
                trim(
                    $request
                        ->getURL()
                        ->getQueryParam("id") ?? ""
                );

            $this->storage->updateStatus(
                $id,
                "success"
            );

            $response->send("
            <script>
            location.href='/admin';
            </script>
            ");
        });

        /*
         * REJECT
         */
        $router->get("/admin/reject", function(
            HttpRequest $request,
            HttpResponse $response
        ) : void {

            $id =
                trim(
                    $request
                        ->getURL()
                        ->getQueryParam("id") ?? ""
                );

            $this->storage->updateStatus(
                $id,
                "rejected"
            );

            $response->send("
            <script>
            location.href='/admin';
            </script>
            ");
        });
    }

    private function getLoginPage() : string{

        return "
<!DOCTYPE html>
<html>
<head>

<title>Admin Login</title>

<style>

body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    background:#1e293b;
    padding:40px;
    border-radius:20px;
    width:350px;
}

input{
    width:100%;
    padding:14px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:#334155;
    color:white;
}

button{
    width:100%;
    padding:14px;
    margin-top:20px;
    border:none;
    border-radius:12px;
    background:#3b82f6;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

</style>

</head>

<body>

<div class='card'>

<h1>Admin Login</h1>

<form action='/admin/login' method='POST'>

<input
    type='text'
    name='username'
    placeholder='Username'
    required
>

<input
    type='password'
    name='password'
    placeholder='Password'
    required
>

<button type='submit'>
Login
</button>

</form>

</div>

</body>
</html>
";
    }

    private function getDashboard() : string{

        return "
<!DOCTYPE html>
<html>
<head>

<title>Admin Panel</title>

<style>

body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    padding:30px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.search{
    margin-top:20px;
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:white;
}

.table{
    width:100%;
    margin-top:20px;
    border-collapse:collapse;
}

.table th,
.table td{
    padding:15px;
    border:1px solid #334155;
}

.table th{
    background:#1e293b;
}

.pending{
    color:#facc15;
}

.success{
    color:#22c55e;
}

.rejected{
    color:#ef4444;
}

.button{
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    font-size:14px;
}

.approve{
    background:#22c55e;
}

.reject{
    background:#ef4444;
}

.copy{
    background:#3b82f6;
    cursor:pointer;
    border:none;
}

</style>

</head>

<body>

<div class='header'>
<h1>Admin Panel</h1>
<h3 id='live'>
LIVE
</h3>
</div>

<input
    type='text'
    id='search'
    class='search'
    placeholder='Search realtime...'
>

<table class='table'>

<thead>

<tr>
<th>Order ID</th>
<th>Gamertag</th>
<th>Rank</th>
<th>Payment</th>
<th>Status</th>
<th>Action</th>
</tr>

</thead>

<tbody id='orders'></tbody>

</table>

<script>

async function loadOrders(){

    let response =
        await fetch('/admin/orders');

    let data =
        await response.json();

    let html = '';

    let keyword =
        document
            .getElementById('search')
            .value
            .toLowerCase();

    data.reverse();

    for(let order of data){

        let text =
            JSON.stringify(order)
            .toLowerCase();

        if(!text.includes(keyword)){
            continue;
        }

        html += `
        <tr>

        <td>

        \${order.id}

        <button
            class='button copy'
            onclick='copyId(\"\\${order.id}\")'
        >
        Copy
        </button>

        </td>

        <td>\${order.gamertag}</td>

        <td>\${order.rank}</td>

        <td>\${order.method}</td>

        <td class='\${order.status}'>
            \${order.status.toUpperCase()}
        </td>

        <td>

        <a
            href='/admin/approve?id=\${order.id}'
            class='button approve'
        >
        Approve
        </a>

        <a
            href='/admin/reject?id=\${order.id}'
            class='button reject'
        >
        Reject
        </a>

        </td>

        </tr>
        `;
    }

    document
        .getElementById('orders')
        .innerHTML = html;
}

function copyId(id){

    navigator.clipboard.writeText(id);

    alert('Copied: ' + id);
}

document
    .getElementById('search')
    .addEventListener(
        'keyup',
        loadOrders
    );

setInterval(loadOrders, 3000);

loadOrders();

</script>

</body>
</html>
";
    }
}
