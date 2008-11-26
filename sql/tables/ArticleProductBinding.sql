create table ArticleProductBinding (
	article int not null references Article(id) on delete cascade,
	product int not null references Product(id) on delete cascade,
	primary key (article, product)
);

