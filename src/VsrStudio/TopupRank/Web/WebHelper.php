<?php

declare(strict_types=1);

namespace VsrStudio\TopupRank\Web;

final class WebHelper {

    public static function loadOrders(string $file) : array{

        if(!file_exists($file)){
            return [];
        }

        return json_decode(
            file_get_contents($file),
            true
        ) ?? [];
    }

    public static function saveOrders(
        string $file,
        array $orders
    ) : void{

        file_put_contents(
            $file,
            json_encode(
                $orders,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE
            )
        );
    }

    public static function updateOrderStatus(
        string $file,
        string $orderId,
        string $status
    ) : bool{

        $orders = self::loadOrders($file);

        foreach($orders as $key => $order){

            if(
                strtolower($order["id"]) ===
                strtolower($orderId)
            ){

                $orders[$key]["status"] = $status;

                self::saveOrders(
                    $file,
                    $orders
                );

                return true;
            }
        }

        return false;
    }
}
