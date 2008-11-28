create table CardTypeRegionBinding (
	card_type int not null references CardType(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	primary key(card_type, region)
);
