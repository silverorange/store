create table ArticleProductBinding (
	article int not null references Article(id) on delete cascade,
	product int not null references Product(id) on delete cascade,
	primary key (article, product)
);

CREATE INDEX ArticleProductBinding_article_index ON ArticleProductBinding(article);
CREATE INDEX ArticleProductBinding_product_index ON ArticleProductBinding(product);
