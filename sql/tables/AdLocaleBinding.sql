create table AdLocaleBinding (
	ad int not null references Ad(id) on delete cascade,
	locale char(5) not null references Locale(id) on delete cascade,
	total numeric(11, 2) not null default 0,
	total_orders int not null default 0,
	primary key (ad, locale)
);

