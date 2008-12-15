create or replace view OrderProductCrosslistView as
select
    OrderItem.ordernum,
    OrderItem.product as source_product,
    RelatedOrderItem.product as related_product,
    sum(OrderItem.extension) as extension,
    sum(OrderItem.quantity) as quantity

from OrderItem

inner join OrderItem as RelatedOrderItem
    on OrderItem.ordernum = RelatedOrderItem.ordernum

where RelatedItem.product != OrderItem.product
group by OrderItem.ordernum, source_product, related_product;
