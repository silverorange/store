create table Locale (
	id char(5) not null,
	region int not null references Region(id),
	primary key(id)
);

CREATE INDEX Locale_region_index ON Locale(region);

