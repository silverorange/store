-- as long as the item is enabled in one region, its considered enabled by this view
-- this is soley for the use of ProductItemCountByStatusView
create or replace view ProductImageMinimumDisplayorderView as

select min(displayorder) as displayorder, product
	from ProductImageBinding
	group by ProductImageBinding.product;
