-- as long as the item is enabled in one region, its considered enabled by this view
-- this is soley for the use of ProductItemCountByStatusView
create or replace view ProductPrimaryImageView as

select min(ProductImageBinding.image) as image,
	ProductImageBinding.product
	from ProductImageBinding
	inner join ProductImageMinimumDisplayorderView
		on ProductImageMinimumDisplayorderView.product =
			ProductImageBinding.product
		and ProductImageMinimumDisplayorderView.displayorder = 
			ProductImageBinding.displayorder
	group by ProductImageBinding.product
