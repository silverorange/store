create table ShippingRate (
	id serial, --renamed from rateid
	region int not null references Region(id),
	threshold numeric(11, 2),
	amount numeric(11, 2),
	percentage numeric(5,4),
	shipping_type int not null references ShippingType(id) on delete cascade,
	primary key (id)
);

