create table ArticleCategoryBinding (
	article int not null references Article(id) on delete cascade,
	category int not null references Category(id) on delete cascade,
	primary key (article, category)
);

CREATE INDEX ArticleCategoryBinding_article_index ON ArticleCategoryBinding(article);
CREATE INDEX ArticleCategoryBinding_category_index ON ArticleCategoryBinding(category);

