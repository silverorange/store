create table ShippingTypeRegionBinding (
	shipping_type int not null references ShippingType(id) on delete cascade,
	region int not null references Region(id),
	price numeric(11, 2),
	primary key (id)
);

