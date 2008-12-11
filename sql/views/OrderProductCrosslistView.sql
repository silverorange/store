/**
 * Gets a list of related products
 *
 * Selects a cross-list of all products in an order and check if
 * a relation aleady exists between the products in the popular
 * products binding table
 */

create or replace view OrderProductCrosslistView as
select distinct
	OrderItem.ordernum,
	OrderItem.product as source_product,
	OrderItem.extension,
	OrderItem.quantity,
	RelatedOrderItem.product as related_product,
	ProductPopularProductBinding.order_count,
	ProductPopularity.order_count as popularity,
	ProductPopularity.total_quantity,
	ProductPopularity.total_sales
from OrderItem
inner join Orders on OrderItem.ordernum = Orders.id
inner join OrderItem as RelatedOrderItem
	on OrderItem.ordernum = RelatedOrderItem.ordernum
left outer join ProductPopularProductBinding on
	ProductPopularProductBinding.source_product =
		OrderItem.product
	and ProductPopularProductBinding.related_product =
		RelatedOrderItem.product
left outer join ProductPopularity on
	ProductPopularity.product = OrderItem.product
inner join Product on OrderItem.product = Product.id
inner join Product as RelatedProduct
	on RelatedOrderItem.product = RelatedProduct.id
where RelatedOrderItem.product != OrderItem.product;
