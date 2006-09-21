create table PaymentTypeRegionBinding (
	payment_type int not null references PaymentType(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	primary key(payment_type, region)
);
