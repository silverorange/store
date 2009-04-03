create table QuantityDiscountRegionBinding (
	quantity_discount int not null references QuantityDiscount(id) on delete cascade,
	region int not null references Region(id),
	price numeric(11, 2) not null,
	primary key (quantity_discount, region)
);

CREATE INDEX QuantityDiscountRegionBinding_quantity_discount_index ON QuantityDiscountRegionBinding(quantity_discount);

