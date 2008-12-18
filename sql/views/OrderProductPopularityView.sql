create or replace view OrderProductPopularityView as
select
    OrderItem.ordernum,
    OrderItem.product,
    sum(OrderItem.extension) as extension,
    sum(OrderItem.quantity) as quantity
from OrderItem
where OrderItem.product is not null
group by OrderItem.ordernum, OrderItem.product;
