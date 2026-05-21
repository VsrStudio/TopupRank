<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

final class OrderStorage {

    private string $file;

    public function __construct(string $file){
        $this->file = $file;
    }

    public function getOrders() : array{

        if(!file_exists($this->file)){
            return [];
        }

        return json_decode(
            file_get_contents($this->file),
            true
        ) ?? [];
    }

    public function saveOrders(array $orders) : void{

        file_put_contents(
            $this->file,
            json_encode(
                $orders,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE
            )
        );
    }

    public function addOrder(array $order) : void{

        $orders = $this->getOrders();

        $orders[] = $order;

        $this->saveOrders($orders);
    }

    public function updateStatus(
        string $id,
        string $status
    ) : bool {

        $orders = $this->getOrders();

        foreach($orders as $key => $order){

            if(
                strtolower($order["id"]) ===
                strtolower($id)
            ){

                $orders[$key]["status"] = $status;

                $this->saveOrders($orders);

                return true;
            }
        }

        return false;
    }
}
