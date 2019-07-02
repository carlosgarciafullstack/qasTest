<?php

class Product
{
    public static function stock(
        $productId,
        $quantityAvailable,
        $cache = false,
        $cacheDuration = 60,
        $securityStockConfig = null
    ) {
        $quantityStockBlocked = $this->getQuantityStockBlocked($productId, $cache, $cacheDuration);

        return $this->getQuantityStock($quantityAvailable, $quantityStockBlocked);
    }

    private function getQuantityStockBlocked($productId, $cache, $cacheDuration) {

        if ($cache) {

            $quantityStockBlockedByOrdersInProgress = OrderLine::getDb()->cache(
                function ($db) use ($productId) {
                    return $this->getQuantityStockBlockedByOrdersInProgress($productId);
            }, $cacheDuration);

            $quantityStockBlockedByPendingStatus = BlockedStock::getDb()->cache(
                function ($db) use ($productId) {
                    return $this->getQuantityStockBlockedByPendingStatus($productId,'blocked_stock_date');
            }, $cacheDuration);

        } else {

            $quantityStockBlockedByOrdersInProgress = $this->getQuantityStockBlockedByOrdersInProgress($productId);

            $quantityStockBlockedByPendingStatus = $this->getQuantityStockBlockedByPendingStatus($productId, 'blocked_stock_to_date');
        }

        $quantityStockBlocked = 0;

        if (isset($quantityStockBlockedByOrdersInProgress)){
            $quantityStockBlocked = $quantityStockBlocked + $quantityStockBlockedByOrdersInProgress;
        }

        if (isset($quantityStockBlockedByPendingStatus)) {
            $quantityStockBlocked = $quantityStockBlocked +$quantityStockBlockedByPendingStatus;
        }

        return $quantityStockBlocked;
    }

    private function getQuantityStockBlockedByPendingStatus($productId, $blocked_stock_expresion) {
        return BlockedStock::find()
            ->select('SUM(quantity) as quantity')
            ->joinWith('shoppingCart')
            ->where(
                "blocked_stock.product_id = $productId 
                AND ".$blocked_stock_expresion." > '" .date('Y-m-d H:i:s'). 
                "' AND 
                    (shopping_cart_id IS NULL 
                    OR shopping_cart.status = '" .ShoppingCart::STATUS_PENDING. 
                "')"
            )
            ->scalar();
    }

    private function getQuantityStockBlockedByOrdersInProgress($productId) {
        return OrderLine::find()
            ->select('SUM(quantity) as quantity')
            ->joinWith('order')
            ->where(
                "(
                    order.status = '" . Order::STATUS_PENDING . 
                    "' OR order.status = '" . Order::STATUS_PROCESSING . 
                    "' OR order.status = '" . Order::STATUS_WAITING_ACCEPTANCE . 
                "') 
                AND order_line.product_id = $productId"
            )
            ->scalar();
    }

    private function getQuantityStock($quantityAvailable, $quantityStockBlocked) {

        if ($quantityAvailable >= 0) {

            $quantityAvailable = applySecurity($securityStockConfig, $quantityAvailable - $quantityStockBlocked);

            return $quantityAvailable > 0 ? $quantityAvailable : 0;

        } elseif ($quantityAvailable < 0) {

            return $quantityAvailable;
        }
        return 0;
    }

    private function applySecurity($securityStockConfig, $quantity) {
        if (!empty($securityStockConfig)) {
            $quantity = ShopChannel::applySecurityStockConfig(
                $quantity,
                @$securityStockConfig->mode,
                @$securityStockConfig->quantity
            );
        } 
        return $quantity;
    }
}

?>