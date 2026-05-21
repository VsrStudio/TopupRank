<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

use Hebbinkpro\WebServer\http\message\HttpRequest;
use Hebbinkpro\WebServer\http\message\HttpResponse;

use VsrStudio\TopupRank\Main;

final class AdminWebPanel {

    private Main $plugin;

    private string $ordersFile;

    public function __construct(
        Main $plugin,
        string $ordersFile
    ){

        $this->plugin = $plugin;
        $this->ordersFile = $ordersFile;
    }

    public function handleLogin(
        HttpRequest $request,
        HttpResponse $response
    ) : void{

        $config = $this->plugin
            ->getConfig()
            ->get("admin-panel", []);

        $username =
            $config["username"] ?? "admin";

        $password =
            $config["password"] ?? "admin123";

        if($request->getMethod() === "POST"){

            parse_str(
                $request->getBody(),
                $body
            );

            $user = $body["username"] ?? "";
            $pass = $body["password"] ?? "";

            if(
                $user === $username &&
                $pass === $password
            ){

                $response->send($this->dashboard());

                return;
            }

            $error = "
            <div class='error'>
                Invalid username or password
            </div>
            ";

        }else{
            $error = "";
        }

        $response->send("
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
    min-height:100vh;
}

.card{
    width:400px;
    background:#1e293b;
    padding:35px;
    border-radius:25px;
}

input{
    width:100%;
    padding:15px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:#334155;
    color:white;
}

button{
    width:100%;
    padding:15px;
    margin-top:20px;
    border:none;
    border-radius:12px;
    background:#3b82f6;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

.error{
    background:#ef4444;
    padding:12px;
    border-radius:10px;
    margin-top:15px;
}

</style>

</head>

<body>

<div class='card'>

<h1>Admin Panel</h1>

{$error}

<form method='POST'>

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
");
    }

    public function dashboard() : string{

        $orders = WebHelper::loadOrders(
            $this->ordersFile
        );

        $rows = "";

        foreach(array_reverse($orders) as $order){

            $status =
                strtolower($order["status"]);

            $color = match($status){

                "success" => "#22c55e",

                "rejected" => "#ef4444",

                default => "#facc15"
            };

            $id =
                htmlspecialchars($order["id"]);

            $rows .= "
<tr>

<td>

<div class='copy-box'>

<input
    value='{$id}'
    id='copy-{$id}'
    readonly
>

<button onclick=\"copyOrder('copy-{$id}')\">
COPY
</button>

</div>

</td>

<td>{$order["gamertag"]}</td>
<td>{$order["rank"]}</td>
<td>{$order["discord"]}</td>
<td>{$order["method"]}</td>

<td>
<span style='color:{$color};font-weight:bold'>
" . strtoupper($status) . "
</span>
</td>

<td>{$order["time"]}</td>

<td>

<a
href='/admin/approve?id={$id}'
class='approve'
>
Approve
</a>

<a
href='/admin/reject?id={$id}'
class='reject'
>
Reject
</a>

</td>

</tr>
";
        }

        return "
<!DOCTYPE html>
<html>
<head>

<meta charset='UTF-8'>

<meta
name='viewport'
content='width=device-width, initial-scale=1.0'
>

<title>Admin Dashboard</title>

<style>

body{
    background:#0f172a;
    color:white;
    font-family:Arial;
    margin:0;
}

.header{
    padding:25px;
    background:#1e293b;
}

.container{
    padding:20px;
}

.search{
    width:100%;
    padding:15px;
    border:none;
    border-radius:12px;
    background:#1e293b;
    color:white;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,
td{
    padding:15px;
    border-bottom:1px solid #334155;
    text-align:left;
}

tr{
    background:#1e293b;
}

.approve{
    background:#22c55e;
    padding:10px 14px;
    border-radius:10px;
    color:white;
    text-decoration:none;
}

.reject{
    background:#ef4444;
    padding:10px 14px;
    border-radius:10px;
    color:white;
    text-decoration:none;
    margin-left:10px;
}

.copy-box{
    display:flex;
    gap:5px;
}

.copy-box input{
    background:#334155;
    border:none;
    color:white;
    padding:10px;
    border-radius:8px;
    width:100px;
}

.copy-box button{
    border:none;
    padding:10px;
    border-radius:8px;
    background:#3b82f6;
    color:white;
    cursor:pointer;
}

.live{
    color:#22c55e;
    font-weight:bold;
}

</style>

</head>

<body>

<div class='header'>

<h1>
Admin Panel
</h1>

<div class='live'>
LIVE STATUS
</div>

</div>

<div class='container'>

<input
type='text'
id='searchInput'
class='search'
placeholder='Search realtime...'
onkeyup='searchTable()'
>

<table id='orderTable'>

<thead>

<tr>

<th>Order ID</th>
<th>Gamertag</th>
<th>Rank</th>
<th>Discord</th>
<th>Payment</th>
<th>Status</th>
<th>Time</th>
<th>Action</th>

</tr>

</thead>

<tbody>

{$rows}

</tbody>

</table>

</div>

<script>

function copyOrder(id){

    let copyText =
        document.getElementById(id);

    copyText.select();

    document.execCommand('copy');
}

function searchTable(){

    let input =
        document.getElementById('searchInput');

    let filter =
        input.value.toLowerCase();

    let table =
        document.getElementById('orderTable');

    let tr =
        table.getElementsByTagName('tr');

    for(let i = 1; i < tr.length; i++){

        let text =
            tr[i].innerText.toLowerCase();

        tr[i].style.display =
            text.includes(filter)
            ? ''
            : 'none';
    }
}

setInterval(() => {
    location.reload();
}, 10000);

</script>

</body>
</html>
";
    }

    public function approve(
        HttpRequest $request,
        HttpResponse $response
    ) : void{

        $id = $request
            ->getURL()
            ->getQueryParam("id") ?? "";

        if(
            WebHelper::updateOrderStatus(
                $this->ordersFile,
                $id,
                "success"
            )
        ){

            $order = $this->plugin
                ->getOrderManager()
                ->getOrderById($id);

            if($order !== null){

                $this->plugin
                    ->getRankManager()
                    ->grantRank(
                        $order["gamertag"],
                        $order["rank"]
                    );
            }
        }

        $response->send($this->dashboard());
    }

    public function reject(
        HttpRequest $request,
        HttpResponse $response
    ) : void{

        $id = $request
            ->getURL()
            ->getQueryParam("id") ?? "";

        WebHelper::updateOrderStatus(
            $this->ordersFile,
            $id,
            "rejected"
        );

        $response->send(
            $this->dashboard()
        );
    }
}
