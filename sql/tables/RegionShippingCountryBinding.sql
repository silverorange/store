create table RegionShippingCountryBinding (
	region int not null references Region(id) on delete cascade,
	country char(2) not null references Country(id) on delete cascade,
	primary key (region, country)
);

INSERT INTO RegionShippingCountryBinding (region, country) VALUES (1, 'CA');
INSERT INTO RegionShippingCountryBinding (region, country) VALUES (2, 'US');
