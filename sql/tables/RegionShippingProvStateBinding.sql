create table RegionShippingProvStateBinding (
	region int not null references Region(id) on delete cascade,
	provstate int not null references ProvState(id) on delete cascade,
	primary key (region, provstate)
);

