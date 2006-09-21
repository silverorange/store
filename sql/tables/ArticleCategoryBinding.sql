create table ArticleCategoryBinding (
	article int not null references Article(id) on delete cascade,
	category int not null references Category(id) on delete cascade,
	primary key (article, category)
);

