create table ArticleRegionBinding (
	article int not null references Article(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	primary key (article, region)
);
