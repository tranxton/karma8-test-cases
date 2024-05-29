-- users (id, username)
-- orders (id, user_id, amount, payed)
-- payments (id, order_id, amount, pay_system, status)

-- Find users where the proportion of paid orders to unpaid orders is 1 to 2
-- and payments in the failed status are less than 15% of the total


WITH suitable_order_ids AS (SELECT DISTINCT order_id
                            FROM payments
                            HAVING COUNT(*) / 15 * 100 > COUNT(status = 'failed' OR NULL))

SELECT users.*
FROM orders
         RIGHT JOIN users ON users.id ON orders.user_id
WHERE orders.order_id IN (SELECT order_id FROM suitable_order_ids)
HAVING COUNT (orders.payed = 1 OR NULL) / COUNT (*) > 0.5
