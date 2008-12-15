create or replace view OrderProductPopularityView as
select
    OrderItem.ordernum,
    OrderItem.product,
    sum(OrderItem.extension) as extension,
    sum(OrderItem.quantity) as quantity
from OrderItem
group by OrderItem.ordernum, OrderItem.product;
